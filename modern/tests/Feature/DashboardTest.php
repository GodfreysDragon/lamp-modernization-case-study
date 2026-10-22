<?php

use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;

it('shows ticket counts per status', function () {
    Ticket::factory(3)->status(TicketStatus::Open)->create();
    Ticket::factory(2)->status(TicketStatus::Pending)->create();
    Ticket::factory()->status(TicketStatus::Closed)->create();
    Ticket::factory()->status(TicketStatus::Open)->create()->delete(); // soft deleted, not counted

    $this->actingAs(agent())
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('counts', ['open' => 3, 'pending' => 2, 'closed' => 1]));
});

it("lists only the current user's unresolved tickets", function () {
    $me = agent();
    Ticket::factory(2)->create(['assignee_id' => $me->id]);
    Ticket::factory()->status(TicketStatus::Closed)->create(['assignee_id' => $me->id]);
    Ticket::factory()->create(['assignee_id' => agent()->id]);

    $this->actingAs($me)
        ->get('/')
        ->assertInertia(fn ($page) => $page->has('tickets', 2));
});

it('counts overdue tickets', function () {
    Ticket::factory()->create(['due_at' => now()->subHour()]);
    Ticket::factory()->create(['due_at' => now()->addHour()]);
    Ticket::factory()->status(TicketStatus::Closed)->create(['due_at' => now()->subDay()]);

    $this->actingAs(agent())->get('/')->assertInertia(fn ($page) => $page->where('overdueCount', 1));
});

// The legacy dashboard ran 3 + (2 × tickets) queries. This one runs a fixed number.
it('runs the same number of queries for 2 tickets or 40', function () {
    $me = agent();
    $this->actingAs($me);

    Ticket::factory(2)->has(Comment::factory(2))->create(['assignee_id' => $me->id]);
    $few = countQueries(fn () => $this->get('/')->assertOk());

    Ticket::factory(38)->has(Comment::factory(2))->create(['assignee_id' => $me->id]);
    $many = countQueries(fn () => $this->get('/')->assertOk());

    expect($many)->toBe($few)->toBeLessThanOrEqual(6);
});
