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

        if ($request->is(
            "{$adminPath}/keamanan/2fa",
            "{$adminPath}/logout",
            "{$adminPath}/user/*",
        ) || $request->routeIs('admin.two-factor.setup', 'logout', 'password.confirm*')) {
            return $next($request);
        }

        return redirect()->route('admin.two-factor.setup');
    }
}
