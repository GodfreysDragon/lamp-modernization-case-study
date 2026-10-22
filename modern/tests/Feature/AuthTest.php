<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('redirects guests to the login page', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with(['/', '/tickets', '/customers', '/tickets/create']);

it('renders the login page', function () {
    $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('auth/Login'));
});

it('logs a user in with a bcrypt password', function () {
    $user = agent(['username' => 'sarah', 'password' => 'secret-pass']);

    $this->post('/login', ['username' => 'sarah', 'password' => 'secret-pass'])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password', function () {
    agent(['username' => 'sarah', 'password' => 'secret-pass']);

    $this->post('/login', ['username' => 'sarah', 'password' => 'nope'])->assertSessionHasErrors('username');

    $this->assertGuest();
});

// The legacy login query was built by concatenating $_POST['username'] into the SQL.
it('is not vulnerable to the legacy SQL injection login bypass', function (string $payload) {
    agent(['username' => 'admin', 'password' => 'secret-pass']);

    $this->post('/login', ['username' => $payload, 'password' => 'anything'])->assertSessionHasErrors('username');

    $this->assertGuest();
})->with([
    "admin' -- ",
    "' OR '1'='1",
    "admin'#",
]);

it('upgrades an imported MD5 password to bcrypt on first login', function () {
    $user = User::factory()->legacy('sarah2010')->create(['username' => 'sarah']);

    $this->post('/login', ['username' => 'sarah', 'password' => 'sarah2010'])->assertRedirect('/');

    $user->refresh();
    $this->assertAuthenticatedAs($user);
    expect($user->legacy_password)->toBeNull()
        ->and(Hash::check('sarah2010', $user->password))->toBeTrue();
});

it('does not upgrade a legacy hash when the password is wrong', function () {
    $user = User::factory()->legacy('sarah2010')->create(['username' => 'sarah']);

    $this->post('/login', ['username' => 'sarah', 'password' => 'wrong'])->assertSessionHasErrors('username');

    expect($user->refresh()->legacy_password)->toBe(md5('sarah2010'))
        ->and($user->password)->toBeNull();
});

it('throttles repeated failed logins', function () {
    agent(['username' => 'sarah', 'password' => 'secret-pass']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['username' => 'sarah', 'password' => 'wrong']);
    }

    $this->post('/login', ['username' => 'sarah', 'password' => 'secret-pass'])->assertTooManyRequests();
    $this->assertGuest();
});

it('logs out and invalidates the session', function () {
    $this->actingAs(agent())->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});
