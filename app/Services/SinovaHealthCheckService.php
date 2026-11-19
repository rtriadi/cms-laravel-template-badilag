<?php

namespace App\Services;

use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use App\Models\Document;
use App\Models\LinkCheckLog;
use App\Models\MenuItem;
use App\Models\Official;
use App\Models\Page;
use App\Models\SatkerSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

class SinovaHealthCheckService
{
    public function checkItem(CriteriaItem $item, ?SatkerSetting $satker = null, bool $probeExternal = false): CriteriaItem
    {
        $satker ??= SatkerSetting::current();
        $item->loadMissing(['linkedPage', 'linkedMenu', 'group']);

        $url = $item->resolvedUrl($satker);

        if ($url === null || trim($url) === '') {
            $item->forceFill([
                'health_status' => 'empty',
                'http_status' => null,
                'estimated_score' => 0,
                'health_message' => 'URL kriteria belum diisi.',
                'last_checked_at' => now(),
            ])->save();

            return $item;
        }

        if ($item->use_manual_url && filled($item->manual_url)) {
            return $this->evaluateUrl($item, $item->manual_url, $probeExternal, 'Tautan manual terkonfigurasi.');
        }

        if ($item->linkedPage !== null) {
            return $this->evaluateLinkedPage($item, $item->linkedPage, $satker, $probeExternal);
        }

        if (filled($item->default_route)) {
            $item->forceFill([
                'health_status' => 'healthy',
                'http_status' => 200,
                'estimated_score' => 10,
                'health_message' => 'Rute sistem aktif dan siap diakses.',
                'last_checked_at' => now(),
            ])->save();

            return $item;
        }

        return $this->evaluateUrl($item, $url, $probeExternal, 'Tautan terkonfigurasi.');
    }

    /**
     * @return array{checked: int, healthy: int, warning: int, broken: int, empty: int}
     */
    public function checkAll(?string $tingkat = null, bool $probeExternal = false): array
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $tingkat ?? $satker?->tingkat ?? 'pertama';

        $items = CriteriaItem::query()
            ->with(['linkedPage', 'linkedMenu', 'group'])
            ->get()
            ->filter(fn (CriteriaItem $item): bool => $item->isApplicableFor($activeTingkat));

        $counts = [
            'checked' => 0,
            'healthy' => 0,
            'warning' => 0,
            'broken' => 0,
            'empty' => 0,
        ];

        foreach ($items as $item) {
            $updated = $this->checkItem($item, $satker, $probeExternal);
            $counts['checked']++;
            $status = $updated->health_status;

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        $this->auditMenuLinks($activeTingkat, $probeExternal);

        return $counts;
    }

    /**
     * Compute real SINOVA score summary across the 3 PDF weights (60% Konten, 20% UI/UX, 20% Performa).
     *
     * @return array{
     *     total: int,
     *     filled: int,
     *     empty: int,
     *     healthy: int,
     *     warning: int,
     *     broken: int,
     *     weighted_score: float,
     *     aspects: array<string, array{label: string, weight: int, score_pct: float, total: int, healthy: int, warning: int, broken: int, empty: int}>
     * }
     */
    public function scoreSummary(?string $tingkat = null): array
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $tingkat ?? $satker?->tingkat ?? 'pertama';

        $groups = CriteriaGroup::query()
            ->with(['items.linkedPage', 'items.linkedMenu'])
            ->orderBy('sort_order')
            ->get();

        $aspectConfig = [
            'konten' => ['label' => 'Kualitas & Kepatuhan Konten Wajib', 'weight' => 60],
            'uiux' => ['label' => 'Desain, UI/UX & Aksesibilitas', 'weight' => 20],
            'performa' => ['label' => 'Performa, Keamanan & Infrastruktur', 'weight' => 20],
        ];

        $aspectBuckets = [];
        foreach ($aspectConfig as $key => $meta) {
            $aspectBuckets[$key] = [
                'label' => $meta['label'],
                'weight' => $meta['weight'],
                'earned_points' => 0,
                'max_points' => 0,
                'total' => 0,
                'healthy' => 0,
                'warning' => 0,
                'broken' => 0,
                'empty' => 0,
                'score_pct' => 0.0,
            ];
        }

        $totals = [
            'total' => 0,
            'filled' => 0,
            'empty' => 0,
            'healthy' => 0,
            'warning' => 0,
            'broken' => 0,
        ];

