<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Kriteria Juknis SINOVA</h1>
            <p class="mt-1 text-sm text-ink-body">
                Perbarui daftar kelompok dan butir penilaian bila Badilag menerbitkan SK Dirjen terbaru. Gunakan impor JSON dengan pratinjau perubahan sebelum diterapkan.
            </p>
        </div>
        <button type="button" wire:click="exportJson" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center justify-center whitespace-nowrap rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10">
            Ekspor JSON Cadangan ({{ $activeVersion }})
        </button>
    </div>

    <div class="studio-card overflow-hidden">
        <div role="tablist" aria-label="Pengaturan Kriteria SINOVA" class="studio-tabs px-4 pt-2 sm:px-6">
            @foreach ([
                'items' => 'Kelompok & Butir',
                'groups' => 'Pengaturan Kelompok',
                'import' => 'Impor & Pembaruan JSON',
            ] as $tKey => $tLabel)
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === $tKey ? 'true' : 'false' }}"
                    wire:click="$set('tab', '{{ $tKey }}')"
                    @class([
                        'inline-flex min-h-11 shrink-0 cursor-pointer items-center border-b-2 px-3 text-sm transition-colors',
                        'border-primary font-bold text-primary' => $tab === $tKey,
                        'border-transparent font-medium text-ink-body hover:text-ink' => $tab !== $tKey,
                    ])
                >
                    {{ $tLabel }}
                </button>
            @endforeach
        </div>

        <div class="p-4 sm:p-6">
            @if ($tab === 'items')
                <div class="grid items-start gap-6 xl:grid-cols-12">
                    <div class="rounded-xl border border-hairline bg-mist p-5 xl:col-span-5">
                        <div class="flex items-center justify-between border-b border-hairline pb-3">
                            <h2 class="text-base font-bold text-ink">
                                {{ $editingItemId ? 'Ubah Butir Kriteria' : 'Tambah Butir Kriteria' }}
                            </h2>
                            @if ($editingItemId)
                                <button type="button" wire:click="newItem" class="min-h-10 cursor-pointer text-xs font-semibold text-primary hover:underline">+ Butir Baru</button>
                            @endif
                        </div>

                        <form wire:submit="saveItem" class="mt-4 space-y-4">
                            <flux:field>
                                <flux:label for="ci_group">Kelompok kriteria</flux:label>
                                <x-admin.select id="ci_group" wire:model="criteria_group_id" required search-placeholder="Cari kelompok kriteria...">
                                    @foreach ($groups as $grp)
                                        <option value="{{ $grp->id }}">{{ $grp->code }} - {{ $grp->name }}</option>
                                    @endforeach
                                </x-admin.select>
                                <flux:error name="criteria_group_id" />
                            </flux:field>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="ci_code">Kode butir</flux:label>
                                    <flux:input id="ci_code" wire:model="item_code" required placeholder="Contoh: 1.a.3.l" class="font-mono" />
                                    <flux:error name="item_code" />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="ci_order">Urutan</flux:label>
                                    <flux:input id="ci_order" type="number" wire:model="item_sort_order" min="1" required />
                                </flux:field>
                            </div>

                            <flux:field>
                                <flux:label for="ci_name">Nama butir kriteria</flux:label>
                                <flux:input id="ci_name" wire:model="item_name" required placeholder="Nama kriteria penilaian SINOVA" />
                                <flux:error name="item_name" />
                            </flux:field>

                            <flux:field>
                                <flux:label for="ci_desc">Keterangan juknis</flux:label>
                                <x-rich-editor
                                    id="ci_desc"
                                    model="item_description"
                                    label="Keterangan juknis"
                                    placeholder="Penjelasan singkat bukti dukung..."
                                    :min-height="150"
                                    :compact="true"
                                />
                            </flux:field>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="ci_scope">Cakupan tingkat</flux:label>
                                    <x-admin.select id="ci_scope" wire:model="tingkat_scope">
                                        <option value="all">Semua Tingkat</option>
                                        <option value="pertama">Khusus Tingkat Pertama</option>
                                        <option value="banding">Khusus Tingkat Banding</option>
                                    </x-admin.select>
                                </flux:field>
                                <flux:field>
                                    <flux:label for="ci_type">Tipe target</flux:label>
                                    <x-admin.select id="ci_type" wire:model="item_type">
                                        <option value="page">Halaman CMS</option>
                                        <option value="collection">Koleksi Dokumen</option>
                                        <option value="external_app">Aplikasi Eksternal</option>
                                        <option value="system">Rute Sistem</option>
                                        <option value="link">Tautan Umum</option>
                                    </x-admin.select>
                                </flux:field>
                            </div>

                            <flux:field>
                                <flux:label for="ci_route">Rute bawaan (opsional)</flux:label>
                                <flux:input id="ci_route" wire:model="default_route" placeholder="/halaman/... atau /status-sistem" class="font-mono" />
                            </flux:field>

                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                {{ $editingItemId ? 'Simpan Perubahan Butir' : 'Simpan Butir Kriteria' }}
                            </flux:button>
                        </form>
                    </div>

                    <div class="space-y-4 xl:col-span-7">
                        <h2 class="text-base font-bold text-ink">Daftar Kelompok &amp; Butir Juknis {{ $activeVersion }}</h2>
                        @forelse ($groups as $grp)
                            <div wire:key="grp-{{ $grp->id }}" class="rounded-xl border border-hairline bg-mist p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-xs font-bold text-primary">{{ $grp->code }}</span>
                                        <span class="text-sm font-bold text-ink">{{ $grp->name }}</span>
                                    </div>
                                    <span class="rounded bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-ink-muted">
                                        {{ strtoupper($grp->aspect) }} &middot; {{ number_format((float) $grp->weight, 1) }}%
                                    </span>
                                </div>

                                <ul class="mt-3 divide-y divide-hairline rounded-lg border border-hairline bg-white px-3">
                                    @forelse ($grp->items as $cItem)
                                        <li wire:key="ci-{{ $cItem->id }}" class="flex flex-col gap-2 py-2.5 text-xs sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <span class="font-mono font-bold text-primary">{{ $cItem->code }}</span>
                                                <span class="ml-1 font-medium text-ink">{{ $cItem->name }}</span>
                                                <span class="ml-1 rounded bg-mist px-1.5 py-0.5 font-mono text-[11px] text-ink-muted">{{ $cItem->tingkat_scope }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" wire:click="editItem({{ $cItem->id }})" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-8">Ubah</button>
                                                <button type="button" wire:click="deleteItem({{ $cItem->id }})" wire:confirm="Hapus butir kriteria ini?" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">Hapus</button>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="py-3 text-xs text-ink-muted">Belum ada butir kriteria di kelompok ini.</li>
                                    @endforelse
                                </ul>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-ink-muted">Belum ada kelompok kriteria pada versi ini.</p>
                        @endforelse
                    </div>
                </div>
            @elseif ($tab === 'groups')
                <div class="grid items-start gap-6 lg:grid-cols-12">
                    <div class="rounded-xl border border-hairline bg-mist p-5 lg:col-span-5">
                        <h2 class="border-b border-hairline pb-3 text-base font-bold text-ink">
                            {{ $editingGroupId ? 'Ubah Kelompok Kriteria' : 'Tambah Kelompok Kriteria' }}
                        </h2>
                        <form wire:submit="saveGroup" class="mt-4 space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="cg_code">Kode kelompok</flux:label>
                                    <flux:input id="cg_code" wire:model="group_code" required placeholder="Contoh: 1.a.3" class="font-mono" />
                                    <flux:error name="group_code" />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="cg_order">Urutan</flux:label>
                                    <flux:input id="cg_order" type="number" wire:model="group_sort_order" min="1" required />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label for="cg_name">Nama kelompok</flux:label>
                                <flux:input id="cg_name" wire:model="group_name" required placeholder="Nama kelompok indikator" />
                                <flux:error name="group_name" />
                            </flux:field>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="cg_aspect">Aspek penilaian</flux:label>
                                    <x-admin.select id="cg_aspect" wire:model="group_aspect">
                                        <option value="konten">Konten &amp; Keterbukaan (60%)</option>
                                        <option value="uiux">Tampilan &amp; Aksesibilitas (20%)</option>
                                        <option value="performa">Teknis, Keamanan &amp; Kecepatan (20%)</option>
                                    </x-admin.select>
                                </flux:field>
                                <flux:field>
                                    <flux:label for="cg_weight">Bobot (%)</flux:label>
                                    <flux:input id="cg_weight" type="number" step="0.1" wire:model="group_weight" min="0" max="100" required />
                                </flux:field>
                            </div>
                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                Simpan Kelompok Kriteria
                            </flux:button>
                        </form>
                    </div>

                    <div class="lg:col-span-7">
                        <h2 class="mb-3 text-base font-bold text-ink">Daftar Kelompok Kriteria ({{ $groups->count() }})</h2>
                        <ul class="divide-y divide-hairline rounded-xl border border-hairline bg-white">
                            @foreach ($groups as $grp)
                                <li wire:key="glist-{{ $grp->id }}" class="flex flex-col gap-3 p-4 text-xs sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <span class="font-mono font-bold text-primary">{{ $grp->code }}</span>
                                        <span class="ml-1 text-sm font-semibold text-ink">{{ $grp->name }}</span>
                                        <span class="ml-1 text-ink-muted">({{ $grp->items->count() }} butir)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" wire:click="editGroup({{ $grp->id }})" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-8">Ubah</button>
                                        <button type="button" wire:click="deleteGroup({{ $grp->id }})" wire:confirm="Hapus kelompok beserta seluruh butir di dalamnya?" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">Hapus</button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @else
                <div class="space-y-5">
                    <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-950">
                        <strong>Pratinjau perubahan wajib diperiksa sebelum diterapkan.</strong>
                        Impor JSON tidak menghapus tautan halaman yang sudah terpasang maupun override URL manual satker.
                    </div>

                    <div class="grid items-start gap-6 lg:grid-cols-12">
                        <div class="space-y-4 lg:col-span-7">
                            <flux:field>
                                <flux:label for="json_file">Unggah berkas kriteria-sinova.json (opsional)</flux:label>
                                <input id="json_file" type="file" wire:model="jsonFile" accept=".json,application/json" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:min-h-10 file:cursor-pointer file:rounded-lg file:border file:border-hairline file:bg-mist file:px-3 file:text-xs file:font-semibold file:text-primary" />
                                <flux:error name="jsonFile" />
                            </flux:field>

                            <flux:field>
                                <flux:label for="json_raw">Atau tempel isi JSON juknis</flux:label>
                                <flux:textarea id="json_raw" wire:model="jsonInput" rows="10" class="font-mono text-xs" placeholder='{"juknis_version": "2026", "groups": [...]}' />
                                <flux:error name="jsonInput" />
                            </flux:field>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button type="button" wire:click="previewImport" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-primary bg-white px-4 text-xs font-semibold text-primary hover:bg-primary-soft">
                                    Periksa Pratinjau Perbedaan
                                </button>
                                <button type="button" wire:click="applyImport" wire:confirm="Terapkan impor kriteria Juknis JSON sekarang?" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover">
                                    Terapkan Pembaruan Kriteria
                                </button>
                            </div>
                        </div>

                        <div class="rounded-xl border border-hairline bg-mist p-5 lg:col-span-5">
                            <h2 class="text-base font-bold text-ink">Pratinjau Perbedaan Impor</h2>
                            @if ($previewDiff === null)
                                <p class="py-10 text-center text-xs text-ink-muted">
                                    Klik tombol <strong>Periksa Pratinjau Perbedaan</strong> untuk melihat perubahan sebelum menyimpan.
                                </p>
                            @else
                                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-lg border border-hairline bg-white p-2.5">
                                        <p class="font-mono text-base font-bold text-emerald-800">+{{ count($previewDiff['added_items']) }}</p>
                                        <p class="text-[11px] text-ink-muted">Ditambah</p>
                                    </div>
                                    <div class="rounded-lg border border-hairline bg-white p-2.5">
                                        <p class="font-mono text-base font-bold text-amber-800">~{{ count($previewDiff['updated_items']) }}</p>
                                        <p class="text-[11px] text-ink-muted">Diubah</p>
                                    </div>
                                    <div class="rounded-lg border border-hairline bg-white p-2.5">
                                        <p class="font-mono text-base font-bold text-red-700">-{{ count($previewDiff['removed_items']) }}</p>
                                        <p class="text-[11px] text-ink-muted">Tidak Ada</p>
                                    </div>
                                </div>

                                <dl class="mt-4 space-y-3 text-xs">
                                    <div class="rounded-lg border border-hairline bg-white p-3">
                                        <dt class="font-semibold text-ink">Versi Juknis</dt>
                                        <dd class="mt-0.5 font-mono text-primary">{{ $previewDiff['juknis_version'] }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-hairline bg-white p-3">
                                        <dt class="font-semibold text-ink">Kelompok Baru ({{ count($previewDiff['added_groups']) }})</dt>
                                        <dd class="mt-0.5 font-mono text-ink-muted">{{ implode(', ', $previewDiff['added_groups']) ?: 'Tidak ada' }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-hairline bg-white p-3">
                                        <dt class="font-semibold text-ink">Butir Baru ({{ count($previewDiff['added_items']) }})</dt>
                                        <dd class="mt-0.5 font-mono text-emerald-800">{{ implode(', ', $previewDiff['added_items']) ?: 'Tidak ada' }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-hairline bg-white p-3">
                                        <dt class="font-semibold text-ink">Butir Diperbarui ({{ count($previewDiff['updated_items']) }})</dt>
                                        <dd class="mt-0.5 font-mono text-amber-800">{{ implode(', ', $previewDiff['updated_items']) ?: 'Tidak ada' }}</dd>
                                    </div>
                                    <div class="rounded-lg border border-hairline bg-white p-3">
                                        <dt class="font-semibold text-ink">Butir Tidak Ada di JSON Baru ({{ count($previewDiff['removed_items']) }})</dt>
                                        <dd class="mt-0.5 font-mono text-red-700">{{ implode(', ', $previewDiff['removed_items']) ?: 'Tidak ada' }}</dd>
                                    </div>
                                </dl>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
