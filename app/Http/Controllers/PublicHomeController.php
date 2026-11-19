<?php

namespace App\Http\Controllers;

use App\Models\HomeSlider;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use Illuminate\Contracts\View\View;

class PublicHomeController extends Controller
{
    public function __invoke(): View
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';

        $sliders = HomeSlider::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(HomeSlider::MAX_ACTIVE_SLIDES)
            ->get()
            ->map(function (HomeSlider $slide) use ($satker): HomeSlider {
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

                return $slide;
            });

        $sertaMerta = Post::query()
            ->where('is_published', true)
            ->where('category', 'serta_merta')
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(2)
            ->get();

        $latestNews = Post::query()
            ->where('is_published', true)
            ->whereIn('category', ['berita', 'inovasi', 'opini', 'infografis'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $announcements = Post::query()
            ->where('is_published', true)
            ->where('category', 'pengumuman')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $transparencyPages = Page::query()
            ->where('is_published', true)
            ->where('slug', 'like', 'transparansi/%')
            ->where(function ($q) use ($activeTingkat): void {
                $q->where('tingkat_scope', 'all')->orWhere('tingkat_scope', $activeTingkat);
            })
            ->orderBy('criteria_code')
            ->limit(6)
            ->get()
            ->map(function (Page $page) use ($satker): Page {
                if ($satker !== null) {
                    $page->title = $satker->replacePlaceholders($page->title);
                }

                return $page;
            });

        $servicePages = Page::query()
            ->where('is_published', true)
            ->where('slug', 'like', 'layanan-hukum/%')
            ->where(function ($q) use ($activeTingkat): void {
                $q->where('tingkat_scope', 'all')->orWhere('tingkat_scope', $activeTingkat);
            })
            ->orderBy('criteria_code')
            ->limit(6)
            ->get()
            ->map(function (Page $page) use ($satker): Page {
                if ($satker !== null) {
                    $page->title = $satker->replacePlaceholders($page->title);
                }

                return $page;
            });

        $shortcuts = [
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
        ];

        return view('public.home', [
            'satker' => $satker,
            'sliders' => $sliders,
            'sertaMerta' => $sertaMerta,
            'latestNews' => $latestNews,
            'announcements' => $announcements,
            'transparencyPages' => $transparencyPages,
            'servicePages' => $servicePages,
            'shortcuts' => $shortcuts,
        ]);
    }
}
