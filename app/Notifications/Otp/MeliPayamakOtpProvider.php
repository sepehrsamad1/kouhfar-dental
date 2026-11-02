<?php

declare(strict_types=1);

namespace App\Notifications\Otp;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class MeliPayamakOtpProvider implements OtpProvider
{
    private const ENDPOINT_BASE = 'https://console.melipayamak.com/api/send/otp/';

    public function sendCode(string $normalizedPhone): string
    {
        $token = config('notifications.otp.melipayamak.token');

        if (! is_string($token) || preg_match('/^[A-Za-z0-9_-]{20,128}$/D', $token) !== 1) {
            throw new RuntimeException('MeliPayamak OTP is not configured.');
        }

        if (preg_match('/^09[0-9]{9}$/D', $normalizedPhone) !== 1) {
            throw new RuntimeException('OTP recipient phone number is invalid.');
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->asJson()
                ->timeout(10)
                ->post(self::ENDPOINT_BASE.$token, ['to' => $normalizedPhone]);

            if (! $response->successful()) {
                throw new RuntimeException('MeliPayamak OTP delivery failed.');
            }

            $payload = $response->json();
            $code = is_array($payload) ? ($payload['code'] ?? null) : null;
            $code = is_string($code) || is_int($code) ? (string) $code : '';

            if (preg_match('/^[0-9]{4,12}$/D', $code) !== 1) {
                throw new RuntimeException('MeliPayamak OTP response was invalid.');
            }

            return $code;
        } catch (Throwable) {
            // Do not chain exceptions: the request URL contains the provider token.
            throw new RuntimeException('MeliPayamak OTP delivery failed.');
        }
    }
}
