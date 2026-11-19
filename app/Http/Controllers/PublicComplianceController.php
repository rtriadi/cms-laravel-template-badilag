<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Services\SinovaHealthCheckService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicComplianceController extends Controller
{
    public function accessibility(): View
    {
        return view('public.accessibility', [
            'satker' => SatkerSetting::current(),
        ]);
    }

    public function systemStatus(Request $request, SinovaHealthCheckService $healthCheck): View
    {
        return view('public.system-status', [
            'satker' => SatkerSetting::current(),
            'cmsVersion' => (string) config('satker.cms_version', '1.0.0-dev'),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'isPhpNonEol' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'isHttps' => $request->isSecure(),
            'scoreSummary' => $healthCheck->scoreSummary(),
        ]);
    }

    public function visitorStats(): View
    {
        $totalVisits = (int) PageVisit::query()->sum('visits_count');
        $last30Days = (int) PageVisit::query()
            ->where('visit_date', '>=', now()->subDays(30)->toDateString())
            ->sum('visits_count');

        $dailyStats = PageVisit::query()
            ->selectRaw('visit_date, SUM(visits_count) as total')
            ->groupBy('visit_date')
            ->orderByDesc('visit_date')
            ->limit(14)
            ->get();

        $topPaths = PageVisit::query()
            ->selectRaw('path, SUM(visits_count) as total')
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        return view('public.visitor-stats', [
            'totalVisits' => $totalVisits,
            'last30Days' => $last30Days,
            'dailyStats' => $dailyStats,
            'topPaths' => $topPaths,
        ]);
    }

    public function sitemapHtml(): View
    {
        $satker = SatkerSetting::current();

        $pages = Page::query()
            ->where('is_published', true)
            ->orderBy('slug')
            ->get()
            ->map(function (Page $page) use ($satker): Page {
                if ($satker !== null) {
                    $page->title = $satker->replacePlaceholders($page->title);
                }

                return $page;
            });

        $posts = Post::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(40)
            ->get();

        return view('public.sitemap', [
            'pages' => $pages,
            'posts' => $posts,
        ]);
    }

    public function sitemapXml(): Response
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => now()->toAtomString()],
            ['loc' => route('public.posts'), 'lastmod' => now()->toAtomString()],
            ['loc' => route('public.accessibility'), 'lastmod' => now()->toAtomString()],
            ['loc' => route('public.system-status'), 'lastmod' => now()->toAtomString()],
            ['loc' => route('public.visitor-stats'), 'lastmod' => now()->toAtomString()],
            ['loc' => route('public.sitemap'), 'lastmod' => now()->toAtomString()],
        ];

        $pages = Page::query()
            ->where('is_published', true)
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        foreach ($pages as $page) {
            $urls[] = [
                'loc' => route('public.page', ['slug' => $page->slug]),
                'lastmod' => $page->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        }

        $posts = Post::query()
            ->where('is_published', true)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['slug', 'updated_at']);

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('public.posts.show', ['slug' => $post->slug]),
                'lastmod' => $post->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        }

        return response()
            ->view('public.sitemap-xml', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
