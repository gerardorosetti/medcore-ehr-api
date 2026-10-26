<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle statuses for clinical appointments.
 */
enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Determine whether the status can transition to a target status.
     */
    #[\NoDiscard]
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }

    /**
     * Determine whether the appointment is currently active (used for double-booking prevention).
     */
    #[\NoDiscard]
    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed => true,
            self::Completed, self::Cancelled => false,
        };
    }

    /**
     * Get an array of all appointment status values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
