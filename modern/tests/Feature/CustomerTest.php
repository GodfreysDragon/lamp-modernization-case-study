<?php

use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;

it('lists customers alphabetically with their open ticket count', function () {
    $zed = Customer::factory()->create(['name' => 'Zed']);
    $amy = Customer::factory()->create(['name' => 'Amy']);
    Ticket::factory(2)->for($amy)->create();
    Ticket::factory()->for($amy)->status(TicketStatus::Closed)->create();

    $this->actingAs(agent())
        ->get('/customers')
        ->assertInertia(fn ($page) => $page
            ->component('customers/Index')
            ->where('customers.data.0.name', 'Amy')
            ->where('customers.data.0.open_tickets_count', 2)
            ->where('customers.data.1.id', $zed->id));
});

it('searches by name or company', function () {
    Customer::factory()->create(['name' => 'Linda Hart', 'company' => 'Hart & Sons']);
    Customer::factory()->create(['name' => 'Raj Patel', 'company' => 'Patel Dental']);

    $this->actingAs(agent())->get('/customers?q=dental')->assertInertia(fn ($page) => $page->has('customers.data', 1));
});

it('creates a customer', function () {
    $this->actingAs(agent())
        ->post('/customers', ['name' => "Tom O'Brien", 'company' => "O'Brien Logistics", 'email' => 'tom@example.com'])
        ->assertRedirect('/customers');

    expect(Customer::sole()->name)->toBe("Tom O'Brien");
});

it('validates customer input', function () {
    $this->actingAs(agent())
        ->post('/customers', ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);
});

it('updates a customer', function () {
    $customer = Customer::factory()->create();

    $this->actingAs(agent())
        ->put("/customers/{$customer->id}", ['name' => 'New Name'])
        ->assertRedirect('/customers');

    expect($customer->fresh()->name)->toBe('New Name');
});

// Legacy deleted on GET customers.php?delete=ID. A link in an email could wipe a customer.
it('never deletes on a GET request', function () {
    $customer = Customer::factory()->create();

    $this->actingAs(admin())->get("/customers?delete={$customer->id}")->assertOk();

    expect($customer->fresh()->trashed())->toBeFalse();
});

it('only lets admins delete customers, and keeps their tickets', function () {
    $customer = Customer::factory()->create();
    $ticket = Ticket::factory()->for($customer)->create();

    $this->actingAs(agent())->delete("/customers/{$customer->id}")->assertForbidden();

    $this->actingAs(admin())->delete("/customers/{$customer->id}")->assertRedirect('/customers');

    expect($customer->fresh()->trashed())->toBeTrue()
        ->and($ticket->fresh()->customer->name)->toBe($customer->name);
});

it('returns valid JSON from the search endpoint, even for names with quotes', function () {
    Customer::factory()->create(['name' => "Tom O'Brien", 'company' => 'O"Brien "Logistics"']);
    Customer::factory()->create(['name' => 'Someone Else']);

    $this->actingAs(agent())
        ->getJson('/customers/search?term=brien')
        ->assertOk()
        ->assertExactJson([
            ['id' => Customer::where('name', "Tom O'Brien")->value('id'), 'label' => 'Tom O\'Brien (O"Brien "Logistics")'],
        ]);
});

it('does not expose the search endpoint to guests', function () {
    $this->getJson('/customers/search?term=a')->assertUnauthorized();
});
