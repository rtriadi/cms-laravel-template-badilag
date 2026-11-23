<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if ($request->hasSession()) {
            $expectedVersion = (int) ($user->session_version ?? 1);
            $sessionVersion = $request->session()->get('auth.session_version');
            $sessionPasswordHash = $request->session()->get('auth.password_hash');
            $currentPasswordHash = (string) $user->getAuthPassword();

            if (
                ($sessionVersion !== null && (int) $sessionVersion !== $expectedVersion)
                || (is_string($sessionPasswordHash) && $sessionPasswordHash !== '' && ! hash_equals($sessionPasswordHash, $currentPasswordHash))
            ) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            if ($sessionVersion === null) {
                $request->session()->put('auth.session_version', $expectedVersion);
            }

            if ($sessionPasswordHash === null && $currentPasswordHash !== '') {
                $request->session()->put('auth.password_hash', $currentPasswordHash);
            }
        }

        $adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');
        $isConfirmed = $user->two_factor_confirmed_at !== null;

        if ($request->is("{$adminPath}/user/two-factor-authentication") && $request->isMethod('DELETE')) {
            if (! $isConfirmed) {
                return redirect()->route('admin.two-factor.setup');
            }

            abort(403, 'Penonaktifan 2FA tidak diizinkan karena 2FA TOTP bersifat wajib.');
        }

        if ($isConfirmed) {
            if (
                $request->is(
                    "{$adminPath}/user/two-factor-authentication",
                    "{$adminPath}/user/two-factor-secret-key",
                    "{$adminPath}/user/two-factor-qr-code",
                    "{$adminPath}/user/two-factor-recovery-codes",
                )
            ) {
                abort(403, 'Pengelolaan 2FA hanya diizinkan melalui halaman Keamanan 2FA resmi.');
            }

            if ($request->hasSession() && Auth::guard('web')->viaRemember() && ! $request->session()->get('auth.two_factor_confirmed_session', false)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            return $next($request);
        }

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
