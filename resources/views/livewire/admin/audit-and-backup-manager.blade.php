<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @if ($errorNotice)
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ $errorNotice }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Audit, Keamanan &amp; Backup</h1>
            <p class="mt-1 text-sm text-ink-body">
                Pantau kesehatan tautan website, atur pengalihan alamat lama (Redirect 301), periksa jejak aktivitas pengelola, serta unduh arsip backup.
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
            @if (auth()->user()?->hasRole('super_admin'))
                <button type="button" wire:click="runBackup" wire:loading.attr="disabled" wire:target="runBackup" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary disabled:opacity-60 sm:min-h-10">
                    <span wire:loading.remove wire:target="runBackup">Buat Backup Sekarang</span>
                    <span wire:loading wire:target="runBackup">Menjalankan backup...</span>
                </button>
            @endif
            @if ($canModify)
                <button type="button" wire:click="runLinkAudit" wire:loading.attr="disabled" wire:target="runLinkAudit" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover disabled:opacity-60 sm:min-h-10">
                    <span wire:loading.remove wire:target="runLinkAudit">Jalankan Audit Tautan</span>
                    <span wire:loading wire:target="runLinkAudit">Memeriksa tautan...</span>
                </button>
            @endif
        </div>
    </div>

    <div class="studio-card overflow-hidden">
        <!-- Underline Tabs -->
        <div role="tablist" aria-label="Audit Tautan, Redirect, Log Aktivitas, dan Backup" class="studio-tabs px-4 pt-2 sm:px-6">
            @foreach ([
                'links' => ['Pemeriksaan Tautan', $linkStats['broken'] + $linkStats['healthy']],
                'redirects' => ['Redirect 301', $redirects?->total()],
                'activity' => ['Log Aktivitas', $activities->count()],
                'backup' => ['Lingkungan & Backup', count($backups)],
            ] as $tKey => [$tLabel, $tCount])
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === $tKey ? 'true' : 'false' }}"
                    wire:click="$set('tab', '{{ $tKey }}')"
                    @class([
                        'inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 border-b-2 px-3 text-sm transition-colors',
                        'border-primary font-bold text-primary' => $tab === $tKey,
                        'border-transparent font-medium text-ink-body hover:text-ink' => $tab !== $tKey,
                    ])
                >
                    <span>{{ $tLabel }}</span>
                    @if ($tCount !== null)
                        <span @class([
                            'rounded-md px-2 py-0.5 font-mono text-xs font-semibold',
                            'bg-primary-soft text-primary' => $tab === $tKey,
                            'bg-mist text-ink-muted' => $tab !== $tKey,
                        ])>{{ $tCount }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="p-4 sm:p-6">
            @if ($tab === 'links')
                <div class="space-y-4">
                    @if (($linkStats['broken'] + $linkStats['healthy']) > 0)
                        <div class="flex flex-col gap-3 rounded-xl border border-hairline bg-mist px-4 py-3 text-xs sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <span @class([
                                    'rounded-md px-2.5 py-1 font-semibold',
                                    'bg-red-50 text-red-700' => $linkStats['broken'] > 0,
                                    'bg-emerald-50 text-emerald-800' => $linkStats['broken'] === 0,
                                ])>
                                    Bermasalah: {{ number_format($linkStats['broken']) }}
                                </span>
                                <span class="rounded-md bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-800">
                                    Sehat: {{ number_format($linkStats['healthy']) }}
                                </span>
                                @if ($linkStats['last_checked_at'])
                                    <span class="text-ink-muted">
                                        Diperiksa: {{ \Illuminate\Support\Carbon::parse($linkStats['last_checked_at'])->format('d M Y, H:i') }}
                                    </span>
                                @endif
                            </div>

                            <div role="group" aria-label="Filter status pemeriksaan tautan" class="inline-flex rounded-lg bg-[#e6ece9] p-1">
                                <button type="button" wire:click="$set('linkFilter', 'all')" aria-pressed="{{ $linkFilter === 'all' ? 'true' : 'false' }}" @class([
                                    'min-h-9 cursor-pointer rounded-md px-3 text-xs font-semibold transition-colors',
                                    'bg-white text-ink shadow-2xs' => $linkFilter === 'all',
                                    'text-ink-body hover:text-ink' => $linkFilter !== 'all',
                                ])>
                                    Semua
                                </button>
                                <button type="button" wire:click="$set('linkFilter', 'broken')" aria-pressed="{{ $linkFilter === 'broken' ? 'true' : 'false' }}" @class([
                                    'min-h-9 cursor-pointer rounded-md px-3 text-xs font-semibold transition-colors',
                                    'bg-red-700 text-white' => $linkFilter === 'broken',
                                    'text-ink-body hover:text-ink' => $linkFilter !== 'broken',
                                ])>
                                    Bermasalah Saja
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($linkLogs->isEmpty())
                        <div class="py-12 text-center">
                            <p class="text-base font-bold text-ink">
                                {{ $linkFilter === 'broken' && ($linkStats['healthy'] > 0) ? 'Semua tautan dalam kondisi sehat' : 'Belum ada catatan pemeriksaan tautan' }}
                            </p>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{ $linkFilter === 'broken' && ($linkStats['healthy'] > 0) ? 'Tidak ditemukan tautan rusak pada pemeriksaan terakhir.' : 'Klik tombol Jalankan Audit Tautan di atas untuk memeriksa seluruh tautan.' }}
                            </p>
                        </div>
                    @else
                        <!-- Mobile Cards -->
                        <ul class="space-y-3 md:hidden">
                            @foreach ($linkLogs as $log)
                                <li wire:key="lcl-mob-{{ $log->id }}" class="space-y-1.5 rounded-xl border border-hairline bg-white p-4 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-ink">{{ $log->source_label }}</span>
                                        <span @class([
                                            'rounded-md px-2 py-0.5 font-semibold',
                                            'bg-red-50 text-red-700' => $log->is_broken,
                                            'bg-emerald-50 text-emerald-800' => ! $log->is_broken,
                                        ])>
                                            {{ $log->is_broken ? 'Bermasalah' : 'Sehat' }} ({{ $log->http_status ?? '-' }})
                                        </span>
                                    </div>
                                    <p class="break-all font-mono text-ink-muted">{{ $log->url }}</p>
                                    @if ($log->error_message)
                                        <p class="text-red-700">{{ $log->error_message }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <!-- Desktop Table -->
                        <div class="hidden overflow-x-auto rounded-xl border border-hairline md:block">
                            <table class="w-full border-collapse text-left text-xs">
                                <thead>
                                    <tr class="border-b border-hairline bg-mist text-ink-muted">
                                        <th class="px-4 py-3 font-semibold">Sumber</th>
                                        <th class="px-3 py-3 font-semibold">URL tujuan</th>
                                        <th class="px-3 py-3 font-semibold">Status HTTP</th>
                                        <th class="px-4 py-3 font-semibold">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-hairline/70">
                                    @foreach ($linkLogs as $log)
                                        <tr wire:key="lcl-{{ $log->id }}" class="hover:bg-mist/60">
                                            <td class="px-4 py-3 font-semibold text-ink">{{ $log->source_label }}</td>
                                            <td class="px-3 py-3 break-all font-mono text-ink-muted">{{ $log->url }}</td>
                                            <td class="whitespace-nowrap px-3 py-3">
                                                <span @class([
                                                    'inline-flex items-center gap-1.5 rounded-md px-2.5 py-0.5 font-semibold',
                                                    'bg-red-50 text-red-700' => $log->is_broken,
                                                    'bg-emerald-50 text-emerald-800' => ! $log->is_broken,
                                                ])>
                                                    {{ $log->is_broken ? 'Bermasalah' : 'Sehat' }} ({{ $log->http_status ?? '-' }})
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-ink-muted">{{ $log->error_message ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @elseif ($tab === 'redirects')
                <div class="space-y-5">
                    <div class="rounded-xl border border-primary/25 bg-primary-soft px-4 py-3 text-xs leading-relaxed text-ink-body">
                        <strong class="text-primary-deep">Fungsi Pengalihan (Redirect 301):</strong>
                        Menyimpan pemetaan alamat lama ke alamat baru agar pengunjung dari Google maupun tautan SINOVA (Kriteria 3.c.1) tidak mengalami galat 404.
                    </div>

                    <div class="grid items-start gap-6 lg:grid-cols-12">
                        @if ($canModify)
                            <div class="rounded-xl border border-hairline bg-mist p-5 lg:col-span-5">
                                <h2 class="border-b border-hairline pb-3 text-base font-bold text-ink">Tambah Redirect Permanen (301)</h2>
                                <form wire:submit="saveRedirect" class="mt-4 space-y-4">
                                    <flux:field>
                                        <flux:label for="rd_from">Path lama</flux:label>
                                        <flux:input id="rd_from" wire:model="from_path" required placeholder="/halaman/lama" class="font-mono" />
                                        <flux:error name="from_path" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label for="rd_to">Path atau URL tujuan</flux:label>
                                        <flux:input id="rd_to" wire:model="to_path" required placeholder="/halaman/baru" class="font-mono" />
                                        <flux:error name="to_path" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label for="rd_code">Kode status HTTP</flux:label>
                                        <x-admin.select id="rd_code" wire:model="status_code">
                                            <option value="301">301 - Permanen (Disarankan SEO)</option>
                                            <option value="302">302 - Sementara</option>
                                        </x-admin.select>
                                    </flux:field>
                                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                        Simpan Aturan Redirect
                                    </flux:button>
                                </form>
                            </div>
                        @endif

                        <div @class(['lg:col-span-7' => $canModify, 'lg:col-span-12' => ! $canModify])>
                            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h2 class="text-base font-bold text-ink">
                                    Daftar Aturan Pengalihan ({{ number_format($redirects?->total() ?? 0) }})
                                </h2>
                                <div class="w-full sm:w-64">
                                    <flux:input wire:model.live.debounce.300ms="redirectSearch" aria-label="Cari aturan redirect" placeholder="Cari path lama atau tujuan..." />
                                </div>
                            </div>
                            @if ($redirects === null || $redirects->isEmpty())
                                <p class="rounded-xl border border-hairline bg-mist/60 py-10 text-center text-sm text-ink-muted">
                                    {{ trim($redirectSearch) !== '' ? 'Tidak ada aturan pengalihan yang cocok.' : 'Belum ada aturan redirect tersimpan.' }}
                                </p>
                            @else
                                <ul class="divide-y divide-hairline rounded-xl border border-hairline bg-white">
                                    @foreach ($redirects as $rd)
                                        <li wire:key="rd-{{ $rd->id }}" class="flex flex-col gap-2 p-4 text-xs sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <p class="break-all font-mono font-semibold text-ink">{{ $rd->from_path }} &rarr; {{ $rd->to_path }}</p>
                                                <p class="mt-0.5 text-ink-muted">HTTP {{ $rd->status_code }} &middot; Hits: {{ number_format((int) $rd->hits) }}</p>
                                            </div>
                                            @if ($canModify)
                                                <button type="button" wire:click="deleteRedirect({{ $rd->id }})" wire:confirm="Hapus aturan pengalihan ini?" class="inline-flex min-h-10 w-fit shrink-0 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">
                                                    Hapus
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-4">
                                    {{ $redirects->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif ($tab === 'activity')
                <div>
                    <h2 class="mb-3 text-base font-bold text-ink">Log Aktivitas Terbaru (50 Entri)</h2>
                    @if ($activities->isEmpty())
                        <p class="rounded-xl border border-hairline bg-mist/60 py-10 text-center text-sm text-ink-muted">Belum ada aktivitas tercatat.</p>
                    @else
                        <ul class="divide-y divide-hairline rounded-xl border border-hairline bg-white">
                            @foreach ($activities as $act)
                                <li wire:key="act-{{ $act->id }}" class="flex flex-col gap-1.5 p-4 text-xs sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded bg-primary-soft px-2 py-0.5 font-mono font-semibold text-primary">{{ $act->log_name }}</span>
                                        <span class="font-semibold text-ink">{{ $act->description }}</span>
                                        <span class="text-ink-muted">oleh {{ $act->causer?->name ?? 'Sistem' }}</span>
                                    </div>
                                    <span class="shrink-0 font-mono text-ink-muted">{{ $act->created_at?->format('Y-m-d H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @else
                <div class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="rounded-xl border border-hairline bg-mist p-5">
                            <div class="flex items-center justify-between gap-2 border-b border-hairline pb-3">
                                <h2 class="text-base font-bold text-ink">Informasi Lingkungan &amp; Keamanan (SINOVA 3.a)</h2>
                                <span class="rounded-md bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Non-EOL</span>
                            </div>
                            <dl class="mt-4 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between gap-2 border-b border-hairline/70 pb-2">
                                    <dt class="text-ink-muted">Versi SatkerCMS</dt>
                                    <dd class="font-mono font-semibold text-ink">{{ config('satker.cms_version', '1.0.0-dev') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-2 border-b border-hairline/70 pb-2">
                                    <dt class="text-ink-muted">Versi Laravel</dt>
                                    <dd class="font-mono font-semibold text-ink">{{ app()->version() }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-2 border-b border-hairline/70 pb-2">
                                    <dt class="text-ink-muted">Versi PHP</dt>
                                    <dd class="font-mono font-semibold text-emerald-800">PHP {{ PHP_VERSION }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <dt class="text-ink-muted">Login Admin Wajib 2FA TOTP</dt>
                                    <dd class="font-semibold text-emerald-800">Aktif</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-hairline bg-mist p-5">
                            <h2 class="border-b border-hairline pb-3 text-base font-bold text-ink">Prosedur Backup &amp; Pembaruan CLI</h2>
                            <p class="mt-3 text-xs text-ink-muted">
                                Gunakan perintah Artisan berikut di terminal peladen dengan hak akses operator infrastruktur:
                            </p>
                            <div class="mt-3 space-y-2 font-mono text-xs">
                                <div class="rounded-lg border border-hairline bg-white p-2.5 text-ink">php artisan backup:run</div>
                                <div class="rounded-lg border border-hairline bg-white p-2.5 text-ink">php artisan cms:update --dry-run</div>
                                <div class="rounded-lg border border-hairline bg-white p-2.5 text-ink">php artisan cms:update</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-hairline bg-mist p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                            <h2 class="text-base font-bold text-ink">Arsip Backup Terbaru</h2>
                            @if (auth()->user()?->hasRole('super_admin'))
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="queueBackup" wire:loading.attr="disabled" wire:target="queueBackup" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary disabled:opacity-60 sm:min-h-9">
                                        <span wire:loading.remove wire:target="queueBackup">Antrekan Latar Belakang</span>
                                        <span wire:loading wire:target="queueBackup">Mengantrekan...</span>
                                    </button>
                                    <button type="button" wire:click="runBackup" wire:loading.attr="disabled" wire:target="runBackup" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover disabled:opacity-60 sm:min-h-9">
                                        <span wire:loading.remove wire:target="runBackup">Jalankan Backup Database</span>
                                        <span wire:loading wire:target="runBackup">Menjalankan backup...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                        @if (is_array($backupJobStatus))
                            @php
                                $jobState = (string) ($backupJobStatus['state'] ?? 'queued');
                                $jobBadgeClass = match ($jobState) {
                                    'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                                    'failed' => 'border-red-200 bg-red-50 text-red-900',
                                    default => 'border-amber-200 bg-amber-50 text-amber-900',
                                };
                            @endphp
                            <div role="status" class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3.5 py-2.5 text-xs {{ $jobBadgeClass }}">
                                <div>
                                    <span class="font-semibold">{{ $backupJobStatus['message'] ?? '' }}</span>
                                    <span class="ml-1.5 opacity-80">({{ $backupJobStatus['updated_at'] ?? '' }} &middot; {{ $backupJobStatus['actor'] ?? 'Super Admin' }})</span>
                                </div>
                                <button type="button" wire:click="refreshBackups" class="cursor-pointer font-semibold underline hover:opacity-80">
                                    Segarkan daftar
                                </button>
                            </div>
                        @endif
                        <p class="mt-3 text-xs text-ink-muted">
                            Backup otomatis berjalan setiap hari pukul 02.00 melalui jadwal cron <span class="font-mono">schedule:run</span>. Arsip disimpan di disk privat dan hanya dapat diunduh Super Admin.
                        </p>
                        @if ($backups === [])
                            <p class="py-8 text-center text-sm text-ink-muted">Belum ada arsip backup pada disk privat.</p>
                        @else
                            <ul class="mt-4 space-y-2.5">
                                @foreach ($backups as $backup)
                                    <li wire:key="backup-{{ $backup['name'] }}" class="flex flex-col gap-2 rounded-xl border border-hairline bg-white p-3.5 text-xs sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="break-all font-mono font-semibold text-ink">{{ $backup['name'] }}</p>
                                            <p class="mt-0.5 text-ink-muted">
                                                {{ $backup['size'] >= 1048576 ? number_format($backup['size'] / 1048576, 2).' MB' : number_format($backup['size'] / 1024, 1).' KB' }}
                                                &middot; {{ \Illuminate\Support\Carbon::createFromTimestamp($backup['modified'])->format('d M Y, H:i') }}
                                            </p>
                                        </div>
                                        <button type="button" wire:click="downloadBackup('{{ $backup['name'] }}')" class="inline-flex min-h-11 w-fit shrink-0 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-primary hover:border-primary hover:bg-primary-soft sm:min-h-9">
                                            Unduh
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
