<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPageVisitMiddleware
{
    /**
     * Increment cookie-free daily visit counter for successful public GET responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $this->shouldSkip($request)) {
            return $response;
        }

        try {
            $today = now()->toDateString();
            $cleanPath = substr('/'.ltrim($request->path(), '/'), 0, 255);

            $updated = PageVisit::query()
                ->where('visit_date', $today)
                ->where('path', $cleanPath)
                ->increment('visits_count');

            if ($updated === 0) {
                $visit = PageVisit::query()->firstOrCreate(
                    [
                        'visit_date' => $today,
                        'path' => $cleanPath,
                    ],
                    [
                        'visits_count' => 0,
                    ],
                );
                $visit->increment('visits_count');
            }
        } catch (Throwable) {
            // Never block public portal rendering if visit tracking fails
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');

        return $request->is(
            $adminPath,
            $adminPath.'/*',
            'setup',
            'setup/*',
            'login',
            'two-factor-challenge',
            'livewire/*',
            'livewire-*/*',
            'flux/*',
            'captcha-image',
            'up',
            'sitemap.xml',
            'build/*',
            'storage/*',
        );
    }
}
