<?php

declare(strict_types=1);

namespace App\Services;

use App\Notifications\Otp\MeliPayamakOtpProvider;
use App\Notifications\Otp\NullOtpProvider;
use App\Notifications\Otp\OtpProvider;
use InvalidArgumentException;

final class OtpProviderManager
{
    public function resolve(): OtpProvider
    {
        return match ((string) config('notifications.otp.driver', 'null')) {
            'melipayamak' => app(MeliPayamakOtpProvider::class),
            'null' => app(NullOtpProvider::class),
            default => throw new InvalidArgumentException('Unsupported OTP driver.'),
        };
    }
}