        foreach ($groups as $group) {
            $aspect = array_key_exists($group->aspect, $aspectBuckets) ? $group->aspect : 'konten';

            foreach ($group->items as $item) {
                if (! $item->isApplicableFor($activeTingkat)) {
                    continue;
                }

                $totals['total']++;
                $aspectBuckets[$aspect]['total']++;
                $aspectBuckets[$aspect]['max_points'] += 10;
                $aspectBuckets[$aspect]['earned_points'] += (int) $item->estimated_score;

                $url = $item->resolvedUrl($satker);
                if (filled($url)) {
                    $totals['filled']++;
                }

                $status = $item->health_status ?: 'empty';
                if (isset($totals[$status])) {
                    $totals[$status]++;
                }
                if (isset($aspectBuckets[$aspect][$status])) {
                    $aspectBuckets[$aspect][$status]++;
                }
            }
        }

        $weightedScore = 0.0;
        $formattedAspects = [];

        foreach ($aspectBuckets as $key => $bucket) {
            $pct = $bucket['max_points'] > 0
                ? round(($bucket['earned_points'] / $bucket['max_points']) * 100, 1)
                : 0.0;

            $weightedScore += ($pct / 100) * $bucket['weight'];

            $formattedAspects[$key] = [
                'label' => $bucket['label'],
                'weight' => $bucket['weight'],
                'score_pct' => $pct,
                'total' => $bucket['total'],
                'healthy' => $bucket['healthy'],
                'warning' => $bucket['warning'],
                'broken' => $bucket['broken'],
                'empty' => $bucket['empty'],
            ];
        }

