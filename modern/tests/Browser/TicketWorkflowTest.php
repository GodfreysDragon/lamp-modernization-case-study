<?php

use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Ticket;

// The main agent workflow, end to end in a real browser: log in, open a
// ticket through the customer autocomplete, change its status inline,
// and comment on it.

it('runs the main ticket workflow in a real browser', function () {
    $agent = agent(['username' => 'sarah', 'name' => 'Sarah Kim']);
    Customer::factory()->create(['name' => 'Linda Hart', 'company' => 'Hart & Sons Plumbing']);

    $page = visit('/login')
        ->fill('#username', 'sarah')
        ->fill('#password', 'password')
        ->press('Log in')
        ->assertPathIs('/')
        ->assertSee('Dashboard');

    $page->click('Tickets')
        ->assertPathIs('/tickets')
        ->click('New ticket')
        ->assertPathIs('/tickets/create')
        ->fill('#customer', 'Hart')
        ->assertSee('Linda Hart (Hart & Sons Plumbing)')
        ->click('[role=option]')
        ->fill('#subject', 'Invoice PDF not generating')
        ->fill('#body', 'Clicking "Download invoice" gives a blank page.')
        ->select('#priority', 'high')
        ->select('#assignee', (string) $agent->id)
        ->press('Create ticket')
        ->assertSee('Ticket created.')
        ->assertSee('Invoice PDF not generating');

    $ticket = Ticket::sole();
    expect($ticket->customer->name)->toBe('Linda Hart')
        ->and($ticket->assignee_id)->toBe($agent->id);

    $page->select("[aria-label='Status for ticket #{$ticket->id}']", 'pending')
        ->assertSee("Ticket #{$ticket->id} marked Pending.");

    expect($ticket->fresh()->status)->toBe(TicketStatus::Pending);

    $page->fill('#comment-body', 'Reproduced. <script>window.xss = true</script>')
        ->press('Add comment')
        ->assertSee('Reproduced. <script>window.xss = true</script>')
        ->assertScript('window.xss === undefined')
        ->assertNoJavaScriptErrors();

    expect(Comment::sole()->user_id)->toBe($agent->id);
});
