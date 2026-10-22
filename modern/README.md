# HelpDesk (modern)

The Laravel 13 + Vue 3 (Inertia) rewrite of [HelpDesk Pro](../legacy). See the [case study](../README.md) for context.

## Setup

Requires PHP 8.3+, Composer and Node 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # or point DB_* at MySQL/Postgres
php artisan migrate --seed          # demo data; log in as admin / admin123
npm install && npm run build        # or `npm run dev`
php artisan serve
```

## Importing the legacy database

```bash
# with `docker compose up` running in ../legacy, set in .env:
#   LEGACY_DB_PORT=3307  LEGACY_DB_PASSWORD=hunter2
php artisan migrate:fresh
php artisan legacy:import --dry-run   # rehearse: prints counts + warnings, saves nothing
php artisan legacy:import             # idempotent; safe to re-run
```
Legacy users keep their passwords. They're upgraded from MD5 to bcrypt on first login.

## Tests

```bash
php artisan test     # 98 Pest tests: characterization, unit, feature
npm test             # 13 Vitest component tests
vendor/bin/pint      # code style
```

The characterization suite loads `../legacy/includes/functions.php`, so keep the two folders side by side.

## Layout

```
app/
  Enums/            TicketStatus, TicketPriority (SLA rules live here)
  Models/           User, Customer, Ticket, Comment
  Http/Controllers/ thin controllers, one per resource
  Http/Requests/    validation
  Http/Resources/   the exact shape of the data sent to Vue
  Policies/         who may delete what
  Console/Commands/ ImportLegacyData
resources/js/
  pages/            one Vue page per route (Inertia)
  components/       StatusSelect, CommentThread, CustomerPicker, …
  layouts/          AppLayout
tests/
  Characterization/ legacy behaviour, pinned before the rewrite
  Unit/ Feature/    Pest
```
