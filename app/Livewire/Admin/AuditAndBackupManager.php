<?php

namespace App\Livewire\Admin;

use App\Models\LinkCheckLog;
use App\Models\Redirect;
use App\Services\SinovaHealthCheckService;
use App\Support\MysqlDumpBinaryResolver;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('components.layouts.admin')]
class AuditAndBackupManager extends Component
{
    use WithPagination;

    public string $tab = 'links';

    public string $linkFilter = 'all';

    public string $redirectSearch = '';

    public string $from_path = '';

    public string $to_path = '';

    public int $status_code = 301;

    public ?string $statusNotice = null;

    public ?string $errorNotice = null;

    /** @var array<int, array{name: string, size: int, modified: int}> */
    public array $backups = [];

    public function mount(): void
    {
        $this->authorizeView();
        $this->loadBackups();
    }

    public function runBackup(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $this->statusNotice = null;
        $this->errorNotice = null;

        $this->ensureDumpBinaryConfigured();

        try {
            // shortcut: sinkron di request admin, cukup untuk database kecil; pindah ke queue bila arsip > 100 MB.
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);
            $output = trim(Artisan::output());
        } catch (Throwable $e) {
            report($e);
            $this->loadBackups();
            $this->errorNotice = 'Backup database gagal dijalankan: '.$this->summarizeBackupFailure($e->getMessage());

            return;
        }

        $this->loadBackups();

        if ($exitCode !== 0) {
            $reason = $this->summarizeBackupFailure($output);

            activity('backup')
                ->causedBy(auth()->id())
                ->withProperties([
                    'exit_code' => $exitCode,
                    'reason' => $reason,
                ])
                ->log('Backup database manual gagal dijalankan');

            $this->errorNotice = 'Backup database gagal dijalankan. '.$reason;

            return;
        }

        activity('backup')->causedBy(auth()->id())->log('Menjalankan backup database manual dari panel admin');

