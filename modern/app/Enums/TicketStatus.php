<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * The legacy column was a free-text varchar, so production data contains
     * values like "Closed" and "closed ". Anything unrecognised is treated as open.
     */
    public static function fromLegacy(?string $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::Open;
    }
}
