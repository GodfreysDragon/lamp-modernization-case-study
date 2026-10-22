<?php

use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;

beforeEach(fn () => $this->actingAs(agent()));

it('lists tickets newest first with their relations', function () {
    $older = Ticket::factory()->create();
    $newer = Ticket::factory()->has(Comment::factory(3))->create();

    $this->get('/tickets')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/Index')
            ->where('tickets.data.0.id', $newer->id)
            ->where('tickets.data.0.comments_count', 3)
            ->where('tickets.data.0.customer.id', $newer->customer_id)
            ->where('tickets.data.1.id', $older->id));
});

it('filters by status', function () {
    Ticket::factory(2)->status(TicketStatus::Pending)->create();
    Ticket::factory(3)->status(TicketStatus::Open)->create();

    $this->get('/tickets?status=pending')
        ->assertInertia(fn ($page) => $page->has('tickets.data', 2)->where('filters.status', 'pending'));
});

it('searches subject and body', function () {
    Ticket::factory()->create(['subject' => 'Invoice PDF broken']);
    Ticket::factory()->create(['subject' => 'Other', 'body' => 'the invoice is wrong']);
    Ticket::factory()->create(['subject' => 'Unrelated', 'body' => 'nothing here']);

    $this->get('/tickets?q=invoice')->assertInertia(fn ($page) => $page->has('tickets.data', 2));
});

it('filters by assignee', function () {
    $sarah = agent();
    Ticket::factory()->create(['assignee_id' => $sarah->id]);
    Ticket::factory(2)->create();

    $this->get("/tickets?assignee={$sarah->id}")->assertInertia(fn ($page) => $page->has('tickets.data', 1));
});

it('excludes soft-deleted tickets', function () {
    Ticket::factory()->create();
    Ticket::factory()->create()->delete();

    $this->get('/tickets')->assertInertia(fn ($page) => $page->has('tickets.data', 1));
});

// The legacy page put $_GET['q'] and $_GET['status'] straight into the SQL.
it('treats SQL in the search box as plain text', function () {
    Ticket::factory(3)->create(['subject' => 'Normal ticket']);
    $injected = Ticket::factory()->create(['subject' => "It's broken"]);

    $this->get('/tickets?q='.urlencode("' OR 1=1 -- "))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('tickets.data', 0));

    $this->get('/tickets?q='.urlencode("It's"))
        ->assertInertia(fn ($page) => $page->has('tickets.data', 1)->where('tickets.data.0.id', $injected->id));
});

it('rejects a status that is not a real status', function () {
    $this->get('/tickets?status='.urlencode("open' OR '1'='1"))->assertSessionHasErrors('status');
});

it('paginates 20 per page and keeps the filters in the links', function () {
    Ticket::factory(25)->status(TicketStatus::Open)->create();

    $this->get('/tickets?status=open&page=2')
        ->assertInertia(fn ($page) => $page
            ->has('tickets.data', 5)
            ->where('tickets.meta.current_page', 2)
            ->where('tickets.meta.links.1.url', fn ($url) => str_contains($url, 'status=open')));
});

// Legacy ran 2 + (3 × rows) queries per page: customer, assignee and comment count for each row.
it('runs a fixed number of queries regardless of page size', function () {
    Ticket::factory(2)->has(Comment::factory())->create(['assignee_id' => agent()->id]);
    $few = countQueries(fn () => $this->get('/tickets')->assertOk());

    Ticket::factory(18)->has(Comment::factory())->create(['assignee_id' => agent()->id]);
    $many = countQueries(fn () => $this->get('/tickets')->assertOk());

    expect($many)->toBe($few);
});
