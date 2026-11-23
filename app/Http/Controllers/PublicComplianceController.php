<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\SatkerSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class PublicComplianceController extends Controller
{
    public static function flushCache(): void
    {
        Cache::forget('public_sitemap_xml.pertama');
        Cache::forget('public_sitemap_xml.banding');
        Cache::forget('public_sitemap_html.pertama');
        Cache::forget('public_sitemap_html.banding');
        Cache::forget('public_visitor_stats');
    }

    public function accessibility(): View
    {
        return view('public.accessibility', [
            'satker' => SatkerSetting::current(),
        ]);
    }

    public function systemStatus(Request $request): View
    {
        return view('public.system-status', [
            'satker' => SatkerSetting::current(),
            'cmsVersion' => (string) config('satker.cms_version', '1.0.0-dev'),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'isPhpNonEol' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'isHttps' => $request->isSecure(),
        ]);
    }

    public function visitorStats(): View
    {
        $stats = Cache::get('public_visitor_stats');

        if (! is_array($stats) || ! isset($stats['totalVisits'], $stats['last30Days'], $stats['dailyStats'], $stats['topPaths'])) {
            $totalVisits = (int) PageVisit::query()->sum('visits_count');
            $last30Days = (int) PageVisit::query()
                ->where('visit_date', '>=', now()->subDays(30)->toDateString())
                ->sum('visits_count');

            $dailyStats = PageVisit::query()
                ->selectRaw('visit_date, SUM(visits_count) as total')
                ->groupBy('visit_date')
                ->orderByDesc('visit_date')
                ->limit(14)
                ->get()
                ->map(fn (PageVisit $row): array => [
                    'visit_date' => $row->visit_date instanceof CarbonInterface
                        ? $row->visit_date->format('Y-m-d')
                        : (string) $row->visit_date,
                    'total' => (int) $row->getAttribute('total'),
                ])
                ->all();

            $topPaths = PageVisit::query()
                ->selectRaw('path, SUM(visits_count) as total')
                ->groupBy('path')
                ->orderByDesc('total')
                ->limit(15)
                ->get()
                ->map(fn (PageVisit $row): array => [
                    'path' => (string) $row->path,
                    'total' => (int) $row->getAttribute('total'),
                ])
                ->all();

            $stats = [
                'totalVisits' => $totalVisits,
                'last30Days' => $last30Days,
                'dailyStats' => $dailyStats,
                'topPaths' => $topPaths,
            ];

            Cache::put('public_visitor_stats', $stats, 120);
        }

        return view('public.visitor-stats', [
            'satker' => SatkerSetting::current(),
            'totalVisits' => (int) $stats['totalVisits'],
            'last30Days' => (int) $stats['last30Days'],
            'dailyStats' => collect($stats['dailyStats'])->map(fn (array $row): object => (object) $row),
            'topPaths' => collect($stats['topPaths'])->map(fn (array $row): object => (object) $row),
        ]);
    }

    public function sitemapHtml(): View
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';
        $cacheKey = 'public_sitemap_html.'.$activeTingkat;
        $cached = Cache::get($cacheKey);

        if (! is_array($cached) || ! isset($cached['pages'], $cached['posts'])) {
            $cached = [
                'pages' => Page::query()
                    ->select(['id', 'title', 'slug', 'criteria_code'])
                    ->where('is_published', true)
                    ->whereIn('tingkat_scope', ['all', $activeTingkat])
                    ->orderBy('slug')
                    ->get()
                    ->map(fn (Page $page): array => $page->getAttributes())
                    ->all(),
                'posts' => Post::query()
                    ->select(['id', 'title', 'slug', 'published_at'])
                    ->publishedActive()
                    ->orderByDesc('published_at')
                    ->limit(40)
                    ->get()
                    ->map(fn (Post $post): array => $post->getAttributes())
                    ->all(),
            ];

            Cache::put($cacheKey, $cached, 600);
        }

        $pages = (new Collection(array_map(
            fn (array $attrs): Page => (new Page)->newFromBuilder($attrs),
            $cached['pages']
        )))->map(function (Page $page) use ($satker): Page {
            if ($satker !== null) {
                $page->title = $satker->replacePlaceholders($page->title);
            }

            return $page;
        });

        $posts = new Collection(array_map(
            fn (array $attrs): Post => (new Post)->newFromBuilder($attrs),
            $cached['posts']
        ));

        return view('public.sitemap', [
            'satker' => $satker,
            'pages' => $pages,
            'posts' => $posts,
        ]);
    }

    public function sitemapXml(Request $request): Response
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';
        $cacheKey = 'public_sitemap_xml.'.$activeTingkat;
        $expectedHost = (string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
        $isCanonicalHost = $expectedHost === '' || strcasecmp($request->getHost(), $expectedHost) === 0;
        $xml = $isCanonicalHost ? Cache::get($cacheKey) : null;

        if (! is_string($xml) || $xml === '') {
            $pages = Page::query()
                ->where('is_published', true)
                ->whereIn('tingkat_scope', ['all', $activeTingkat])
                ->orderBy('slug')
                ->get(['slug', 'updated_at']);

            $posts = Post::query()
                ->publishedActive()
                ->orderByDesc('updated_at')
                ->limit(1000)
                ->get(['slug', 'updated_at']);

            $satkerLastMod = $satker?->updated_at;
            $latestPageMod = $pages->max('updated_at');
            $latestPostMod = $posts->max('updated_at');

            $timestamps = array_values(array_filter(
                [$satkerLastMod, $latestPageMod, $latestPostMod],
                static fn (mixed $ts): bool => $ts !== null
            ));
            $siteLastMod = $timestamps !== []
                ? Carbon::parse(max($timestamps))->toAtomString()
                : Carbon::create(2026, 1, 1, 0, 0, 0, 'UTC')->toAtomString();
            $postsLastMod = $latestPostMod !== null
                ? Carbon::parse($latestPostMod)->toAtomString()
                : $siteLastMod;
            $staticLastMod = $satkerLastMod !== null
                ? $satkerLastMod->toAtomString()
                : $siteLastMod;

            $urls = [
                ['loc' => route('home'), 'lastmod' => $siteLastMod],
                ['loc' => route('public.posts'), 'lastmod' => $postsLastMod],
                ['loc' => route('public.accessibility'), 'lastmod' => $staticLastMod],
                ['loc' => route('public.system-status'), 'lastmod' => $staticLastMod],
                ['loc' => route('public.visitor-stats'), 'lastmod' => $staticLastMod],
                ['loc' => route('public.sitemap'), 'lastmod' => $siteLastMod],
            ];

            foreach ($pages as $page) {
                $urls[] = [
                    'loc' => route('public.page', ['slug' => $page->slug]),
                    'lastmod' => $page->updated_at?->toAtomString() ?? $siteLastMod,
                ];
            }

            foreach ($posts as $post) {
                $urls[] = [
                    'loc' => route('public.posts.show', ['slug' => $post->slug]),
                    'lastmod' => $post->updated_at?->toAtomString() ?? $postsLastMod,
                ];
            }

            $xml = view('public.sitemap-xml', ['urls' => $urls])->render();
            if ($isCanonicalHost) {
                Cache::put($cacheKey, $xml, 900);
            }
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
            'Vary' => 'Host',
        ]);
    }

    public function robotsTxt(): Response
    {
        $adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');
        $sitemapUrl = route('public.sitemap.xml');

        $lines = [
            'User-agent: *',
        ];

        // Only list the default admin path; avoid broadcasting a customized stealth ADMIN_PATH.
        if ($adminPath === 'panel-admin') {
            $lines[] = 'Disallow: /panel-admin';
        }

        $lines = [
            ...$lines,
            'Disallow: /login',
            'Disallow: /two-factor-challenge',
            'Disallow: /setup',
            'Disallow: /cari',
            'Disallow: /captcha-image',
            'Disallow: /captcha-text',
            'Disallow: /livewire/',
            '',
            "Sitemap: {$sitemapUrl}",
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'Vary' => 'Host',
        ]);
    }
}
