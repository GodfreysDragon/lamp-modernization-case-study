<?php

/*
|--------------------------------------------------------------------------
| Characterization tests for the legacy app
|--------------------------------------------------------------------------
|
| These tests were written BEFORE any new code. They load the real legacy
| helper functions and pin down what they actually do, bugs included. Each
| test is one of three kinds:
|
|   "preserves"   behaviour the new system must keep
|   "BUG"         behaviour the business agreed to fix (the new system is
|                 tested to do the right thing in tests/Unit or tests/Feature)
|
| Nothing here touches a database. The DB-dependent legacy functions are only
| defined, never called.
*/

require_once __DIR__.'/../../../legacy/includes/functions.php';

$jan15 = mktime(9, 0, 0, 1, 15, 2026);

it('preserves: SLA days per priority (low 5, normal 3, high 1)', function () use ($jan15) {
    expect(calc_due_date(1, $jan15))->toBe('01/20/2026')
        ->and(calc_due_date(2, $jan15))->toBe('01/18/2026')
        ->and(calc_due_date(3, $jan15))->toBe('01/16/2026');
});

it('BUG: urgent tickets are due "same day" in functions.php but "+1 day" in ticket_edit.php', function () use ($jan15) {
    expect(calc_due_date(4, $jan15))->toBe('01/15/2026');

    // ticket_edit.php has its own copy of the rule, which is the one actually used when saving a ticket.
    $source = file_get_contents(__DIR__.'/../../../legacy/ticket_edit.php');
    expect($source)->toContain("elseif (\$priority == 4) \$due = date('m/d/Y', strtotime('+1 day'));");
});

it('BUG: due dates have no time component, so "urgent" means "any time today"', function () use ($jan15) {
    expect(calc_due_date(4, $jan15))->toMatch('#^\d{2}/\d{2}/\d{4}$#');
});

it('preserves: closed tickets and tickets without a due date are never overdue', function () {
    expect(is_overdue('01/01/2000', 'closed', '06/01/2026'))->toBeFalse()
        ->and(is_overdue('', 'open', '06/01/2026'))->toBeFalse();
});

it('preserves: an open ticket past its due date in the same year is overdue', function () {
    expect(is_overdue('03/01/2026', 'open', '03/02/2026'))->toBeTrue()
        ->and(is_overdue('03/05/2026', 'open', '03/02/2026'))->toBeFalse();
});

it('BUG: overdue check compares m/d/Y strings, so it breaks across a year boundary', function () {
    // A ticket due 30 Dec 2025, viewed on 5 Jan 2026, is 6 days late...
    // ...but "12/30/2025" sorts after "01/05/2026" as a string.
    expect(is_overdue('12/30/2025', 'open', '01/05/2026'))->toBeFalse();

    // And the reverse: a ticket due next January looks overdue in December.
    expect(is_overdue('01/10/2027', 'open', '12/20/2026'))->toBeTrue();
});

it('BUG: status checks are case sensitive, so "Closed" tickets show as overdue', function () {
    // Real data contains "Closed" and "closed " (see legacy/seed.sql).
    expect(is_overdue('01/01/2026', 'Closed', '02/01/2026'))->toBeTrue()
        ->and(is_overdue('01/01/2026', 'closed ', '02/01/2026'))->toBeTrue();
});

it('BUG: status_badge() does not escape its input', function () {
    expect(status_badge('<script>x</script>'))->toContain('<script>x</script>');
});

it('preserves: priority labels, with unknown values falling back to Normal', function () {
    expect(array_map('priority_label', [1, 2, 3, 4, 9]))
        ->toBe(['Low', 'Normal', 'High', 'URGENT', 'Normal']);
});

it('BUG: short_text() cuts multibyte strings mid-character', function () {
    $text = str_repeat('é', 40);

    expect(mb_check_encoding(short_text($text, 61), 'UTF-8'))->toBeFalse();
});
