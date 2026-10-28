<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use RuntimeException;

final class AppointmentConcurrencyException extends RuntimeException
{
}
