# 3. Before and after

Side-by-side comparisons of the changes that matter most. Every "after" snippet is taken from [`modern/`](../modern) and covered by tests.

- [Raw SQL → Eloquent](#raw-sql--eloquent)
- [N+1 loops → eager loading](#n1-loops--eager-loading)
- [Copy-pasted rules → one domain model](#copy-pasted-rules--one-domain-model)
- [Page scripts → controller + form request + policy](#page-scripts--controller--form-request--policy)
- [jQuery → Vue components](#jquery--vue-components)
- [MD5 → bcrypt without a reset email](#md5--bcrypt-without-a-reset-email)
- [No tests → 112 tests](#no-tests--112-tests)

---

## Raw SQL → Eloquent

**Before:** `legacy/tickets.php`
```php
$where = "WHERE deleted = 0";
if ($_GET['status']) {
    $where .= " AND status = '" . $_GET['status'] . "'";
}
if ($_GET['q']) {
    $where .= " AND (subject LIKE '%" . $_GET['q'] . "%' OR body LIKE '%" . $_GET['q'] . "%')";
}
$page = $_GET['page'] ? $_GET['page'] : 1;
$start = ($page - 1) * $per_page;
$total = db_fetch_one("SELECT COUNT(*) AS c FROM tickets $where");
$tickets = db_fetch_all("SELECT * FROM tickets $where ORDER BY id DESC LIMIT $start, $per_page");
```

**After:** `app/Models/Ticket.php` + `TicketController@index`
```php
public function scopeFilter(Builder $query, array $filters): void
{
    $query
        ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
        ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
            ->where('subject', 'like', "%{$term}%")
            ->orWhere('body', 'like', "%{$term}%")))
        ->when($filters['assignee'] ?? null, fn (Builder $q, $assignee) => $q->where('assignee_id', $assignee));
}

// controller
$filters = $request->validate([
    'status' => ['nullable', Rule::enum(TicketStatus::class)],
    'q' => ['nullable', 'string', 'max:100'],
    'assignee' => ['nullable', 'integer'],
]);

$tickets = Ticket::query()
    ->filter($filters)
    ->with(['customer', 'assignee:id,name'])
    ->withCount('comments')
    ->latest('id')
    ->paginate(20)
    ->withQueryString();
```

| | Before | After |
|---|---|---|
| Injection | Every parameter | Impossible: values are bound, never concatenated |
| Input validation | None | Status must be a real enum value; search capped at 100 characters |
| Soft deletes | `deleted = 0` written by hand (and forgotten in `ticket.php`) | Applied to every query automatically by `SoftDeletes` |
| Pagination | Hand-rolled, filters dropped from page links unless re-appended | `paginate()->withQueryString()` |
| Reuse | Copy the string | `Ticket::filter()` works anywhere: exports, API, reports |

---

## N+1 loops → eager loading

**Before:** `legacy/index.php`. For an agent with 20 open tickets: **44 queries**.
```php
foreach ($statuses as $s) {                                       // 3 queries
    $row = db_fetch_one("SELECT COUNT(*) AS c FROM tickets WHERE status = '$s' AND deleted = 0");
}
$mine = db_fetch_all("SELECT * FROM tickets WHERE assigned_to = ...");  // 1 query
// …then in the template, per row:
<?php echo get_customer_name($t['customer_id']); ?>              // +1 query per row
<?php echo count_comments($t['id']); ?>                          // +1 query per row
```

**After:** `DashboardController`. **4 queries** whether there are 2 tickets or 2,000 (one of them is the logged-in user lookup).
```php
$totals = Ticket::query()->toBase()
    ->selectRaw('status, count(*) as total')
    ->groupBy('status')
    ->pluck('total', 'status');

$mine = $request->user()
    ->assignedTickets()
    ->unresolved()
    ->with('customer')
    ->withCount('comments')
    ->latest('id')
    ->get();
```

| Page (20 rows) | Legacy queries | Modern queries |
|---|---:|---:|
| Dashboard | 44 | 4 |
| Ticket list | 62 | 5 |
| Ticket detail (10 comments) | 14 | 5 |

Modern counts were measured with `DB::getQueryLog()`. Legacy counts come from reading the code paths. Two safeguards keep it this way:
1. `Model::preventLazyLoading()` in `AppServiceProvider` throws in dev and test environments the moment someone writes a loop that lazy-loads.
2. `DashboardTest` and `TicketIndexTest` assert the query count is **the same for 2 rows as for 40**.

---

## Copy-pasted rules → one domain model

**Before:** the SLA rule exists twice, and the two copies disagree.
```php
// includes/functions.php
else $days = 0;                                                   // urgent = same day

// ticket_edit.php (the copy that actually runs)
elseif ($priority == 4) $due = date('m/d/Y', strtotime('+1 day')); // urgent = +1 day
```
…and "overdue" is a string comparison:
```php
return $due_date < $today;   // "12/30/2025" < "01/05/2026" === false
```

**After:** `app/Enums/TicketPriority.php` and `app/Models/Ticket.php`
```php
enum TicketPriority: string
{
    case Low = 'low'; case Normal = 'normal'; case High = 'high'; case Urgent = 'urgent';

    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 5 * 24,
            self::Normal => 3 * 24,
            self::High => 24,
            self::Urgent => 4,
        };
    }
}

// Ticket model: due_at is set in exactly one place, for every code path
static::creating(function (Ticket $ticket) {
    $ticket->due_at ??= $ticket->priority->dueFrom($ticket->created_at ?? Carbon::now());
});

public function isOverdue(): bool
{
    return $this->status !== TicketStatus::Closed
        && $this->due_at !== null
        && $this->due_at->isPast();
}
```
The frontend never re-implements the rule. `is_overdue` comes from the server in `TicketResource`, and `DueDate.vue` only displays it.

---

## Page scripts → controller + form request + policy

**Before:** `legacy/customers.php` does routing, authorization, SQL and HTML in one file. Delete is a GET link:
```php
if ($_GET['delete']) {
    db_query("DELETE FROM customers WHERE id = " . $_GET['delete']);
    header('Location: customers.php?msg=Customer deleted');
}
// …
<?php if (is_admin()) { ?><a href="customers.php?delete=<?php echo $c['id']; ?>">delete</a><?php } ?>
```
The `is_admin()` check only hides the link. The delete itself runs for anyone who knows the URL.

**After:**
```php
// routes/web.php
Route::resource('customers', CustomerController::class)->except('show');   // DELETE /customers/{id}

// CustomerController
public function destroy(Customer $customer): RedirectResponse
{
    Gate::authorize('delete', $customer);      // CustomerPolicy: admins only, enforced server-side
    $customer->delete();                       // soft delete; tickets keep their customer
    return redirect()->route('customers.index')->with('success', 'Customer deleted.');
}
```
Validation lives in `StoreCustomerRequest`, authorization in `CustomerPolicy`, presentation in `customers/Index.vue`, and CSRF protection is on for every non-GET request by default.

---

## jQuery → Vue components

### Inline status change

**Before:** `legacy/js/app.js` + `ajax/update_status.php`
```js
$('.status-change').change(function () {
    var $sel = $(this);
    $sel.attr('disabled', 'disabled');
    $.post('ajax/update_status.php', { id: $sel.attr('data-id'), status: $sel.val() }, function (data) {
        $sel.removeAttr('disabled');
        if (data == 'OK') {
            $sel.closest('tr').css('background', '#ffffcc');
        } else {
            alert('Error: ' + data);
        }
    });
});
```
```php
// ajax/update_status.php: no login check, raw SQL, returns the string 'OK'
db_query("UPDATE tickets SET status = '" . $_POST['status'] . "' ... WHERE id = " . $_POST['id']);
echo 'OK';
```

**After:** `components/StatusSelect.vue`
```vue
<script setup>
const props = defineProps({ ticketId: Number, status: String });
const saving = ref(false);

function update(event) {
    router.patch(`/tickets/${props.ticketId}/status`, { status: event.target.value }, {
        preserveScroll: true,
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
    });
}
</script>

<template>
    <select :value="status" :disabled="saving" @change="update">
        <option v-for="(meta, value) in statuses" :key="value" :value="value">{{ meta.label }}</option>
    </select>
</template>
```
The same component is used on the list page and the detail page. Errors, flash messages and re-rendering go through Inertia's normal response cycle instead of `alert()`.

### Comments: server-built HTML → data + template

**Before:** the server builds an HTML string, unescaped, and jQuery appends it.
```php
// ajax/add_comment.php
echo '<li><b>' . $_SESSION['full_name'] . '</b> ... <br />' . nl2br($_POST['body']) . '</li>';
```
```js
$.post('ajax/add_comment.php', $(this).serialize(), function (html) {
    $('#comments').append(html);
});
```

**After:** the server returns data and Vue renders it, escaped.
```vue
<!-- components/CommentThread.vue -->
<li v-for="comment in comments" :key="comment.id">
    <span class="font-medium">{{ comment.author }}</span>
    <p class="whitespace-pre-line">{{ comment.body }}</p>
</li>
```

### Customer autocomplete

jQuery UI's autocomplete plus a hidden `#customer_id` field, fed by JSON built through string concatenation (which broke on `"` in a company name), becomes `CustomerPicker.vue`. It binds with `v-model`, debounces and cancels in-flight requests with `AbortController`, and has proper `role="combobox"`/`listbox` semantics. The endpoint returns `response()->json()`, so quotes can't break it. That's covered by `CustomerTest › returns valid JSON… even for names with quotes`.

### What the frontend migration bought

| | jQuery | Vue |
|---|---|---|
| State | Scattered in the DOM and globals (`var saving`, `var commentCount`) | Component-local `ref()`s and props |
| Rendering | String concatenation, server-side or client-side | Declarative templates, escaped by default |
| Reuse | Copy-paste | `StatusSelect`, `DueDate`, `TicketForm`… used across pages |
| Testability | None | Vitest + Vue Test Utils (13 tests) |
| Dependencies | jQuery 1.8 from an HTTP CDN | npm-managed, bundled by Vite, hashed for caching |

---

## MD5 → bcrypt without a reset email

```php
// Auth\LoginController
private function passwordMatches(User $user, string $password): bool
{
    if ($user->password !== null) {
        return Hash::check($password, $user->password);
    }

    if ($user->legacy_password !== null && hash_equals($user->legacy_password, md5($password))) {
        $user->forceFill(['password' => $password, 'legacy_password' => null])->save();
        return true;
    }

    return false;
}
```
Plus throttling, which the legacy login never had: 5 attempts per minute per username and IP (`RateLimiter::for('login')`).

---

## No tests → 112 tests

| Suite | Tool | Tests | What it proves |
|---|---|---:|---|
| Characterization | Pest | 10 | What the legacy code *actually* did, bugs included |
| Unit | Pest | 22 | SLA rules, legacy value mapping, overdue logic across year boundaries |
| Feature | Pest | 66 | Every route: auth, validation, authorization, filters, N+1 limits, the import command |
| Browser | Pest + Playwright | 1 | The main agent workflow end to end in a real Chromium |
| Components | Vitest | 13 | Rendering, XSS escaping, pagination |

See [04-testing-strategy.md](04-testing-strategy.md).
