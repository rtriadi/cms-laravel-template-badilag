<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\SatkerSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SecurityHeadersMiddleware
{
    public const string FRAME_SRC_CACHE_KEY = 'security.csp_frame_src_origins';

    /**
     * Baseline official origins permitted for public iframe embeds (SIMTEPA, OpenStreetMap, docs/video).
     *
     * @var array<int, string>
     */
    private const array DEFAULT_FRAME_ORIGINS = [
        'https://simtepa.mahkamahagung.go.id',
        'https://www.openstreetmap.org',
        'https://www.youtube-nocookie.com',
        'https://www.youtube.com',
        'https://drive.google.com',
        'https://docs.google.com',
        'https://www.google.com',
        'https://maps.google.com',
    ];

    public static function flushFrameSrcCache(): void
    {
        Cache::forget(self::FRAME_SRC_CACHE_KEY);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        $isStrictPublic = ! $this->isInteractiveAdminOrSetupRoute($request) && ! Vite::isRunningHot();
        $nonce = $isStrictPublic ? Vite::useCspNonce() : null;

        $response = $next($request);

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $scriptSrc = $nonce !== null
            ? "script-src 'self' 'nonce-{$nonce}'"
            : "script-src 'self' 'unsafe-inline' 'unsafe-eval'";

        $frameSrc = $this->resolveFrameSrcDirective();

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; {$scriptSrc}; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; {$frameSrc}; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function isInteractiveAdminOrSetupRoute(Request $request): bool
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
            'login',
            'logout',
            'two-factor-challenge',
            'user/*',
        );
    }

    private function resolveFrameSrcDirective(): string
    {
        $origins = Cache::get(self::FRAME_SRC_CACHE_KEY);

        if (! is_array($origins)) {
            $origins = $this->collectAllowedFrameOrigins();
            Cache::put(self::FRAME_SRC_CACHE_KEY, $origins, 600);
        }

        return "frame-src 'self' ".implode(' ', $origins);
    }

    /**
     * @return array<int, string>
     */
    private function collectAllowedFrameOrigins(): array
    {
        $originSet = [];

        foreach (self::DEFAULT_FRAME_ORIGINS as $defaultOrigin) {
            $originSet[$defaultOrigin] = true;
        }

        try {
            $satker = SatkerSetting::current();
            if ($satker !== null) {
                $urlFields = [
                    'sipp_url',
                    'simtepa_pegawai_url',
                    'simtepa_struktur_url',
                    'skm_url',
                    'spak_url',
                    'ecourt_url',
                    'siwas_url',
                    'direktori_putusan_url',
                    'cctv_aco_url',
                    'pta_url',
                    'video_profil_url',
                    'video_ptsp_url',
                    'video_kebijakan_ma_url',
                ];

                foreach ($urlFields as $field) {
                    $origin = $this->extractSafeOrigin((string) ($satker->{$field} ?? ''));
                    if ($origin !== null) {
                        $originSet[$origin] = true;
                    }
                }
            }

            $pagesWithBlocks = Page::query()
                ->where('is_published', true)
                ->whereNotNull('blocks')
                ->get(['blocks']);

            foreach ($pagesWithBlocks as $page) {
                if (! is_array($page->blocks)) {
                    continue;
                }

                foreach ($page->blocks as $block) {
                    if (! is_array($block)) {
                        continue;
                    }

                    $customUrl = (string) ($block['custom_embed_url'] ?? '');
                    if ($customUrl !== '') {
                        $origin = $this->extractSafeOrigin($customUrl);
                        if ($origin !== null) {
                            $originSet[$origin] = true;
                        }
                    }
                }
            }
        } catch (Throwable) {
            // Fresh install or pre-migration probe falls back safely to baseline official origins.
        }

        return array_keys($originSet);
    }

    private function extractSafeOrigin(string $url): ?string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return null;
        }

        $parts = parse_url($trimmed);
        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || preg_match('/^[a-z0-9.-]+$/', $host) !== 1) {
            return null;
        }

        if ($scheme !== 'https' && ! ($scheme === 'http' && ! app()->isProduction())) {
            return null;
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $portSuffix = ($port !== null && ! in_array($port, [80, 443], true)) ? ':'.$port : '';

        return "{$scheme}://{$host}{$portSuffix}";
    }
}
