<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Session as StaffSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffSessionIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('staff');

        if (! $guard->check()) {
            return $next($request);
        }

        $registryId = $request->session()->get('staff_registry_session_id');
        $registry = $registryId === null ? null : StaffSession::query()->find($registryId);

        if ($registry === null || $registry->revoked_at !== null) {
            return $this->expire($request);
        }

        $now = now('UTC');
        $authenticatedAt = (int) $request->session()->get('staff_authenticated_at', $now->timestamp);
        $lastActivityAt = (int) $request->session()->get('staff_last_activity_at', $now->timestamp);
        $idleMinutes = config('staff_auth.idle_timeout_minutes');
        $absoluteMinutes = config('staff_auth.absolute_timeout_minutes');

        if (($idleMinutes !== null && $now->timestamp > $lastActivityAt + ((int) $idleMinutes * 60))
            || ($absoluteMinutes !== null && $now->timestamp > $authenticatedAt + ((int) $absoluteMinutes * 60))
            || $registry->expires_at->isPast()) {
            return $this->expire($request, $registry);
        }

        $request->session()->put('staff_last_activity_at', $now->timestamp);
        $registry->forceFill(['last_seen_at' => $now])->save();

        return $next($request);
    }

    private function expire(Request $request, ?StaffSession $registry = null): Response
    {
        if ($registry !== null) {
            $registry->forceFill(['revoked_at' => now('UTC')])->save();
        }

        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login')->withErrors(['email' => 'نشست شما منقضی شده است. دوباره وارد شوید.']);
    }
}
