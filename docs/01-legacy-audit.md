# 1. Legacy audit: HelpDesk Pro v2.3

**Scope:** every file in [`legacy/`](../legacy): 532 lines of PHP and jQuery across 15 files.
**Method:** a manual code review that traces each finding from request to query or output. The pure helper functions are also exercised by executable characterization tests. The app runs locally with `docker compose up` in `legacy/` if you want to reproduce the findings by hand.

The app is a typical "built in 2009, patched ever since" internal tool. It works, the support team depends on it, and nobody wants to touch it.

## Summary

| Severity | Count | Themes |
|---|---:|---|
| 🔴 Critical | 5 | SQL injection (login bypass, data destruction), unauthenticated write endpoint, stored XSS |
| 🟠 High | 6 | Reflected XSS, CSRF on destructive actions, MD5 passwords, credentials in source, SQL errors shown to users, unauthenticated data endpoint |
| 🟡 Medium | 5 | Business-rule bugs (SLA, overdue), N+1 queries, no referential integrity, broken auth redirect |
| ⚪ Low / maintainability | 6 | No tests, no dependency management, dead jQuery 1.8 CDN, duplicated logic, mixed PHP/HTML, latin1 charset |

The code contains **22 raw SQL strings and 0 calls to `htmlspecialchars()`.** 11 of the queries have request data concatenated straight into the SQL.

---

## 🔴 Critical

### C1. SQL injection lets anyone log in as any user without a password
`legacy/login.php:6`
```php
$sql = "SELECT * FROM users WHERE username = '" . $_POST['username'] . "' AND password = '" . md5($_POST['password']) . "'";
```
Entering `admin' -- ` as the username comments out the password check. The first user in the table is the admin.

**Fixed by:** an Eloquent lookup with bound parameters. **Regression test:** `AuthTest › is not vulnerable to the legacy SQL injection login bypass` (3 payloads).

### C2. SQL injection on every list, filter and form
`tickets.php:8,11,18`, `ticket.php:6`, `ticket_edit.php:20-25,34`, `customers.php:7,13`, `ajax/search_customers.php:6`

`$_GET`/`$_POST` values are concatenated into `WHERE`, `LIMIT`, `INSERT` and `UPDATE` statements. `addslashes()` is used in a few places and skipped in most. Even where it's used, it isn't a defence: integer contexts such as `WHERE id = $id` have no quotes to escape. One example: a search for `') UNION SELECT id, username, password, … FROM users -- ` on `tickets.php` lists every username and password hash in the ticket table.

**Fixed by:** Eloquent/query builder everywhere. The one `selectRaw` in the new app (`DashboardController`) is a static string. **Tests:** `TicketIndexTest › treats SQL in the search box as plain text`, `› rejects a status that is not a real status`, and `TicketCrudTest › returns 404 for a missing ticket instead of a raw SQL error`.

### C3. Unauthenticated endpoint can rewrite any ticket
`ajax/update_status.php:6`
```php
db_query("UPDATE tickets SET status = '" . $_POST['status'] . "', updated = NOW() WHERE id = " . $_POST['id']);
```
There's no `check_login()`, and it's injectable as well. `curl -d "id=1 OR 1=1&status=closed"` closes every ticket in the system, with no login needed.

**Fixed by:** `TicketStatusController` sits behind `auth`, a policy, CSRF protection and `Rule::enum`. **Tests:** `TicketStatusTest` (guest blocked, invalid values rejected).

### C4. Stored XSS in comments
`ajax/add_comment.php:15`, `ticket.php:23,32`

Comment and ticket bodies are echoed through `nl2br()` with no escaping. A comment containing `<script>` runs in every agent's browser that opens the ticket, and can read their session cookie, which isn't `HttpOnly`.

**Fixed by:** Vue `{{ }}` interpolation, with `white-space: pre-line` instead of `v-html`. **Tests:** `CommentThread.test.js › escapes HTML in comment bodies` (Vitest) and `CommentTest › stores HTML exactly as typed` (Pest).

### C5. SQL injection in customer creation
`customers.php:13`: all five form fields are concatenated into an `INSERT`. Beyond the security risk, a customer called *O'Brien* simply can't be saved, which is why `seed.sql` has a hand-escaped `O''Brien`.

