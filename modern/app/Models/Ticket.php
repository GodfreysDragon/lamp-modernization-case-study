<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable(['customer_id', 'assignee_id', 'subject', 'body', 'status', 'priority', 'due_at', 'legacy_id'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'open',
        'priority' => 'normal',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'due_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // The SLA lives in one place (TicketPriority::slaHours) instead of being
        // copy-pasted into every page that saves a ticket.
        static::creating(function (Ticket $ticket) {
            $ticket->due_at ??= $ticket->priority->dueFrom($ticket->created_at ?? Carbon::now());
        });

        static::updating(function (Ticket $ticket) {
            if ($ticket->isDirty('priority') && ! $ticket->isDirty('due_at')) {
                $ticket->due_at = $ticket->priority->dueFrom($ticket->created_at ?? Carbon::now());
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    /**
     * @param  array{status?: ?string, q?: ?string, assignee?: int|string|null}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
                ->where('subject', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")))
            ->when($filters['assignee'] ?? null, fn (Builder $q, $assignee) => $q->where('assignee_id', $assignee));
    }

    public function scopeUnresolved(Builder $query): void
    {
        $query->where('status', '!=', TicketStatus::Closed);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->unresolved()->whereNotNull('due_at')->where('due_at', '<', Carbon::now());
    }

    /**
     * Compares real timestamps. The legacy version compared "m/d/Y" strings,
     * so "12/30/2010" counted as later than "01/05/2011".
     */
    public function isOverdue(): bool
    {
        return $this->status !== TicketStatus::Closed
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}
