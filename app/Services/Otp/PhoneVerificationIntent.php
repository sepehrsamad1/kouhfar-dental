<?php

declare(strict_types=1);

namespace App\Services\Otp;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;

final class PhoneVerificationIntent
{
    public static function hash(array $payload): string
    {
        $canonical = [
            'full_name' => trim((string) $payload['full_name']),
            'mobile' => (string) $payload['mobile'],
            'service_id' => (string) $payload['service_id'],
            'start_at' => (new DateTimeImmutable((string) $payload['start_at']))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\\TH:i:s\\Z'),
        ];

        try {
            return hash('sha256', json_encode(
                $canonical,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
        } catch (JsonException $exception) {
            throw new \\LogicException('Could not hash phone verification intent.', 0, $exception);
        }
    }
}
