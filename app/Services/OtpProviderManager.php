<?php

declare(strict_types=1);

namespace AppServices;

use AppNotificationsOtpMeliPayamakOtpProvider;
use AppNotificationsOtpNullOtpProvider;
use AppNotificationsOtpOtpProvider;
use InvalidArgumentException;
use RuntimeException;

final class OtpProviderManager
{
    public function resolve(): OtpProvider
    {
        return match ((string) config('notifications.otp.driver', 'null')) {
            'melipayamak' => $this->resolveMeliPayamak(),
            'null' => app(NullOtpProvider::class),
            default => throw new InvalidArgumentException('Unsupported OTP driver.'),
        };
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
