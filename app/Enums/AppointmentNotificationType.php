<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentNotificationType: string
{
    case Pending = 'booking_pending';
    case Confirmed = 'booking_confirmed';
    case Reminder = 'appointment_reminder';
    case Expired = 'hold_expired';
    case Cancelled = 'booking_cancelled';
    case Rescheduled = 'booking_rescheduled';

    public function isAutomated(): bool
    {
        return $this === self::Reminder || $this === self::Expired;
    }
}
