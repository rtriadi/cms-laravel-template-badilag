@php
    $user = auth()->user();
    $canManageSinova = $user?->can('manage-sinova-links') || $user?->hasRole('super_admin');
    $canManageDocs = $user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']);
    $canManageKepaniteraan = $user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasAnyRole(['super_admin', 'admin_konten']);
    $canManageSliders = $user?->can('manage-pages') || $user?->hasAnyRole(['super_admin', 'admin_konten']);

    $jobStatus = $updateJob['status'] ?? null;
    $isJobActive = in_array($jobStatus, ['queued', 'running'], true);

    $priorityTasks = [];
    if (($canModerateComments ?? false) && $pendingCommentsCount > 0) {
        $priorityTasks[] = [
            'tone' => 'danger',
            'title' => $pendingCommentsCount.' komentar masyarakat menunggu moderasi',
            'desc' => 'Periksa komentar warga sebelum ditayangkan dan berikan tanggapan resmi satker bila diperlukan.',
            'cta' => 'Tinjau Komentar',
            'href' => route('admin.comments', ['status' => 'pending']),
            'primary' => true,
        ];
    }
    $sinovaProblemCount = (int) ($summary['broken'] ?? 0) + (int) ($summary['empty'] ?? 0);
    if ($canManageSinova && $sinovaProblemCount > 0) {
        $priorityTasks[] = [
            'tone' => 'danger',
            'title' => $sinovaProblemCount.' butir kriteria SINOVA bermasalah atau belum terisi',
            'desc' => 'Lengkapi tautan halaman atau URL tujuan agar skor kesiapan portal tetap optimal.',
            'cta' => 'Perbaiki Tautan',
            'href' => route('admin.sinova-links'),
            'primary' => true,
        ];
    }
    if ($canManageSinova && (int) ($summary['warning'] ?? 0) > 0) {
        $priorityTasks[] = [
            'tone' => 'warning',
            'title' => $summary['warning'].' butir kriteria SINOVA memerlukan revisi',
            'desc' => 'Tinjau catatan pemeriksaan otomatis pada kokpit Link SINOVA.',
            'cta' => 'Lihat Daftar',
            'href' => route('admin.sinova-links'),
            'primary' => false,
        ];
    }
    if (($canWritePosts ?? false) && ($draftPostsCount ?? 0) > 0) {
        $priorityTasks[] = [
            'tone' => 'neutral',
            'title' => $draftPostsCount.' draf publikasi belum ditayangkan',
            'desc' => 'Lanjutkan penyuntingan berita, pengumuman, atau artikel yang masih berstatus draf.',
            'cta' => 'Buka Draf',
            'href' => route('admin.posts'),
            'primary' => false,
        ];
    }

    $quickShortcuts = collect([
        ['show' => $canWritePosts ?? false, 'title' => 'Tulis berita', 'desc' => 'Berita, pengumuman, atau artikel', 'href' => route('admin.posts'), 'icon' => 'news'],
        ['show' => $canManageDocs, 'title' => 'Unggah dokumen PDF', 'desc' => 'LKjIP, DIPA, Renstra, SK', 'href' => route('admin.documents'), 'icon' => 'upload'],
        ['show' => $canManageKepaniteraan, 'title' => 'Data kepaniteraan', 'desc' => 'Jadwal sidang, perkara & aparatur', 'href' => route('admin.kepaniteraan'), 'icon' => 'calendar'],
        ['show' => $canManageSliders, 'title' => 'Ganti slider beranda', 'desc' => 'Maksimal 5 gambar, tanpa video', 'href' => route('admin.sliders'), 'icon' => 'photo'],
    ])->filter(fn (array $item): bool => (bool) $item['show'])->values();
@endphp

