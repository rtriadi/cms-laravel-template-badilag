<?php

namespace App\Http\Controllers;

use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\Document;
use App\Models\Official;
use App\Models\Page;
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

        $page = Page::query()
            ->where('slug', $cleanSlug)
            ->where('is_published', true)
            ->firstOrFail();

        $resolvedTitle = $satker !== null
            ? $satker->replacePlaceholders($page->title)
            : $page->title;

        $selectedYear = $request->integer('tahun');
        $forceFallback = $request->boolean('fallback');
        $rawBlocks = is_array($page->blocks) ? $page->blocks : [];

        $documentsByCollection = [];
        $yearsByCollection = [];
        $needsOfficials = false;
        $needsOrgChart = false;
        $needsSchedules = false;
        $needsStatistics = false;

        $blocks = array_map(function (array $block) use (
            $satker,
            $selectedYear,
            &$documentsByCollection,
            &$yearsByCollection,
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

                    $documentsByCollection[$col] = Document::query()
                        ->where('collection_type', $col)
                        ->where('is_published', true)
                        ->when($selectedYear > 0, fn ($q) => $q->where('year', $selectedYear))
                        ->orderByDesc('year')
                        ->orderByDesc('month')
                        ->orderByDesc('id')
                        ->get();
                }
            }

            if ($type === 'embed_fallback') {
                $fallbackMode = (string) ($block['fallback_mode'] ?? 'officials_table');
                $sourceKey = (string) ($block['source_key'] ?? '');

                $block['resolved_embed_url'] = SafeHtml::cleanUrl(match ($sourceKey) {
                    'simtepa_pegawai' => $satker?->simtepa_pegawai_url,
                    'simtepa_struktur' => $satker?->simtepa_struktur_url,
                    'sipp_jadwal', 'sipp_statistik' => $satker?->sipp_url,
                    default => null,
                });

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

        return view('public.page', [
            'page' => $page,
            'resolvedTitle' => $resolvedTitle,
            'satker' => $satker,
            'blocks' => $blocks,
            'documentsByCollection' => $documentsByCollection,
            'yearsByCollection' => $yearsByCollection,
            'selectedYear' => $selectedYear,
            'forceFallback' => $forceFallback,
            'officials' => $officials,
            'orgOfficials' => $orgOfficials,
            'schedules' => $schedules,
            'statistics' => $statistics,
        ]);
    }
}
