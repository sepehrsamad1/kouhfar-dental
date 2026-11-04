<?php

declare(strict_types=1);

namespace App\Services\Otp;

use App\VerificationChallenge;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class PhoneVerificationService
{
    public const PUBLIC_BOOKING = 'public_booking';

    public const STAFF_LOGIN = 'staff_login';

    private const TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    private const RESEND_SECONDS = 60;

    private readonly MeliPayamakOtpProvider $provider;

    public function __construct(MeliPayamakOtpProvider $provider)
    {
        $this->provider = $provider;
    }

    public function start(string $mobile, string $purpose, string $intentHash): VerificationChallenge
    {
        if (in_array($purpose, [self::PUBLIC_BOOKING, self::STAFF_LOGIN], true) === false) {
            throw new PhoneVerificationException('Unsupported verification purpose.');
        }

        $key = 'otp-send:'.hash('sha256', $purpose.'|'.$mobile);
        $code = RateLimiter::attempt(
            $key,
            1,
            fn (): string => $this->provider->send($mobile),
            self::RESEND_SECONDS,
        );

        if ($code === false) {
            throw new PhoneVerificationException('OTP resend cooldown is active.');
        }

        return DB::transaction(function () use ($mobile, $purpose, $intentHash, $code): VerificationChallenge {
            VerificationChallenge::query()
                ->where('mobile_normalized', $mobile)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['expires_at' => now('UTC')]);

            $challenge = VerificationChallenge::query()->create([
                'mobile_normalized' => $mobile,
                'purpose' => $purpose,
                'intent_hash' => $intentHash,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now('UTC')->addMinutes(self::TTL_MINUTES),
            ]);

            return $challenge;
        });
    }

    public function verify(string $challengeId, string $code, string $purpose): string
    {
        $grant = DB::transaction(function () use ($challengeId, $code, $purpose): ?string {
            $challenge = VerificationChallenge::query()->whereKey($challengeId)->lockForUpdate()->first();

            if ($challenge === null || $challenge->purpose !== $purpose
                || $challenge->verified_at !== null || $challenge->consumed_at !== null
                || CarbonImmutable::parse((string) $challenge->expires_at, 'UTC')->isPast() || $challenge->attempts >= self::MAX_ATTEMPTS) {
                return null;
            }

            if (Hash::check($code, $challenge->code_hash) === false) {
                $challenge->increment('attempts');

                return null;
            }

            $plainGrant = Str::random(64);
            $challenge->forceFill([
                'verified_at' => now('UTC'),
                'grant_hash' => hash('sha256', $plainGrant),
            ])->save();

            return $plainGrant;
        });

        if ($grant === null) {
            throw new PhoneVerificationException('Verification code is invalid or expired.');
        }

        return $grant;
    }

    /**
     * Must be called inside the appointment-creation database transaction.
     */
    public function consumeGrant(
        string $grant,
        string $mobile,
        string $purpose,
        string $intentHash,
        string $idempotencyKey,
    ): void {
        $challenge = VerificationChallenge::query()
            ->where('grant_hash', hash('sha256', $grant))
            ->lockForUpdate()
            ->first();

        if ($challenge === null || $challenge->purpose !== $purpose
            || $challenge->mobile_normalized !== $mobile
            || $challenge->verified_at === null || CarbonImmutable::parse((string) $challenge->expires_at, 'UTC')->isPast()
            || hash_equals($challenge->intent_hash, $intentHash) === false
            || $challenge->consumed_at !== null) {
            throw new PhoneVerificationException('A valid phone verification grant is required.');
        }

        $challenge->forceFill([
            'consumed_at' => now('UTC'),
            'consumed_key_hash' => hash('sha256', $idempotencyKey),
        ])->save();
    }
}
