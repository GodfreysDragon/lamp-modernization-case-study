# 4. Testing strategy

The legacy app had zero tests. The modern app ships with **112** (99 Pest, one of them a real-browser test, plus 13 Vitest), and they run in about 20 seconds. Line coverage is **95.1%**, measured in CI.

```bash
cd modern
npx playwright install chromium   # once, for the browser test
php artisan test                  # Pest: characterization + unit + feature + browser
npm test                          # Vitest: Vue components
```

## The four layers

### 1. Characterization tests: pin down the old behaviour first
[`tests/Characterization/LegacyBehaviourTest.php`](../modern/tests/Characterization/LegacyBehaviourTest.php)

These were written **before any new code**. They `require` the real `legacy/includes/functions.php` and assert what it does today, bugs included. No database is needed: the legacy file keeps its pure helpers separate from the DB-dependent ones, so they can be loaded safely.

```php
it('BUG: overdue check compares m/d/Y strings, so it breaks across a year boundary', function () {
    // A ticket due 30 Dec 2025, viewed on 5 Jan 2026, is 6 days late...
    expect(is_overdue('12/30/2025', 'open', '01/05/2026'))->toBeFalse();
});
```

Every test name starts with either:
- **`preserves:`** a behaviour the client confirmed is correct. The new system must match it.
- **`BUG:`** a behaviour the client agreed to fix. The fix has its own test that asserts the *correct* behaviour.

| Legacy behaviour (Characterization) | Correct behaviour (new test) |
|---|---|
| `BUG:` urgent SLA defined two different ways | `Unit/TicketEnumsTest › has one SLA per priority` and `Feature/TicketCrudTest › creates a ticket and sets the due date from the SLA` |
| `BUG:` due dates have no time component | `Unit/TicketEnumsTest › computes due dates to the hour` |
| `BUG:` overdue breaks at the year boundary | `Unit/TicketOverdueTest › treats a ticket due last December as overdue in January` |
| `BUG:` status is case-sensitive | `Unit/TicketEnumsTest › normalises messy legacy status values` |
| `BUG:` `status_badge()` doesn't escape | `CommentThread.test.js › escapes HTML…` (Vue escapes by default) |
| `preserves:` closed or undated tickets are never overdue | `Unit/TicketOverdueTest › never treats closed tickets or tickets with no due date as overdue` |

The characterization suite is also **the deliverable the client signs off on**. It's an executable list of "this is what your system does today", reviewed line by line before the rewrite starts.

### 2. Unit tests: domain rules in isolation
`tests/Unit`: enums and the `isOverdue()` rule, with `Carbon::setTestNow()` for deterministic time. Pest datasets keep the edge cases readable:

```php
it('normalises messy legacy status values', function (?string $legacy, TicketStatus $expected) {
    expect(TicketStatus::fromLegacy($legacy))->toBe($expected);
})->with([
    ['Pending', TicketStatus::Pending],
    ['closed ', TicketStatus::Closed],
    ['', TicketStatus::Open],
    // ...
]);
```

### 3. Feature tests: every route, every rule
`tests/Feature`: full HTTP requests against an in-memory SQLite database (`RefreshDatabase`). Inertia responses are checked with `assertInertia()`, which also fails if a page component file doesn't exist.

Each **security finding from the audit has a named regression test**:

| Audit finding | Regression test |
|---|---|
| C1 SQL-injection login bypass | `AuthTest › is not vulnerable to the legacy SQL injection login bypass` (3 payloads) |
| C2 SQL injection in search and filters | `TicketIndexTest › treats SQL in the search box as plain text`, `› rejects a status that is not a real status` |
| C3 unauthenticated status endpoint | `TicketStatusTest › does not let a guest change a ticket status` |
| C4 stored XSS | `CommentTest › stores HTML exactly as typed…` + `CommentThread.test.js › escapes HTML…` |
| H2 delete via GET (CSRF) | `CustomerTest › never deletes on a GET request` |
| H3 MD5 passwords | `AuthTest › upgrades an imported MD5 password to bcrypt on first login` |
| H6 unauthenticated search | `CustomerTest › does not expose the search endpoint to guests` |
| M4 N+1 queries | `DashboardTest › runs the same number of queries for 2 tickets or 40`, `TicketIndexTest › runs a fixed number of queries…` |
| — brute force (no limit in legacy) | `AuthTest › throttles repeated failed logins` |

