<?php

declare(strict_types=1);

namespace App\Services;

use App\Notifications\Sms\KavenegarSmsProvider;
use App\Notifications\Sms\LogSmsProvider;
use App\Notifications\Sms\NullSmsProvider;
use App\Notifications\Sms\SmsProvider;
use InvalidArgumentException;

final class SmsProviderManager
{
    public function resolve(): SmsProvider
    {
        return match ((string) config('notifications.sms.driver', 'log')) {
            'log' => app(LogSmsProvider::class),
            'null' => app(NullSmsProvider::class),
            'kavenegar' => app(KavenegarSmsProvider::class),
            default => throw new InvalidArgumentException('Unsupported SMS driver.'),
        };
    }
}
