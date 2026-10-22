# 2. Migration strategy

The goal was to replace HelpDesk Pro without the support team losing a day, a ticket or a password.

## Approach: characterize, rebuild behind tests, import, cut over

```
 ┌──────────────┐   ┌───────────────┐   ┌──────────────┐   ┌──────────────┐   ┌────────────┐
 │ 1. Audit &   │──▶│ 2. New schema │──▶│ 3. Feature   │──▶│ 4. Data      │──▶│ 5. Cutover │
 │ characterize │   │ + domain      │   │ parity       │   │ import       │   │ + rollback │
 └──────────────┘   └───────────────┘   └──────────────┘   │ rehearsals   │   │   window   │
   tests pin old      enums, FKs,        page by page,     └──────────────┘   └────────────┘
   behaviour          SLA in one place   test-first          idempotent, dry-run
```

### Why not a big-bang rewrite from a spec?
There was no spec. The real requirements lived in the PHP files, including the bugs users had learned to work around. So **step 1 writes tests against the legacy code itself** ([`tests/Characterization`](../modern/tests/Characterization/LegacyBehaviourTest.php)). Each legacy behaviour gets one of two labels:

- **`preserves:`** the new system must do the same thing (e.g. closed tickets are never overdue).
- **`BUG:`** documented, signed off by the client, and fixed. The fix gets its own test in `tests/Unit` or `tests/Feature`.

That turns "the new one feels different" arguments into a reviewable list of decisions.

### Why not strangler fig (proxying page by page)?
For an app this size, about 10 screens, running two systems side by side costs more than it saves. Session sharing, dual writes and a proxy layer would be more code than the app itself. **For a larger legacy app (50+ screens, several teams), we'd recommend strangler fig**: put Laravel in front, route migrated URLs to it, and fall back to the legacy app for everything else. The same characterization → parity → import steps still apply, one module at a time.

## Step 2: schema decisions

| Legacy | Modern | Why |
|---|---|---|
| MyISAM, no FKs | InnoDB/SQLite with foreign keys | Orphans become impossible: `nullOnDelete` for customer/assignee, `cascadeOnDelete` for comments |
| `status varchar(20)` free text | `TicketStatus` backed enum + validation | Fixes M3; values can't drift |
| `priority int(1)` | `TicketPriority` enum with `slaHours()` | The SLA lives in exactly one place (fixes M1) |
| `due_date varchar 'm/d/Y'` | `due_at timestamp` | Hour precision; correct comparisons (fixes M2) |
| `deleted tinyint` | `SoftDeletes` (`deleted_at`) | Same behaviour, and the framework applies it to every query automatically |
| `password varchar(32)` MD5 | `password` bcrypt + nullable `legacy_password` | See [passwords](#passwords) |
| — | `legacy_id` on every table | Idempotent re-imports; a trail back to the old record for support questions |

## Step 3: feature parity, page by page

| Legacy file | Replaced by |
|---|---|
| `login.php`, `logout.php` | `Auth\LoginController` + `pages/auth/Login.vue` |
| `index.php` | `DashboardController` + `pages/Dashboard.vue` |
| `tickets.php` | `TicketController@index` + `pages/tickets/Index.vue` |
| `ticket.php` | `TicketController@show` + `pages/tickets/Show.vue` |
| `ticket_edit.php` | `TicketController@create/store/edit/update` + `TicketForm.vue` |
| `customers.php` | `CustomerController` + `pages/customers/*.vue` |
| `ajax/update_status.php` | `TicketStatusController` + `StatusSelect.vue` |
| `ajax/add_comment.php` | `CommentController` + `CommentForm.vue` / `CommentThread.vue` |
| `ajax/search_customers.php` | `CustomerSearchController` + `CustomerPicker.vue` |
| `js/app.js` (jQuery) | Vue components (see [before/after](03-before-after.md#jquery--vue-components)) |

**Inertia.js** was chosen over a separate SPA + REST API. The client's team knows Laravel, and Inertia keeps routing, auth, validation and authorization on the server, with Vue for the UI. It removes a whole category of work (API versioning, token auth, CORS, duplicated validation) that a 4-person support tool doesn't need.

## Step 4: data import

`php artisan legacy:import` ([source](../modern/app/Console/Commands/ImportLegacyData.php)) reads the old MySQL database over a separate `legacy` connection and **never writes to it**.

| Property | How |
|---|---|
| **Idempotent** | Every row is upserted on `legacy_id`. Run it nightly during the parallel period; the final run only catches the delta |
| **Rehearsable** | `--dry-run` runs everything in a transaction and rolls back, showing exactly what would happen |
| **Atomic** | Any exception rolls back the whole import |
| **Cleans as it goes** | `'closed '`→`closed`; `0000-00-00`→null (with a sensible fallback); `'m/d/Y'`→end-of-day timestamp; empty or garbage due dates are recomputed from the SLA; emails lower-cased and trimmed |
| **Reports, doesn't hide** | Orphaned tickets are imported without a customer and listed as warnings. Orphaned comments are skipped and listed |

Every one of these rules is covered by [`LegacyImportTest`](../modern/tests/Feature/LegacyImportTest.php), which builds a copy of the legacy schema in memory, loads the same messy rows as `legacy/seed.sql`, and checks the result.

### Passwords
MD5 hashes can't be converted to bcrypt without the plain-text password. Forcing a reset for every user at cutover means a flood of support tickets, at the helpdesk of all places. Instead:

1. The import copies the MD5 hash into `legacy_password` and leaves `password` null.
2. On login, if `password` is null and `md5(input)` matches `legacy_password` (using a constant-time `hash_equals`), we **rehash with bcrypt and delete the MD5**.
3. Re-running the import never overwrites a password that has already been upgraded.
4. After 90 days, any remaining `legacy_password` values are cleared and those accounts go through the normal reset flow.

Users notice nothing. Within a few weeks every active account is on bcrypt. Tests: `AuthTest › upgrades an imported MD5 password…` and `LegacyImportTest › does not overwrite a password that was already upgraded`.

## Step 5: cutover plan

| When | What |
|---|---|
| T-14 days | Deploy the new app to production at a separate hostname. Nightly `legacy:import`. Agents try it and report differences |
| T-2 days | Final `--dry-run` against a production snapshot; review the warnings with the client |
| T-0 (Friday 6pm) | Legacy app set to read-only (one line in `header.php`). Final import. DNS switch |
| T+0 to T+14 | Legacy stays read-only at `legacy.` for lookups. **Rollback** = point DNS back and remove the read-only flag. Tickets created in the new system during that window are exported by `legacy_id IS NULL` |
| T+90 | Clear remaining `legacy_password` values. Archive the legacy DB |

## Risks and mitigations

| Risk | Mitigation |
|---|---|
| Hidden behaviour we didn't characterize | 14-day parallel run; agents flag differences; each one becomes a test |
| Data that won't map cleanly | `--dry-run` warnings reviewed with the client before cutover; nothing is dropped silently |
| Users locked out | Transparent MD5 upgrade; the legacy app stays readable during the rollback window |
| Performance regression | N+1 guard tests; `preventLazyLoading()` throws in dev and test environments if a query slips into a loop |
