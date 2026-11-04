<?php

declare(strict_types=1);

namespace App\Services;

use App\BookingOtpChallenge;
use App\Notifications\Otp\OtpProvider;
use App\Support\IranianPhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class BookingOtpService
{
    private const TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const PHONE_15_MINUTE_LIMIT = 3;
    private const PHONE_HOURLY_LIMIT = 5;
    private const IP_HOURLY_LIMIT = 10;

    public function __construct(private readonly OtpProviderManager $providers)
    {
    }

    public function issue(string $phone, string $ip): string
    {
        $normalized = IranianPhoneNumber::normalize($phone);
        $phoneHash = $this->digest('phone', $normalized);
        $ipHash = $this->digest('ip', $ip);
        $challengeId = (string) \Illuminate\Support\Str::uuid();

        DB::transaction(function () use ($phoneHash, $ipHash, $challengeId): void {
            DB::selectOne('select pg_advisory_xact_lock(hashtextextended(?, 0))', ['booking-otp:'.$phoneHash]);
            $now = CarbonImmutable::now('UTC');

            $last = BookingOtpChallenge::query()
                ->where('phone_hash', $phoneHash)
                ->latest('created_at')
                ->first();

            if ($last !== null && $last->created_at->diffInSeconds($now) < self::RESEND_COOLDOWN_SECONDS) {
                throw new RuntimeException('OTP rate limit exceeded.');
            }

            $phone15 = BookingOtpChallenge::query()->where('phone_hash', $phoneHash)
                ->where('created_at', '>=', $now->subMinutes(15))->count();
            $phoneHour = BookingOtpChallenge::query()->where('phone_hash', $phoneHash)
                ->where('created_at', '>=', $now->subHour())->count();
            $ipHour = BookingOtpChallenge::query()->where('ip_hash', $ipHash)
                ->where('created_at', '>=', $now->subHour())->count();

            if ($phone15 >= self::PHONE_15_MINUTE_LIMIT
                || $phoneHour >= self::PHONE_HOURLY_LIMIT
                || $ipHour >= self::IP_HOURLY_LIMIT) {
                throw new RuntimeException('OTP rate limit exceeded.');
            }

            BookingOtpChallenge::query()
                ->where('phone_hash', $phoneHash)
                ->whereIn('status', ['dispatching', 'pending', 'verified'])
                ->update(['status' => 'invalidated', 'updated_at' => $now]);

            BookingOtpChallenge::query()->create([
                'id' => $challengeId,
                'phone_hash' => $phoneHash,
                'ip_hash' => $ipHash,
                'status' => 'dispatching',
                'attempts' => 0,
                'expires_at' => $now->addMinutes(self::TTL_MINUTES),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, 3);

        try {
            $code = $this->providers->resolve()->sendCode($normalized);
            if (preg_match('/^[0-9]{4,12}$/D', $code) !== 1) {
                throw new RuntimeException('OTP provider response is invalid.');
            }

            $updated = BookingOtpChallenge::query()
                ->whereKey($challengeId)
                ->where('status', 'dispatching')
                ->where('expires_at', '>', CarbonImmutable::now('UTC'))
                ->update([
                    'code_hash' => $this->digest('code', $challengeId.'|'.$code),
                    'status' => 'pending',
                    'last_sent_at' => CarbonImmutable::now('UTC'),
                    'updated_at' => CarbonImmutable::now('UTC'),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException('OTP challenge is no longer active.');
            }

            return $challengeId;
        } catch (Throwable) {
            BookingOtpChallenge::query()->whereKey($challengeId)
                ->where('status', 'dispatching')
                ->update(['status' => 'delivery_failed', 'updated_at' => CarbonImmutable::now('UTC')]);

            // Do not chain provider exceptions; they may contain the token-bearing URL.
            throw new RuntimeException('OTP delivery is unavailable.');
        }
    }

    public function verify(string $challengeId, string $code): bool
    {
        return DB::transaction(function () use ($challengeId, $code): bool {
            $challenge = BookingOtpChallenge::query()->whereKey($challengeId)->lockForUpdate()->first();

            if ($challenge === null || $challenge->status !== 'pending') {
                return false;
            }

            $now = CarbonImmutable::now('UTC');
            if ($challenge->expires_at->lessThanOrEqualTo($now)) {
                $challenge->forceFill(['status' => 'expired'])->save();

                return false;
            }

            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                $challenge->forceFill(['status' => 'exhausted'])->save();

                return false;
            }

            $expected = $this->digest('code', $challengeId.'|'.$code);
            if (! is_string($challenge->code_hash) || ! hash_equals($challenge->code_hash, $expected)) {
                $attempts = $challenge->attempts + 1;
                $challenge->forceFill([
                    'attempts' => $attempts,
                    'status' => $attempts >= self::MAX_ATTEMPTS ? 'exhausted' : 'pending',
                ])->save();

                return false;
            }

            $challenge->forceFill(['status' => 'verified', 'verified_at' => $now])->save();

            return true;
        }, 3);
    }

    public function consume(string $challengeId, string $phone): void
    {
        $normalized = IranianPhoneNumber::normalize($phone);
        $challenge = BookingOtpChallenge::query()->whereKey($challengeId)->lockForUpdate()->first();

        if ($challenge === null
            || $challenge->status !== 'verified'
            || $challenge->consumed_at !== null
            || $challenge->expires_at->lessThanOrEqualTo(CarbonImmutable::now('UTC'))
            || ! hash_equals($challenge->phone_hash, $this->digest('phone', $normalized))) {
            throw new RuntimeException('Phone verification is required.');
        }

        $challenge->forceFill([
            'status' => 'consumed',
            'consumed_at' => CarbonImmutable::now('UTC'),
        ])->save();
    }

    private function digest(string $purpose, string $value): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('Application key is not configured.');
        }

        return hash_hmac('sha256', $purpose.'|'.$value, $key);
    }
}
