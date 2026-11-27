<?php

namespace App\Livewire\Admin;

use App\Models\CriteriaItem;
use App\Models\LinkCheckLog;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\CmsUpdateCheckService;
use App\Services\CmsUpdateQueueService;
use App\Services\CmsZeroDowntimeUpdater;
use App\Services\SinovaHealthCheckService;
use App\Support\CmsVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Throwable;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public ?string $auditMessage = null;

    public ?string $updateNotice = null;

    public ?string $updateError = null;

    public bool $showUpdateConfirm = false;

    public bool $skipUpdateBackup = false;

    public bool $showRollbackConfirm = false;

    public string $quickDraftTitle = '';

    public string $quickDraftCategory = 'berita';

    public string $quickDraftBody = '';

    public ?string $quickDraftNotice = null;

    public function mount(): void
    {
        $this->authorizeView();
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

    public function checkForUpdates(CmsUpdateCheckService $updateChecker): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && $user->hasAnyRole(['super_admin', 'admin_konten']),
            403
        );

        $this->updateNotice = null;
        $this->updateError = null;

        $state = $updateChecker->check(force: true);

        activity('system')
            ->causedBy($user->id)
            ->withProperties([
                'channel' => $state['channel'] ?? 'stable',
                'installed_version' => $state['installed_version'] ?? CmsVersion::current(),
                'latest_version' => $state['latest_version'] ?? null,
                'update_available' => (bool) ($state['update_available'] ?? false),
            ])
            ->log('Memeriksa ketersediaan pembaruan SatkerCMS dari GitHub');

        if (! empty($state['last_error'])) {
            $this->updateError = (string) $state['last_error'];

            return;
        }

        if (($state['update_available'] ?? false) === true) {
            $this->updateNotice = 'Tersedia pembaruan baru versi '.($state['latest_version'] ?? '').'. Gunakan perintah terminal pada kotak informasi di bawah untuk memperbarui server.';

            return;
        }

        $this->updateNotice = 'SatkerCMS sudah menggunakan versi terbaru ('.($state['installed_version'] ?? CmsVersion::current()).').';
    }

    public function queueWebUpdate(CmsUpdateCheckService $updateChecker, CmsUpdateQueueService $queue): void
    {
        $user = $this->authorizeWebTrigger();

        $this->updateNotice = null;
        $this->updateError = null;

        $state = $updateChecker->getCachedState();
        $targetTag = trim((string) ($state['latest_tag'] ?? ''));

        if (($state['channel'] ?? 'stable') !== 'stable' || ($state['update_available'] ?? false) !== true || $targetTag === '') {
            $this->updateError = 'Tidak ada rilis stabil baru yang siap dijadwalkan saat ini. Klik Cek Pembaruan terlebih dahulu.';

            return;
        }

        try {
            $queue->enqueueUpdate(
                targetTag: $targetTag,
                skipBackup: $this->skipUpdateBackup,
                userId: $user->id,
                userName: (string) $user->name,
                fromVersion: CmsVersion::current(),
            );
        } catch (Throwable $e) {
            $this->updateError = $e->getMessage();

            return;
        }

        activity('system')
            ->causedBy($user->id)
            ->withProperties([
                'action' => 'update',
                'target_tag' => $targetTag,
                'skip_backup' => $this->skipUpdateBackup,
            ])
            ->log('Menjadwalkan pembaruan SatkerCMS melalui antrean cron Panel Admin');

        $this->showUpdateConfirm = false;
        $this->skipUpdateBackup = false;
        $this->updateNotice = "Pembaruan ke {$targetTag} berhasil masuk antrean. Cron schedule:run akan mengeksekusinya dalam <= 1 menit.";
    }

    public function queueWebRollback(CmsZeroDowntimeUpdater $updater, CmsUpdateQueueService $queue): void
    {
        $user = $this->authorizeWebTrigger();

        $this->updateNotice = null;
        $this->updateError = null;

        if (! $updater->hasRollbackCandidate()) {
            $this->updateError = 'Tidak tersedia rilis sebelumnya atau snapshot cadangan untuk rollback.';

            return;
        }

        try {
            $queue->enqueueRollback(
                userId: $user->id,
                userName: (string) $user->name,
                fromVersion: CmsVersion::current(),
            );
        } catch (Throwable $e) {
            $this->updateError = $e->getMessage();

            return;
        }

        activity('system')
            ->causedBy($user->id)
            ->withProperties(['action' => 'rollback'])
            ->log('Menjadwalkan rollback SatkerCMS melalui antrean cron Panel Admin');

        $this->showRollbackConfirm = false;
        $this->updateNotice = 'Permintaan rollback masuk antrean. Cron schedule:run akan memulihkan versi sebelumnya dalam <= 1 menit.';
    }

    public function cancelQueuedUpdate(CmsUpdateQueueService $queue): void
    {
        $user = $this->authorizeWebTrigger();

        $this->updateNotice = null;
        $this->updateError = null;

        if ($queue->cancelQueued()) {
            activity('system')
                ->causedBy($user->id)
                ->log('Membatalkan antrean pembaruan SatkerCMS sebelum dieksekusi cron');

            $this->updateNotice = 'Antrean pembaruan berhasil dibatalkan sebelum dijalankan cron.';
        }
    }

    public function dismissUpdateJob(CmsUpdateQueueService $queue): void
    {
        $this->authorizeWebTrigger();
        $status = $queue->getJobStatus();
        if ($status !== null && in_array((string) ($status['status'] ?? ''), ['succeeded', 'failed'], true)) {
            $queue->clearJobStatus();
        }
    }

    private function authorizeWebTrigger(): User
    {
        $user = auth()->user();
        abort_unless(
            $user !== null
                && $user->hasRole('super_admin')
                && (bool) config('satker.update.web_trigger_enabled', true),
            403
        );

        return $user;
    }

    public function saveQuickDraft(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-ppid-humas') || $user->hasAnyRole(['super_admin', 'admin_konten'])),
            403
        );

        $this->validate([
            'quickDraftTitle' => ['required', 'string', 'min:3', 'max:255'],
            'quickDraftCategory' => ['required', Rule::in(array_keys(Post::categoryLabels()))],
            'quickDraftBody' => ['required', 'string', 'min:10'],
        ], [
            'quickDraftTitle.required' => 'Judul draf cepat wajib diisi.',
            'quickDraftTitle.min' => 'Judul draf minimal 3 karakter.',
            'quickDraftBody.required' => 'Catatan atau isi draf wajib diisi.',
            'quickDraftBody.min' => 'Isi draf minimal 10 karakter.',
        ]);

        $baseSlug = Str::slug($this->quickDraftTitle) ?: 'draf-publikasi';
        $slug = $baseSlug;
        $suffix = 2;
        while (Post::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        $escapedBody = '<p>'.nl2br(e(trim($this->quickDraftBody))).'</p>';

        Post::query()->create([
            'title' => trim($this->quickDraftTitle),
            'slug' => $slug,
            'category' => $this->quickDraftCategory,
            'excerpt' => Str::limit(trim(preg_replace('/\s+/', ' ', $this->quickDraftBody) ?: ''), 140),
            'body' => $escapedBody,
            'is_pinned' => false,
            'is_published' => false,
            'comments_enabled' => true,
            'published_at' => null,
            'author_id' => $user->id,
        ]);

        $this->quickDraftTitle = '';
        $this->quickDraftBody = '';
        $this->quickDraftNotice = 'Draf cepat berhasil disimpan ke Daftar Berita & Publikasi (status: Draf).';
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('view-compliance-dashboard') || $user->hasRole('super_admin')),
            403
        );
    }

    public function render(
        SinovaHealthCheckService $healthCheck,
        CmsUpdateCheckService $updateChecker,
        CmsUpdateQueueService $updateQueue,
        CmsZeroDowntimeUpdater $updater,
    ): View {
        $this->authorizeView();

        $satker = SatkerSetting::current();
        $activeTingkat = $satker?->tingkat ?? 'pertama';
        $summary = $healthCheck->scoreSummary($activeTingkat);

        $attentionItems = CriteriaItem::query()
            ->with(['group:id,code,name,aspect', 'linkedPage:id,slug,title,is_published'])
            ->whereIn('health_status', ['broken', 'warning', 'empty'])
            ->orderBy('criteria_group_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (CriteriaItem $item): bool => $item->isApplicableFor($activeTingkat))
            ->take(10);

        $user = auth()->user();
        $canWritePosts = $user !== null && ($user->can('manage-ppid-humas') || $user->hasAnyRole(['super_admin', 'admin_konten']));
        $canModerateComments = $user !== null && $user->can('viewAny', PostComment::class);
        $canViewAudit = $user !== null && $user->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal']);
        $canManageUpdates = $user !== null && $user->hasAnyRole(['super_admin', 'admin_konten']);
        $canTriggerWebUpdate = $user !== null
            && $user->hasRole('super_admin')
            && (bool) config('satker.update.web_trigger_enabled', true);
        $updateState = $canManageUpdates ? $updateChecker->checkIfStale() : null;
        $updateJob = $canTriggerWebUpdate ? $updateQueue->getJobStatus() : null;
        $hasRollbackCandidate = $canTriggerWebUpdate && $updater->hasRollbackCandidate();

        $recentActivities = $canViewAudit
            ? Activity::query()
                ->with('causer')
                ->latest('id')
                ->limit(6)
                ->get()
            : collect();

        $recentPostsQuery = Post::query()
            ->select(['id', 'title', 'slug', 'category', 'is_published', 'views_count', 'updated_at'])
            ->when(! $canWritePosts, fn ($q) => $q->publishedActive());

        if ($canModerateComments) {
            $recentPostsQuery->withCount(['approvedComments', 'pendingComments']);
        } else {
            $recentPostsQuery->withCount(['approvedComments']);
        }

        $recentPosts = $recentPostsQuery
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $publishedPostsCount = Post::query()->publishedActive()->count();

        $pendingCommentsCount = $canModerateComments
            ? PostComment::query()
                ->where('status', PostComment::STATUS_PENDING)
                ->count()
            : 0;

        $totalVisits = (int) PageVisit::query()->sum('visits_count');
        $brokenLinksCount = $canViewAudit
            ? LinkCheckLog::query()->where('is_broken', true)->count()
            : 0;

        return view('livewire.admin.dashboard', [
            'satker' => $satker,
            'summary' => $summary,
            'attentionItems' => $attentionItems,
            'recentActivities' => $recentActivities,
            'recentPosts' => $recentPosts,
            'publishedPostsCount' => $publishedPostsCount,
            'pendingCommentsCount' => $pendingCommentsCount,
            'canWritePosts' => $canWritePosts,
            'canModerateComments' => $canModerateComments,
            'canViewAudit' => $canViewAudit,
            'canManageUpdates' => $canManageUpdates,
            'canTriggerWebUpdate' => $canTriggerWebUpdate,
            'updateState' => $updateState,
            'updateJob' => $updateJob,
            'hasRollbackCandidate' => $hasRollbackCandidate,
            'autoUpdateEnabled' => (bool) config('satker.update.auto_update_enabled', false),
            'cmsVersion' => CmsVersion::current(),
            'categories' => Post::categoryLabels(),
            'totalVisits' => $totalVisits,
            'brokenLinksCount' => $brokenLinksCount,
            'phpNearEol' => version_compare(PHP_VERSION, '8.4.0', '<'),
        ])->title('Dashboard Studio & Kepatuhan SINOVA');
    }
}
