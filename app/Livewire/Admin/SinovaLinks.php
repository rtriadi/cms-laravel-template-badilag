<?php

namespace App\Livewire\Admin;

use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use App\Models\Page;
use App\Models\SatkerSetting;
use App\Services\SinovaHealthCheckService;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.admin')]
class SinovaLinks extends Component
{
    /** @var array<int, bool> */
    public array $expandedGroups = [];

    #[Locked]
    public ?int $editingItemId = null;

    public string $editingManualUrl = '';

    public bool $editingUseManual = false;

    public ?int $editingLinkedPageId = null;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeView();

        $defaultExpanded = CriteriaGroup::query()
            ->where('code', '1.a.2')
            ->value('id');

        if ($defaultExpanded !== null) {
            $this->expandedGroups[(int) $defaultExpanded] = true;
        }
    }

    public function openAll(): void
    {
        foreach (CriteriaGroup::query()->pluck('id') as $id) {
            $this->expandedGroups[(int) $id] = true;
        }
    }

    public function closeAll(): void
    {
        $this->expandedGroups = [];
    }

    public function toggleGroup(int $groupId): void
    {
        $this->expandedGroups[$groupId] = ! ($this->expandedGroups[$groupId] ?? false);
    }

    public function startEdit(int $itemId): void
    {
        $this->authorizeEdit();

        $item = CriteriaItem::query()->findOrFail($itemId);
        $this->editingItemId = $item->id;
        $this->editingManualUrl = (string) ($item->manual_url ?? '');
        $this->editingUseManual = (bool) $item->use_manual_url;
        $this->editingLinkedPageId = $item->linked_page_id;
    }

    public function cancelEdit(): void
    {
        $this->editingItemId = null;
        $this->resetValidation();
    }

    public function saveEdit(SinovaHealthCheckService $healthCheck): void
    {
        $this->authorizeEdit();

        $this->validate([
            'editingManualUrl' => ['nullable', 'string', 'max:500'],
            'editingLinkedPageId' => ['nullable', 'exists:pages,id'],
        ], [
            'editingManualUrl.max' => 'URL manual maksimal 500 karakter.',
        ]);

        $trimmedManualUrl = trim($this->editingManualUrl);

        if ($this->editingUseManual && $trimmedManualUrl === '') {
            $this->addError('editingManualUrl', 'Isi URL manual terlebih dahulu jika mode URL manual diaktifkan.');

            return;
        }

        $cleanManualUrl = null;
        if ($trimmedManualUrl !== '') {
            $cleanManualUrl = SafeHtml::cleanUrl($trimmedManualUrl);
            if ($cleanManualUrl === null) {
                $this->addError('editingManualUrl', 'URL harus diawali http(s):// atau jalur relatif /.');

                return;
            }
        }

        $item = CriteriaItem::query()->findOrFail($this->editingItemId);
        $item->update([
            'manual_url' => $cleanManualUrl,
            'use_manual_url' => $this->editingUseManual,
            'linked_page_id' => $this->editingLinkedPageId ?: null,
        ]);

        $healthCheck->checkItem($item, SatkerSetting::current(), probeExternal: false);

        $this->editingItemId = null;
        $this->statusNotice = "Tautan kriteria {$item->code} berhasil diperbarui dan berlaku sampai Anda mengubahnya.";
    }

    public function runHealthCheck(SinovaHealthCheckService $healthCheck): void
    {
        $this->authorizeEdit();

        $counts = $healthCheck->checkAll(probeExternal: false);
        $this->statusNotice = "Audit kesehatan selesai: {$counts['healthy']} siap, {$counts['warning']} perlu perbaikan, {$counts['broken']} bermasalah, {$counts['empty']} kosong.";
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorizeView();

        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';

        $groups = CriteriaGroup::query()
            ->with(['items.linkedPage', 'items.linkedMenu'])
            ->orderBy('sort_order')
            ->get();

        $filename = 'link-sinova-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($groups, $satker, $activeTingkat): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Grup', 'Kode', 'Kriteria', 'URL', 'Status', 'Perkiraan Skor', 'Catatan']);

            foreach ($groups as $group) {
                foreach ($group->items as $item) {
                    if (! $item->isApplicableFor($activeTingkat)) {
                        continue;
                    }

                    fputcsv($out, [
                        SafeHtml::sanitizeCsvCell("{$group->code} - {$group->name}"),
                        SafeHtml::sanitizeCsvCell($item->code),
                        SafeHtml::sanitizeCsvCell($item->name),
                        SafeHtml::sanitizeCsvCell($item->resolvedUrl($satker) ?? ''),
                        SafeHtml::sanitizeCsvCell($item->health_status),
                        SafeHtml::sanitizeCsvCell($item->estimated_score),
                        SafeHtml::sanitizeCsvCell($item->health_message ?? ''),
                    ]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('view-compliance-dashboard') || $user->can('manage-sinova-links') || $user->hasRole('super_admin')),
            403
        );
    }

    private function authorizeEdit(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-sinova-links') || $user->hasRole('super_admin')),
            403
        );
    }

    public function render(SinovaHealthCheckService $healthCheck): View
    {
        $this->authorizeView();

        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';

        $groups = CriteriaGroup::query()
            ->with(['items.linkedPage', 'items.linkedMenu'])
            ->orderBy('sort_order')
            ->get()
            ->map(function (CriteriaGroup $group) use ($activeTingkat, $satker): array {
                $items = $group->items
                    ->filter(fn (CriteriaItem $item): bool => $item->isApplicableFor($activeTingkat))
                    ->map(fn (CriteriaItem $item): array => [
                        'model' => $item,
                        'url' => $item->resolvedUrl($satker),
                    ])
                    ->values();

                $filled = $items->filter(fn (array $row): bool => filled($row['url']))->count();

                return [
                    'group' => $group,
                    'items' => $items,
                    'total' => $items->count(),
                    'filled' => $filled,
                    'all_urls_text' => $items->pluck('url')->filter()->implode("\n"),
                ];
            })
            ->filter(fn (array $entry): bool => $entry['total'] > 0)
            ->values();

        $summary = $healthCheck->scoreSummary($activeTingkat);

        return view('livewire.admin.sinova-links', [
            'groups' => $groups,
            'summary' => $summary,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'slug']),
            'canEdit' => auth()->user()?->can('manage-sinova-links') || auth()->user()?->hasRole('super_admin'),
        ])->title('Link Kriteria SINOVA');
    }
}
