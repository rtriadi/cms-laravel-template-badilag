<div
    class="space-y-5"
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
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div x-cloak x-show="copiedNotice !== ''" role="status" class="flex items-center justify-between rounded-lg border border-primary/30 bg-white px-4 py-2.5 text-sm font-medium text-ink">
        <span x-text="copiedNotice"></span>
        <button type="button" x-on:click="copiedNotice = ''" class="inline-flex min-h-11 items-center px-2 text-xs font-semibold text-primary">Tutup</button>
    </div>

    <!-- Header Summary & Bulk Actions -->
    <div class="rounded-lg border border-hairline bg-white p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-serif text-xl font-bold text-ink">Link Kriteria</h2>
                <p class="mt-1 text-sm text-ink-body">
                    Kelola URL untuk setiap kriteria evaluasi. Link ini berlaku sampai Anda mengubahnya.
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-4 font-mono text-xs font-semibold">
                    <span class="text-emerald-700">{{ $summary['filled'] }} link terisi</span>
                    <span class="text-red-700">{{ $summary['total'] - $summary['filled'] }} link kosong</span>
                    <span class="text-ink-muted">Skor simulasi: {{ $summary['weighted_score'] }} / 100</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button type="button" wire:click="openAll" variant="outline" class="min-h-11">
                    Buka Semua
                </flux:button>
                <flux:button type="button" wire:click="closeAll" variant="outline" class="min-h-11">
                    Tutup Semua
                </flux:button>
                <flux:button type="button" wire:click="exportCsv" variant="outline" class="min-h-11 border-primary text-primary">
                    Ekspor CSV
                </flux:button>
                @if ($canEdit)
                    <flux:button type="button" wire:click="runHealthCheck" variant="primary" class="min-h-11 bg-primary font-semibold text-white hover:bg-primary-hover">
                        <span wire:loading.remove wire:target="runHealthCheck">Cek Kesehatan Link</span>
                        <span wire:loading wire:target="runHealthCheck">Memeriksa...</span>
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    <!-- Criteria Accordions -->
    <div class="space-y-3">
        @foreach ($groups as $entry)
            @php
                $group = $entry['group'];
                $isOpen = $expandedGroups[$group->id] ?? false;
            @endphp

            <section wire:key="grp-{{ $group->id }}" @class([
                'overflow-hidden rounded-lg border bg-white',
                'border-primary' => $isOpen,
                'border-hairline' => ! $isOpen,
            ])>
                <div @class([
                    'flex flex-wrap items-center justify-between gap-3 px-5 py-3.5',
                    'bg-primary-soft' => $isOpen,
                    'bg-white' => ! $isOpen,
                ])>
                    <button
                        type="button"
                        wire:click="toggleGroup({{ $group->id }})"
                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        aria-controls="criteria-group-{{ $group->id }}"
                        class="flex min-h-11 flex-1 items-center gap-3 text-left"
                    >
                        <span class="font-mono text-xs font-bold text-primary">{{ $isOpen ? 'v' : '>' }}</span>
                        <span class="text-sm font-semibold text-ink">
                            {{ $group->code }} · {{ $group->name }}
                        </span>
                    </button>

                    <div class="flex items-center gap-2">
                        @if ($entry['total'] > 1)
                            <button
                                type="button"
                                x-on:click="copyGroupUrls(@js($entry['all_urls_text']), @js($group->code))"
                                class="inline-flex min-h-11 cursor-pointer items-center rounded border border-hairline bg-white px-2.5 text-xs font-semibold text-ink hover:bg-mist"
                            >
                                Salin Semua
                            </button>
                        @endif

                        <span class="rounded border border-hairline bg-mist px-2.5 py-1 font-mono text-xs font-medium text-ink">
                            @if ($entry['total'] === 1 && $entry['items']->first()['model']->item_type === 'item')
                                1 item
                            @else
                                {{ $entry['filled'] }}/{{ $entry['total'] }} link
                            @endif
                        </span>
                    </div>
                </div>

                @if ($isOpen)
                    <div id="criteria-group-{{ $group->id }}" class="divide-y divide-hairline border-t border-hairline">
                        @foreach ($entry['items'] as $row)
                            @php
                                $item = $row['model'];
                                $url = $row['url'];
                                $status = $item->health_status;
                            @endphp

                            <div wire:key="crit-{{ $item->id }}" class="p-4 sm:px-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0 lg:w-72">
                                        <p class="text-sm font-semibold text-ink">
                                            <span class="font-mono text-xs text-primary">{{ $item->code }}</span>
                                            {{ $item->name }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-ink-muted">
                                            {{ $item->use_manual_url ? 'Mode: Override Manual' : ($item->linkedPage ? 'Mode: Auto Halaman' : 'Mode: Rute Sistem') }}
                                        </p>
                                    </div>

                                    <div class="min-w-0 flex-1 rounded-md border border-hairline bg-mist px-3 py-2 font-mono text-xs text-ink">
                                        @if ($url)
                                            <span class="break-all">{{ $url }}</span>
                                        @else
                                            <span class="text-ink-muted">Belum diatur (pilih halaman atau isi URL manual)</span>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'inline-flex items-center rounded border px-2.5 py-1 text-xs font-semibold',
                                            'border-emerald-200 bg-emerald-50 text-emerald-800' => $status === 'healthy',
                                            'border-amber-200 bg-amber-50 text-amber-900' => $status === 'warning',
                                            'border-red-200 bg-red-50 text-red-800' => in_array($status, ['broken', 'empty'], true),
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

                                        <button
                                            type="button"
                                            x-on:click="copyUrl(@js($url), @js($status))"
                                            class="inline-flex min-h-11 cursor-pointer items-center rounded-md bg-primary px-3 text-xs font-semibold text-white hover:bg-primary-hover"
                                        >
                                            Salin
                                        </button>

                                        @if ($url)
                                            <a
                                                href="{{ $url }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="inline-flex min-h-11 items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                                            >
                                                Buka
                                            </a>
                                        @endif

                                        @if ($canEdit)
                                            <button
                                                type="button"
                                                wire:click="startEdit({{ $item->id }})"
                                                class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-primary hover:bg-primary-soft"
                                            >
                                                Ubah
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if ($item->health_message)
                                    <p class="mt-2 text-xs text-ink-muted">Catatan pemeriksaan: {{ $item->health_message }}</p>
                                @endif

                                @if ($editingItemId === $item->id)
                                    <form wire:submit="saveEdit" class="mt-4 space-y-3 rounded-lg border border-primary/40 bg-mist p-4">
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <flux:field>
                                                <flux:label for="linked_page_{{ $item->id }}">Tautkan Halaman CMS</flux:label>
                                                <flux:select id="linked_page_{{ $item->id }}" wire:model="editingLinkedPageId">
                                                    <option value="">-- Pilih halaman --</option>
                                                    @foreach ($pages as $pg)
                                                        <option value="{{ $pg->id }}">{{ $pg->title }} (/halaman/{{ $pg->slug }})</option>
                                                    @endforeach
                                                </flux:select>
                                            </flux:field>

                                            <flux:field>
                                                <flux:label for="manual_url_{{ $item->id }}">URL Manual (Opsional / Override)</flux:label>
                                                <flux:input id="manual_url_{{ $item->id }}" wire:model="editingManualUrl" placeholder="https://..." />
                                                <flux:error name="editingManualUrl" />
                                            </flux:field>
                                        </div>

                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-ink">
                                            <input type="checkbox" wire:model="editingUseManual" class="size-4 rounded border-hairline text-primary">
                                            <span>Prioritaskan URL manual (abaikan tautan otomatis halaman)</span>
                                        </label>

                                        <div class="flex items-center gap-2">
                                            <flux:button type="submit" variant="primary" class="min-h-11 cursor-pointer bg-primary text-xs font-semibold text-white">
                                                Simpan Tautan
                                            </flux:button>
                                            <flux:button type="button" wire:click="cancelEdit" variant="outline" class="min-h-11 cursor-pointer text-xs">
                                                Batal
                                            </flux:button>
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
