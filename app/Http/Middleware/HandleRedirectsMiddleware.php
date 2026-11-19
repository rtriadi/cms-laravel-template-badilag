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
        if ($request->isMethod('GET')) {
            $path = '/'.ltrim($request->path(), '/');

            if ($path !== '/') {
                $map = Redirect::cachedMap();
                $matched = $map[$path] ?? null;

                if ($matched !== null) {
                    Redirect::query()->whereKey($matched['id'])->increment('hits');

                    return redirect()->to($matched['to_path'], $matched['status_code']);
                }
            }
        }

        return $next($request);
    }
}
