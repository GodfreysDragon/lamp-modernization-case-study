# Project summary: Legacy LAMP → Laravel + Vue case study

_Last updated: 2026-10-04_

## What this is

A complete, runnable before-and-after of modernizing a legacy PHP/MySQL app into Laravel + Vue. It's written as a business case for agencies and companies that have this exact problem in their codebase. See [README.md](README.md) for the overview and [docs/](docs) for the full case study.

## Status

All checks pass:
- **Pest:** 98/98 (10 characterization, 22 unit, 66 feature)
- **Vitest:** 13/13
- **Production build** (`npm run build`): succeeds
- **Pint:** clean

## What's included

### `legacy/`: the "before"
"HelpDesk Pro v2.3", a made-up 2009-style support-ticket app in procedural PHP 7.4, mysqli and jQuery 1.8.
- Pages: login/logout, dashboard, ticket list, ticket detail, ticket create/edit, customers
- `ajax/` endpoints: status update, add comment, customer search
- Deliberate, realistic problems:
  - SQL injection on login and on every list and form
  - a status endpoint that doesn't check login
  - comment bodies that can carry scripts (stored XSS), and deletes on a plain GET link
  - MD5 passwords
  - N+1 queries (the same query repeated for every row)
  - the urgent-ticket SLA defined twice with different values
  - an overdue check that compares `m/d/Y` strings, so it breaks every December
- `schema.sql` (MyISAM, no foreign keys) and `seed.sql`, with realistically messy data: inconsistent status casing, zero dates, orphaned rows
- `docker-compose.yml`: PHP 7.4 + Apache + MySQL 5.7, with MySQL exposed on port 3307 for the import

### `modern/`: the "after"
Laravel 13 + Vue 3 (Inertia v3), Vite 8, Tailwind 4, Pest 5, Vitest.
- **Domain:**
  - `TicketStatus` and `TicketPriority` enums; the SLA rules live in one place
  - Eloquent models with soft deletes, foreign keys and scopes (`filter`, `unresolved`, `overdue`)
- **HTTP layer:**
  - thin controllers, form requests, policies (only admins can delete) and API resources
  - login throttling (5 attempts per minute)
  - `preventLazyLoading()` turned on outside production, so an accidental N+1 query throws
- **Auth:** MD5 passwords are upgraded to bcrypt on first login, so no reset emails are needed
- **Vue:** 9 pages plus reusable components (`StatusSelect`, `CommentThread`, `CommentForm`, `CustomerPicker`, `TicketForm`, `CustomerForm`, `Pagination`, `DueDate`, `StatusBadge`, `PriorityLabel`, `FlashMessage`)
- **`php artisan legacy:import`:**
  - cleans data on the way in
  - idempotent: rows are matched on `legacy_id`, so re-runs only update
  - atomic, with a `--dry-run` mode
  - reports orphaned records instead of dropping them silently
  - never overwrites a password that has already been upgraded
- **Query counts** (20-row pages, measured):

  | Page | Legacy | Modern |
  |---|---:|---:|
  | Dashboard | 44 | 4 |
  | Ticket list | 62 | 5 |
  | Ticket detail | 14 | 5 |

### Tests
- **Characterization** (`tests/Characterization`): loads the real legacy functions and records what they actually do, labelled `preserves:` or `BUG:`
- **Unit:** enums, SLA calculation, and the overdue check across year boundaries
- **Feature:**
  - every route, including a named regression test for each audit security finding
  - query counts that stay the same as data grows (no N+1)
  - the legacy import, run against an in-memory copy of the legacy schema
- **Vitest:** component rendering, XSS escaping, pagination

### `docs/`: the case study
1. `01-legacy-audit.md`: 22 findings with file:line references (5 critical, 6 high, 5 medium, 6 low)
2. `02-migration-strategy.md`: characterize → rebuild → import → cut over, plus the password upgrade, rollback plan and risks
3. `03-before-after.md`: side-by-side code (raw SQL → Eloquent, jQuery → Vue, N+1 → eager loading, MD5 → bcrypt)
4. `04-testing-strategy.md`: the test layers, plus a table mapping each audit finding to its regression test
5. `05-business-case.md`: cost of inaction (about $37k/yr) vs cost of modernizing (about $11.4k), with payback in about 4 months. **The figures are illustrative assumptions.**

## Known gaps (not yet verified)

- **The legacy app has never actually been run.** Docker isn't installed on this machine. The PHP files only passed `php -l` syntax checks.
- **No browser click-through of the modern app.** Every page returned HTTP 200 with seeded data, and a one-off test mounted every Vue page with real server props without errors. Interactive behaviour (autocomplete, inline status change, form submits) still hasn't been tried in a real browser.
- **No measured code coverage.** The local PHP has neither Xdebug nor PCOV.
- **Business-case numbers are a worked example**, not client data.
- **The import has only run against SQLite** (in tests), not a real MySQL 5.7 legacy database.
- **Laravel Boost is not installed**, although the generated `modern/CLAUDE.md` / `AGENTS.md` ask for it.
- **No git repository or commits yet.**

## To-dos / next steps

### Verification
- [ ] Install Docker, run `docker compose up` in `legacy/`, and click through every legacy page
- [ ] Reproduce the key audit findings by hand against the running legacy app: the SQL-injection login bypass, the unauthenticated status update, stored XSS
- [ ] Run `php artisan legacy:import --dry-run`, then a real import, against the Dockerized MySQL (`LEGACY_DB_PORT=3307`, `LEGACY_DB_PASSWORD=hunter2`). Confirm the warnings for ticket #6 and comment #4
- [ ] Click through the modern app in a browser: login, filters, inline status change, customer autocomplete, comments, create/edit/delete
- [ ] Install PCOV or Xdebug, run `php artisan test --coverage`, and add the real number to the README and `docs/04-testing-strategy.md`

### Repository and CI
- [ ] `git init`, add a root `.gitignore`, and make the initial commit
- [ ] Add a GitHub Actions workflow (Pint, Pest, Vitest, build), using the outline in `docs/04-testing-strategy.md`
- [ ] Decide whether to install Laravel Boost, or delete/replace the scaffold `modern/CLAUDE.md` and `AGENTS.md`

### Case-study polish
- [ ] Add screenshots or a short GIF of legacy vs modern UI to the README
- [ ] Add a browser test (Pest browser plugin or Playwright) for the main ticket workflow
- [ ] Optionally publish the case study as a shareable web page for prospects
- [ ] Tailor `docs/05-business-case.md` numbers to a real client or industry benchmark before using it in a pitch

### Possible extensions
- [ ] Strangler-fig variant: show Laravel proxying un-migrated legacy routes, for larger apps
- [ ] Email-to-ticket, a customer portal, or a REST API, to show how cheap new features are after modernization
- [ ] Static analysis (Larastan / PHPStan) on `modern/`, and a "before" score from running it on `legacy/`
- [ ] A cutover runbook script: read-only flag for the legacy app, final import, smoke checks

## Quick reference

```bash
# Modern
cd modern
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed   # admin / admin123
npm install && npm run build && php artisan serve
php artisan test && npm test

# Legacy (Docker)
cd legacy && docker compose up    # http://localhost:8080/login.php  (admin / admin123)
```
