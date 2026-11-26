<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPageVisitMiddleware
{
    /**
     * User-Agent fragments that identify automated crawlers, monitors, or headless audits.
     *
     * @var array<int, string>
     */
    private const array BOT_UA_SIGNATURES = [
        'googlebot',
        'bingbot',
        'yandex',
        'baiduspider',
        'duckduckbot',
        'slurp',
        'semrush',
        'ahrefs',
        'mj12bot',
        'dotbot',
        'petalbot',
        'facebookexternalhit',
        'twitterbot',
        'whatsapp',
        'telegrambot',
        'lighthouse',
        'headlesschrome',
        'pagespeed',
        'gtmetrix',
        'pingdom',
        'uptimerobot',
        'statuscake',
        'crawler',
        'spider',
        'curl/',
        'wget/',
        'python-requests',
        'go-http-client',
    ];

    /**
     * Increment cookie-free daily visit counter for successful public GET responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Increment cookie-free daily visit counter after sending HTTP response to client.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $this->shouldSkip($request)) {
            return;
        }

        try {
            $today = now()->toDateString();
            $now = now()->toDateTimeString();
            $cleanPath = substr('/'.ltrim($request->path(), '/'), 0, 255);

            $updated = DB::table('page_visits')
                ->where('visit_date', $today)
                ->where('path', $cleanPath)
                ->increment('visits_count', 1, ['updated_at' => $now]);

            if ($updated === 0) {
                $inserted = DB::table('page_visits')->insertOrIgnore([
                    'visit_date' => $today,
                    'path' => $cleanPath,
                    'visits_count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($inserted === 0) {
                    DB::table('page_visits')
                        ->where('visit_date', $today)
                        ->where('path', $cleanPath)
                        ->increment('visits_count', 1, ['updated_at' => $now]);
                }
            }
        } catch (Throwable) {
            // Never block public portal rendering if visit tracking fails
        }
    }

    private function shouldSkip(Request $request): bool
    {
        if ($this->isPrefetchRequest($request) || $this->isAutomatedBot($request)) {
            return true;
        }

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
            'captcha-text',
            'up',
            'sitemap.xml',
            'robots.txt',
            'build/*',
            'storage/*',
            '_system',
            '_system/*',
        );
    }

    private function isPrefetchRequest(Request $request): bool
    {
        foreach (['Sec-Purpose', 'Purpose', 'X-Purpose', 'X-Moz'] as $header) {
            $value = strtolower((string) $request->headers->get($header, ''));
            if ($value !== '' && (str_contains($value, 'prefetch') || str_contains($value, 'prerender'))) {
                return true;
            }
        }

        return false;
    }

    private function isAutomatedBot(Request $request): bool
    {
        $ua = strtolower((string) $request->userAgent());
        if ($ua === '') {
            return false;
        }

        foreach (self::BOT_UA_SIGNATURES as $signature) {
            if (str_contains($ua, $signature)) {
                return true;
            }
        }

        return false;
    }
}
