<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Support\Carbon;

it('has one SLA per priority', function (TicketPriority $priority, int $hours) {
    expect($priority->slaHours())->toBe($hours);
})->with([
    [TicketPriority::Low, 120],
    [TicketPriority::Normal, 72],
    [TicketPriority::High, 24],
    [TicketPriority::Urgent, 4],
]);

it('computes due dates to the hour, not just the day', function () {
    $from = Carbon::parse('2026-01-15 09:00:00');

    expect(TicketPriority::Urgent->dueFrom($from)->toDateTimeString())->toBe('2026-01-15 13:00:00')
        ->and($from->toDateTimeString())->toBe('2026-01-15 09:00:00'); // the input is not mutated
});

it('maps legacy integer priorities', function ($legacy, TicketPriority $expected) {
    expect(TicketPriority::fromLegacy($legacy))->toBe($expected);
})->with([
    [1, TicketPriority::Low],
    [2, TicketPriority::Normal],
    ['3', TicketPriority::High],
    [4, TicketPriority::Urgent],
    [0, TicketPriority::Normal],
    [null, TicketPriority::Normal],
]);

it('normalises messy legacy status values', function (?string $legacy, TicketStatus $expected) {
    expect(TicketStatus::fromLegacy($legacy))->toBe($expected);
})->with([
    ['open', TicketStatus::Open],
    ['Pending', TicketStatus::Pending],
    ['closed ', TicketStatus::Closed],
    ['CLOSED', TicketStatus::Closed],
    ['', TicketStatus::Open],
    [null, TicketStatus::Open],
    ['on hold', TicketStatus::Open],
]);
