<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Builds a throwaway copy of the legacy schema in a second in-memory SQLite
| database and fills it with the same messy rows as legacy/seed.sql.
*/
beforeEach(function () {
    config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    DB::purge('legacy');
    $schema = Schema::connection('legacy');

    $schema->create('users', function (Blueprint $t) {
        $t->increments('id');
        $t->string('username');
        $t->string('password', 32);
        $t->string('full_name')->nullable();
        $t->string('email')->nullable();
        $t->boolean('is_admin')->default(0);
        $t->string('created')->nullable();
    });
    $schema->create('customers', function (Blueprint $t) {
        $t->increments('id');
        foreach (['name', 'company', 'email', 'phone', 'notes', 'created'] as $col) {
            $t->string($col)->nullable();
        }
    });
    $schema->create('tickets', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('customer_id')->nullable();
        $t->integer('assigned_to')->nullable();
        foreach (['subject', 'body', 'status', 'due_date', 'created', 'updated'] as $col) {
            $t->string($col)->nullable();
        }
        $t->integer('priority')->nullable();
        $t->boolean('deleted')->default(0);
    });
    $schema->create('comments', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('ticket_id')->nullable();
        $t->integer('user_id')->nullable();
        $t->text('body')->nullable();
        $t->boolean('is_internal')->default(0);
        $t->string('created')->nullable();
    });

    $legacy = DB::connection('legacy');
    $legacy->table('users')->insert([
        ['id' => 1, 'username' => 'admin', 'password' => md5('admin123'), 'full_name' => 'Dave Patterson', 'email' => 'dave@acme-support.test', 'is_admin' => 1, 'created' => '2009-02-11 09:00:00'],
        ['id' => 3, 'username' => 'mike', 'password' => md5('password'), 'full_name' => 'Mike Ortega', 'email' => 'MIKE@acme-support.test ', 'is_admin' => 0, 'created' => '0000-00-00 00:00:00'],
    ]);
    $legacy->table('customers')->insert([
        ['id' => 1, 'name' => 'Linda Hart', 'company' => 'Hart & Sons', 'email' => 'linda@hart.test', 'phone' => '555-0101', 'notes' => null, 'created' => '2009-03-02 10:00:00'],
        ['id' => 3, 'name' => "Tom O'Brien", 'company' => '', 'email' => ' TOM@OBRIEN.TEST', 'phone' => '', 'notes' => 'VIP', 'created' => '2011-07-19 16:05:00'],
    ]);
    $legacy->table('tickets')->insert([
        ['id' => 1, 'customer_id' => 1, 'assigned_to' => 3, 'subject' => 'Invoice PDF', 'body' => 'Blank page', 'status' => 'open', 'priority' => 3, 'due_date' => '03/15/2011', 'created' => '2011-03-14 09:12:00', 'updated' => '2011-03-14 09:12:00', 'deleted' => 0],
        ['id' => 2, 'customer_id' => 3, 'assigned_to' => null, 'subject' => 'Site down', 'body' => '', 'status' => 'closed ', 'priority' => 4, 'due_date' => '', 'created' => '2011-03-16 07:00:00', 'updated' => '0000-00-00 00:00:00', 'deleted' => 0],
        ['id' => 5, 'customer_id' => 1, 'assigned_to' => 1, 'subject' => 'Dupe', 'body' => null, 'status' => 'Closed', 'priority' => 2, 'due_date' => 'garbage', 'created' => '2011-03-17 15:00:00', 'updated' => '2011-03-17 15:05:00', 'deleted' => 1],
        ['id' => 6, 'customer_id' => 7, 'assigned_to' => 99, 'subject' => 'Orphan', 'body' => null, 'status' => 'Pending', 'priority' => 1, 'due_date' => '12/30/2010', 'created' => '2010-12-27 10:00:00', 'updated' => null, 'deleted' => 0],
    ]);
    $legacy->table('comments')->insert([
        ['id' => 1, 'ticket_id' => 1, 'user_id' => 3, 'body' => 'Reproduced on IE8', 'is_internal' => 1, 'created' => '2011-03-14 10:00:00'],
        ['id' => 4, 'ticket_id' => 99, 'user_id' => 3, 'body' => 'Orphan comment', 'is_internal' => 0, 'created' => '2010-12-01 12:00:00'],
    ]);
});

