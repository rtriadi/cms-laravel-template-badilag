<?php

namespace App\Http\Controllers;

use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\Document;
use App\Models\MenuItem;
use App\Models\Official;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $cleanSlug = trim($slug, '/');
        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';

        $page = Page::query()
            ->where('slug', $cleanSlug)
            ->where('is_published', true)
            ->whereIn('tingkat_scope', ['all', $activeTingkat])
            ->firstOrFail();

        $resolvedTitle = $satker !== null
            ? $satker->replacePlaceholders($page->title)
            : $page->title;

        $selectedYear = $request->integer('tahun');
        $docSearch = mb_substr(trim((string) $request->query('cari_dok', '')), 0, 100);
        $forceFallback = $request->boolean('fallback');
        $requestedSimtepaTab = (string) $request->query('simtepa_tab', '');
        $rawBlocks = is_array($page->blocks) ? $page->blocks : [];

        $documentsByCollection = [];
        $yearsByCollection = [];
        $postsByCategory = [];
        $needsOfficials = false;
        $needsOrgChart = false;
        $needsSchedules = false;
        $needsStatistics = false;

        $blocks = array_map(function (array $block) use (
            $request,
            $satker,
            $selectedYear,
            $docSearch,
            $requestedSimtepaTab,
            &$documentsByCollection,
            &$yearsByCollection,
            &$postsByCategory,
            &$needsOfficials,
            &$needsOrgChart,
            &$needsSchedules,
            &$needsStatistics
        ): array {
            if ($satker !== null) {
                foreach (['heading', 'content', 'description', 'note'] as $textField) {
                    if (isset($block[$textField]) && is_string($block[$textField])) {
                        $block[$textField] = $satker->replacePlaceholders($block[$textField]);
                    }
                }
            }

            $type = $block['type'] ?? 'richtext';

            if ($type === 'article_list') {
                $cat = (string) ($block['post_category'] ?? 'layanan_hukum');
                if (! array_key_exists($cat, $postsByCategory)) {
                    $postsByCategory[$cat] = Post::query()
                        ->select(['id', 'title', 'slug', 'category', 'excerpt', 'featured_image_path', 'featured_image_alt', 'is_pinned', 'views_count', 'published_at', 'created_at'])
                        ->withCount('approvedComments')
                        ->publishedActive()
                        ->when($cat !== 'semua', fn ($q) => $q->where('category', $cat))
                        ->orderByDesc('is_pinned')
                        ->orderByDesc('published_at')
                        ->orderByDesc('id')
                        ->limit(12)
                        ->get();
                }
            }

            if ($type === 'document_list') {
                $col = (string) ($block['collection_type'] ?? 'lkjip');
                if (! array_key_exists($col, $documentsByCollection)) {
                    $yearsByCollection[$col] = Document::query()
                        ->where('collection_type', $col)
                        ->where('is_published', true)
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year')
                        ->all();

                    $escapedDocSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $docSearch);

                    $documentsByCollection[$col] = Document::query()
                        ->where('collection_type', $col)
                        ->where('is_published', true)
                        ->when($selectedYear > 0, fn ($q) => $q->where('year', $selectedYear))
                        ->when($docSearch !== '', function ($q) use ($escapedDocSearch): void {
                            $like = "%{$escapedDocSearch}%";
                            $q->where(fn ($sub) => $sub->where('title', 'like', $like)->orWhere('status_label', 'like', $like));
                        })
                        ->orderByDesc('year')
                        ->orderByDesc('month')
                        ->orderByDesc('id')
                        ->paginate(20, ['*'], 'hal_'.$col)
                        ->withQueryString();
                }
            }

            if ($type === 'embed_fallback') {
                $fallbackMode = (string) ($block['fallback_mode'] ?? 'officials_table');
                $sourceKey = (string) ($block['source_key'] ?? '');
                $customOverride = isset($block['custom_embed_url']) ? (string) $block['custom_embed_url'] : null;

                $resolvedSource = $satker !== null
                    ? $satker->resolveEmbedSource($sourceKey, $customOverride)
                    : ['embed_url' => SafeHtml::cleanUrl($customOverride), 'tabs' => []];

                $activeTabKey = $requestedSimtepaTab;
                $activeEmbedUrl = $resolvedSource['embed_url'];

                foreach ($resolvedSource['tabs'] as $tabItem) {
                    if ($tabItem['key'] === $activeTabKey) {
                        $activeEmbedUrl = $tabItem['html_url'];
                        break;
                    }
                }

                $block['resolved_embed_url'] = $activeEmbedUrl;
                $block['resolved_embed_tabs'] = $resolvedSource['tabs'];
                $block['active_simtepa_tab'] = $activeTabKey !== '' ? $activeTabKey : ($resolvedSource['tabs'][0]['key'] ?? '');
                $isSippEmbed = str_starts_with($sourceKey, 'sipp')
                    || (is_string($activeEmbedUrl) && str_contains(strtolower((string) parse_url($activeEmbedUrl, PHP_URL_HOST)), 'sipp'));
                $block['is_cross_site_sipp'] = $isSippEmbed && $this->isCrossSiteEmbed($request, $activeEmbedUrl);

                match ($fallbackMode) {
                    'org_chart' => $needsOrgChart = true,
                    'court_schedule' => $needsSchedules = true,
                    'case_statistics' => $needsStatistics = true,
                    default => $needsOfficials = true,
                };
            }

            if ($type === 'external_portal') {
                $urlSource = (string) ($block['url_source'] ?? '');
                $resolved = ($satker !== null && $urlSource !== '' && isset($satker->{$urlSource}) && filled($satker->{$urlSource}))
                    ? (string) $satker->{$urlSource}
                    : (string) ($block['default_url'] ?? '');
                $block['resolved_portal_url'] = SafeHtml::cleanUrl($resolved) ?? '';
            }

            return $block;
        }, $rawBlocks);

        $officials = $needsOfficials
            ? Official::query()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        // SK Badilag: struktur organisasi hanya menampilkan pimpinan & pejabat struktural/fungsional (tanpa staf/honorer)
        $orgOfficials = $needsOrgChart
            ? Official::query()
                ->where('is_active', true)
                ->whereIn('kategori', ['pimpinan', 'pejabat', 'hakim'])
                ->with('parent')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $schedules = $needsSchedules
            ? CourtSchedule::query()->orderByDesc('tanggal_sidang')->limit(50)->get()
            : collect();

        $statistics = $needsStatistics
            ? CaseStatistic::query()->orderByDesc('year')->orderByDesc('month')->get()
            : collect();

        $sectionPrefix = str_contains($cleanSlug, '/') ? explode('/', $cleanSlug)[0] : '';
        $sectionLabels = [
            'profil' => 'Profil Pengadilan',
            'layanan-hukum' => 'Layanan Hukum',
            'transparansi' => 'Transparansi',
            'layanan-publik' => 'Layanan Publik',
        ];
        $sectionLabel = $this->resolveMenuSectionLabel($page, $activeTingkat)
            ?? ($sectionLabels[$sectionPrefix] ?? 'Informasi & Layanan');

        $relatedSectionPages = $sectionPrefix !== ''
            ? Page::query()
                ->select(['id', 'title', 'slug', 'criteria_code'])
                ->where('is_published', true)
                ->where('slug', 'like', $sectionPrefix.'/%')
                ->whereIn('tingkat_scope', ['all', $activeTingkat])
                ->orderBy('id')
                ->get()
                ->map(function (Page $relPage) use ($satker): Page {
                    if ($satker !== null) {
                        $relPage->title = $satker->replacePlaceholders($relPage->title);
                    }

                    return $relPage;
                })
            : collect();

        return view('public.page', [
            'page' => $page,
            'resolvedTitle' => $resolvedTitle,
            'sectionLabel' => $sectionLabel,
            'relatedSectionPages' => $relatedSectionPages,
            'satker' => $satker,
            'blocks' => $blocks,
            'documentsByCollection' => $documentsByCollection,
            'yearsByCollection' => $yearsByCollection,
            'postsByCategory' => $postsByCategory,
            'selectedYear' => $selectedYear,
            'docSearch' => $docSearch,
            'forceFallback' => $forceFallback,
            'officials' => $officials,
            'orgOfficials' => $orgOfficials,
            'schedules' => $schedules,
            'statistics' => $statistics,
        ]);
    }

    private function resolveMenuSectionLabel(Page $page, string $tingkat): ?string
    {
        $headerMenus = MenuItem::cachedPublicMenus($tingkat)['header'];
        $targetPath = '/halaman/'.trim($page->slug, '/');

        foreach ($headerMenus as $topMenu) {
            if ($this->menuBranchContainsPage($topMenu, $page->id, $targetPath)) {
                return $topMenu->title;
            }
        }

        return null;
    }

    private function menuBranchContainsPage(MenuItem $item, int $pageId, string $targetPath): bool
    {
        if ($item->page_id === $pageId) {
            return true;
        }

        $url = $item->resolvedUrl();
        if ($url !== '#' && ! str_starts_with($url, 'http')) {
            $norm = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
            if ($norm === $targetPath) {
                return true;
            }
        }

        foreach ($item->children as $child) {
            if ($this->menuBranchContainsPage($child, $pageId, $targetPath)) {
                return true;
            }
        }

        return false;
    }

    private function isCrossSiteEmbed(Request $request, ?string $embedUrl): bool
    {
        if ($embedUrl === null || trim($embedUrl) === '') {
            return false;
        }

        $parts = parse_url($embedUrl);
        if (! is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $embedScheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $requestScheme = $request->isSecure() ? 'https' : strtolower($request->getScheme());

        if ($embedScheme !== $requestScheme) {
            return true;
        }

        $embedSite = $this->registrableSite((string) $parts['host']);
        $requestSite = $this->registrableSite($request->getHost());

        return $embedSite === '' || $requestSite === '' || $embedSite !== $requestSite;
    }

    private function registrableSite(string $host): string
    {
        $clean = rtrim(strtolower(trim($host, '[]')), '.');
        if ($clean === '' || filter_var($clean, FILTER_VALIDATE_IP) !== false) {
            return $clean;
        }

        $labels = explode('.', $clean);
        $count = count($labels);
        if ($count <= 2) {
            return $clean;
        }

        $twoLevelPublicSuffixes = [
            'go.id', 'ac.id', 'or.id', 'co.id', 'sch.id', 'web.id', 'my.id', 'net.id', 'biz.id', 'mil.id', 'desa.id',
        ];
        $lastTwo = $labels[$count - 2].'.'.$labels[$count - 1];

        if (in_array($lastTwo, $twoLevelPublicSuffixes, true)) {
            return $labels[$count - 3].'.'.$lastTwo;
        }

        return $lastTwo;
    }
}
