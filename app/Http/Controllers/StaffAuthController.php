<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StaffLoginRequest;
use App\LoginAttempt;
use App\Session as StaffSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffAuthController
{
    public function create(): View
    {
        return view('staff.auth.login');
    }

    public function store(StaffLoginRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim($request->string('email')->toString()));
        $throttleKey = hash('sha256', $email.'|'.$request->ip());
        $maxAttempts = (int) config('staff_auth.login_throttle_attempts');
        $decaySeconds = (int) config('staff_auth.login_throttle_seconds');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->recordAttempt($email, $request, false);
            return back()->withErrors(['email' => 'اطلاعات ورود صحیح نیست.'])->onlyInput('email');
        }

        $authenticated = Auth::guard('staff')->attempt([
            'email' => $email,
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ]);

        if (! $authenticated) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            $this->recordAttempt($email, $request, false);
            return back()->withErrors(['email' => 'اطلاعات ورود صحیح نیست.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $now = now('UTC');
        $absoluteMinutes = config('staff_auth.absolute_timeout_minutes');

        $registry = StaffSession::query()->create([
            'user_id' => Auth::guard('staff')->id(),
            'token_hash' => hash('sha256', Str::random(64)),
            'created_at' => $now,
            'expires_at' => $absoluteMinutes === null ? $now->copy()->addYears(10) : $now->copy()->addMinutes((int) $absoluteMinutes),
            'last_seen_at' => $now,
        ]);

        $request->session()->put('staff_registry_session_id', $registry->getKey());
        $request->session()->put('staff_authenticated_at', $now->timestamp);
        $request->session()->put('staff_last_activity_at', $now->timestamp);
        $this->recordAttempt($email, $request, true);

        return redirect()->to($this->safeIntendedUrl());
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

    private function safeIntendedUrl(): string
    {
        $intended = session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return route('staff.dashboard');
        }

        $parts = parse_url($intended);

        if ($parts === false || isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'])) {
            return route('staff.dashboard');
        }

        if (! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return route('staff.dashboard');
        }

        return url($intended);
    }

    private function recordAttempt(string $email, Request $request, bool $success): void
    {
        LoginAttempt::query()->create([
            'identifier_hash' => hash('sha256', $email),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'created_at' => now('UTC'),
            'success' => $success,
        ]);
    }
}
