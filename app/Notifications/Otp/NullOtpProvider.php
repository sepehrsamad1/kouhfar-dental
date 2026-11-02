<?php

declare(strict_types=1);

namespace App\Notifications\Otp;

use RuntimeException;

final class NullOtpProvider implements OtpProvider
{
    public function sendCode(string $normalizedPhone): string
    {
        throw new RuntimeException('OTP delivery is not configured.');
    }
}
