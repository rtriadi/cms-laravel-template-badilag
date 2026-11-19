<?php

namespace App\Http\Middleware;

use App\Models\SatkerSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupCompletedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $setting = SatkerSetting::current();

        if ($setting === null || ! $setting->is_setup_completed) {
            return redirect()->route('setup');
        }

        return $next($request);
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is(
            'setup',
            'setup/*',
            'up',
            'captcha-image',
            'livewire/*',
            'livewire-*/*',
            'flux/*',
        );
    }
}
