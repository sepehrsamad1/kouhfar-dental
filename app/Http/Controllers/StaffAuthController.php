<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StaffLoginRequest;
use App\LoginAttempt;
use App\Session as StaffSession;
use App\Services\TotpService;
use App\StaffUser;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffAuthController
{
    public function create(): View
    {
        return view('staff.auth.login');
    }

    public function store(StaffLoginRequest $request, TotpService $totp): RedirectResponse|Response
    {
        $email = mb_strtolower(trim($request->string('email')->toString()));
        $throttleKey = hash('sha256', $email.'|'.$request->ip());
        $maxAttempts = (int) config('staff_auth.login_throttle_attempts');
        $decaySeconds = (int) config('staff_auth.login_throttle_seconds');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->recordAttempt($email, $request, false);

            return back()->withErrors(['email' => 'اطلاعات ورود صحیح نیست.'])->onlyInput('email');
        }

        $staff = StaffUser::query()->where('email', $email)->where('is_active', true)->first();

        if ($staff === null || ! is_string($staff->getAuthPassword())
            || ! Hash::check($request->string('password')->toString(), $staff->getAuthPassword())) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            $this->recordAttempt($email, $request, false);

            return back()->withErrors(['email' => 'اطلاعات ورود صحیح نیست.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        if (! config('staff_auth.mfa_required', true)) {
            return $this->completeLogin($request, $staff);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'staff_mfa_pending_user_id' => $staff->getKey(),
            'staff_mfa_pending_at' => now('UTC')->timestamp,
        ]);

        if ($staff->mfa_enabled_at === null) {
            if (! is_string($staff->mfa_totp_secret) || $staff->mfa_totp_secret === '') {
                $staff->forceFill(['mfa_totp_secret' => $totp->generateSecret()])->save();
            }

            return redirect()->route('staff.mfa.setup');
        }

        return redirect()->route('staff.mfa.challenge');
    }

    public function setup(Request $request, TotpService $totp): View|RedirectResponse
    {
        $staff = $this->pendingStaff($request);

        if ($staff === null) {
            return redirect()->route('staff.login');
        }

        if ($staff->mfa_enabled_at !== null) {
            return redirect()->route('staff.mfa.challenge');
        }

        if (! is_string($staff->mfa_totp_secret) || $staff->mfa_totp_secret === '') {
            $staff->forceFill(['mfa_totp_secret' => $totp->generateSecret()])->save();
        }

        return view('staff.auth.mfa-setup', [
            'secret' => $staff->mfa_totp_secret,
            'provisioningUri' => $totp->provisioningUri(
                $staff->mfa_totp_secret,
                $staff->email,
                (string) config('app.name'),
            ),
        ]);
    }

    public function completeSetup(Request $request, TotpService $totp): RedirectResponse|Response
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $staff = $this->pendingStaff($request);

        if ($staff === null) {
            return redirect()->route('staff.login');
        }

        $throttleKey = $this->mfaThrottleKey($staff);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['code' => 'کد واردشده معتبر نیست.']);
        }

        /** @var array{staff: StaffUser, recovery_codes: list<string>}|null $enrollment */
        $enrollment = DB::transaction(function () use ($staff, $request, $totp): ?array {
            $lockedStaff = StaffUser::query()->whereKey($staff->getKey())->lockForUpdate()->first();

            if ($lockedStaff === null || $lockedStaff->mfa_enabled_at !== null
                || ! is_string($lockedStaff->mfa_totp_secret)) {
                return null;
            }

            $counter = $totp->matchingCounter($lockedStaff->mfa_totp_secret, (string) $request->input('code'));

            if ($counter === null || ($lockedStaff->mfa_last_totp_counter !== null
                && $counter <= $lockedStaff->mfa_last_totp_counter)) {
                return null;
            }

            $recoveryCodes = [];
            $recoveryHashes = [];

            for ($index = 0; $index < 10; $index++) {
                $code = Str::upper(Str::random(12));
                $recoveryCodes[] = $code;
                $recoveryHashes[] = Hash::make($code);
            }

            AuditLogger::record($lockedStaff, 'staff.mfa.enabled', $lockedStaff, null, ['reason_code' => 'totp_enrolled']);

            $lockedStaff->forceFill([
                'mfa_enabled_at' => now('UTC'),
                'mfa_last_totp_counter' => $counter,
                'mfa_recovery_codes' => $recoveryHashes,
            ])->save();

            return ['staff' => $lockedStaff, 'recovery_codes' => $recoveryCodes];
        });

        if ($enrollment === null) {
            RateLimiter::hit($throttleKey, 300);

            return back()->withErrors(['code' => 'کد واردشده معتبر نیست.']);
        }

        RateLimiter::clear($throttleKey);

        return $this->completeLogin($request, $enrollment['staff'], $enrollment['recovery_codes']);
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $staff = $this->pendingStaff($request);

        if ($staff === null) {
            return redirect()->route('staff.login');
        }

        if ($staff->mfa_enabled_at === null) {
            return redirect()->route('staff.mfa.setup');
        }

        return view('staff.auth.mfa-challenge');
    }

    public function verify(Request $request, TotpService $totp): RedirectResponse|Response
    {
        $request->validate(['code' => ['required', 'string', 'min:6', 'max:64']]);
        $staff = $this->pendingStaff($request);

        if ($staff === null) {
            return redirect()->route('staff.login');
        }

        if ($staff->mfa_enabled_at === null || ! is_string($staff->mfa_totp_secret)) {
            return redirect()->route('staff.mfa.setup');
        }

        $throttleKey = $this->mfaThrottleKey($staff);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['code' => 'کد واردشده معتبر نیست.']);
        }

        $code = trim((string) $request->input('code'));
        /** @var array{verified: bool, staff: StaffUser} $verification */
        $verification = DB::transaction(function () use ($staff, $code, $totp): array {
            $lockedStaff = StaffUser::query()->whereKey($staff->getKey())->lockForUpdate()->first();

            if ($lockedStaff === null || $lockedStaff->mfa_enabled_at === null
                || ! is_string($lockedStaff->mfa_totp_secret)) {
                return ['verified' => false, 'staff' => $staff];
            }

            $counter = preg_match('/^[0-9]{6}$/D', $code) === 1
                ? $totp->matchingCounter($lockedStaff->mfa_totp_secret, $code)
                : null;
            $verified = $counter !== null
                && ($lockedStaff->mfa_last_totp_counter === null || $counter > $lockedStaff->mfa_last_totp_counter);

            if (! $verified && preg_match('/^[A-Za-z0-9]{12}$/D', $code) === 1) {
                $normalized = Str::upper($code);
                $recoveryHashes = (array) $lockedStaff->mfa_recovery_codes;
                $matchedIndex = null;

                foreach ($recoveryHashes as $index => $hash) {
                    if (is_string($hash) && Hash::check($normalized, $hash)) {
                        $matchedIndex = $index;
                    }
                }

                if ($matchedIndex !== null) {
                    unset($recoveryHashes[$matchedIndex]);
                    $lockedStaff->forceFill(['mfa_recovery_codes' => array_values($recoveryHashes)])->save();
                    AuditLogger::record($lockedStaff, 'staff.mfa.recovery_code_used', $lockedStaff, null, ['reason_code' => 'one_time_recovery_code']);
                    $verified = true;
                }
            }

            if ($verified && $counter !== null) {
                $lockedStaff->forceFill(['mfa_last_totp_counter' => $counter])->save();
            }

            return ['verified' => $verified, 'staff' => $lockedStaff];
        });

        if (! $verification['verified']) {
            RateLimiter::hit($throttleKey, 300);

            return back()->withErrors(['code' => 'کد واردشده معتبر نیست.']);
        }

        RateLimiter::clear($throttleKey);

        return $this->completeLogin($request, $verification['staff']);
    }

    public function recoveryCodes(): RedirectResponse
    {
        return redirect()->route('staff.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $registryId = $request->session()->pull('staff_registry_session_id');

        if ($registryId !== null) {
            StaffSession::query()->whereKey($registryId)->update(['revoked_at' => now('UTC')]);
        }

        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    private function mfaThrottleKey(StaffUser $staff): string
    {
        return 'staff-mfa:'.hash('sha256', (string) $staff->getKey());
    }

    private function pendingStaff(Request $request): ?StaffUser
    {
        $userId = $request->session()->get('staff_mfa_pending_user_id');
        $startedAt = (int) $request->session()->get('staff_mfa_pending_at', 0);
        $timeout = max(1, (int) config('staff_auth.mfa_pending_timeout_minutes', 10));

        if (! is_string($userId) || $startedAt === 0
            || now('UTC')->timestamp - $startedAt > $timeout * 60) {
            $request->session()->forget(['staff_mfa_pending_user_id', 'staff_mfa_pending_at']);

            return null;
        }

        $staff = StaffUser::query()->whereKey($userId)->where('is_active', true)->first();

        if ($staff === null) {
            $request->session()->forget(['staff_mfa_pending_user_id', 'staff_mfa_pending_at']);
        }

        return $staff;
    }

    private function completeLogin(Request $request, StaffUser $staff, array $recoveryCodes = []): RedirectResponse|Response
    {
        $request->session()->forget(['staff_mfa_pending_user_id', 'staff_mfa_pending_at']);
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

        $this->recordAttempt($staff->email, $request, true);

        if ($recoveryCodes !== []) {
            return response()->view('staff.auth.mfa-recovery-codes', ['codes' => $recoveryCodes])
                ->header('Cache-Control', 'no-store, private')
                ->header('Pragma', 'no-cache');
        }

        return redirect()->to($this->safeIntendedUrl());
    }

    private function safeIntendedUrl(): string
    {
        $intended = session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return route('staff.dashboard');
        }

        $parts = parse_url($intended);

        if (
            $parts === false
            || isset($parts['scheme'])
            || isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || ! str_starts_with($intended, '/')
            || str_starts_with($intended, '//')
            || str_contains($intended, '\\')
            || preg_match('/[\\x00-\\x1F\\x7F]/', $intended) === 1
        ) {
            return route('staff.dashboard');
        }

        return url($intended);
    }

    private function recordAttempt(string $email, Request $request, bool $success): void
    {
        LoginAttempt::query()->create([
            'identifier_hash' => hash('sha256', mb_strtolower(trim($email))),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'created_at' => now('UTC'),
            'success' => $success,
        ]);
    }
}
