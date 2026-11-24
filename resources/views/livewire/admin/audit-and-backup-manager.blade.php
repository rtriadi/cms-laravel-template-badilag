<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @if ($errorNotice)
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ $errorNotice }}
        </div>
    @endif

    <div role="group" aria-label="Audit Tautan, Redirect, Log Aktivitas, dan Backup" class="flex flex-wrap gap-2 border-b border-hairline pb-3">
        @foreach (['links' => 'Audit Tautan Rusak', 'redirects' => 'Pengalihan (Redirect 301)', 'activity' => 'Log Aktivitas Admin', 'backup' => 'Backup & Pembaruan Sistem'] as $tKey => $tLabel)
            <button
                type="button"
                aria-pressed="{{ $tab === $tKey ? 'true' : 'false' }}"
                wire:click="$set('tab', '{{ $tKey }}')"
                @class([
                    'min-h-11 cursor-pointer rounded-md px-4 py-2 text-xs font-semibold transition-colors',
                    'bg-primary text-white' => $tab === $tKey,
                    'border border-hairline bg-white text-ink hover:bg-mist' => $tab !== $tKey,
                ])>
                {{ $tLabel }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'links')
        <div class="rounded-lg border border-hairline bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                <div>
                    <h2 class="font-serif text-lg font-bold text-ink">Catatan Pemeriksaan Tautan</h2>
                    <p class="text-xs text-ink-muted">Mendeteksi tautan kriteria SINOVA maupun menu navigasi yang bermasalah. Setiap pemeriksaan mengganti catatan sebelumnya agar tidak menumpuk.</p>
                </div>
                @if ($canModify)
                    <button type="button" wire:click="runLinkAudit" wire:loading.attr="disabled" class="min-h-11 cursor-pointer rounded-md bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary-hover disabled:cursor-wait disabled:opacity-60">
                        <span wire:loading.remove wire:target="runLinkAudit">Jalankan Pemeriksaan Tautan</span>
                        <span wire:loading wire:target="runLinkAudit">Memeriksa tautan...</span>
                    </button>
                @endif
            </div>

            @if (($linkStats['broken'] + $linkStats['healthy']) > 0)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-hairline bg-mist/60 px-3.5 py-2.5 text-xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'rounded px-2.5 py-1 font-semibold',
                            'bg-red-100 text-red-800' => $linkStats['broken'] > 0,
                            'bg-emerald-50 text-emerald-800' => $linkStats['broken'] === 0,
                        ])>
                            Bermasalah: {{ number_format($linkStats['broken']) }}
                        </span>
                        <span class="rounded bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-800">
                            Sehat: {{ number_format($linkStats['healthy']) }}
                        </span>
                        @if ($linkStats['last_checked_at'])
                            <span class="text-ink-muted">
                                Diperiksa: {{ \Illuminate\Support\Carbon::parse($linkStats['last_checked_at'])->format('d M Y, H:i') }}
                            </span>
                        @endif
                    </div>

                    <div role="group" aria-label="Filter status pemeriksaan tautan" class="flex items-center gap-1.5">
                        <button
                            type="button"
                            wire:click="$set('linkFilter', 'all')"
                            aria-pressed="{{ $linkFilter === 'all' ? 'true' : 'false' }}"
                            @class([
                                'min-h-8 cursor-pointer rounded px-2.5 text-xs font-semibold transition-colors',
                                'bg-primary text-white' => $linkFilter === 'all',
                                'border border-hairline bg-white text-ink hover:bg-mist' => $linkFilter !== 'all',
                            ])
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            wire:click="$set('linkFilter', 'broken')"
                            aria-pressed="{{ $linkFilter === 'broken' ? 'true' : 'false' }}"
                            @class([
                                'min-h-8 cursor-pointer rounded px-2.5 text-xs font-semibold transition-colors',
                                'bg-red-700 text-white' => $linkFilter === 'broken',
                                'border border-hairline bg-white text-ink hover:bg-mist' => $linkFilter !== 'broken',
                            ])
                        >
                            Bermasalah Saja
                        </button>
                    </div>
                </div>
            @endif

            @if ($linkLogs->isEmpty())
                <p class="py-10 text-center text-sm text-ink-muted">
                    {{ $linkFilter === 'broken' && ($linkStats['healthy'] > 0) ? 'Tidak ada tautan bermasalah pada pemeriksaan terakhir.' : 'Belum ada catatan pemeriksaan tautan.' }}
                </p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-hairline text-ink-muted">
                                <th class="py-2 pr-3 font-semibold">Sumber</th>
                                <th class="py-2 pr-3 font-semibold">URL</th>
                                <th class="py-2 pr-3 font-semibold">Status</th>
                                <th class="py-2 font-semibold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @foreach ($linkLogs as $log)
                                <tr wire:key="lcl-{{ $log->id }}">
                                    <td class="py-2.5 pr-3 font-semibold text-ink">{{ $log->source_label }}</td>
                                    <td class="py-2.5 pr-3 font-mono text-ink-muted">{{ $log->url }}</td>
                                    <td class="py-2.5 pr-3">
                                        <span @class([
                                            'rounded px-2 py-0.5 font-semibold',
                                            'bg-red-100 text-red-800' => $log->is_broken,
                                            'bg-emerald-50 text-emerald-800' => ! $log->is_broken,
                                        ])>
                                            {{ $log->is_broken ? 'Bermasalah' : 'Sehat' }} ({{ $log->http_status ?? '-' }})
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-ink-muted">{{ $log->error_message ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif ($tab === 'redirects')
        <div class="space-y-4">
            <div class="rounded-lg border border-hairline bg-mist/60 px-4 py-3 text-xs leading-relaxed text-ink-muted">
                <span class="font-semibold text-ink">Fungsi Pengalihan (Redirect 301):</span>
                Menyimpan pemetaan alamat website lama (termasuk arsip migrasi CMS sebelumnya) ke alamat baru di SatkerCMS agar pengunjung dari mesin pencari Google maupun tautan penilaian SINOVA (Kriteria 3.c.1) tidak mengalami halaman hilang (404). Aturan ini aman dibiarkan aktif dan tidak perlu dihapus.
            </div>
            <div class="grid gap-6 lg:grid-cols-12">
            @if ($canModify)
                <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                    <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Tambah Redirect Permanen (301)</h2>
                    <form wire:submit="saveRedirect" class="mt-4 space-y-4">
                        <flux:field>
                            <flux:label for="rd_from">Path Lama</flux:label>
                            <flux:input id="rd_from" wire:model="from_path" required placeholder="/halaman/lama" />
                            <flux:error name="from_path" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="rd_to">Path atau URL Tujuan</flux:label>
                            <flux:input id="rd_to" wire:model="to_path" required placeholder="/halaman/baru" />
                            <flux:error name="to_path" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="rd_code">Kode Status HTTP</flux:label>
                            <flux:select id="rd_code" wire:model="status_code">
                                <option value="301">301 - Permanen (Disarankan SEO)</option>
                                <option value="302">302 - Sementara</option>
                            </flux:select>
                        </flux:field>
                        <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                            Simpan Aturan Redirect
                        </flux:button>
                    </form>
                </div>
            @endif

            <div @class(['rounded-lg border border-hairline bg-white p-5', 'lg:col-span-7' => $canModify, 'lg:col-span-12' => ! $canModify])>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                    <h2 class="font-serif text-lg font-bold text-ink">
                        Daftar Aturan Pengalihan ({{ number_format($redirects?->total() ?? 0) }})
                    </h2>
                    <div class="w-full sm:w-64">
                        <flux:input wire:model.live.debounce.300ms="redirectSearch" aria-label="Cari aturan redirect" placeholder="Cari path lama atau tujuan..." />
                    </div>
                </div>
                @if ($redirects === null || $redirects->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">
                        {{ trim($redirectSearch) !== '' ? 'Tidak ada aturan pengalihan yang cocok dengan pencarian.' : 'Belum ada aturan redirect tersimpan.' }}
                    </p>
                @else
                    <ul class="mt-3 divide-y divide-hairline">
                        @foreach ($redirects as $rd)
                            <li wire:key="rd-{{ $rd->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-xs">
                                <div class="min-w-0 flex-1">
                                    <p class="break-all font-mono font-semibold text-ink">{{ $rd->from_path }} &rarr; {{ $rd->to_path }}</p>
                                    <p class="text-ink-muted">HTTP {{ $rd->status_code }} | Hits: {{ number_format((int) $rd->hits) }}</p>
                                </div>
                                @if ($canModify)
                                    <button type="button" wire:click="deleteRedirect({{ $rd->id }})" wire:confirm="Hapus aturan pengalihan ini?" class="min-h-11 shrink-0 cursor-pointer rounded border border-red-200 px-3 py-1 font-semibold text-red-700 hover:bg-red-50">
                                        Hapus
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 border-t border-hairline pt-3">
                        {{ $redirects->links() }}
                    </div>
                @endif
            </div>
            </div>
        </div>
    @elseif ($tab === 'activity')
        <div class="rounded-lg border border-hairline bg-white p-5">
            <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Log Aktivitas Terbaru (50 Entri)</h2>
            @if ($activities->isEmpty())
                <p class="py-10 text-center text-sm text-ink-muted">Belum ada aktivitas tercatat.</p>
            @else
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($activities as $act)
                        <li wire:key="act-{{ $act->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-xs">
                            <div>
                                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono font-semibold text-primary">{{ $act->log_name }}</span>
                                <span class="font-semibold text-ink">{{ $act->description }}</span>
                                <span class="text-ink-muted">oleh {{ $act->causer?->name ?? 'Sistem' }}</span>
                            </div>
                            <span class="font-mono text-ink-muted">{{ $act->created_at?->format('Y-m-d H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @else
        <div class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-hairline bg-white p-5">
                    <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Informasi Lingkungan & Keamanan (SINOVA 3.a)</h2>
                    <dl class="mt-4 space-y-2.5 text-xs">
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Versi SatkerCMS</dt>
                            <dd class="font-mono font-semibold text-ink">{{ config('satker.cms_version', '1.0.0-dev') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Versi Laravel</dt>
                            <dd class="font-mono font-semibold text-ink">{{ app()->version() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Versi PHP</dt>
                            <dd class="font-mono font-semibold text-emerald-800">PHP {{ PHP_VERSION }} (Didukung Non-EOL)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Login Admin Wajib 2FA TOTP</dt>
                            <dd class="font-semibold text-emerald-800">Aktif</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-hairline bg-white p-5">
                    <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Prosedur Backup & Pembaruan CLI</h2>
                    <p class="mt-3 text-xs text-ink-muted">
                        Gunakan perintah Artisan berikut di terminal peladen (server) dengan hak akses operator infrastruktur:
                    </p>
                    <div class="mt-3 space-y-2 font-mono text-xs">
                        <div class="rounded border border-hairline bg-mist p-2.5 text-ink">php artisan backup:run</div>
                        <div class="rounded border border-hairline bg-mist p-2.5 text-ink">php artisan cms:update --dry-run</div>
                        <div class="rounded border border-hairline bg-mist p-2.5 text-ink">php artisan cms:update</div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                    <h2 class="font-serif text-lg font-bold text-ink">Arsip Backup Terbaru</h2>
                    @if (auth()->user()?->hasRole('super_admin'))
                        <button
                            type="button"
                            wire:click="runBackup"
                            wire:loading.attr="disabled"
                            class="min-h-9 cursor-pointer rounded-lg bg-primary px-3.5 text-xs font-bold text-white hover:bg-primary-hover disabled:cursor-wait disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="runBackup">Jalankan Backup Database</span>
                            <span wire:loading wire:target="runBackup">Menjalankan backup…</span>
                        </button>
                    @endif
                </div>
                <p class="mt-3 text-xs text-ink-muted">
                    Backup otomatis berjalan setiap hari pukul 02:00 (lihat jadwal cron <span class="font-mono">schedule:run</span> di README). Arsip disimpan di disk privat dan hanya dapat diunduh Super Admin.
                </p>
                @if ($backups === [])
                    <p class="py-8 text-center text-sm text-ink-muted">Belum ada arsip backup pada disk privat.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-hairline text-ink-muted">
                                <tr>
                                    <th class="py-2 pr-3 font-semibold">Nama Arsip</th>
                                    <th class="py-2 pr-3 font-semibold">Ukuran</th>
                                    <th class="py-2 pr-3 font-semibold">Dibuat</th>
                                    <th class="py-2 font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                @foreach ($backups as $backup)
                                    <tr wire:key="backup-{{ $backup['name'] }}">
                                        <td class="py-2 pr-3 font-mono text-ink">{{ $backup['name'] }}</td>
                                        <td class="py-2 pr-3 text-ink-muted">
                                            {{ $backup['size'] >= 1048576 ? number_format($backup['size'] / 1048576, 2).' MB' : number_format($backup['size'] / 1024, 1).' KB' }}
                                        </td>
                                        <td class="py-2 pr-3 text-ink-muted">{{ \Illuminate\Support\Carbon::createFromTimestamp($backup['modified'])->format('d M Y, H:i') }}</td>
                                        <td class="py-2">
                                            <button
                                                type="button"
                                                wire:click="downloadBackup('{{ $backup['name'] }}')"
                                                class="min-h-8 cursor-pointer rounded border border-hairline px-2.5 text-2xs font-semibold text-primary hover:bg-primary-soft"
                                            >
                                                Unduh
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
