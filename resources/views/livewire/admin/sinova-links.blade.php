<div
    class="space-y-6"
    x-data="{
        copiedNotice: '',
        async writeClipboard(text) {
            if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (e) {}
            }
            const area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (e) {
                copied = false;
            }
            document.body.removeChild(area);
            return copied;
        },
        async copyUrl(url, status) {
            if (! url) {
                this.copiedNotice = 'Tautan masih kosong. Isi halaman atau URL manual terlebih dahulu.';
                return;
            }
            const ok = await this.writeClipboard(url);
            const warn = (status === 'broken' || status === 'empty') ? ' (status tautan masih bermasalah)' : '';
            this.copiedNotice = ok ? 'URL disalin' + warn + ': ' + url : 'Gagal menyalin otomatis. Salin manual: ' + url;
        },
        async copyGroupUrls(text, title) {
            if (! text) {
                this.copiedNotice = 'Belum ada URL terisi pada grup ' + title;
                return;
            }
            const ok = await this.writeClipboard(text);
            this.copiedNotice = ok ? 'Semua URL pada grup ' + title + ' disalin.' : 'Gagal menyalin URL grup ' + title + ' secara otomatis.';
        }
    }"
>
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div x-cloak x-show="copiedNotice !== ''" role="status" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-primary/30 bg-white px-4 py-2.5 text-sm font-medium text-ink">
        <span class="break-all" x-text="copiedNotice"></span>
        <button type="button" x-on:click="copiedNotice = ''" class="inline-flex min-h-10 cursor-pointer items-center px-2 text-xs font-semibold text-primary">Tutup</button>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Link Kriteria SINOVA</h1>
            <p class="mt-1 text-sm text-ink-body">
                Semua tautan penilaian SK Dirjen Badilag 2722/2026 terkumpul di sini. Jalankan cek tautan rutin dan ubah tujuan langsung tanpa berpindah halaman.
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
            <button type="button" wire:click="exportCsv" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10">
                Ekspor CSV
            </button>
            @if ($canEdit)
                <button type="button" wire:click="runHealthCheck" wire:loading.attr="disabled" wire:target="runHealthCheck" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover disabled:opacity-60 sm:min-h-10">
                    <span wire:loading.remove wire:target="runHealthCheck">Cek Kesehatan Tautan</span>
                    <span wire:loading wire:target="runHealthCheck">Memeriksa tautan...</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Readiness Progress Strip -->
    @php
        $aspectShortLabels = [
            'konten' => 'Konten Wajib',
            'uiux' => 'Desain & Aksesibilitas',
            'performa' => 'Performa & Keamanan',
        ];
    @endphp
    <section aria-label="Ringkasan Kesiapan Tautan SINOVA" class="studio-card grid items-center gap-5 p-5 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <p class="text-xs font-semibold text-ink-muted">Kesiapan Tautan SINOVA</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <span class="font-serif text-3xl font-bold text-primary">{{ $summary['filled'] }} / {{ $summary['total'] }}</span>
                <span class="rounded-md bg-primary-soft px-2 py-0.5 font-mono text-xs font-semibold text-primary">
                    Skor {{ $summary['weighted_score'] }}
                </span>
            </div>
        </div>

        <div class="grid items-stretch gap-3 sm:grid-cols-3 lg:col-span-9">
            @foreach ($summary['aspects'] as $aKey => $aspect)
                <div wire:key="sl-asp-{{ $aKey }}" class="flex flex-col justify-between gap-2.5 rounded-lg border border-hairline/70 bg-mist/60 px-3.5 py-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-ink" title="{{ $aspect['label'] }}">
                                {{ $aspectShortLabels[$aKey] ?? $aspect['label'] }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-ink-muted">Bobot {{ $aspect['weight'] }}%</p>
                        </div>
                        <span class="shrink-0 font-mono text-sm font-bold text-ink">{{ $aspect['score_pct'] }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-[#e3eae7]" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $aspect['score_pct'] }}" aria-label="{{ $aspect['label'] }}">
                        <div class="h-full rounded-full {{ (int) $aspect['score_pct'] >= 90 ? 'bg-primary' : 'bg-[#c27803]' }}" style="width: {{ min(100, (int) $aspect['score_pct']) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Criteria Workspace Card -->
    <div class="studio-card overflow-hidden">
        <!-- Filter Tabs + Open/Close All -->
        <div class="flex flex-col justify-between gap-2 border-b border-hairline px-4 pt-2 sm:flex-row sm:items-center sm:px-6">
            <div class="studio-tabs border-b-0!" role="tablist" aria-label="Filter kesehatan tautan SINOVA">
                @foreach ([
                    '' => ['Semua', $healthCounts['all']],
                    'healthy' => ['Siap / OK', $healthCounts['healthy']],
                    'warning' => ['Perlu Revisi', $healthCounts['warning']],
                    'attention' => ['Rusak / Kosong', $healthCounts['attention']],
                ] as $hKey => [$hName, $hCount])
                    <button
                        type="button"
                        role="tab"
                        aria-selected="{{ $healthFilter === $hKey ? 'true' : 'false' }}"
                        wire:click="$set('healthFilter', '{{ $hKey }}')"
                        @class([
                            'inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 border-b-2 px-3 text-sm transition-colors',
                            'border-primary font-bold text-primary' => $healthFilter === $hKey,
                            'border-transparent font-medium text-ink-body hover:text-ink' => $healthFilter !== $hKey,
                        ])
                    >
                        <span>{{ $hName }}</span>
                        <span @class([
                            'rounded-md px-2 py-0.5 font-mono text-xs font-semibold',
                            'bg-primary-soft text-primary' => $healthFilter === $hKey,
                            'bg-mist text-ink-muted' => $healthFilter !== $hKey,
                        ])>{{ $hCount }}</span>
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-2 pb-2 sm:pb-0">
                <button type="button" wire:click="openAll" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg px-2.5 text-xs font-semibold text-ink-body hover:bg-mist hover:text-ink">
                    Buka Semua
                </button>
                <button type="button" wire:click="closeAll" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg px-2.5 text-xs font-semibold text-ink-body hover:bg-mist hover:text-ink">
                    Tutup Semua
                </button>
            </div>
        </div>

        <!-- Criteria Accordions -->
        <div class="divide-y divide-hairline">
            @foreach ($groups as $entry)
                @php
                    $group = $entry['group'];
                    $isOpen = $expandedGroups[$group->id] ?? false;
                @endphp

                <section wire:key="grp-{{ $group->id }}">
                    <div @class([
                        'flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-6',
                        'bg-mist' => ! $isOpen,
                        'bg-primary-soft/60' => $isOpen,
                    ])>
                        <button
                            type="button"
                            wire:click="toggleGroup({{ $group->id }})"
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                            aria-controls="criteria-group-{{ $group->id }}"
                            class="flex min-h-11 flex-1 cursor-pointer items-center gap-2.5 text-left"
                        >
                            <span class="font-mono text-xs font-bold text-primary" aria-hidden="true">{{ $isOpen ? '▾' : '▸' }}</span>
                            <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-xs font-bold text-primary">{{ $group->code }}</span>
                            <span class="text-sm font-bold text-ink">{{ $group->name }}</span>
                        </button>

                        <div class="flex items-center gap-2">
                            @if ($entry['total'] > 1)
                                <button
                                    type="button"
                                    x-on:click="copyGroupUrls(@js($entry['all_urls_text']), @js($group->code))"
                                    class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink hover:bg-mist sm:min-h-8"
                                >
                                    Salin Grup
                                </button>
                            @endif

                            <span @class([
                                'rounded-md px-2.5 py-1 font-mono text-xs font-semibold',
                                'bg-emerald-50 text-emerald-800' => $entry['filled'] === $entry['total'],
                                'bg-amber-50 text-amber-900' => $entry['filled'] < $entry['total'],
                            ])>
                                {{ $entry['filled'] }} / {{ $entry['total'] }} siap
                            </span>
                        </div>
                    </div>

                    @if ($isOpen)
                        <div id="criteria-group-{{ $group->id }}" class="divide-y divide-hairline/70 border-t border-hairline bg-white">
                            @foreach ($entry['items'] as $row)
                                @php
                                    $item = $row['model'];
                                    $url = $row['url'];
                                    $status = $item->health_status;
                                @endphp

                                <div wire:key="crit-{{ $item->id }}" @class([
                                    'p-4 sm:px-6',
                                    'bg-[#fafdfb]' => $editingItemId === $item->id,
                                ])>
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-xs font-semibold text-primary">{{ $item->code }}</span>
                                                <span class="text-sm font-semibold text-ink">{{ $item->name }}</span>
                                            </div>
                                            <p @class([
                                                'mt-1 break-all font-mono text-xs',
                                                'text-red-700' => in_array($status, ['broken', 'empty'], true),
                                                'text-ink-muted' => ! in_array($status, ['broken', 'empty'], true),
                                            ])>
                                                {{ $url ?: 'Belum diatur (pilih halaman atau isi URL manual)' }}
                                            </p>
                                            @if ($item->health_message)
                                                <p class="mt-0.5 text-xs text-ink-muted">{{ $item->health_message }}</p>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <span @class([
                                                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold',
                                                'bg-emerald-50 text-emerald-800' => $status === 'healthy',
                                                'bg-amber-50 text-amber-900' => $status === 'warning',
                                                'bg-red-50 text-red-700' => in_array($status, ['broken', 'empty'], true),
                                            ])>
                                                @if ($status === 'healthy')
                                                    Siap (10)
                                                @elseif ($status === 'warning')
                                                    Revisi (5)
                                                @elseif ($status === 'broken')
                                                    Rusak (0)
                                                @else
                                                    Kosong (0)
                                                @endif
                                            </span>

                                            <button type="button" x-on:click="copyUrl(@js($url), @js($status))" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist sm:min-h-8">
                                                Salin
                                            </button>

                                            @if ($url && \App\Support\SafeHtml::cleanUrl($url))
                                                <a href="{{ \App\Support\SafeHtml::cleanUrl($url) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist sm:min-h-8">
                                                    Buka
                                                </a>
                                            @endif

                                            @if ($canEdit)
                                                <button type="button" wire:click="startEdit({{ $item->id }})" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary-soft sm:min-h-8">
                                                    Ubah Tautan
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($editingItemId === $item->id)
                                        <form wire:submit="saveEdit" class="mt-4 space-y-4 rounded-xl border border-primary bg-white p-4">
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <flux:field>
                                                    <flux:label for="linked_page_{{ $item->id }}">Pilih halaman internal</flux:label>
                                                    <x-admin.select id="linked_page_{{ $item->id }}" wire:model="editingLinkedPageId" search-placeholder="Cari judul atau slug halaman...">
                                                        <option value="">Pilih halaman...</option>
                                                        @foreach ($pages as $pg)
                                                            <option value="{{ $pg->id }}">{{ $pg->title }} (/halaman/{{ $pg->slug }})</option>
                                                        @endforeach
                                                    </x-admin.select>
                                                </flux:field>

                                                <flux:field>
                                                    <flux:label for="manual_url_{{ $item->id }}">Atau tautan manual (HTTPS, opsional)</flux:label>
                                                    <flux:input id="manual_url_{{ $item->id }}" wire:model="editingManualUrl" placeholder="https://..." class="font-mono" />
                                                    <flux:error name="editingManualUrl" />
                                                </flux:field>
                                            </div>

                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                <label class="inline-flex min-h-10 cursor-pointer items-center gap-2.5 text-xs font-medium text-ink">
                                                    <input type="checkbox" wire:model="editingUseManual" class="size-4.5 rounded border-[#768780] text-primary">
                                                    <span>Gunakan tautan manual sebagai pengganti halaman internal</span>
                                                </label>

                                                <div class="flex items-center gap-2">
                                                    <button type="button" wire:click="cancelEdit" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink hover:bg-mist sm:min-h-9">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-9">
                                                        Simpan Tautan
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</div>