        $this->statusNotice = 'Backup database berhasil dibuat. Unduh arsip terbaru pada daftar di bawah.';
    }

    public function downloadBackup(string $name): StreamedResponse
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);
        abort_unless(preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name) === 1, 404);

        $path = $this->backupDirectory().'/'.$name;
        $disk = Storage::disk($this->backupDiskName());
        abort_unless($disk->exists($path), 404);

        activity('backup')->causedBy(auth()->id())->log('Mengunduh arsip backup: '.$name);

        return $disk->download($path);
    }

    private function loadBackups(): void
    {
        $disk = Storage::disk($this->backupDiskName());
        $directory = $this->backupDirectory();

        $this->backups = collect($disk->files($directory))
            ->filter(fn (string $file): bool => str_ends_with($file, '.zip'))
            ->map(fn (string $file): array => [
                'name' => basename($file),
                'size' => $disk->size($file),
                'modified' => $disk->lastModified($file),
            ])
            ->sortByDesc('modified')
            ->take(10)
            ->values()
            ->all();
    }

    private function backupDiskName(): string
    {
        $disks = config('backup.backup.destination.disks', ['local']);

        return is_array($disks) && isset($disks[0]) && is_string($disks[0]) && $disks[0] !== ''
            ? $disks[0]
            : 'local';
    }

    private function backupDirectory(): string
    {
        return trim((string) config('backup.backup.name', 'SatkerCMS'), '/');
    }

    private function ensureDumpBinaryConfigured(): void
    {
        $connection = (string) config('database.default', 'mysql');
        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            return;
        }

        $configured = (string) config("database.connections.{$connection}.dump.dump_binary_path", '');
        if ($configured !== '') {
            return;
        }

        $resolved = MysqlDumpBinaryResolver::resolve();
        if ($resolved !== '') {
            config()->set("database.connections.{$connection}.dump.dump_binary_path", $resolved);
        }
    }

    private function summarizeBackupFailure(string $output): string
    {
        $normalized = strtolower($output);

        if (str_contains($normalized, 'mysqldump') && (str_contains($normalized, 'not recognized') || str_contains($normalized, 'not found'))) {
            return 'Perintah mysqldump tidak ditemukan di peladen. Tetapkan MYSQL_DUMP_BINARY_PATH pada berkas .env ke direktori bin MySQL (misalnya C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin).';
        }

        if (str_contains($normalized, 'access denied')) {
            return 'Akses database ditolak saat dump. Periksa DB_USERNAME dan DB_PASSWORD pada berkas .env.';
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $output) ?: [])));
        foreach ($lines as $line) {
            if (stripos($line, 'Backup failed because:') !== false || stripos($line, 'Error') !== false) {
                return mb_strimwidth($line, 0, 220, '...');
            }
        }

        return 'Periksa log sistem di storage/logs/laravel.log untuk rincian teknis.';
    }

    public function updatingRedirectSearch(): void
    {
        $this->resetPage('redirectsPage');
    }

    public function updatingTab(): void
    {
        $this->resetPage('redirectsPage');
    }

    public function runLinkAudit(SinovaHealthCheckService $healthCheck): void
    {
        $this->authorizeModify();
        $this->statusNotice = null;
        $this->errorNotice = null;

        $counts = $healthCheck->checkAll(probeExternal: false);
        $this->statusNotice = "Pemeriksaan selesai: {$counts['checked']} kriteria SINOVA ({$counts['healthy']} siap, {$counts['warning']} peringatan, {$counts['broken']} bermasalah) dan {$counts['menu_checked']} tautan menu ({$counts['menu_broken']} bermasalah).";
    }

    public function saveRedirect(): void
    {
        $this->authorizeModify();
        $this->statusNotice = null;
        $this->errorNotice = null;

        $normalizedFrom = '/'.ltrim(trim($this->from_path), '/');
        $this->from_path = $normalizedFrom;

        $this->validate([
            'from_path' => ['required', 'string', 'max:255', Rule::unique('redirects', 'from_path')],
            'to_path' => ['required', 'string', 'max:500'],
            'status_code' => ['required', 'in:301,302'],
        ], [
            'from_path.required' => 'Path asal wajib diisi.',
            'from_path.unique' => 'Path asal sudah memiliki aturan pengalihan.',
            'to_path.required' => 'Tujuan pengalihan wajib diisi.',
        ]);

        if ($this->isReservedRedirectSource($normalizedFrom)) {
            $this->addError('from_path', 'Path asal tidak boleh menimpa rute sistem, setup, autentikasi, atau panel admin.');

            return;
        }

        $cleanTo = SafeHtml::cleanUrl($this->to_path);
        if ($cleanTo === null) {
            $this->addError('to_path', 'Tujuan pengalihan harus berupa path internal (/...) atau URL http(s) yang valid.');

            return;
        }

        if (! str_starts_with($cleanTo, '/') && ! auth()->user()?->hasRole('super_admin')) {
            $targetHost = strtolower((string) parse_url($cleanTo, PHP_URL_HOST));
            $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
            if ($targetHost === '' || $targetHost !== $appHost) {
                $this->addError('to_path', 'Hanya Super Admin yang dapat membuat pengalihan ke domain eksternal.');

                return;
            }
        }

        if ($cleanTo === $normalizedFrom) {
            $this->addError('to_path', 'Path asal dan tujuan pengalihan tidak boleh sama.');

            return;
        }

        if ($this->wouldCreateRedirectLoop($normalizedFrom, $cleanTo)) {
            $this->addError('to_path', 'Aturan ini akan menciptakan loop pengalihan berbalik dengan aturan yang sudah ada.');

            return;
        }

        Redirect::query()->create([
            'from_path' => $normalizedFrom,
            'to_path' => $cleanTo,
            'status_code' => $this->status_code,
        ]);

        $this->reset(['from_path', 'to_path']);
        $this->status_code = 301;
        $this->statusNotice = 'Aturan Redirect 301 berhasil ditambahkan.';
    }

    public function deleteRedirect(int $id): void
    {
        $this->authorizeModify();
        $this->statusNotice = null;
        $this->errorNotice = null;
        Redirect::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Aturan pengalihan berhasil dihapus.';
    }

    private function isReservedRedirectSource(string $normalizedPath): bool
    {
        $lower = strtolower($normalizedPath);
        $adminPath = strtolower(trim((string) config('satker.admin_path', 'panel-admin'), '/'));

        $reservedExact = [
            '/',
            '/'.$adminPath,
            '/setup',
            '/up',
            '/captcha-image',
            '/captcha-text',
            '/robots.txt',
            '/sitemap.xml',
            '/login',
            '/two-factor-challenge',
            '/_system',
        ];

        if (in_array($lower, $reservedExact, true)) {
            return true;
        }

        $reservedPrefixes = [
            '/'.$adminPath.'/',
            '/setup/',
            '/livewire/',
            '/livewire-',
            '/flux/',
            '/storage/',
            '/_system/',
        ];

        foreach ($reservedPrefixes as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function wouldCreateRedirectLoop(string $fromPath, string $toPath): bool
    {
        if (! str_starts_with($toPath, '/')) {
            return false;
        }

        $map = Redirect::query()->pluck('to_path', 'from_path')->all();
        $cursor = $toPath;
        $visited = [$fromPath => true];

        for ($hop = 0; $hop < 20; $hop++) {
            if (isset($visited[$cursor])) {
                return true;
            }

            if (! isset($map[$cursor])) {
                return false;
            }

            $visited[$cursor] = true;
            $cursor = (string) $map[$cursor];

            if (! str_starts_with($cursor, '/')) {
                return false;
            }
        }

        return true;
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && $user->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal']),
            403
        );
    }

    private function authorizeModify(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && $user->hasAnyRole(['super_admin', 'admin_konten']),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeView();

        $linkStats = [
            'broken' => 0,
            'healthy' => 0,
            'last_checked_at' => null,
        ];

        $linkLogs = collect();
        if ($this->tab === 'links') {
            $linkStats['broken'] = LinkCheckLog::query()->where('is_broken', true)->count();
            $linkStats['healthy'] = LinkCheckLog::query()->where('is_broken', false)->count();
            $linkStats['last_checked_at'] = LinkCheckLog::query()->max('checked_at');

            $linkLogs = LinkCheckLog::query()
                ->when($this->linkFilter === 'broken', fn ($q) => $q->where('is_broken', true))
                ->orderByDesc('is_broken')
                ->orderByDesc('checked_at')
                ->limit(100)
                ->get();
        }

        $redirects = $this->tab === 'redirects'
            ? Redirect::query()
                ->when(trim($this->redirectSearch) !== '', function ($q): void {
                    $term = '%'.trim($this->redirectSearch).'%';
                    $q->where(fn ($sub) => $sub->where('from_path', 'like', $term)->orWhere('to_path', 'like', $term));
                })
                ->orderByDesc('id')
                ->paginate(25, ['*'], 'redirectsPage')
            : null;

        $activities = $this->tab === 'activity'
            ? Activity::query()->with('causer')->latest()->limit(50)->get()
            : collect();

        return view('livewire.admin.audit-and-backup-manager', [
            'linkLogs' => $linkLogs,
            'linkStats' => $linkStats,
            'redirects' => $redirects,
            'activities' => $activities,
            'canModify' => auth()->user()?->hasAnyRole(['super_admin', 'admin_konten']) ?? false,
        ])->title('Tautan Rusak, Audit Log & Backup');
    }
}
