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
                $redirect = Redirect::query()->where('from_path', $path)->first();

                if ($redirect !== null) {
                    $redirect->increment('hits');

                    return redirect()->to($redirect->to_path, $redirect->status_code);
                }
            }
        }

        return $next($request);
    }
}
