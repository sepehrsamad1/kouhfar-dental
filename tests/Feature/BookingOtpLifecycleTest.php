<?php

declare(strict_types=1);

use App\BookingOtpChallenge;
use App\Services\BookingOtpRateLimitException;
use App\Services\BookingOtpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'notifications.otp.driver' => 'melipayamak',
        'notifications.otp.melipayamak.token' => str_repeat('a', 32),
    ]);
    Carbon::setTestNow('2026-11-04T12:00:00Z');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('stores only keyed hashes, verifies once, and consumes the challenge once', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);
    $challengeId = $otp->issue('09120000001', '192.0.2.10');
    $challenge = BookingOtpChallenge::query()->findOrFail($challengeId);

    expect($challenge->status)->toBe('pending')
        ->and($challenge->code_hash)->toBe(hash_hmac('sha256', 'code|'.$challengeId.'|123456', (string) config('app.key')))
        ->and($challenge->code_hash)->not->toBe('123456')
        ->and($challenge->phone_hash)->not->toBe('09120000001')
        ->and($challenge->ip_hash)->not->toBe('192.0.2.10')
        ->and($challenge->expires_at->toIso8601String())->toBe('2026-11-04T12:05:00+00:00');

    expect($otp->verify($challengeId, '123456'))->toBeTrue()
        ->and($otp->verify($challengeId, '123456'))->toBeFalse();

    expect(fn () => DB::transaction(fn () => $otp->consume($challengeId, '09120000002')))
        ->toThrow(RuntimeException::class, 'Phone verification is required.');

    DB::transaction(fn () => $otp->consume($challengeId, '09120000001'));
    expect($challenge->fresh()->status)->toBe('consumed')
        ->and($challenge->fresh()->consumed_at)->not->toBeNull();

    expect(fn () => DB::transaction(fn () => $otp->consume($challengeId, '09120000001')))
        ->toThrow(RuntimeException::class, 'Phone verification is required.');
});

it('exhausts a challenge after five incorrect codes', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);
    $challengeId = $otp->issue('09120000001', '192.0.2.10');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        expect($otp->verify($challengeId, '000000'))->toBeFalse();
    }

    expect(BookingOtpChallenge::query()->findOrFail($challengeId)->status)->toBe('exhausted')
        ->and(BookingOtpChallenge::query()->findOrFail($challengeId)->attempts)->toBe(5)
        ->and($otp->verify($challengeId, '123456'))->toBeFalse();
});

it('expires a challenge after five minutes', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);
    $challengeId = $otp->issue('09120000001', '192.0.2.10');

    Carbon::setTestNow('2026-11-04T12:05:00Z');

    expect($otp->verify($challengeId, '123456'))->toBeFalse()
        ->and(BookingOtpChallenge::query()->findOrFail($challengeId)->status)->toBe('expired');
});

it('invalidates the previous challenge when a new code is issued', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);
    $first = $otp->issue('09120000001', '192.0.2.10');

    Carbon::setTestNow('2026-11-04T12:01:01Z');
    $second = $otp->issue('09120000001', '192.0.2.10');

    expect(BookingOtpChallenge::query()->findOrFail($first)->status)->toBe('invalidated')
        ->and(BookingOtpChallenge::query()->findOrFail($second)->status)->toBe('pending')
        ->and($otp->verify($first, '123456'))->toBeFalse();
});

it('enforces resend cooldown and per-phone hourly issuance limits', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);
    $otp->issue('09120000001', '192.0.2.10');

    expect(fn () => $otp->issue('09120000001', '192.0.2.10'))
        ->toThrow(BookingOtpRateLimitException::class);

    Carbon::setTestNow('2026-11-04T12:01:01Z');
    $otp->issue('09120000001', '192.0.2.10');
    Carbon::setTestNow('2026-11-04T12:02:02Z');
    $otp->issue('09120000001', '192.0.2.10');
    Carbon::setTestNow('2026-11-04T12:03:03Z');

    expect(fn () => $otp->issue('09120000001', '192.0.2.10'))
        ->toThrow(BookingOtpRateLimitException::class);
});

it('enforces the five-send hourly phone limit independently of the fifteen-minute limit', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);

    foreach (['12:00:00', '12:13:00', '12:26:00', '12:39:00', '12:52:00'] as $time) {
        Carbon::setTestNow('2026-11-04T'.$time.'Z');
        $otp->issue('09120000001', '192.0.2.15');
    }

    Carbon::setTestNow('2026-11-04T13:00:00Z');

    expect(fn () => $otp->issue('09120000001', '192.0.2.15'))
        ->toThrow(BookingOtpRateLimitException::class);
});

it('enforces the ten-send hourly IP limit across different phone numbers', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $otp = app(BookingOtpService::class);

    for ($index = 1; $index <= 10; $index++) {
        $otp->issue(sprintf('091200000%02d', $index), '192.0.2.20');
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
    }

    expect(fn () => $otp->issue('09120000099', '192.0.2.20'))
        ->toThrow(BookingOtpRateLimitException::class);
});

it('records provider failure without persisting a code or exposing provider details', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['message' => 'synthetic failure'], 500)]);
    $otp = app(BookingOtpService::class);

    expect(fn () => $otp->issue('09120000001', '192.0.2.30'))
        ->toThrow(RuntimeException::class, 'OTP delivery is unavailable.');

    $challenge = BookingOtpChallenge::query()->firstOrFail();
    expect($challenge->status)->toBe('delivery_failed')
        ->and($challenge->code_hash)->toBeNull();
    Http::assertSentCount(1);
});

it('serves issue and verify endpoints without returning the OTP value', function (): void {
    Http::fake(['console.melipayamak.com/*' => Http::response(['code' => '123456'], 200)]);
    $issue = $this->postJson('/api/v1/booking-otp/issue', ['mobile' => '09120000001'])
        ->assertAccepted()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('expires_in', 300)
        ->assertJsonMissingPath('code');

    $this->postJson('/api/v1/booking-otp/verify', [
        'challenge_id' => $issue->json('challenge_id'),
        'code' => '123456',
    ])->assertOk()->assertJsonPath('verified', true)->assertJsonMissingPath('code');
});
