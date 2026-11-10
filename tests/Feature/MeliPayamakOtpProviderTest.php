<?php

declare(strict_types=1);

use App\Notifications\Otp\MeliPayamakOtpProvider;
use App\Services\OtpProviderManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('notifications.otp.driver', 'melipayamak');
    config()->set('notifications.otp.melipayamak.token', str_repeat('a', 32));
    config()->set('notifications.otp.live_sms_approved', true);
    config()->set('notifications.otp.provider_url_path_risk_accepted', true);
});

it('posts only the normalized recipient to the fixed MeliPayamak OTP endpoint and returns provider code', function (): void {
    Http::fake(fn () => Http::response(['code' => '3741437414'], 200));

    $code = (new MeliPayamakOtpProvider)->sendCode('09123456789');

    expect($code)->toBe('3741437414');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://console.melipayamak.com/api/send/otp/'.str_repeat('a', 32)
        && $request->method() === 'POST'
        && $request->hasHeader('Content-Type', 'application/json')
        && $request->data() === ['to' => '09123456789']);
    Http::assertSentCount(1);
});

it('rejects an invalid recipient before making a request', function (): void {
    Http::fake();

    expect(fn () => (new MeliPayamakOtpProvider)->sendCode('+989123456789'))
        ->toThrow(RuntimeException::class, 'OTP recipient phone number is invalid.');

    Http::assertNothingSent();
});

it('fails closed when the provider token is missing or malformed', function (?string $token): void {
    config()->set('notifications.otp.melipayamak.token', $token);
    Http::fake();

    expect(fn () => (new MeliPayamakOtpProvider)->sendCode('09123456789'))
        ->toThrow(RuntimeException::class, 'MeliPayamak OTP is not configured.');

    Http::assertNothingSent();
})->with([null, '', 'https://attacker.invalid']);

it('rejects malformed provider responses without exposing provider details', function (): void {
    Http::fake(fn () => Http::response(['status' => 'synthetic provider error'], 200));

    try {
        (new MeliPayamakOtpProvider)->sendCode('09123456789');
        test()->fail('Expected provider failure.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('MeliPayamak OTP delivery failed.')
            ->and($exception->getMessage())->not->toContain(str_repeat('a', 32));
    }
});

it('does not follow provider redirects', function (): void {
    Http::fake(fn () => Http::response('', 302, ['Location' => 'https://example.invalid']));

    expect(fn () => (new MeliPayamakOtpProvider)->sendCode('09123456789'))
        ->toThrow(RuntimeException::class, 'MeliPayamak OTP delivery failed.');

    Http::assertSentCount(1);
});

it('rejects provider-declared errors even when a code is also present', function (): void {
    Http::fake(fn () => Http::response([
        'code' => '3741437410',
        'status' => 'synthetic provider error',
    ], 200));

    expect(fn () => (new MeliPayamakOtpProvider)->sendCode('09123456789'))
        ->toThrow(RuntimeException::class, 'MeliPayamak OTP delivery failed.');

    Http::assertSentCount(1);
});

it('fails closed when live SMS has not been explicitly approved', function (): void {
    config()->set('notifications.otp.live_sms_approved', false);

    expect(fn () => app(OtpProviderManager::class)->resolve())
        ->toThrow(RuntimeException::class, 'Live OTP SMS is not explicitly approved.');
});

it('requires explicit provider URL-path risk acceptance in production', function (): void {
    app()->detectEnvironment(fn () => 'production');
    config()->set('notifications.otp.live_sms_approved', true);
    config()->set('notifications.otp.provider_url_path_risk_accepted', false);

    expect(fn () => app(OtpProviderManager::class)->resolve())
        ->toThrow(RuntimeException::class, 'OTP provider URL-path credential risk is not approved.');
});
