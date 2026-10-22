<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * The single source of truth for SLA targets. The legacy app computed this
     * in two places, and they disagreed for urgent tickets (same day vs +1 day).
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 5 * 24,
            self::Normal => 3 * 24,
            self::High => 24,
            self::Urgent => 4,
        };
    }

    /**
     * @template T of CarbonInterface
     *
     * @param  T  $from
     * @return T
     */
    public function dueFrom(CarbonInterface $from): CarbonInterface
    {
        return $from->copy()->addHours($this->slaHours());
    }

    /**
     * Legacy stored priority as an int from 1 to 4.
     */
    public static function fromLegacy(int|string|null $value): self
    {
        return match ((int) $value) {
            1 => self::Low,
            3 => self::High,
            4 => self::Urgent,
            default => self::Normal,
        };
    }
}
