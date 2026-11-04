<?php

declare(strict_types=1);

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class MeliPayamakOtpProvider
{
    public function send(string $mobile): string
    {
        $endpoint = config('services.melipayamak.otp_url');

        if (! is_string($endpoint) || ! str_starts_with($endpoint, 'https://')) {
            throw new RuntimeException('OTP provider is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(10)
                ->post($endpoint, ['to' => $mobile]);

            if (! $response->successful()) {
                throw new RuntimeException('OTP provider request failed.');
            }

            $code = $response->json('code');
            if ((! is_string($code) && ! is_int($code)) || ! preg_match('/^\d{4,10}$/', (string) $code)) {
                throw new RuntimeException('OTP provider returned an invalid response.');
            }

            return (string) $code;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RuntimeException('OTP provider is temporarily unavailable.', 0, $exception);
        }
    }
}
