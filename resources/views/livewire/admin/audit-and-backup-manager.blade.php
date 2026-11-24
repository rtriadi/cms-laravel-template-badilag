<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
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
                    <p class="text-xs text-ink-muted">Mendeteksi tautan internal maupun menu navigasi yang bermasalah.</p>
                </div>
                @if ($canModify)
                    <button type="button" wire:click="runLinkAudit" class="min-h-11 cursor-pointer rounded-md bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary-hover">
                        Jalankan Pemeriksaan Tautan
                    </button>
                @endif
            </div>

            @if ($linkLogs->isEmpty())
                <p class="py-10 text-center text-sm text-ink-muted">Belum ada catatan pemeriksaan tautan.</p>
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
    @endif
</div>
