<?php

declare(strict_types=1);

namespace App\Services;

use App\BookingOtpChallenge;
use App\Support\IranianPhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class BookingOtpService
{
    public function __construct(private readonly OtpProviderManager $providers)
    {
    }

    public function issue(string $phone, string $ip): string
    {
        $normalized = IranianPhoneNumber::normalize($phone);
        $phoneHash = $this->digest('phone', $normalized);
        $ipHash = $this->digest('ip', $ip);
        $challengeId = (string) Str::uuid();

        DB::transaction(function () use ($phoneHash, $ipHash, $challengeId): void {
            // PostgreSQL advisory lock makes phone-specific limits atomic across app nodes.
            DB::selectOne('select pg_advisory_xact_lock(hashtextextended(?, 0))', ['booking-otp:'.$phoneHash]);
            $now = CarbonImmutable::now('UTC');
            $cooldown = (int) config('booking.otp.resend_cooldown_seconds', 60);

            $last = BookingOtpChallenge::query()
                ->where('phone_hash', $phoneHash)
                ->latest('created_at')
                ->first();

            if ($last !== null && $last->created_at->greaterThan($now->subSeconds($cooldown))) {
                throw new BookingOtpRateLimitException;
            }

            $phone15 = BookingOtpChallenge::query()->where('phone_hash', $phoneHash)
                ->where('created_at', '>=', $now->subMinutes(15))->count();
            $phoneHour = BookingOtpChallenge::query()->where('phone_hash', $phoneHash)
                ->where('created_at', '>=', $now->subHour())->count();
            $ipHour = BookingOtpChallenge::query()->where('ip_hash', $ipHash)
                ->where('created_at', '>=', $now->subHour())->count();

            if ($phone15 >= (int) config('booking.otp.phone_send_limit_15_minutes', 3)
                || $phoneHour >= (int) config('booking.otp.phone_send_limit_1_hour', 5)
                || $ipHour >= (int) config('booking.otp.ip_send_limit_1_hour', 10)) {
                throw new BookingOtpRateLimitException;
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
                'expires_at' => $now->addMinutes((int) config('booking.otp.ttl_minutes', 5)),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, 3);

        try {
            $code = $this->providers->resolve()->sendCode($normalized);
            if (preg_match('/^[0-9]{4,12}$/D', $code) !== 1) {
                throw new RuntimeException('OTP provider response is invalid.');
            }

            $now = CarbonImmutable::now('UTC');
            $updated = BookingOtpChallenge::query()
                ->whereKey($challengeId)
                ->where('status', 'dispatching')
                ->where('expires_at', '>', $now)
                ->update([
                    'code_hash' => $this->digest('code', $challengeId.'|'.$code),
                    'status' => 'pending',
                    'last_sent_at' => $now,
                    'updated_at' => $now,
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

            $maxAttempts = (int) config('booking.otp.max_attempts', 5);
            if ($challenge->attempts >= $maxAttempts) {
                $challenge->forceFill(['status' => 'exhausted'])->save();

                return false;
            }

            $expected = $this->digest('code', $challengeId.'|'.$code);
            if (! is_string($challenge->code_hash) || ! hash_equals($challenge->code_hash, $expected)) {
                $attempts = $challenge->attempts + 1;
                $challenge->forceFill([
                    'attempts' => $attempts,
                    'status' => $attempts >= $maxAttempts ? 'exhausted' : 'pending',
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
