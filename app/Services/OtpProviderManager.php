<?php

declare(strict_types=1);

namespace App\Services;

use App\Notifications\Otp\DemoOtpProvider;
use App\Notifications\Otp\MeliPayamakOtpProvider;
use App\Notifications\Otp\NullOtpProvider;
use App\Notifications\Otp\OtpProvider;
use InvalidArgumentException;
use RuntimeException;

final class OtpProviderManager
{
    public function resolve(): OtpProvider
    {
        return match ((string) config('notifications.otp.driver', 'null')) {
            'demo' => $this->resolveUniversityDemo(),
            'melipayamak' => $this->resolveMeliPayamak(),
            'null' => app(NullOtpProvider::class),
            default => throw new InvalidArgumentException('Unsupported OTP driver.'),
        };
    }

    private function resolveUniversityDemo(): OtpProvider
    {
        if (app()->environment('production') || ! (bool) config('notifications.otp.university_demo_mode', false)) {
            throw new RuntimeException('University demo OTP is not enabled.');
        }

        return app(DemoOtpProvider::class);
    }

    private function resolveMeliPayamak(): OtpProvider
    {
        if (! (bool) config('notifications.otp.live_sms_approved', false)) {
            throw new RuntimeException('Live OTP SMS is not explicitly approved.');
        }

        if (app()->environment('production')
            && ! (bool) config('notifications.otp.provider_url_path_risk_accepted', false)) {
            throw new RuntimeException('OTP provider URL-path credential risk is not approved.');
        }

        return app(MeliPayamakOtpProvider::class);
    }
}
