<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

it('shows the create form with the list of agents', function () {
    $this->actingAs(agent())
        ->get('/tickets/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('tickets/Create')->has('agents', 1));
});

it('creates a ticket and sets the due date from the SLA', function () {
    Carbon::setTestNow('2026-01-15 09:00:00');
    $customer = Customer::factory()->create();
    $assignee = agent();

    $this->actingAs(agent())
        ->post('/tickets', [
            'customer_id' => $customer->id,
            'assignee_id' => $assignee->id,
            'subject' => 'Site down',
            'body' => 'Whole portal returns 500.',
            'priority' => 'urgent',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket = Ticket::sole();
    expect($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->priority)->toBe(TicketPriority::Urgent)
        ->and($ticket->assignee_id)->toBe($assignee->id)
        // Urgent = 4 hours. Legacy said "same day" in one file and "+1 day" in another.
        ->and($ticket->due_at->toDateTimeString())->toBe('2026-01-15 13:00:00');
});

it('validates ticket input', function (array $data, string $field) {
    $customer = Customer::factory()->create();

    $this->actingAs(agent())
        ->post('/tickets', [...['customer_id' => $customer->id, 'subject' => 'Ok', 'priority' => 'normal'], ...$data])
        ->assertSessionHasErrors($field);

    expect(Ticket::count())->toBe(0);
})->with([
    'missing customer' => [['customer_id' => null], 'customer_id'],
    'unknown customer' => [['customer_id' => 999], 'customer_id'],
    'missing subject' => [['subject' => ''], 'subject'],
    'subject too long' => [['subject' => str_repeat('a', 256)], 'subject'],
    'bad priority' => [['priority' => '9'], 'priority'],
    'unknown assignee' => [['assignee_id' => 999], 'assignee_id'],
]);

it('shows a ticket with its comments', function () {
    $ticket = Ticket::factory()->hasComments(2)->create();

    $this->actingAs(agent())
        ->get("/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/Show')
            ->where('ticket.id', $ticket->id)
            ->has('ticket.comments', 2)
            ->where('can.delete', false));
});

it('returns 404 for a missing ticket instead of a raw SQL error', function () {
    $this->actingAs(agent())->get('/tickets/999')->assertNotFound();
    $this->actingAs(agent())->get('/tickets/1%20OR%201=1')->assertNotFound();
});

it('updates a ticket and recalculates the due date when the priority changes', function () {
    Carbon::setTestNow('2026-01-15 09:00:00');
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);
    expect($ticket->due_at->toDateTimeString())->toBe('2026-01-20 09:00:00');

    Carbon::setTestNow('2026-01-16 12:00:00');
    $this->actingAs(agent())
        ->put("/tickets/{$ticket->id}", [
            'customer_id' => $ticket->customer_id,
            'subject' => 'Now it is urgent',
            'priority' => 'high',
        ])
        ->assertRedirect("/tickets/{$ticket->id}");

    $ticket->refresh();
    expect($ticket->subject)->toBe('Now it is urgent')
        // The SLA is measured from when the ticket was opened, not when it was edited.
        ->and($ticket->due_at->toDateTimeString())->toBe('2026-01-16 09:00:00');
});

it('keeps the due date when the priority does not change', function () {
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Normal]);
    $due = $ticket->due_at->toDateTimeString();

    $this->actingAs(agent())->put("/tickets/{$ticket->id}", [
        'customer_id' => $ticket->customer_id,
        'subject' => 'Renamed',
        'priority' => 'normal',
    ]);

    expect($ticket->refresh()->due_at->toDateTimeString())->toBe($due);
});

it('only lets admins delete tickets, and deletes are soft', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(agent())->delete("/tickets/{$ticket->id}")->assertForbidden();
    expect($ticket->fresh()->trashed())->toBeFalse();

    $this->actingAs(admin())->delete("/tickets/{$ticket->id}")->assertRedirect('/tickets');
    expect($ticket->fresh()->trashed())->toBeTrue();
});
