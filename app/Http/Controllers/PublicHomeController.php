<?php

namespace App\Http\Controllers;

use App\Models\CourtSchedule;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PublicHomeController extends Controller
{
    public static function flushCache(): void
    {
        Cache::forget('public_home.pertama');
        Cache::forget('public_home.banding');
    }

    public function __invoke(): View
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';
        $cacheKey = 'public_home.'.$activeTingkat;
        $cached = Cache::get($cacheKey);

        if (
            ! is_array($cached)
            || ! isset($cached['sliders'], $cached['sertaMerta'], $cached['latestNews'], $cached['announcements'], $cached['transparencyPages'], $cached['servicePages'], $cached['todaySchedules'], $cached['hasUpcomingSchedules'])
        ) {
            $upcomingSchedules = CourtSchedule::query()
                ->select(['id', 'tanggal_sidang', 'nomor_perkara', 'ruang_sidang', 'agenda'])
                ->whereDate('tanggal_sidang', '>=', now()->toDateString())
                ->orderBy('tanggal_sidang')
                ->orderBy('id')
                ->limit(4)
                ->get();

            $hasUpcomingSchedules = $upcomingSchedules->isNotEmpty();
            $scheduleRows = $hasUpcomingSchedules
                ? $upcomingSchedules
                : CourtSchedule::query()
                    ->select(['id', 'tanggal_sidang', 'nomor_perkara', 'ruang_sidang', 'agenda'])
                    ->orderByDesc('tanggal_sidang')
                    ->orderByDesc('id')
                    ->limit(4)
                    ->get();

            $cached = [
                'sliders' => HomeSlider::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->limit(HomeSlider::MAX_ACTIVE_SLIDES)
                    ->get()
                    ->map(fn (HomeSlider $slide): array => $slide->getAttributes())
                    ->all(),
                'sertaMerta' => Post::query()
                    ->select(['id', 'title', 'slug', 'category', 'is_pinned', 'published_at', 'created_at'])
                    ->publishedActive()
                    ->where('category', 'serta_merta')
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('published_at')
                    ->limit(2)
                    ->get()
                    ->map(fn (Post $post): array => $post->getAttributes())
                    ->all(),
                'latestNews' => Post::query()
                    ->select(['id', 'title', 'slug', 'category', 'excerpt', 'featured_image_path', 'featured_image_alt', 'is_pinned', 'views_count', 'published_at', 'created_at'])
                    ->withCount('approvedComments')
                    ->publishedActive()
                    ->whereIn('category', ['berita', 'layanan_hukum', 'inovasi', 'opini', 'infografis'])
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(4)
                    ->get()
                    ->map(fn (Post $post): array => $post->getAttributes())
                    ->all(),
                'announcements' => Post::query()
                    ->select(['id', 'title', 'slug', 'category', 'views_count', 'published_at', 'created_at'])
                    ->withCount('approvedComments')
                    ->publishedActive()
                    ->where('category', 'pengumuman')
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Post $post): array => $post->getAttributes())
                    ->all(),
                'transparencyPages' => Page::query()
                    ->select(['id', 'title', 'slug', 'criteria_code'])
                    ->where('is_published', true)
                    ->where('slug', 'like', 'transparansi/%')
                    ->whereIn('tingkat_scope', ['all', $activeTingkat])
                    ->orderBy('criteria_code')
                    ->limit(8)
                    ->get()
                    ->map(fn (Page $page): array => $page->getAttributes())
                    ->all(),
                'servicePages' => Page::query()
                    ->select(['id', 'title', 'slug', 'summary', 'criteria_code'])
                    ->where('is_published', true)
                    ->where('slug', 'like', 'layanan-hukum/%')
                    ->whereIn('tingkat_scope', ['all', $activeTingkat])
                    ->orderBy('criteria_code')
                    ->limit(6)
                    ->get()
                    ->map(fn (Page $page): array => $page->getAttributes())
                    ->all(),
                'todaySchedules' => $scheduleRows
                    ->map(fn (CourtSchedule $schedule): array => $schedule->getAttributes())
                    ->all(),
                'hasUpcomingSchedules' => $hasUpcomingSchedules,
            ];

            Cache::put($cacheKey, $cached, 300);
        }

        $sliders = (new Collection(array_map(
            fn (array $attrs): HomeSlider => (new HomeSlider)->newFromBuilder($attrs),
            $cached['sliders']
        )))->map(function (HomeSlider $slide) use ($satker): HomeSlider {
            if ($satker !== null) {
                $slide->title = $satker->replacePlaceholders($slide->title);
                if ($slide->subtitle !== null) {
                    $slide->subtitle = $satker->replacePlaceholders($slide->subtitle);
                }
                $slide->image_alt = $satker->replacePlaceholders($slide->image_alt);
                if ($slide->cta_label !== null) {
                    $slide->cta_label = $satker->replacePlaceholders($slide->cta_label);
                }
            }
            $slide->cta_url = SafeHtml::cleanUrl($slide->cta_url);

            return $slide;
        });

        $sertaMerta = new Collection(array_map(
            fn (array $attrs): Post => (new Post)->newFromBuilder($attrs),
            $cached['sertaMerta']
        ));

        $latestNews = new Collection(array_map(
            fn (array $attrs): Post => (new Post)->newFromBuilder($attrs),
            $cached['latestNews']
        ));

        $announcements = new Collection(array_map(
            fn (array $attrs): Post => (new Post)->newFromBuilder($attrs),
            $cached['announcements']
        ));

        $todaySchedules = new Collection(array_map(
            fn (array $attrs): CourtSchedule => (new CourtSchedule)->newFromBuilder($attrs),
            $cached['todaySchedules']
        ));

        $transparencyPages = (new Collection(array_map(
            fn (array $attrs): Page => (new Page)->newFromBuilder($attrs),
            $cached['transparencyPages']
        )))->map(function (Page $page) use ($satker): Page {
            if ($satker !== null) {
                $page->title = $satker->replacePlaceholders($page->title);
            }

            return $page;
        });

        $servicePages = (new Collection(array_map(
            fn (array $attrs): Page => (new Page)->newFromBuilder($attrs),
            $cached['servicePages']
        )))->map(function (Page $page) use ($satker): Page {
            if ($satker !== null) {
                $page->title = $satker->replacePlaceholders($page->title);
            }

            return $page;
        });

        $customShortcuts = MenuItem::cachedPublicMenus($activeTingkat)['shortcut'];

        $shortcuts = $customShortcuts->isNotEmpty()
            ? $customShortcuts->map(fn (MenuItem $item): array => [
                'label' => $item->title,
                'url' => $item->resolvedUrl(),
                'tag' => 'Layanan',
            ])->all()
            : [
                [
                    'label' => 'Penelusuran Perkara (SIPP)',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/sipp']),
                    'tag' => 'Layanan',
                ],
                [
                    'label' => 'Jadwal Persidangan',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']),
                    'tag' => 'Kepaniteraan',
                ],
                [
                    'label' => 'Statistik Perkara Bulanan',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/statistik-perkara']),
                    'tag' => 'Kepaniteraan',
                ],
                [
                    'label' => 'Pendaftaran Online (e-Court)',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/ecourt']),
                    'tag' => 'Elektronik',
                ],
                [
                    'label' => 'Laporan Kinerja (LKjIP)',
                    'url' => route('public.page', ['slug' => 'transparansi/lkjip']),
                    'tag' => 'SAKIP',
                ],
                [
                    'label' => 'Pengaduan SIWAS MARI',
                    'url' => route('public.page', ['slug' => 'layanan-publik/pengaduan-siwas']),
                    'tag' => 'Pengawasan',
                ],
                [
                    'label' => 'Survei Kepuasan (SKM, SPAK & SPKP)',
                    'url' => route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']),
                    'tag' => 'Survei',
                ],
            ];

        return view('public.home', [
            'satker' => $satker,
            'sliders' => $sliders,
            'sertaMerta' => $sertaMerta,
            'latestNews' => $latestNews,
            'announcements' => $announcements,
            'transparencyPages' => $transparencyPages,
            'servicePages' => $servicePages,
            'todaySchedules' => $todaySchedules,
            'hasUpcomingSchedules' => (bool) $cached['hasUpcomingSchedules'],
            'shortcuts' => $shortcuts,
            'hasCustomShortcuts' => $customShortcuts->isNotEmpty(),
        ]);
    }
}