it('imports every entity and reports orphans', function () {
    $this->artisan('legacy:import')
        ->expectsOutputToContain('Ticket #6: customer #7 no longer exists')
        ->expectsOutputToContain('Comment #4: ticket #99 no longer exists, skipped.')
        ->expectsOutputToContain('Import complete.')
        ->assertSuccessful();

    expect(User::count())->toBe(2)
        ->and(Customer::count())->toBe(2)
        ->and(Ticket::withTrashed()->count())->toBe(4)
        ->and(Comment::count())->toBe(1);
});

it('keeps MD5 hashes for the login upgrade path and cleans user data', function () {
    $this->artisan('legacy:import')->assertSuccessful();

    $mike = User::where('username', 'mike')->sole();
    expect($mike->password)->toBeNull()
        ->and($mike->legacy_password)->toBe(md5('password'))
        ->and($mike->email)->toBe('mike@acme-support.test')
        ->and($mike->created_at)->not->toBeNull(); // zero date replaced

    $this->post('/login', ['username' => 'mike', 'password' => 'password'])->assertRedirect('/');
    expect($mike->fresh()->legacy_password)->toBeNull();
});

it('normalises ticket status, priority and dates', function () {
    $this->artisan('legacy:import')->assertSuccessful();

    $invoice = Ticket::where('legacy_id', 1)->sole();
    expect($invoice->priority)->toBe(TicketPriority::High)
        ->and($invoice->due_at->toDateTimeString())->toBe('2011-03-15 23:59:59')
        ->and($invoice->assignee->username)->toBe('mike')
        ->and($invoice->created_at->toDateTimeString())->toBe('2011-03-14 09:12:00');

    $siteDown = Ticket::where('legacy_id', 2)->sole();
    expect($siteDown->status)->toBe(TicketStatus::Closed)          // "closed " with trailing space
        ->and($siteDown->body)->toBeNull()
        ->and($siteDown->due_at->toDateTimeString())->toBe('2011-03-16 11:00:00') // empty: derived from SLA
        ->and($siteDown->updated_at->toDateTimeString())->toBe('2011-03-16 07:00:00'); // zero date: falls back to created

    $dupe = Ticket::withTrashed()->where('legacy_id', 5)->sole();
    expect($dupe->trashed())->toBeTrue()
        ->and($dupe->status)->toBe(TicketStatus::Closed)
        ->and($dupe->due_at->toDateTimeString())->toBe('2011-03-20 15:00:00'); // unparseable: derived from SLA

    $orphan = Ticket::where('legacy_id', 6)->sole();
    expect($orphan->customer_id)->toBeNull()
        ->and($orphan->assignee_id)->toBeNull()
        ->and($orphan->status)->toBe(TicketStatus::Pending);
});

it('is safe to run twice', function () {
    $this->artisan('legacy:import')->assertSuccessful();
    $this->artisan('legacy:import')->assertSuccessful();

    expect(User::count())->toBe(2)
        ->and(Ticket::withTrashed()->count())->toBe(4)
        ->and(Comment::count())->toBe(1);
});

it('does not overwrite a password that was already upgraded', function () {
    $this->artisan('legacy:import')->assertSuccessful();
    $this->post('/login', ['username' => 'mike', 'password' => 'password']);
    $bcrypt = User::where('username', 'mike')->value('password');

    $this->artisan('legacy:import')->assertSuccessful();

    $mike = User::where('username', 'mike')->sole();
    expect($mike->password)->toBe($bcrypt)->and($mike->legacy_password)->toBeNull();
});

it('saves nothing on a dry run', function () {
    $this->artisan('legacy:import --dry-run')
        ->expectsOutputToContain('Dry run complete')
        ->assertSuccessful();

    expect(User::count())->toBe(0)->and(Ticket::withTrashed()->count())->toBe(0);
});
