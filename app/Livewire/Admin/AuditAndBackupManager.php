<?php

namespace App\Livewire\Admin;

use App\Models\LinkCheckLog;
use App\Models\Redirect;
use App\Services\SinovaHealthCheckService;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.admin')]
class AuditAndBackupManager extends Component
{
    public string $tab = 'links';

    public string $from_path = '';

    public string $to_path = '';

    public int $status_code = 301;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function runLinkAudit(SinovaHealthCheckService $healthCheck): void
    {
        $this->authorizeModify();
        $counts = $healthCheck->checkAll(probeExternal: false);
        $this->statusNotice = "Pemeriksaan selesai: {$counts['checked']} tautan kriteria diperiksa ({$counts['healthy']} siap, {$counts['warning']} peringatan, {$counts['broken']} bermasalah).";
    }

    public function saveRedirect(): void
    {
        $this->authorizeModify();

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

        return view('livewire.admin.audit-and-backup-manager', [
            'linkLogs' => LinkCheckLog::query()->orderByDesc('is_broken')->orderByDesc('checked_at')->limit(50)->get(),
            'redirects' => Redirect::query()->orderByDesc('id')->get(),
            'activities' => Activity::query()->with('causer')->latest()->limit(50)->get(),
            'canModify' => auth()->user()?->hasAnyRole(['super_admin', 'admin_konten']) ?? false,
        ])->title('Tautan Rusak, Audit Log & Backup');
    }
}
