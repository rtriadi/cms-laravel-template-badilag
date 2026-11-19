<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
        <div role="tablist" aria-label="Pengaturan Kriteria SINOVA" class="flex flex-wrap gap-2">
            @foreach (['items' => 'Butir Kriteria (40 Item)', 'groups' => 'Kelompok & Bobot', 'import' => 'Impor / Ekspor JSON Juknis'] as $tKey => $tLabel)
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === $tKey ? 'true' : 'false' }}"
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

        <button type="button" wire:click="exportJson" class="min-h-11 cursor-pointer rounded-md border border-primary bg-white px-4 py-2 text-xs font-semibold text-primary hover:bg-primary-soft">
            Ekspor JSON Juknis {{ $activeVersion }}
        </button>
    </div>

    @if ($tab === 'items')
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h2 class="font-serif text-lg font-bold text-ink">
                        {{ $editingItemId ? 'Edit Butir Kriteria' : 'Tambah Butir Kriteria' }}
                    </h2>
                    @if ($editingItemId)
                        <button type="button" wire:click="newItem" class="cursor-pointer text-xs font-semibold text-primary hover:underline">Butir Baru</button>
                    @endif
                </div>

                <form wire:submit="saveItem" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label for="ci_group">Kelompok Kriteria</flux:label>
                        <flux:select id="ci_group" wire:model="criteria_group_id" required>
                            @foreach ($groups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->code }} - {{ $grp->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="criteria_group_id" />
                    </flux:field>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="ci_code">Kode Butir</flux:label>
                            <flux:input id="ci_code" wire:model="item_code" required placeholder="Contoh: 1.a.3.12" />
                            <flux:error name="item_code" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="ci_order">Urutan</flux:label>
                            <flux:input id="ci_order" type="number" wire:model="item_sort_order" min="1" required />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label for="ci_name">Nama Butir Kriteria</flux:label>
                        <flux:input id="ci_name" wire:model="item_name" required placeholder="Nama kriteria penilaian SINOVA" />
                        <flux:error name="item_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="ci_desc">Keterangan Juknis</flux:label>
                        <flux:textarea id="ci_desc" wire:model="item_description" rows="2" placeholder="Penjelasan singkat bukti dukung..." />
                    </flux:field>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="ci_scope">Cakupan Tingkat</flux:label>
                            <flux:select id="ci_scope" wire:model="tingkat_scope">
                                <option value="all">Semua Tingkat</option>
                                <option value="pertama">Khusus Tingkat Pertama</option>
                                <option value="banding">Khusus Tingkat Banding</option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label for="ci_type">Tipe Target</flux:label>
                            <flux:select id="ci_type" wire:model="item_type">
                                <option value="page">Halaman CMS</option>
                                <option value="collection">Koleksi Dokumen</option>
                                <option value="external_app">Aplikasi Eksternal</option>
                                <option value="system">Rute Sistem</option>
                                <option value="link">Tautan Umum</option>
                            </flux:select>
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label for="ci_route">Rute Bawaan (Opsional)</flux:label>
                        <flux:input id="ci_route" wire:model="default_route" placeholder="/halaman/... atau /status-sistem" />
                    </flux:field>

                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                        {{ $editingItemId ? 'Simpan Perubahan Butir' : 'Simpan Butir Kriteria' }}
                    </flux:button>
                </form>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Daftar Kelompok & Butir Juknis {{ $activeVersion }}</h2>
                <div class="mt-4 space-y-5">
                    @forelse ($groups as $grp)
                        <div wire:key="grp-{{ $grp->id }}" class="rounded-lg border border-hairline bg-mist p-3.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="font-mono text-xs font-bold text-primary">{{ $grp->code }}</span>
                                    <span class="text-sm font-bold text-ink">{{ $grp->name }}</span>
                                </div>
                                <span class="rounded bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-ink-muted">
                                    {{ strtoupper($grp->aspect) }} | Bobot {{ number_format((float) $grp->weight, 1) }}%
                                </span>
                            </div>

                            <ul class="mt-2.5 divide-y divide-hairline rounded border border-hairline bg-white px-3">
                                @forelse ($grp->items as $cItem)
                                    <li wire:key="ci-{{ $cItem->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                                        <div class="min-w-0 flex-1">
                                            <span class="font-mono font-bold text-primary">{{ $cItem->code }}</span>
                                            <span class="font-medium text-ink">{{ $cItem->name }}</span>
                                            <span class="ml-1 rounded bg-mist px-1.5 py-0.5 font-mono text-[10px] text-ink-muted">{{ $cItem->tingkat_scope }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" wire:click="editItem({{ $cItem->id }})" class="cursor-pointer rounded border border-primary px-2 py-1 font-semibold text-primary hover:bg-primary-soft">Edit</button>
                                            <button type="button" wire:click="deleteItem({{ $cItem->id }})" wire:confirm="Hapus butir kriteria ini?" class="cursor-pointer rounded border border-red-200 px-2 py-1 text-red-700 hover:bg-red-50">Hapus</button>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-2 text-xs text-ink-muted">Belum ada butir kriteria di kelompok ini.</li>
                                @endforelse
                            </ul>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-ink-muted">Belum ada kelompok kriteria pada versi ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif ($tab === 'groups')
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">
                    {{ $editingGroupId ? 'Edit Kelompok Kriteria' : 'Tambah Kelompok Kriteria' }}
                </h2>
                <form wire:submit="saveGroup" class="mt-4 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="cg_code">Kode Kelompok</flux:label>
                            <flux:input id="cg_code" wire:model="group_code" required placeholder="Contoh: 1.a.3" />
                            <flux:error name="group_code" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="cg_order">Urutan</flux:label>
                            <flux:input id="cg_order" type="number" wire:model="group_sort_order" min="1" required />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label for="cg_name">Nama Kelompok</flux:label>
                        <flux:input id="cg_name" wire:model="group_name" required placeholder="Nama kelompok indikator" />
                        <flux:error name="group_name" />
                    </flux:field>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="cg_aspect">Aspek Penilaian</flux:label>
                            <flux:select id="cg_aspect" wire:model="group_aspect">
                                <option value="konten">Konten & Keterbukaan (60%)</option>
                                <option value="ui_ux">Tampilan & Aksesibilitas (20%)</option>
                                <option value="performa">Teknis, Keamanan & Kecepatan (20%)</option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label for="cg_weight">Bobot (%)</flux:label>
                            <flux:input id="cg_weight" type="number" step="0.1" wire:model="group_weight" min="0" max="100" required />
                        </flux:field>
                    </div>
                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                        Simpan Kelompok Kriteria
                    </flux:button>
                </form>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Daftar Kelompok Kriteria ({{ $groups->count() }})</h2>
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($groups as $grp)
                        <li wire:key="glist-{{ $grp->id }}" class="flex flex-wrap items-center justify-between gap-2 py-3 text-xs">
                            <div>
                                <span class="font-mono font-bold text-primary">{{ $grp->code }}</span>
                                <span class="font-semibold text-ink">{{ $grp->name }}</span>
                                <span class="ml-2 text-ink-muted">({{ $grp->items->count() }} butir)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" wire:click="editGroup({{ $grp->id }})" class="min-h-11 cursor-pointer rounded border border-primary px-3 py-1 font-semibold text-primary">Edit</button>
                                <button type="button" wire:click="deleteGroup({{ $grp->id }})" wire:confirm="Hapus kelompok beserta seluruh butir di dalamnya?" class="min-h-11 cursor-pointer rounded border border-red-200 px-3 py-1 font-semibold text-red-700">Hapus</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Pembaruan Kriteria Juknis via JSON</h2>
                <p class="mt-2 text-xs text-ink-muted">
                    Unggah berkas JSON atau tempelkan struktur Juknis SINOVA baru. Impor menjaga tautan halaman yang sudah terhubung serta override URL manual satker.
                </p>

                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label for="json_file">Unggah Berkas JSON (Opsional)</flux:label>
                        <input id="json_file" type="file" wire:model="jsonFile" accept=".json,application/json" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-hairline file:bg-mist file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary" />
                        <flux:error name="jsonFile" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="json_raw">Atau Tempel Konten JSON Juknis</flux:label>
                        <flux:textarea id="json_raw" wire:model="jsonInput" rows="10" class="font-mono text-xs" placeholder='{"juknis_version": "2026", "groups": [...]}' />
                        <flux:error name="jsonInput" />
                    </flux:field>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" wire:click="previewImport" class="min-h-11 cursor-pointer rounded-md border border-primary bg-white px-4 py-2 text-xs font-semibold text-primary hover:bg-primary-soft">
                            Pratinjau Perbedaan (Diff)
                        </button>
                        <button type="button" wire:click="applyImport" wire:confirm="Terapkan impor kriteria Juknis JSON sekarang?" class="min-h-11 cursor-pointer rounded-md bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary-hover">
                            Terapkan Impor JSON
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Pratinjau Perbedaan Impor</h2>
                @if ($previewDiff === null)
                    <p class="py-10 text-center text-xs text-ink-muted">Klik tombol "Pratinjau Perbedaan (Diff)" untuk melihat perubahan sebelum menyimpan.</p>
                @else
                    <dl class="mt-4 space-y-3 text-xs">
                        <div>
                            <dt class="font-semibold text-ink">Versi Juknis</dt>
                            <dd class="font-mono text-primary">{{ $previewDiff['juknis_version'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink">Kelompok Baru ({{ count($previewDiff['added_groups']) }})</dt>
                            <dd class="font-mono text-ink-muted">{{ implode(', ', $previewDiff['added_groups']) ?: 'Tidak ada' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink">Butir Baru ({{ count($previewDiff['added_items']) }})</dt>
                            <dd class="font-mono text-emerald-800">{{ implode(', ', $previewDiff['added_items']) ?: 'Tidak ada' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink">Butir Diperbarui ({{ count($previewDiff['updated_items']) }})</dt>
                            <dd class="font-mono text-amber-800">{{ implode(', ', $previewDiff['updated_items']) ?: 'Tidak ada' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink">Butir Tidak Ada di JSON Baru ({{ count($previewDiff['removed_items']) }})</dt>
                            <dd class="font-mono text-red-700">{{ implode(', ', $previewDiff['removed_items']) ?: 'Tidak ada' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        </div>
    @endif
</div>
