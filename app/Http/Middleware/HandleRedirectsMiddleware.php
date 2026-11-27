<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirectsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $this->isReservedPath($request)) {
            $path = '/'.ltrim($request->path(), '/');

            if ($path !== '/') {
                $matched = Redirect::findForPath($path);

                if ($matched !== null) {
                    $request->attributes->set('_matched_redirect_id', $matched['id']);

                    return redirect()->to($matched['to_path'], $matched['status_code']);
                }
            }
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $redirectId = $request->attributes->get('_matched_redirect_id');

        if (is_int($redirectId) && $redirectId > 0) {
            Redirect::query()->whereKey($redirectId)->increment('hits');
        }
    }

    private function isReservedPath(Request $request): bool
    {
        $adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');

        return $request->is(
            $adminPath,
            $adminPath.'/*',
            'setup',
            'setup/*',
            'livewire/*',
            'livewire-*/*',
            'flux/*',
            'up',
            'captcha-image',
            'captcha-text',
            'robots.txt',
            'sitemap.xml',
            'storage/*',
            '_system',
            '_system/*',
        );
    }
}
