<?php

namespace App\Console\Commands;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;
use Throwable;

/**
 * Copies HelpDesk Pro data from the legacy MySQL database into the new schema.
 *
 * - Idempotent: every row is matched on legacy_id, so re-running only updates.
 * - Cleans data on the way in (status casing, zero dates, m/d/Y strings).
 * - Never writes to the legacy database.
 * - --dry-run runs the whole import inside a transaction and rolls it back.
 */
#[Signature('legacy:import {--dry-run : Run the import and roll it back}')]
#[Description('Import users, customers, tickets and comments from the legacy HelpDesk Pro database')]
class ImportLegacyData extends Command
{
    /** @var array<string, array<int, int>> legacy id => new id */
    private array $map = ['users' => [], 'customers' => [], 'tickets' => []];

    /** @var array<string, int> */
    private array $counts = ['users' => 0, 'customers' => 0, 'tickets' => 0, 'comments' => 0];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        DB::beginTransaction();

        try {
            Model::withoutTimestampsOn([User::class, Customer::class, Ticket::class, Comment::class], function () use ($legacy) {
                $legacy->table('users')->orderBy('id')->lazyById(500)->each($this->importUser(...));
                $legacy->table('customers')->orderBy('id')->lazyById(500)->each($this->importCustomer(...));
                $legacy->table('tickets')->orderBy('id')->lazyById(500)->each($this->importTicket(...));
                $legacy->table('comments')->orderBy('id')->lazyById(500)->each($this->importComment(...));
            });
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Import failed and was rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->option('dry-run') ? DB::rollBack() : DB::commit();

        $this->table(['Entity', 'Imported'], collect($this->counts)->map(fn ($n, $k) => [$k, $n])->values());

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        $this->info($this->option('dry-run') ? 'Dry run complete, nothing was saved.' : 'Import complete.');

        return self::SUCCESS;
    }

    private function importUser(stdClass $row): void
    {
        $user = User::firstOrNew(['legacy_id' => $row->id]);

        $user->forceFill([
            'username' => $row->username,
            'name' => $row->full_name ?: $row->username,
            'email' => strtolower(trim((string) $row->email)) ?: "{$row->username}@legacy.invalid",
            'is_admin' => (bool) $row->is_admin,
            'created_at' => $this->datetime($row->created) ?? Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Keep the MD5 hash only until the user logs in and it is upgraded to bcrypt.
        if ($user->password === null) {
            $user->legacy_password = $row->password;
        }

        $user->save();
        $this->map['users'][$row->id] = $user->id;
        $this->counts['users']++;
    }

    private function importCustomer(stdClass $row): void
    {
        $created = $this->datetime($row->created) ?? Carbon::now();

        $customer = Customer::updateOrCreate(['legacy_id' => $row->id], [
            'name' => trim((string) $row->name) ?: '(no name)',
            'company' => trim((string) $row->company) ?: null,
            'email' => strtolower(trim((string) $row->email)) ?: null,
            'phone' => trim((string) $row->phone) ?: null,
            'notes' => $row->notes,
            'created_at' => $created,
            'updated_at' => $created,
        ]);

        $this->map['customers'][$row->id] = $customer->id;
        $this->counts['customers']++;
    }

    private function importTicket(stdClass $row): void
    {
        $customerId = $this->map['customers'][$row->customer_id] ?? null;
        if ($row->customer_id && ! $customerId) {
            $this->warnings[] = "Ticket #{$row->id}: customer #{$row->customer_id} no longer exists, imported without a customer.";
        }

        $priority = TicketPriority::fromLegacy($row->priority);
        $created = $this->datetime($row->created) ?? Carbon::now();
        $updated = $this->datetime($row->updated) ?? $created;

        $ticket = Ticket::withTrashed()->firstOrNew(['legacy_id' => $row->id]);
        $ticket->forceFill([
            'customer_id' => $customerId,
            'assignee_id' => $this->map['users'][$row->assigned_to] ?? null,
            'subject' => trim((string) $row->subject) ?: '(no subject)',
            'body' => $row->body ?: null,
            'status' => TicketStatus::fromLegacy($row->status),
            'priority' => $priority,
            'due_at' => $this->legacyDueDate($row->due_date) ?? $priority->dueFrom($created),
            'created_at' => $created,
            'updated_at' => $updated,
            'deleted_at' => $row->deleted ? $updated : null,
        ])->save();

        $this->map['tickets'][$row->id] = $ticket->id;
        $this->counts['tickets']++;
    }

    private function importComment(stdClass $row): void
    {
        $ticketId = $this->map['tickets'][$row->ticket_id] ?? null;
        if (! $ticketId) {
            $this->warnings[] = "Comment #{$row->id}: ticket #{$row->ticket_id} no longer exists, skipped.";

            return;
        }

        $created = $this->datetime($row->created) ?? Carbon::now();

        Comment::updateOrCreate(['legacy_id' => $row->id], [
            'ticket_id' => $ticketId,
            'user_id' => $this->map['users'][$row->user_id] ?? null,
            'body' => (string) $row->body,
            'is_internal' => (bool) $row->is_internal,
            'created_at' => $created,
            'updated_at' => $created,
        ]);

        $this->counts['comments']++;
    }

    /**
     * MySQL 5 allowed "0000-00-00 00:00:00" as a value; treat it as missing.
     */
    private function datetime(?string $value): ?Carbon
    {
        if (! $value || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Legacy due dates are "m/d/Y" strings with no time, so treat them as due
     * at the end of that day.
     */
    private function legacyDueDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('!m/d/Y', $value)?->endOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