        return [
            ...$totals,
            'weighted_score' => round($weightedScore, 1),
            'aspects' => $formattedAspects,
        ];
    }

    private function evaluateLinkedPage(CriteriaItem $item, Page $page, ?SatkerSetting $satker, bool $probeExternal): CriteriaItem
    {
        if (! $page->is_published) {
            $item->forceFill([
                'health_status' => 'broken',
                'http_status' => 404,
                'estimated_score' => 0,
                'health_message' => 'Halaman tertaut belum dipublikasikan.',
                'last_checked_at' => now(),
            ])->save();

            return $item;
        }

        $blocks = $page->blocks ?? [];
        $warningReasons = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            if ($type === 'document_list' && ! empty($block['collection_type'])) {
                $docCount = Document::query()
                    ->where('collection_type', (string) $block['collection_type'])
                    ->where('is_published', true)
                    ->count();

                if ($docCount === 0) {
                    $warningReasons[] = 'Belum ada dokumen pada koleksi '.$block['collection_type'];
                }
            }

            if ($type === 'embed_fallback') {
                $fallbackMode = $block['fallback_mode'] ?? '';
                if ($fallbackMode === 'case_statistics' && ! CaseStatistic::query()->exists() && blank($satker?->sipp_url)) {
                    $warningReasons[] = 'Data statistik perkara cadangan dan URL SIPP masih kosong';
                } elseif ($fallbackMode === 'court_schedule' && ! CourtSchedule::query()->exists() && blank($satker?->sipp_url)) {
                    $warningReasons[] = 'Jadwal sidang cadangan dan URL SIPP masih kosong';
                } elseif ($fallbackMode === 'officials_table' && ! Official::query()->where('is_active', true)->exists() && blank($satker?->simtepa_pegawai_url)) {
                    $warningReasons[] = 'Data pejabat cadangan dan embed SIMTEPA pegawai masih kosong';
                } elseif ($fallbackMode === 'org_chart' && ! Official::query()->where('is_active', true)->exists() && blank($satker?->simtepa_struktur_url)) {
                    $warningReasons[] = 'Struktur organisasi cadangan dan embed SIMTEPA masih kosong';
                }
            }

            if ($type === 'external_portal' && ! empty($block['url_source']) && $satker !== null) {
                $field = (string) $block['url_source'];
                $portalUrl = $satker->getAttribute($field) ?: ($block['default_url'] ?? null);
                if (blank($portalUrl)) {
                    $warningReasons[] = 'Tautan portal eksternal belum diatur di Pengaturan Satker';
                } elseif ($probeExternal) {
                    $probe = $this->probeExternalUrl((string) $portalUrl, 'criteria', $item->code.' '.$item->name);
                    if (! $probe['ok']) {
                        $warningReasons[] = $probe['message'];
                    }
                }
            }
        }

        if ($warningReasons !== []) {
            $item->forceFill([
                'health_status' => 'warning',
                'http_status' => 200,
                'estimated_score' => 5,
                'health_message' => implode('; ', array_unique($warningReasons)),
                'last_checked_at' => now(),
            ])->save();

            return $item;
        }

        $item->forceFill([
            'health_status' => 'healthy',
            'http_status' => 200,
            'estimated_score' => 10,
            'health_message' => 'Halaman aktif dan konten tersedia.',
            'last_checked_at' => now(),
        ])->save();

        return $item;
    }

    private function evaluateUrl(CriteriaItem $item, string $url, bool $probeExternal, string $defaultOkMessage): CriteriaItem
    {
        if (! $probeExternal || ! str_starts_with($url, 'http')) {
            $item->forceFill([
                'health_status' => 'healthy',
                'http_status' => 200,
                'estimated_score' => 10,
                'health_message' => $defaultOkMessage,
                'last_checked_at' => now(),
            ])->save();

            return $item;
        }

        $probe = $this->probeExternalUrl($url, 'criteria', $item->code.' '.$item->name);

        $item->forceFill([
            'health_status' => $probe['ok'] ? 'healthy' : 'broken',
            'http_status' => $probe['status'],
            'estimated_score' => $probe['ok'] ? 10 : 0,
            'health_message' => $probe['message'],
            'last_checked_at' => now(),
        ])->save();

        return $item;
    }

    /**
     * @return array{ok: bool, status: ?int, message: string}
     */
    private function probeExternalUrl(string $url, string $sourceType, string $label): array
    {
        try {
            $response = Http::timeout(4)->withUserAgent('SatkerCMS-LinkChecker/1.0')->get($url);
            $status = $response->status();
            $ok = $status >= 200 && $status < 400;

            LinkCheckLog::query()->create([
                'source_type' => $sourceType,
                'source_label' => $label,
                'url' => $url,
                'http_status' => $status,
                'is_broken' => ! $ok,
                'error_message' => $ok ? null : "HTTP {$status}",
                'checked_at' => now(),
            ]);

            return [
                'ok' => $ok,
                'status' => $status,
                'message' => $ok ? "HTTP {$status} OK" : "Tautan mengembalikan HTTP {$status}",
            ];
        } catch (Throwable $e) {
            LinkCheckLog::query()->create([
                'source_type' => $sourceType,
                'source_label' => $label,
                'url' => $url,
                'http_status' => null,
                'is_broken' => true,
                'error_message' => mb_strimwidth($e->getMessage(), 0, 220, '...'),
                'checked_at' => now(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'message' => 'Gagal menjangkau URL: '.mb_strimwidth($e->getMessage(), 0, 120, '...'),
            ];
        }
    }

    private function auditMenuLinks(string $activeTingkat, bool $probeExternal): void
    {
        $menus = MenuItem::query()
            ->with('page')
            ->where('is_visible', true)
            ->get()
            ->filter(fn (MenuItem $menu): bool => $menu->tingkat_scope === 'all' || $menu->tingkat_scope === $activeTingkat);

        foreach ($menus as $menu) {
            if ($menu->type === 'group') {
                continue;
            }

            $url = $menu->resolvedUrl();
            $isBroken = false;
            $status = 200;
            $err = null;

            if ($menu->type === 'page') {
                if ($menu->page === null || ! $menu->page->is_published) {
                    $isBroken = true;
                    $status = 404;
                    $err = 'Halaman target tidak ditemukan atau belum dipublikasikan.';
                }
            } elseif ($url === '#' || trim($url) === '') {
                $isBroken = true;
                $status = 404;
                $err = 'URL menu kosong.';
            } elseif ($probeExternal && str_starts_with($url, 'http')) {
                $probe = $this->probeExternalUrl($url, 'menu', $menu->title);
                $isBroken = ! $probe['ok'];
                $status = $probe['status'];
                $err = $probe['ok'] ? null : $probe['message'];
            }

            if ($isBroken) {
                LinkCheckLog::query()->create([
                    'source_type' => 'menu',
                    'source_label' => $menu->title,
                    'url' => $url,
                    'http_status' => $status,
                    'is_broken' => true,
                    'error_message' => $err,
                    'checked_at' => now(),
                ]);
            }
        }
    }
}
