<?php

use App\Models\Ticket;
use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

it('adds a comment as the current user', function () {
    $ticket = Ticket::factory()->create();
    $me = agent();

    $this->actingAs($me)
        ->from("/tickets/{$ticket->id}")
        ->post("/tickets/{$ticket->id}/comments", ['body' => 'Reproduced on Chrome.', 'is_internal' => true])
        ->assertRedirect("/tickets/{$ticket->id}");

    $comment = $ticket->comments()->sole();
    expect($comment->user_id)->toBe($me->id)
        ->and($comment->is_internal)->toBeTrue();
});

it("bumps the ticket's updated_at", function () {
    Carbon::setTestNow('2026-01-01 09:00:00');
    $ticket = Ticket::factory()->create();

    Carbon::setTestNow('2026-01-02 15:00:00');
    $this->actingAs(agent())->post("/tickets/{$ticket->id}/comments", ['body' => 'Update']);

    expect($ticket->fresh()->updated_at->toDateTimeString())->toBe('2026-01-02 15:00:00');
});

it('stores HTML exactly as typed and passes it to Vue as plain data', function () {
    $ticket = Ticket::factory()->create();
    $payload = '<script>alert(document.cookie)</script>';

    $this->actingAs(agent())->post("/tickets/{$ticket->id}/comments", ['body' => $payload]);

    // Vue escapes it when rendering, which CommentThread.test.js covers.
    $this->get("/tickets/{$ticket->id}")
        ->assertInertia(fn ($page) => $page->where('ticket.comments.0.body', $payload));
});

it('requires a body', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(agent())
        ->post("/tickets/{$ticket->id}/comments", ['body' => ''])
        ->assertSessionHasErrors('body');
});

it('does not let guests comment', function () {
    $ticket = Ticket::factory()->create();

    $this->post("/tickets/{$ticket->id}/comments", ['body' => 'hi'])->assertRedirect('/login');

    expect($ticket->comments()->count())->toBe(0);
});

it('returns 404 when commenting on a ticket that does not exist', function () {
    $this->actingAs(agent())->post('/tickets/999/comments', ['body' => 'hi'])->assertNotFound();
});