<div class="space-y-6">
    @if ($phpNearEol)
        <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Versi PHP saat ini ({{ PHP_VERSION }}) berada di bawah PHP 8.4. Segera perbarui versi PHP ke cabang stabil terbaru (PHP 8.5) sesuai syarat non-EOL SK Dirjen Badilag.
        </div>
    @endif

    @if ($auditMessage)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $auditMessage }}
        </div>
    @endif

    @if ($updateNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $updateNotice }}
        </div>
    @endif

    @if ($updateError)
        <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
            {{ $updateError }}
        </div>
    @endif

    @if (($canTriggerWebUpdate ?? false) && $updateJob !== null)
        <section
            aria-label="Status Antrean Pembaruan Sistem"
            @if ($isJobActive) wire:poll.5s @endif
            @class([
                'rounded-xl border p-4 sm:p-5',
                'border-emerald-200 bg-primary-soft' => in_array($jobStatus, ['queued', 'running', 'succeeded'], true),
                'border-red-300 bg-red-50' => $jobStatus === 'failed',
            ])
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h3 @class([
                        'font-serif text-base font-bold',
                        'text-ink' => $jobStatus !== 'failed',
                        'text-red-800' => $jobStatus === 'failed',
                    ])>
                        @if ($jobStatus === 'queued')
                            Antrean Pembaruan Menunggu Cron
                        @elseif ($jobStatus === 'running')
                            Pembaruan Sedang Berjalan di Peladen
                        @elseif ($jobStatus === 'succeeded')
                            Pembaruan SatkerCMS Selesai
                        @else
                            Pembaruan SatkerCMS Gagal
                        @endif
                    </h3>
                    <p @class([
                        'text-sm leading-relaxed',
                        'text-ink-body' => $jobStatus !== 'failed',
                        'text-red-900' => $jobStatus === 'failed',
                    ])>
                        {{ $updateJob['message'] ?? '' }}
                    </p>
                    @if (! empty($updateJob['output_excerpt']))
                        <details class="pt-1">
                            <summary class="cursor-pointer text-xs font-semibold text-primary underline underline-offset-4">
                                Lihat Ringkasan Log Eksekusi
                            </summary>
                            <pre class="mt-2 max-h-48 overflow-auto rounded-lg border border-hairline bg-obsidian p-3 font-mono text-[11px] leading-relaxed text-white">{{ $updateJob['output_excerpt'] }}</pre>
                        </details>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if ($jobStatus === 'queued')
                        <button type="button" wire:click="cancelQueuedUpdate" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-amber-700/40 bg-white px-3.5 text-xs font-semibold text-amber-900 hover:bg-amber-50 sm:min-h-9">
                            Batalkan Antrean
                        </button>
                    @elseif ($jobStatus === 'succeeded')
                        <button type="button" onclick="window.location.reload()" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-3.5 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-9">
                            Muat Ulang Halaman
                        </button>
                        <button type="button" wire:click="dismissUpdateJob" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink-body hover:bg-mist sm:min-h-9">
                            Tutup
                        </button>
                    @elseif ($jobStatus === 'failed')
                        <button type="button" wire:click="dismissUpdateJob" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-red-300 bg-white px-3.5 text-xs font-semibold text-red-700 hover:bg-red-50 sm:min-h-9">
                            Tutup
                        </button>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if (($canManageUpdates ?? false) && (($updateState['update_available'] ?? false) === true))
        @php
            $isStableChannel = ($updateState['channel'] ?? 'stable') === 'stable';
            $updateCliCommand = $isStableChannel ? 'php artisan cms:update' : 'git pull && php artisan cms:update --local-only';
            $targetDisplayTag = (string) ($updateState['latest_tag'] ?? ('v'.($updateState['latest_version'] ?? '')));
        @endphp
        <section aria-label="Informasi Pembaruan Aplikasi" class="rounded-xl border border-emerald-200 bg-primary-soft p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-serif text-base font-bold text-ink">Pembaruan SatkerCMS Tersedia</h3>
                        <span class="rounded-md border border-primary/30 bg-white px-2 py-0.5 font-mono text-xs font-semibold text-primary">
                            {{ $updateState['installed_version'] ?? $cmsVersion }} &rarr; {{ $updateState['latest_version'] }}
                        </span>
                    </div>
                    <p class="text-sm leading-relaxed text-ink-body">
                        Rilis baru terdeteksi dari repositori resmi GitHub. Anda dapat menjadwalkan pembaruan lewat antrean cron atau menjalankan perintah terminal secara manual.
                    </p>
                    @if (! empty($updateState['release_notes']))
                        <p class="whitespace-pre-line rounded-lg border border-hairline bg-white px-3 py-2 text-xs leading-relaxed text-ink-body">{{ $updateState['release_notes'] }}</p>
                    @endif
                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        @if (($canTriggerWebUpdate ?? false) && $isStableChannel && ! $isJobActive)
                            <button type="button" wire:click="$toggle('showUpdateConfirm')" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-9">
                                Perbarui ke {{ $targetDisplayTag }}
                            </button>
                        @endif
                        @if (! empty($updateState['release_url']))
                            <a href="{{ $updateState['release_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center text-xs font-semibold text-primary underline underline-offset-4 hover:text-primary-hover sm:min-h-0">
                                Lihat Catatan Rilis di GitHub
                            </a>
                        @endif
                    </div>

                    @if (($canTriggerWebUpdate ?? false) && $showUpdateConfirm && $isStableChannel && ! $isJobActive)
                        <div class="mt-3 space-y-3 rounded-lg border border-emerald-200 bg-white p-4">
                            <p class="text-xs leading-relaxed text-ink">
                                Tiket pembaruan bertanda tangan akan diproses oleh cron <code class="font-mono text-primary">schedule:run</code> dalam waktu maksimal 1 menit.
                            </p>
                            <label class="flex cursor-pointer items-start gap-2.5 text-xs text-ink-body">
                                <input type="checkbox" wire:model="skipUpdateBackup" class="mt-0.5 size-4 rounded border-[#768780] text-primary">
                                <span>Lewati backup database otomatis (centang hanya jika Anda sudah mengunduh backup manual atau perintah <code class="font-mono">mysqldump</code> dibatasi hosting).</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="queueWebUpdate" wire:loading.attr="disabled" wire:target="queueWebUpdate" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover disabled:opacity-60 sm:min-h-9">
                                    <span wire:loading.remove wire:target="queueWebUpdate">Konfirmasi &amp; Jadwalkan Pembaruan</span>
                                    <span wire:loading wire:target="queueWebUpdate">Menyimpan antrean...</span>
                                </button>
                                <button type="button" wire:click="$set('showUpdateConfirm', false)" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink-body hover:bg-mist sm:min-h-9">
                                    Batal
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div x-data="{ copied: false, cmd: @js($updateCliCommand) }" class="w-full shrink-0 rounded-lg border border-obsidian bg-obsidian p-3 text-white lg:w-auto lg:min-w-[300px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono text-[11px] font-medium text-[#d3e8df]">Perintah Terminal Peladen</span>
                        <button type="button" x-on:click="navigator.clipboard?.writeText(cmd); copied = true; setTimeout(() => copied = false, 2000)" class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-white/25 bg-[#0d4434] px-2.5 text-xs font-semibold text-white hover:bg-primary sm:min-h-8">
                            <span x-show="!copied">Salin</span>
                            <span x-cloak x-show="copied">Tersalin</span>
                        </button>
                    </div>
                    <code class="mt-2 block overflow-x-auto font-mono text-xs text-white">{{ $updateCliCommand }}</code>
                </div>
            </div>
        </section>
    @endif

    @if (($canTriggerWebUpdate ?? false) && ($hasRollbackCandidate ?? false) && ! $isJobActive)
        <div class="studio-card px-4 py-3.5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-relaxed text-ink-body">
                    Tersedia cadangan versi sebelumnya pada peladen. Jika ditemukan kendala pasca-pembaruan, Anda dapat memulihkan versi sebelumnya lewat antrean cron.
                </p>
                <button type="button" wire:click="$toggle('showRollbackConfirm')" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-mist px-3.5 text-xs font-semibold text-ink hover:border-amber-600/40 hover:bg-amber-50 hover:text-amber-900 sm:min-h-9">
                    Rollback ke Versi Sebelumnya
                </button>
            </div>
            @if ($showRollbackConfirm)
                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-hairline pt-3">
                    <span class="text-xs font-medium text-amber-900">Yakin mengembalikan aplikasi ke rilis sebelumnya?</span>
                    <button type="button" wire:click="queueWebRollback" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-amber-800 px-3.5 text-xs font-semibold text-white hover:bg-amber-900 sm:min-h-9">
                        Ya, Jadwalkan Rollback
                    </button>
                    <button type="button" wire:click="$set('showRollbackConfirm', false)" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink-body sm:min-h-9">
                        Batal
                    </button>
                </div>
            @endif
        </div>
    @endif

    <!-- Page Header: Greeting & Primary Check Actions -->
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <p class="text-xs font-semibold text-primary">
                {{ \App\Support\Tanggal::format(now(), 'l, d F Y') }}
            </p>
            <h1 class="mt-1 font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px] sm:leading-tight">
                Selamat datang, {{ $user?->name ?? 'Pengelola' }}
            </h1>
            <p class="mt-1 text-sm text-ink-body">
                Berikut hal yang perlu diselesaikan agar website satker tetap lengkap dan sesuai penilaian SINOVA (SK Dirjen Badilag 2722/2026).
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
            @if ($canManageUpdates ?? false)
                <button
                    type="button"
                    wire:click="checkForUpdates"
                    wire:loading.attr="disabled"
                    wire:target="checkForUpdates"
                    class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink transition-colors hover:border-primary/50 hover:bg-primary-soft hover:text-primary disabled:opacity-60 sm:min-h-10"
                >
                    <span wire:loading.remove wire:target="checkForUpdates">Cek Pembaruan</span>
                    <span wire:loading wire:target="checkForUpdates">Memeriksa GitHub...</span>
                </button>
            @endif
            @if ($canManageSinova)
                <button
                    type="button"
                    wire:click="runQuickAudit"
                    wire:loading.attr="disabled"
                    wire:target="runQuickAudit"
                    class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink transition-colors hover:border-primary hover:bg-primary-soft hover:text-primary disabled:opacity-60 sm:min-h-10"
                >
                    <span wire:loading.remove wire:target="runQuickAudit">Cek Semua Tautan</span>
                    <span wire:loading wire:target="runQuickAudit">Memeriksa tautan...</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Row 1: Priority Action Queue (8 cols) + SINOVA Readiness Summary (4 cols) -->
    <div class="grid items-start gap-6 lg:grid-cols-12">
        <section aria-label="Perlu Tindakan Anda" class="studio-card overflow-hidden lg:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-base font-bold text-ink">Perlu tindakan Anda</h2>
                    <span @class([
                        'rounded-md px-2 py-0.5 text-xs font-semibold',
                        'bg-[#fff4e0] text-[#8a4b00]' => count($priorityTasks) > 0,
                        'bg-emerald-50 text-emerald-800' => count($priorityTasks) === 0,
                    ])>
                        {{ count($priorityTasks) > 0 ? count($priorityTasks).' tugas' : 'Semua beres' }}
                    </span>
                </div>
                @if ($canViewAudit ?? false)
                    <a href="{{ route('admin.audit') }}" class="text-xs font-semibold text-primary hover:underline">
                        Lihat riwayat audit
                    </a>
                @endif
            </div>

            @if ($priorityTasks === [])
                <div class="px-5 py-10 text-center sm:px-6">
                    <p class="text-sm font-semibold text-ink">Tidak ada tugas mendesak saat ini.</p>
                    <p class="mt-1 text-xs text-ink-muted">
                        Semua komentar telah dimoderasi dan tautan kriteria SINOVA dalam kondisi baik. Anda dapat menulis publikasi baru atau memeriksa arsip dokumen.
                    </p>
                </div>
            @else
                <ul class="divide-y divide-hairline/70">
                    @foreach ($priorityTasks as $task)
                        <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="flex min-w-0 items-start gap-3.5">
                                <span @class([
                                    'mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold',
                                    'bg-red-50 text-red-700' => $task['tone'] === 'danger',
                                    'bg-amber-50 text-amber-800' => $task['tone'] === 'warning',
                                    'bg-mist text-ink-body' => $task['tone'] === 'neutral',
                                ]) aria-hidden="true">
                                    @if ($task['tone'] === 'danger')
                                        !
                                    @elseif ($task['tone'] === 'warning')
                                        &bull;
                                    @else
                                        &#9998;
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink">{{ $task['title'] }}</p>
                                    <p class="mt-0.5 text-xs leading-relaxed text-ink-muted">{{ $task['desc'] }}</p>
                                </div>
                            </div>
                            <a
                                href="{{ $task['href'] }}"
                                @class([
                                    'inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg px-4 text-xs font-semibold transition-colors sm:min-h-9',
                                    'bg-primary text-white hover:bg-primary-hover' => $task['primary'],
                                    'border border-hairline bg-white text-ink hover:border-primary hover:text-primary' => ! $task['primary'],
                                ])
                            >
                                {{ $task['cta'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($attentionItems->isNotEmpty())
                <details class="border-t border-hairline bg-mist/60 px-5 py-3 sm:px-6">
                    <summary class="flex min-h-10 cursor-pointer items-center justify-between text-xs font-semibold text-primary">
                        <span>Rincian {{ $attentionItems->count() }} butir kriteria SINOVA perlu tindak lanjut</span>
                        <span>Buka daftar</span>
                    </summary>
                    <ul class="mt-2 divide-y divide-hairline/70">
                        @foreach ($attentionItems as $item)
                            <li wire:key="attn-{{ $item->id }}" class="flex flex-col gap-1.5 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-ink">
                                        <span class="font-mono text-primary">{{ $item->code }}</span>
                                        {{ $item->name }}
                                    </p>
                                    <p class="text-xs text-ink-muted">{{ $item->health_message ?: 'Perlu pengisian dokumen atau verifikasi tautan.' }}</p>
                                </div>
                                <span @class([
                                    'inline-flex w-fit shrink-0 items-center rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-amber-50 text-amber-900' => $item->health_status === 'warning',
                                    'bg-red-50 text-red-700' => in_array($item->health_status, ['broken', 'empty'], true),
                                ])>
                                    {{ $item->health_status === 'warning' ? 'Revisi (5)' : 'Belum Siap (0)' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>

        <aside aria-label="Kesiapan SINOVA" class="studio-card p-5 sm:p-6 lg:col-span-4">
            <h2 class="text-base font-bold text-ink">Kesiapan SINOVA</h2>
            <p class="mt-0.5 text-xs text-ink-muted">Dihitung dari {{ $summary['total'] }} butir kriteria juknis.</p>

            <div class="mt-4 flex flex-wrap items-baseline gap-2">
                <span class="font-serif text-4xl leading-none font-bold text-primary">{{ $summary['weighted_score'] }}</span>
                <span class="text-sm font-medium text-ink-muted">/ 100</span>
                <span class="rounded-md bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary">
                    {{ $summary['filled'] }} dari {{ $summary['total'] }} terisi
                </span>
            </div>

            @php
                $dashboardAspectLabels = [
                    'konten' => 'Konten Wajib',
                    'uiux' => 'Desain & Aksesibilitas',
                    'performa' => 'Performa & Keamanan',
                ];
            @endphp
            <div class="mt-5 space-y-3.5">
                @foreach ($summary['aspects'] as $key => $aspect)
                    <div wire:key="aspect-{{ $key }}">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="truncate font-medium text-ink-body" title="{{ $aspect['label'] }}">
                                {{ $dashboardAspectLabels[$key] ?? $aspect['label'] }} ({{ $aspect['weight'] }}%)
                            </span>
                            <span class="shrink-0 font-mono font-semibold text-ink">{{ $aspect['score_pct'] }}%</span>
                        </div>
                        <div
                            class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-[#e3eae7]"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="{{ (int) $aspect['score_pct'] }}"
                            aria-label="Skor {{ $aspect['label'] }}"
                        >
                            <div
                                class="h-full rounded-full {{ (int) $aspect['score_pct'] >= 90 ? 'bg-primary' : 'bg-[#c27803]' }}"
                                style="width: {{ min(100, (int) $aspect['score_pct']) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <a
                href="{{ route('admin.sinova-links') }}"
                class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-primary-soft px-4 text-xs font-semibold text-primary transition-colors hover:bg-primary hover:text-white"
            >
                Buka Link SINOVA &rarr;
            </a>
        </aside>
    </div>

    <!-- Row 2: Mulai Cepat (Permission-aware Quick Actions) -->
    @if ($quickShortcuts->isNotEmpty())
        <section aria-label="Mulai Cepat" class="space-y-3">
            <h2 class="text-base font-bold text-ink">Mulai cepat</h2>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($quickShortcuts as $sc)
                    <a href="{{ $sc['href'] }}" class="studio-card flex min-h-16 items-center gap-3.5 p-4 transition-colors hover:border-primary/60 hover:bg-primary-soft/30">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary">
                            @if ($sc['icon'] === 'news')
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="1.5"/><path d="M7 9h5M7 12.5h10M7 16h10"/></svg>
                            @elseif ($sc['icon'] === 'upload')
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 15.5v-11M7.5 9 12 4.5 16.5 9M4.5 15v3.5c0 .8.7 1.5 1.5 1.5h12c.8 0 1.5-.7 1.5-1.5V15"/></svg>
                            @elseif ($sc['icon'] === 'calendar')
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>
                            @else
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="1.5"/><circle cx="9" cy="10" r="1.6"/><path d="m4 17 5-4.5 3.5 3 3-2.5L20 17"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $sc['title'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $sc['desc'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Row 3: Publikasi Terbaru & Draf Cepat (8 cols) + Aktivitas & Status Sistem (4 cols) -->
    <div class="grid items-start gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">
            <section aria-label="Publikasi Terbaru" class="studio-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-base font-bold text-ink">Publikasi terbaru</h2>
                        <p class="text-xs text-ink-muted">Publikasi Tayang: <span class="font-mono font-semibold text-ink">{{ number_format($publishedPostsCount) }}</span> di portal.</p>
                    </div>
                    <a href="{{ route('admin.posts') }}" class="text-xs font-semibold text-primary hover:underline">
                        Kelola publikasi &rarr;
                    </a>
                </div>

                @if ($recentPosts->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-medium text-ink">Belum ada berita, pengumuman, atau artikel.</p>
                        <p class="mt-1 text-xs text-ink-muted">Gunakan formulir Draf Cepat di bawah atau menu Publikasi untuk mulai menulis.</p>
                    </div>
                @else
                    <ul class="divide-y divide-hairline/70">
                        @foreach ($recentPosts as $rPost)
                            <li wire:key="rpost-{{ $rPost->id }}" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink">{{ $rPost->title }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-ink-muted">
                                        <span>{{ $categories[$rPost->category] ?? $rPost->category }}</span>
                                        <span aria-hidden="true">&middot;</span>
                                        <span class="font-mono">{{ number_format((int) $rPost->views_count) }} dilihat</span>
                                        <span aria-hidden="true">&middot;</span>
                                        <span class="font-mono">{{ number_format((int) $rPost->approved_comments_count) }} komentar</span>
                                        @if (($canModerateComments ?? false) && (int) ($rPost->pending_comments_count ?? 0) > 0)
                                            <span class="font-semibold text-amber-800">({{ $rPost->pending_comments_count }} menunggu)</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center justify-between gap-2 sm:justify-end">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $rPost->is_published,
                                        'bg-mist text-ink-body' => ! $rPost->is_published,
                                    ])>
                                        <span class="size-1.5 rounded-full {{ $rPost->is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                                        {{ $rPost->is_published ? 'Tayang' : 'Draf' }}
                                    </span>
                                    @if ($canWritePosts ?? false)
                                        <a href="{{ route('admin.posts', ['edit' => $rPost->id]) }}" class="inline-flex min-h-11 items-center rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-8">
                                            Ubah
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($canWritePosts)
                <section aria-label="Draf Cepat Publikasi" class="studio-card p-5 sm:p-6">
                    <div class="border-b border-hairline pb-3.5">
                        <h2 class="text-base font-bold text-ink">Draf Cepat Publikasi</h2>
                        <p class="mt-0.5 text-xs text-ink-muted">Catat ide berita atau pengumuman langsung dari dashboard. Disimpan sebagai draf agar bisa dilengkapi nanti.</p>
                    </div>

                    @if ($quickDraftNotice)
                        <div role="status" class="mt-4 rounded-lg border border-emerald-200 bg-primary-soft px-3.5 py-2.5 text-xs font-medium text-primary">
                            {{ $quickDraftNotice }}
                        </div>
                    @endif

                    <form wire:submit="saveQuickDraft" class="mt-4 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-12">
                            <div class="sm:col-span-8">
                                <flux:field>
                                    <flux:label for="qd_title">Judul Draf <span class="text-red-700">*</span></flux:label>
                                    <flux:input id="qd_title" wire:model="quickDraftTitle" required placeholder="Contoh: Sidang Keliling Triwulan IV" />
                                    <flux:error name="quickDraftTitle" />
                                </flux:field>
                            </div>
                            <div class="sm:col-span-4">
                                <flux:field>
                                    <flux:label for="qd_category">Kategori</flux:label>
                                    <x-admin.select id="qd_category" wire:model="quickDraftCategory" search-placeholder="Cari kategori...">
                                        @foreach ($categories as $catKey => $catLabel)
                                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                                        @endforeach
                                    </x-admin.select>
                                </flux:field>
                            </div>
                        </div>

                        <flux:field>
                            <flux:label for="qd_body">Catatan Ringkas <span class="text-red-700">*</span></flux:label>
                            <flux:textarea id="qd_body" wire:model="quickDraftBody" rows="3" required placeholder="Tulis poin utama pengumuman atau agenda kegiatan satuan kerja..." />
                            <flux:error name="quickDraftBody" />
                        </flux:field>

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary px-5 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-10 sm:w-auto">
                                Simpan sebagai Draf
                            </flux:button>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="space-y-6 lg:col-span-4">
            @if ($canViewAudit ?? false)
                <section aria-label="Aktivitas Terakhir" class="studio-card p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-2 border-b border-hairline pb-3.5">
                        <h2 class="text-base font-bold text-ink">Aktivitas terakhir</h2>
                        <a href="{{ route('admin.audit') }}" class="text-xs font-semibold text-primary hover:underline">Semua log</a>
                    </div>
                    @if ($recentActivities->isEmpty())
                        <p class="mt-4 text-xs text-ink-muted">Belum ada catatan aktivitas yang direkam.</p>
                    @else
                        <ul class="mt-4 space-y-3.5">
                            @foreach ($recentActivities as $act)
                                <li wire:key="act-{{ $act->id }}" class="flex items-start gap-3">
                                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-ink">{{ $act->description }}</p>
                                        <p class="mt-0.5 text-xs text-ink-muted">
                                            {{ $act->causer?->name ?? 'Sistem' }} &middot; {{ $act->created_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            <section aria-label="Status Sistem" class="studio-card p-5 sm:p-6">
                <div class="flex items-center justify-between gap-2 border-b border-hairline pb-3.5">
                    <h2 class="text-base font-bold text-ink">Status sistem</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        <span class="size-1.5 rounded-full bg-emerald-700" aria-hidden="true"></span>
                        Normal
                    </span>
                </div>
                <dl class="mt-4 space-y-2.5 text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-muted">PHP</dt>
                        <dd class="font-mono font-semibold text-ink">{{ PHP_VERSION }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-muted">Laravel</dt>
                        <dd class="font-mono font-semibold text-ink">{{ app()->version() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-muted">SatkerCMS</dt>
                        <dd class="font-mono font-semibold text-ink">{{ $cmsVersion }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-muted">Total Kunjungan</dt>
                        <dd class="font-mono font-semibold text-ink">{{ number_format($totalVisits) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-muted">Tautan Bermasalah</dt>
                        <dd class="font-mono font-semibold {{ $brokenLinksCount > 0 ? 'text-red-700' : 'text-emerald-800' }}">
                            {{ $brokenLinksCount }} tautan
                        </dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</div>
