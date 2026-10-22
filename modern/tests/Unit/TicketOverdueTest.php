<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

function ticketDue(?string $dueAt, TicketStatus $status = TicketStatus::Open): Ticket
{
    return new Ticket(['due_at' => $dueAt, 'status' => $status]);
}

// Fixes the year-boundary bug pinned in tests/Characterization.
it('treats a ticket due last December as overdue in January', function () {
    Carbon::setTestNow('2026-01-05 10:00:00');

    expect(ticketDue('2025-12-30 17:00:00')->isOverdue())->toBeTrue();
});

it('does not treat a ticket due next January as overdue in December', function () {
    Carbon::setTestNow('2026-12-20 10:00:00');

    expect(ticketDue('2027-01-10 17:00:00')->isOverdue())->toBeFalse();
});

it('is precise to the minute', function () {
    Carbon::setTestNow('2026-03-01 13:01:00');

    expect(ticketDue('2026-03-01 13:00:00')->isOverdue())->toBeTrue()
        ->and(ticketDue('2026-03-01 13:05:00')->isOverdue())->toBeFalse();
});

it('never treats closed tickets or tickets with no due date as overdue', function () {
    Carbon::setTestNow('2026-03-01');

    expect(ticketDue('2020-01-01', TicketStatus::Closed)->isOverdue())->toBeFalse()
        ->and(ticketDue(null)->isOverdue())->toBeFalse();
});
