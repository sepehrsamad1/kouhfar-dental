<?php

declare(strict_types=1);

namespace App\Notifications\Otp;

use RuntimeException;

final class DemoOtpProvider implements OtpProvider
{
    public function sendCode(string $normalizedPhone): string
    {
        if (app()->environment('production') || ! (bool) config('notifications.otp.university_demo_mode', false)) {
            throw new RuntimeException('University demo OTP is not enabled.');
        }

        $code = (string) config('notifications.otp.demo_code', '123456');

        if (! preg_match('/^\d{4,12}$/', $code)) {
            throw new RuntimeException('University demo OTP is not configured.');
        }

        return $code;
    }
}
