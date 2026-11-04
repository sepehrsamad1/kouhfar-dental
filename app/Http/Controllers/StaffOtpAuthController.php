<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\LoginAttempt;
use App\Services\Otp\PhoneVerificationService;
use App\Session as StaffSession;
use App\StaffUser;
use App\Support\IranianPhoneNumber;
use App\VerificationChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class StaffOtpAuthController
{
    private const GENERIC_STATUS = 'اگر شماره متعلق به حساب فعال کارکنان باشد، کد ورود ارسال می‌شود.';

    public function send(Request $request, PhoneVerificationService $verification): RedirectResponse
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'max:32']]);

        try {
            $mobile = IranianPhoneNumber::normalize($data['mobile']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['mobile' => ['شماره موبایل معتبر نیست.']]);
        }

        $staff = StaffUser::query()
            ->where('mobile_normalized', $mobile)
            ->where('is_active', true)
            ->first();

        if ($staff === null) {
            // Keep the public response identical; no challenge or SMS is created for unknown numbers.
            $request->session()->put('staff_otp_challenge_id', (string) Str::uuid());
            $request->session()->put('staff_otp_mobile', $mobile);

            return back()->with('status', self::GENERIC_STATUS);
        }

        try {
            $challenge = $verification->start(
                $mobile,
                PhoneVerificationService::STAFF_LOGIN,
                hash('sha256', $staff->getKey().'|'.$mobile),
            );
        } catch (RuntimeException) {
            if ($request->session()->has('staff_otp_challenge_id') === false) {
                $request->session()->put('staff_otp_challenge_id', (string) Str::uuid());
                $request->session()->put('staff_otp_mobile', $mobile);
            }

            return back()->with('status', self::GENERIC_STATUS);
        }

        $request->session()->put('staff_otp_challenge_id', $challenge->getKey());
        $request->session()->put('staff_otp_mobile', $mobile);

        return back()->with('status', self::GENERIC_STATUS);
    }

    public function verify(Request $request, PhoneVerificationService $verification): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^\d{4,10}$/']]);
        $challengeId = $request->session()->get('staff_otp_challenge_id');

        try {
            if (is_string($challengeId) === false || $challengeId === '') {
                throw new RuntimeException('Invalid login challenge.');
            }

            $grant = $verification->verify(
                $challengeId,
                $data['code'],
                PhoneVerificationService::STAFF_LOGIN,
            );

            $staff = DB::transaction(function () use ($grant, $verification): StaffUser {
                $challenge = VerificationChallenge::query()
                    ->where('grant_hash', hash('sha256', $grant))
                    ->where('purpose', PhoneVerificationService::STAFF_LOGIN)
                    ->lockForUpdate()
                    ->first();

                if ($challenge === null) {
                    throw new RuntimeException('Invalid login challenge.');
                }

                $staff = StaffUser::query()
                    ->where('mobile_normalized', $challenge->mobile_normalized)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if ($staff === null) {
                    throw new RuntimeException('Invalid login challenge.');
                }

                $expectedIntent = hash('sha256', $staff->getKey().'|'.$challenge->mobile_normalized);
                if (hash_equals($challenge->intent_hash, $expectedIntent) === false) {
                    throw new RuntimeException('Invalid login challenge.');
                }

                $verification->consumeGrant(
                    $grant,
                    $challenge->mobile_normalized,
                    PhoneVerificationService::STAFF_LOGIN,
                    $expectedIntent,
                    Str::random(64),
                );

                return $staff;
            });

            $request->session()->forget(['staff_otp_challenge_id', 'staff_otp_mobile']);
            $this->authenticate($staff, $request);

            return redirect()->to($this->safeIntendedUrl());
        } catch (RuntimeException) {
            $this->recordAttempt((string) $request->session()->get('staff_otp_mobile', ''), $request, false);

            return back()->withErrors(['code' => 'کد ورود معتبر نیست یا اعتبار آن پایان یافته است.']);
        }
    }

    private function authenticate(StaffUser $staff, Request $request): void
    {
        Auth::guard('staff')->login($staff);
        $request->session()->regenerate();

        $now = now('UTC');
        $absoluteMinutes = config('staff_auth.absolute_timeout_minutes');
        $registry = StaffSession::query()->create([
            'user_id' => $staff->getKey(),
            'token_hash' => hash('sha256', Str::random(64)),
            'created_at' => $now,
            'expires_at' => $absoluteMinutes === null ? $now->copy()->addYears(10) : $now->copy()->addMinutes((int) $absoluteMinutes),
            'last_seen_at' => $now,
        ]);

        $request->session()->put('staff_registry_session_id', $registry->getKey());
        $request->session()->put('staff_authenticated_at', $now->timestamp);
        $request->session()->put('staff_last_activity_at', $now->timestamp);
        $this->recordAttempt((string) $staff->mobile_normalized, $request, true);
    }

    private function safeIntendedUrl(): string
    {
        $intended = session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return route('staff.dashboard');
        }

        $parts = parse_url($intended);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || str_starts_with($intended, '/') === false || str_starts_with($intended, '//')) {
            return route('staff.dashboard');
        }

        return url($intended);
    }

    private function recordAttempt(string $identifier, Request $request, bool $success): void
    {
        LoginAttempt::query()->create([
            'identifier_hash' => hash('sha256', $identifier),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'created_at' => now('UTC'),
            'success' => $success,
        ]);
    }
}
