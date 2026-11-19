<?php

namespace App\Livewire\Admin;

use App\Models\CriteriaItem;
use App\Models\LinkCheckLog;
use App\Models\PageVisit;
use App\Models\SatkerSetting;
use App\Services\SinovaHealthCheckService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public ?string $auditMessage = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('view-compliance-dashboard') || $user->hasRole('super_admin')),
            403
        );
    }

    public function runQuickAudit(SinovaHealthCheckService $healthCheck): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-sinova-links') || $user->hasRole('super_admin')),
            403
        );

        $counts = $healthCheck->checkAll(probeExternal: false);
        $this->auditMessage = "Pemeriksaan selesai: {$counts['healthy']} siap, {$counts['warning']} perlu perbaikan, {$counts['broken']} bermasalah, {$counts['empty']} kosong.";
    }

    public function render(SinovaHealthCheckService $healthCheck): View
    {
        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';
        $summary = $healthCheck->scoreSummary($activeTingkat);

        $attentionItems = CriteriaItem::query()
            ->with(['group', 'linkedPage'])
            ->whereIn('health_status', ['broken', 'warning', 'empty'])
            ->orderBy('criteria_group_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (CriteriaItem $item): bool => $item->isApplicableFor($activeTingkat))
            ->take(10);

        $recentActivities = Activity::query()
            ->with('causer')
            ->latest('id')
            ->limit(8)
            ->get();

        $totalVisits = (int) PageVisit::query()->sum('visits_count');
        $brokenLinksCount = LinkCheckLog::query()->where('is_broken', true)->count();

        return view('livewire.admin.dashboard', [
            'satker' => $satker,
            'summary' => $summary,
            'attentionItems' => $attentionItems,
            'recentActivities' => $recentActivities,
            'totalVisits' => $totalVisits,
            'brokenLinksCount' => $brokenLinksCount,
            'phpNearEol' => version_compare(PHP_VERSION, '8.4.0', '<'),
        ])->title('Dashboard Kepatuhan SINOVA');
    }
}
