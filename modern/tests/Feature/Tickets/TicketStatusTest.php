<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;

// Legacy ajax/update_status.php had no check_login(): anyone on the network could close any ticket.
it('does not let a guest change a ticket status', function () {
    $ticket = Ticket::factory()->create();

    $this->patch("/tickets/{$ticket->id}/status", ['status' => 'closed'])->assertRedirect('/login');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
});

it('lets an agent change the status', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(agent())
        ->from('/tickets')
        ->patch("/tickets/{$ticket->id}/status", ['status' => 'pending'])
        ->assertRedirect('/tickets')
        ->assertSessionHas('success', "Ticket #{$ticket->id} marked Pending.");

    expect($ticket->fresh()->status)->toBe(TicketStatus::Pending);
});

it('rejects statuses outside the allowed set', function (string $status) {
    $ticket = Ticket::factory()->create();

    $this->actingAs(agent())
        ->patch("/tickets/{$ticket->id}/status", ['status' => $status])
        ->assertSessionHasErrors('status');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
})->with([
    'Closed',
    'archived',
    "closed', subject='pwned",
]);
