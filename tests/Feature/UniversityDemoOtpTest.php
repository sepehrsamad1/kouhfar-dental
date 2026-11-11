<?php

declare(strict_types=1);

use App\Notifications\Otp\DemoOtpProvider;
use App\Services\OtpProviderManager;

it('returns the configured synthetic university OTP without sending anything externally', function (): void {
    config()->set('notifications.otp.university_demo_mode', true);
    config()->set('notifications.otp.demo_code', '123456');

    expect((new DemoOtpProvider)->sendCode('09123456789'))->toBe('123456');
});

it('resolves the demo provider only when university demo mode is enabled', function (): void {
    config()->set('notifications.otp.driver', 'demo');
    config()->set('notifications.otp.university_demo_mode', true);

    expect(app(OtpProviderManager::class)->resolve())->toBeInstanceOf(DemoOtpProvider::class);
});

it('rejects university demo OTP in production', function (): void {
    config()->set('notifications.otp.driver', 'demo');
    config()->set('notifications.otp.university_demo_mode', true);
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(OtpProviderManager::class)->resolve())
        ->toThrow(RuntimeException::class, 'University demo OTP is not enabled.');
});

it('rejects malformed demo codes', function (): void {
    config()->set('notifications.otp.university_demo_mode', true);
    config()->set('notifications.otp.demo_code', 'not-a-code');

    expect(fn () => (new DemoOtpProvider)->sendCode('09123456789'))
        ->toThrow(RuntimeException::class, 'University demo OTP is not configured.');
});
