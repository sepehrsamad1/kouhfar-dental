<?php

declare(strict_types=1);

namespace App\Domain\Booking\Exceptions;

use RuntimeException;

final class IdempotencyKeyReused extends RuntimeException
{
}