---

## 🟠 High

| # | Finding | Location | Fix |
|---|---|---|---|
| H1 | **Reflected XSS** in search box, login form, flash message | `tickets.php:22`, `login.php:15,25`, `header.php:33` (`?msg=<script>`) | Vue escaping; flash messages come from the session, not the URL |
| H2 | **CSRF**: deletes happen on `GET customers.php?delete=ID`, and no form has a token | `customers.php:7,31` | `DELETE` route plus Laravel CSRF middleware. Test: `CustomerTest › never deletes on a GET request` |
| H3 | **Unsalted MD5 passwords**, crackable in seconds with rainbow tables | `login.php:6`, `schema.sql` | bcrypt, with a transparent upgrade on first login (see [migration strategy](02-migration-strategy.md#passwords)) |
| H4 | **DB root password committed to the repo** | `config.php:6` | `.env`, never committed |
| H5 | **SQL errors and queries shown to users** (`display_errors=1`, `die(mysqli_error() . $sql)`) | `config.php:13`, `db.php:9` | Laravel exception handler; `APP_DEBUG=false` in production |
| H6 | **Unauthenticated customer search** leaks names and companies | `ajax/search_customers.php` | Behind `auth`. Test: `CustomerTest › does not expose the search endpoint to guests` |

## 🟡 Medium: bugs the business was living with

| # | Finding | Evidence | Impact |
|---|---|---|---|
| M1 | **Urgent SLA defined twice, inconsistently.** `functions.php` says same day; `ticket_edit.php:16` (the copy that actually runs) says +1 day | Characterization test `BUG: urgent tickets are due "same day"…` | Urgent tickets reported as on time when they were a day late. SLA reports to customers were wrong |
| M2 | **Overdue check compares `m/d/Y` strings.** `"12/30/2025" < "01/05/2026"` is false | `functions.php:30`; characterization test `BUG: overdue check compares m/d/Y strings…` | Every December, overdue tickets disappear from the red list, and next-January tickets show as overdue |
| M3 | **Status is free text.** `'Closed'` and `'closed '` exist in production, so closed tickets show as overdue and are missing from the dashboard counts | `seed.sql`; characterization test `BUG: status checks are case sensitive…` | Dashboard numbers don't add up |
| M4 | **N+1 queries.** Dashboard = 4 + 2n queries; ticket list = 2 + 3n (62 queries for one 20-row page) | `index.php:9,32,35`, `tickets.php:38-49` | Gets slower as data grows |
| M5 | **Auth redirect never fires.** `header.php` outputs HTML before `check_login()` calls `header('Location: …')`. The warning is hidden by `error_reporting(E_ALL ^ E_WARNING)`, and the page relies on `exit` alone | every page | Unless `output_buffering` happens to be on in php.ini, guests get a half-rendered page instead of the login screen. It only works by accident on servers configured that way |

The schema also has **no foreign keys** (MyISAM), so deleting a customer leaves orphaned tickets and comments behind. The sample data reproduces this: ticket #6 points at deleted customer #7, and comment #4 at deleted ticket #99.

## ⚪ Maintainability

- **No tests of any kind.** Changes are verified by clicking around.
- **No dependency management.** jQuery 1.8.3 and jQuery UI 1.9.2 load from `http://code.jquery.com`. That's plain HTTP, so they're blocked as mixed content on any HTTPS page.
- **Logic is duplicated across files:** SLA rules in 2 places, the status list in 3, and customer-name formatting in 2.
- **Presentation, SQL and business logic share every file.** `ticket_edit.php` handles the POST, builds the SQL, computes the SLA and renders the form in 70 lines.
- **`latin1` charset.** `short_text()` cuts multibyte names mid-character (characterization test `BUG: short_text()…`).
- **PHP 7.4 has been end-of-life since November 2022.** `mysql_*` → `mysqli_*` was the last "upgrade", done with find-and-replace.

## What we deliberately kept

The audit isn't a list of reasons to throw everything away. The **workflows are right**: the support team's process (open → pending → closed, priorities with SLAs, internal notes vs replies, a per-agent dashboard) is sound and was kept 1:1. The characterization tests marked `preserves:` pin down those behaviours so the rewrite couldn't drift from them.
