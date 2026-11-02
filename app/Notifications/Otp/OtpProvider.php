<?php

declare(strict_types=1);

namespace App\Notifications\Otp;

interface OtpProvider
{
    /**
     * Send a provider-generated OTP and return the code for secure server-side verification.
     * Callers must never expose or log the returned code.
     */
    public function sendCode(string $normalizedPhone): string;
}
