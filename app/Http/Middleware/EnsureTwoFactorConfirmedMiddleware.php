<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorConfirmedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        $adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');

        if ($this->isAllowedWithoutTwoFactor($request, $adminPath)) {
            return $next($request);
        }

        return redirect()->route('admin.two-factor.setup');
    }

    private function isAllowedWithoutTwoFactor(Request $request, string $adminPath): bool
    {
        if ($request->routeIs('admin.two-factor.setup', 'logout', 'password.confirm*')) {
            return true;
        }

        if ($request->is(
            "{$adminPath}/keamanan/2fa",
            "{$adminPath}/logout",
            "{$adminPath}/user/confirm-password",
            "{$adminPath}/user/confirmed-password-status",
            "{$adminPath}/user/confirmed-two-factor-authentication",
            "{$adminPath}/user/two-factor-qr-code",
            "{$adminPath}/user/two-factor-secret-key",
            "{$adminPath}/user/two-factor-recovery-codes",
        )) {
            return true;
        }

        return $request->is("{$adminPath}/user/two-factor-authentication") && $request->isMethod('POST');
    }
}
