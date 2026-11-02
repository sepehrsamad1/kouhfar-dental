<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StaffLoginRequest;
use App\LoginAttempt;
use App\Session as StaffSession;
use App\Services\TotpService;
use App\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(StaffLoginRequest $request, TotpService $totp): RedirectResponse
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
        $passwordValid = $staff !== null
            && is_string($staff->getAuthPassword())
            && Hash::check($request->string('password')->toString(), $staff->getAuthPassword());

        if (! $passwordValid) {
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

    public function completeSetup(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $staff = $this->pendingStaff($request);

        if ($staff === null) {
            return redirect()->route('staff.login');
        }

        if ($staff->mfa_enabled_at !== null || ! is_string($staff->mfa_totp_secret)
            || ! $totp->verify($staff->mfa_totp_secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'کد واردشده معتبر نیست.']);
        }

        $recoveryCodes = [];
        $recoveryHashes = [];

        for ($index = 0; $index < 10; $index++) {
            $code = Str::upper(Str::random(12));
            $recoveryCodes[] = $code;
            $recoveryHashes[] = Hash::make($code);
        }

        $staff->forceFill([
            'mfa_enabled_at' => now('UTC'),
            'mfa_recovery_codes' => $recoveryHashes,
        ])->save();

        return $this->completeLogin($request, $staff, $recoveryCodes);
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

    public function verify(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'min:6', 'max:64']]);
        $staff = $this->pendingStaff($request);

        if ($staff === null) {
            return redirect()->route('staff.login');
        }

        if ($staff->mfa_enabled_at === null || ! is_string($staff->mfa_totp_secret)) {
            return redirect()->route('staff.mfa.setup');
        }

        $code = trim((string) $request->input('code'));
        $verified = preg_match('/^[0-9]{6}$/D', $code) === 1
            && $totp->verify($staff->mfa_totp_secret, $code);

        if (! $verified && preg_match('/^[A-Za-z0-9]{12}$/D', $code) === 1) {
            $normalized = Str::upper($code);
            $recoveryHashes = (array) $staff->mfa_recovery_codes;
            $matchedIndex = null;

            foreach ($recoveryHashes as $index => $hash) {
                if (is_string($hash) && Hash::check($normalized, $hash)) {
                    $matchedIndex = $index;
                }
            }

            if ($matchedIndex !== null) {
                unset($recoveryHashes[$matchedIndex]);
                $staff->forceFill(['mfa_recovery_codes' => array_values($recoveryHashes)])->save();
                $verified = true;
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'کد واردشده معتبر نیست.']);
        }

        return $this->completeLogin($request, $staff);
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->pull('staff_mfa_recovery_codes');

        if (! is_array($codes) || $codes === []) {
            return redirect()->route('staff.dashboard');
        }

        return view('staff.auth.mfa-recovery-codes', ['codes' => $codes]);
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

    private function completeLogin(Request $request, StaffUser $staff, array $recoveryCodes = []): RedirectResponse
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
            $request->session()->put('staff_mfa_recovery_codes', $recoveryCodes);

            return redirect()->route('staff.mfa.recovery-codes');
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
