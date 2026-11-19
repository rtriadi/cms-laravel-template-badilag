<div class="space-y-6">
    @if ($statusNotice)
        <div class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Page Editor -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-6">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">
                    {{ $editingId ? 'Edit Halaman & Blok' : 'Buat Halaman Baru' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="text-xs font-semibold text-primary">Halaman Baru</button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="page_title">Judul Halaman</flux:label>
                    <flux:input id="page_title" wire:model.live="title" required placeholder="Contoh: Rencana Strategis @{{satker.nama}}" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="page_slug">Slug URL (/halaman/...)</flux:label>
                    <flux:input id="page_slug" wire:model="slug" required placeholder="transparansi/renstra" />
                    <p class="mt-1 text-[11px] text-ink-muted">Mengubah slug otomatis membuat pengalihan permanen (Redirect 301).</p>
                    <flux:error name="slug" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="page_cat">Kategori KMA 2-144</flux:label>
                        <flux:select id="page_cat" wire:model="category_tag">
                            <option value="berkala">Berkala</option>
                            <option value="serta_merta">Serta Merta</option>
                            <option value="setiap_saat">Setiap Saat</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="page_crit">Kode Kriteria</flux:label>
                        <flux:select id="page_crit" wire:model="criteria_code">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($criteriaOptions as $cOpt)
                                <option value="{{ $cOpt->code }}">{{ $cOpt->code }} - {{ $cOpt->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="page_tingkat">Tingkat</flux:label>
                        <flux:select id="page_tingkat" wire:model="tingkat_scope">
                            <option value="all">Semua Tingkat</option>
                            <option value="pertama">Tingkat Pertama</option>
                            <option value="banding">Tingkat Banding</option>
                        </flux:select>
                    </flux:field>
                </div>

                <!-- Dynamic Blocks -->
                <div class="space-y-3 rounded-lg border border-hairline bg-mist p-3.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-ink">Blok Konten Halaman</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['richtext' => '+ Teks', 'document_list' => '+ Dokumen', 'embed_fallback' => '+ Embed/Cadangan', 'external_portal' => '+ Portal', 'map_yurisdiksi' => '+ Peta', 'satker_contact' => '+ Kontak'] as $bType => $bLabel)
                                <button type="button" wire:click="addBlock('{{ $bType }}')" class="cursor-pointer rounded border border-hairline bg-white px-2 py-1 text-[11px] font-semibold text-primary hover:bg-primary-soft">
                                    {{ $bLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($blocks as $index => $block)
                        <div wire:key="blk-{{ $editingId ?? 'new' }}-{{ $index }}-{{ $block['type'] ?? 'richtext' }}" class="space-y-2 rounded-md border border-hairline bg-white p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-primary">Blok #{{ $index + 1 }}: {{ $block['type'] }}</span>
                                <button type="button" wire:click="removeBlock({{ $index }})" class="text-xs font-semibold text-red-700">Hapus</button>
                            </div>

                            <flux:input wire:model="blocks.{{ $index }}.heading" aria-label="Judul subbagian blok #{{ $index + 1 }}" placeholder="Judul subbagian blok" />

                            @if (($block['type'] ?? '') === 'richtext')
                                <x-rich-editor
                                    model="blocks.{{ $index }}.content"
                                    label="Konten blok teks #{{ $index + 1 }}"
                                    placeholder="Tulis isi konten halaman, sisipkan tabel, gambar, atau lampiran PDF..."
                                    :min-height="260"
                                />
                            @elseif (($block['type'] ?? '') === 'document_list')
                                <flux:select wire:model="blocks.{{ $index }}.collection_type" aria-label="Koleksi dokumen blok #{{ $index + 1 }}">
                                    @foreach ($collectionTypes as $cKey => $cLabel)
                                        <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                    @endforeach
                                </flux:select>
                            @elseif (($block['type'] ?? '') === 'embed_fallback')
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <flux:select wire:model="blocks.{{ $index }}.source_key" aria-label="Sumber embed blok #{{ $index + 1 }}">
                                        <option value="simtepa_pegawai">SIMTEPA Pegawai</option>
                                        <option value="simtepa_struktur">SIMTEPA Struktur</option>
                                        <option value="sipp_jadwal">SIPP Jadwal Sidang</option>
                                        <option value="sipp_statistik">SIPP Statistik Perkara</option>
                                    </flux:select>
                                    <flux:select wire:model="blocks.{{ $index }}.fallback_mode" aria-label="Mode cadangan lokal blok #{{ $index + 1 }}">
                                        <option value="officials_table">Cadangan Tabel Pegawai</option>
                                        <option value="org_chart">Cadangan Struktur Organisasi</option>
                                        <option value="court_schedule">Cadangan Jadwal Sidang</option>
                                        <option value="case_statistics">Cadangan Statistik Perkara</option>
                                    </flux:select>
                                </div>
                                <flux:input wire:model="blocks.{{ $index }}.note" aria-label="Catatan mode cadangan blok #{{ $index + 1 }}" placeholder="Catatan penjelas (opsional)" />
                            @elseif (($block['type'] ?? '') === 'external_portal')
                                <flux:input wire:model="blocks.{{ $index }}.default_url" aria-label="URL bawaan portal blok #{{ $index + 1 }}" placeholder="https://..." />
                                <x-rich-editor
                                    model="blocks.{{ $index }}.description"
                                    label="Penjelasan portal blok #{{ $index + 1 }}"
                                    placeholder="Penjelasan portal, panduan layanan, gambar, atau dokumen PDF..."
                                    :min-height="170"
                                    :compact="true"
                                />
                            @elseif (($block['type'] ?? '') === 'map_yurisdiksi')
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <flux:input wire:model="blocks.{{ $index }}.latitude" aria-label="Latitude peta blok #{{ $index + 1 }}" placeholder="-6.9175" />
                                    <flux:input wire:model="blocks.{{ $index }}.longitude" aria-label="Longitude peta blok #{{ $index + 1 }}" placeholder="107.6191" />
                                    <flux:input type="number" wire:model="blocks.{{ $index }}.zoom" aria-label="Level zoom peta blok #{{ $index + 1 }}" min="1" max="18" placeholder="11" />
                                </div>
                            @elseif (($block['type'] ?? '') === 'satker_contact')
                                <p class="text-xs text-ink-muted">Menampilkan identitas alamat, kontak resmi, dan foto gedung dari Pengaturan Satker secara otomatis.</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-xs font-medium text-ink">
                    <input type="checkbox" wire:model="is_published" class="size-4 rounded border-hairline text-primary">
                    <span>Publikasikan halaman ini</span>
                </label>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Halaman' : 'Simpan Halaman Baru' }}
                </flux:button>
            </form>
        </div>

        <!-- Page List -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">Daftar Halaman ({{ $pages->count() }})</h2>
                <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari halaman atau kode kriteria" placeholder="Cari halaman atau kode..." class="w-56" />
            </div>

            @if ($pages->isEmpty())
                <p class="py-8 text-center text-sm text-ink-muted">Tidak ada halaman yang ditemukan.</p>
            @else
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($pages as $pg)
                        <li wire:key="pg-{{ $pg->id }}" class="flex flex-wrap items-center justify-between gap-2 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $pg->title }}</p>
                                <p class="truncate font-mono text-xs text-ink-muted">
                                    /halaman/{{ $pg->slug }}
                                    @if ($pg->criteria_code)
                                        · [{{ $pg->criteria_code }}]
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs">
                                <span @class([
                                    'rounded px-2 py-0.5 font-semibold',
                                    'bg-emerald-50 text-emerald-800' => $pg->is_published,
                                    'bg-gray-100 text-ink-muted' => ! $pg->is_published,
                                ])>
                                    {{ $pg->is_published ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <button type="button" wire:click="edit({{ $pg->id }})" class="rounded border border-primary px-2.5 py-1 font-semibold text-primary">Edit</button>
                                <button type="button" wire:click="delete({{ $pg->id }})" wire:confirm="Hapus halaman ini?" class="rounded border border-red-200 px-2.5 py-1 text-red-700">Hapus</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
