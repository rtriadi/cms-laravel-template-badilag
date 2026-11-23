<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecordPostViewAction
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
     * Record a deduplicated publication view atomically without flushing homepage or sitemap caches.
     */
    public function execute(Post $post, Request $request): bool
    {
        if ($this->isPrefetchRequest($request) || $this->isAutomatedBot($request)) {
            return false;
        }

        $sessionFlag = 'viewed_post_'.$post->id;
        if ($request->hasSession() && $request->session()->has($sessionFlag)) {
            return false;
        }

        $cookieName = (string) config('session.cookie');
        $hasIncomingSessionCookie = $cookieName !== '' && $request->cookies->has($cookieName);
        $sessionId = ($hasIncomingSessionCookie && $request->hasSession())
            ? (string) $request->session()->getId()
            : '';
        $visitorMaterial = $sessionId !== ''
            ? 'sess:'.$sessionId
            : 'net:'.(string) $request->ip().'|'.substr((string) $request->userAgent(), 0, 120);

        $appKey = (string) config('app.key');
        if ($appKey === '') {
            throw new \RuntimeException('APP_KEY wajib dikonfigurasi.');
        }

        $cacheKey = 'post_view:'.$post->id.':'.hash_hmac(
            'sha256',
            $visitorMaterial,
            $appKey
        );

        if (! Cache::add($cacheKey, 1, now()->addMinutes(30))) {
            if ($request->hasSession()) {
                $request->session()->put($sessionFlag, true);
            }

            return false;
        }

        if ($request->hasSession()) {
            $request->session()->put($sessionFlag, true);
        }

        // Atomic query increment avoids Post::saved cache flushes and activitylog noise per view.
        DB::table('posts')->where('id', $post->id)->increment('views_count');
        $post->views_count = (int) $post->views_count + 1;

        return true;
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
        $ua = strtolower(trim((string) $request->userAgent()));
        if ($ua === '') {
            return true;
        }

        foreach (self::BOT_UA_SIGNATURES as $signature) {
            if (str_contains($ua, $signature)) {
                return true;
            }
        }

        return false;
    }
}