The **import command** has its own suite ([`LegacyImportTest`](../modern/tests/Feature/LegacyImportTest.php)). It builds the legacy schema in a second in-memory database, loads the same messy rows as `legacy/seed.sql`, and asserts on every cleaning rule, the orphan warnings, idempotency, dry-run, and that an already-upgraded password survives a re-import.

### 4. Component tests: the Vue layer
`resources/js/**/*.test.js`, run with Vitest + `@vue/test-utils` in jsdom. They cover rendering states (empty, internal note, overdue), the XSS guarantee, and pagination behaviour. Components that need Inertia's router stub it with `vi.mock`.

## Query-count assertions

```php
it('runs the same number of queries for 2 tickets or 40', function () {
    Ticket::factory(2)->has(Comment::factory(2))->create(['assignee_id' => $me->id]);
    $few = countQueries(fn () => $this->get('/')->assertOk());

    Ticket::factory(38)->has(Comment::factory(2))->create(['assignee_id' => $me->id]);
    $many = countQueries(fn () => $this->get('/')->assertOk());

    expect($many)->toBe($few)->toBeLessThanOrEqual(6);
});
```
This is the test that stops the N+1 problem from creeping back in six months from now. `Model::preventLazyLoading()` backs it up by throwing on any accidental lazy load in the dev and test environments.

## One browser test, end to end
[`tests/Browser/TicketWorkflowTest.php`](../modern/tests/Browser/TicketWorkflowTest.php)

The feature tests prove the server side and the Vitest tests prove individual components. One test drives the whole thing in a real Chromium, using the Pest browser plugin with Playwright: log in, find a customer through the autocomplete, create a ticket, change its status from the dropdown, and add a comment containing a `<script>` tag. It asserts that the tag shows as text and never runs, and that the page logged no JavaScript errors.

It's deliberately just one test. Browser tests are slow and brittle compared with feature tests, so they're kept for the path a real agent takes every day.

## Coverage

CI measures line coverage with PCOV on every push: **95.1%**. The build fails if it drops below 90%. To run it locally you need a coverage driver:

```bash
pecl install pcov     # or enable Xdebug
php artisan test --coverage --min=90
```

## Static analysis

[Larastan](https://github.com/larastan/larastan) (PHPStan for Laravel) checks `modern/` at **level 8** in CI, with zero errors. The same level on the legacy code finds **43**, including variables that may be used before they're set. PHPStan checks types and logic, not security, so it doesn't see the SQL injection or XSS. That's what the audit and the regression tests above are for.

```bash
cd modern && vendor/bin/phpstan analyse                     # modern: 0 errors
cd .. && modern/vendor/bin/phpstan analyse -c legacy/phpstan.neon  # legacy: 43 errors
```

Getting from level 6 to 8 meant typing the import command's legacy rows as `stdClass`, injecting the signed-in user with `#[CurrentUser] User $user` instead of the nullable `$request->user()`, and handling the `null` that `Carbon::createFromFormat()` can return. Level 9 would only add complaints about casting `mixed` from raw query results.

## CI

The core steps are below. The full workflow is [`.github/workflows/ci.yml`](../.github/workflows/ci.yml), and it also runs `php -l` over `legacy/` on PHP 7.4.

```yaml
- run: composer install --no-interaction
- run: npm ci && npm run build
- run: npx playwright install --with-deps chromium
- run: vendor/bin/pint --test
- run: vendor/bin/phpstan analyse
- run: php artisan test --coverage --min=90
- run: npm test
```
