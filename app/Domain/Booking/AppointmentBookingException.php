<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use RuntimeException;

final class AppointmentBookingException extends RuntimeException
{
    public function __construct(
        public readonly string $codeName,
        string $message = 'The appointment could not be created.',
    ) {
        parent::__construct($message);
    }
}
