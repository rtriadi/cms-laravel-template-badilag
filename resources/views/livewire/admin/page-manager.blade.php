<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-[#edf7f3] px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @if ($editorOpen)
    <!-- Page Builder Studio v3 Workspace (Single Unified Action Bar + 67% Document Sheet + 33% Tabbed Inspector) -->
    <div id="workspace-editor-halaman" class="space-y-4">
        <!-- Single Unified Editor Top Action Bar -->
        <div class="studio-card flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    wire:click="closeEditor"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-[#e2e8e5] bg-[#f5f7f6] px-3 text-xs font-semibold text-[#334155] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary"
                >
                    <span>&larr; Direktori</span>
                </button>

                <span @class([
                    'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 font-mono text-[10px] font-bold uppercase',
                    'bg-[#edf7f3] text-primary' => $is_published,
                    'bg-[#f1f5f9] text-[#64748b]' => ! $is_published,
                ])>
                    <span>{{ $is_published ? '● Tayang' : '○ Draf' }}</span>
                </span>

                <span class="truncate font-mono text-xs text-[#64748b]">
                    /halaman/{{ $slug ?: 'slug-baru' }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($editingId && $slug)
                    <a
                        href="{{ route('public.page', ['slug' => $slug]) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-9 items-center rounded-lg border border-[#e2e8e5] bg-white px-3.5 text-xs font-semibold text-[#0f172a] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary"
                    >
                        Pratinjau &nearr;
                    </a>
                @endif
                <button
                    type="submit"
                    form="page-builder-form"
                    class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-primary-hover"
                >
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan Halaman Baru' }}
                </button>
            </div>
        </div>

        <form id="page-builder-form" wire:submit="save" class="grid items-start gap-5 xl:grid-cols-12">
            <!-- Left 8 Columns (~67%): Clean White Document Sheet -->
            <div class="studio-card space-y-5 p-5 sm:p-6 xl:col-span-8">
                <!-- Title & Subtitle (Full-width single-line labels, zero wrapping) -->
                <div class="space-y-4">
                    <flux:field>
                        <flux:label for="page_title">Judul Halaman *</flux:label>
                        <flux:input id="page_title" wire:model.live="title" required placeholder="Contoh: Hak Pencari Keadilan (Prodeo & Posbakum)" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <flux:label for="page_summary" class="mb-0!">Subjudul Halaman</flux:label>
                            <span class="font-mono text-[11px] text-[#64748b]">Opsional</span>
                        </div>
                        <flux:input id="page_summary" wire:model="summary" placeholder="Deskripsi singkat yang tampil tepat di bawah judul halaman..." />
                    </flux:field>
                </div>

                <!-- Inline Block Inserter Strip -->
                <div class="space-y-2.5 border-t border-[#e2e8e5] pt-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-mono text-[10.5px] font-bold tracking-wider text-[#64748b] uppercase">
                            Blok Konten ({{ count($blocks) }} Blok)
                        </span>
                        <span class="text-xs text-[#64748b]">Klik salah satu jenis blok untuk menyisipkan di urutan bawah</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ([
                            'richtext' => '+ Teks',
                            'document_list' => '+ Dokumen PDF',
                            'article_list' => '+ Daftar Artikel',
                            'embed_fallback' => '+ Embed SIPP/SIMTEPA',
                            'external_portal' => '+ Portal',
                            'map_yurisdiksi' => '+ Peta',
                            'satker_contact' => '+ Kontak',
                        ] as $bType => $bLabel)
                            <button
                                type="button"
                                wire:click="addBlock('{{ $bType }}')"
                                class="inline-flex h-8 cursor-pointer items-center rounded-md border border-[#c7e6da] bg-[#edf7f3] px-2.5 text-xs font-semibold text-primary transition-colors hover:border-primary hover:bg-primary hover:text-white"
                            >
                                {{ $bLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Modular Block Stack -->
                <div class="space-y-3.5">
                    @foreach ($blocks as $index => $block)
                        <div
                            wire:key="blk-{{ $editingId ?? 'new' }}-{{ $index }}-{{ $block['type'] ?? 'richtext' }}"
                            x-data="{ open: {{ $index === 0 || count($blocks) <= 2 ? 'true' : 'false' }} }"
                            class="rounded-xl border border-[#e2e8e5] bg-[#f9fbfa] p-4"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <button
                                    type="button"
                                    x-on:click="open = !open"
                                    class="flex min-w-0 flex-1 cursor-pointer items-center gap-2.5 text-left"
                                >
                                    <span class="shrink-0 rounded-md bg-[#edf7f3] px-2 py-0.5 font-mono text-[10.5px] font-bold text-primary">
                                        {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} &middot; {{ strtoupper((string) ($block['type'] ?? 'richtext')) }}
                                    </span>
                                    <span class="truncate text-[13px] font-semibold text-[#0f172a]">
                                        {{ trim((string) ($block['heading'] ?? '')) ?: 'Tanpa judul subbagian' }}
                                    </span>
                                </button>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="moveBlockUp({{ $index }})"
                                        @disabled($index === 0)
                                        title="Pindahkan blok ke atas"
                                        aria-label="Naikkan blok #{{ $index + 1 }}"
                                        class="inline-flex h-7 cursor-pointer items-center rounded-md border border-[#e2e8e5] bg-white px-2 text-xs font-semibold text-[#334155] hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        &uarr;
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="moveBlockDown({{ $index }})"
                                        @disabled($index >= count($blocks) - 1)
                                        title="Pindahkan blok ke bawah"
                                        aria-label="Turunkan blok #{{ $index + 1 }}"
                                        class="inline-flex h-7 cursor-pointer items-center rounded-md border border-[#e2e8e5] bg-white px-2 text-xs font-semibold text-[#334155] hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        &darr;
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="duplicateBlock({{ $index }})"
                                        class="inline-flex h-7 cursor-pointer items-center rounded-md border border-[#e2e8e5] bg-white px-2.5 text-[11px] font-semibold text-primary hover:border-primary hover:bg-[#edf7f3]"
                                    >
                                        Duplikat
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="removeBlock({{ $index }})"
                                        class="inline-flex h-7 cursor-pointer items-center rounded-md border border-red-200 bg-[#fef2f2] px-2.5 text-[11px] font-semibold text-red-700 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="open = !open"
                                        class="inline-flex h-7 cursor-pointer items-center rounded-md px-2 text-[11px] font-semibold text-primary hover:bg-[#edf7f3]"
                                    >
                                        <span x-text="open ? 'Tutup ▴' : 'Buka ▾'">Buka</span>
                                    </button>
                                </div>
                            </div>

                            <div x-show="open" class="mt-3.5 space-y-3.5 border-t border-[#e2e8e5] pt-3.5">
                                <flux:field>
                                    <flux:label>Judul Subbagian</flux:label>
                                    <flux:input wire:model="blocks.{{ $index }}.heading" aria-label="Judul subbagian blok #{{ $index + 1 }}" placeholder="Contoh: 1. Layanan Pembebasan Biaya Perkara (Prodeo)" />
                                </flux:field>

                                @if (($block['type'] ?? '') === 'richtext')
                                    <x-rich-editor
                                        wire:key="page-rt-{{ $editingId ?? 'new' }}-{{ $index }}"
                                        model="blocks.{{ $index }}.content"
                                        label="Konten blok teks #{{ $index + 1 }}"
                                        placeholder="Tulis isi konten halaman, sisipkan tabel, gambar, atau lampiran PDF..."
                                        :min-height="260"
                                    />
                                @elseif (($block['type'] ?? '') === 'document_list')
                                    <flux:field>
                                        <flux:label>Koleksi Dokumen PDF</flux:label>
                                        <flux:select wire:model="blocks.{{ $index }}.collection_type" aria-label="Koleksi dokumen blok #{{ $index + 1 }}">
                                            @foreach ($collectionTypes as $cKey => $cLabel)
                                                <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                            @endforeach
                                        </flux:select>
                                        <p class="mt-1 text-[11px] text-[#64748b]">Dokumen PDF dari menu Dokumen PDF pada koleksi ini otomatis ditampilkan beserta filter tahun.</p>
                                    </flux:field>
                                @elseif (($block['type'] ?? '') === 'article_list')
                                    <flux:field>
                                        <flux:label>Kategori Publikasi</flux:label>
                                        <flux:select wire:model="blocks.{{ $index }}.post_category" aria-label="Kategori artikel blok #{{ $index + 1 }}">
                                            @foreach ($postCategories as $pKey => $pLabel)
                                                <option value="{{ $pKey }}">{{ $pLabel }}</option>
                                            @endforeach
                                            <option value="semua">Semua Kategori Publikasi</option>
                                        </flux:select>
                                        <p class="mt-1 text-[11px] text-[#64748b]">Otomatis menampilkan daftar artikel dari menu Publikasi sesuai kategori ini.</p>
                                    </flux:field>
                                @elseif (($block['type'] ?? '') === 'embed_fallback')
                                    @php
                                        $srcKey = (string) ($block['source_key'] ?? 'simtepa_pegawai');
                                        $customEmbed = (string) ($block['custom_embed_url'] ?? '');
                                        $previewResolved = $satker?->resolveEmbedSource($srcKey, $customEmbed) ?? ['embed_url' => null, 'tabs' => []];
                                    @endphp
                                    <div class="grid gap-3.5 sm:grid-cols-2">
                                        <flux:field>
                                            <flux:label>Sumber Embed SIPP / SIMTEPA</flux:label>
                                            <flux:select wire:model.live="blocks.{{ $index }}.source_key" aria-label="Sumber embed blok #{{ $index + 1 }}">
                                                <optgroup label="SIPP (Otomatis dari URL SIPP Pengaturan Satker)">
                                                    <option value="sipp_jadwal">SIPP Jadwal Sidang (/list_jadwal_sidang)</option>
                                                    <option value="sipp_perkara">SIPP Daftar / Penelusuran Perkara (/)</option>
                                                    <option value="sipp_statistik">SIPP Statistik Perkara (/statistik_perkara)</option>
                                                </optgroup>
                                                <optgroup label="SIMTEPA Struktur &amp; Profil Gabungan">
                                                    <option value="simtepa_struktur">SIMTEPA Struktur Organisasi (Link Bagan Struktur)</option>
                                                    <option value="simtepa_pegawai">SIMTEPA Semua Profil Pegawai (7 Tab)</option>
                                                    <option value="simtepa_struktur_tabs">SIMTEPA Daftar Pejabat Struktural (6 Tab)</option>
                                                </optgroup>
                                                <optgroup label="SIMTEPA Per Kategori Khusus">
                                                    <option value="simtepa_ketua">SIMTEPA 1. Profil Ketua</option>
                                                    <option value="simtepa_wakil">SIMTEPA 2. Profil Wakil Ketua</option>
                                                    <option value="simtepa_hakim">SIMTEPA 3. Profil Hakim</option>
                                                    <option value="simtepa_kepaniteraan">SIMTEPA 4. Profil Kepaniteraan</option>
                                                    <option value="simtepa_kesekretariatan">SIMTEPA 5. Profil Kesekretariatan</option>
                                                    <option value="simtepa_fungsional">SIMTEPA 6. Profil Fungsional</option>
                                                    <option value="simtepa_pelaksana">SIMTEPA 7. Profil Pelaksana</option>
                                                </optgroup>
                                            </flux:select>
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Mode Cadangan Lokal</flux:label>
                                            <flux:select wire:model="blocks.{{ $index }}.fallback_mode" aria-label="Mode cadangan lokal blok #{{ $index + 1 }}">
                                                <option value="officials_table">Cadangan Tabel Pegawai</option>
                                                <option value="org_chart">Cadangan Struktur Organisasi</option>
                                                <option value="court_schedule">Cadangan Jadwal Sidang</option>
                                                <option value="case_statistics">Cadangan Statistik Perkara</option>
                                            </flux:select>
                                        </flux:field>
                                    </div>

                                    <div class="rounded-lg border border-[#c7e6da] bg-[#edf7f3] px-3 py-2 text-xs text-[#0f172a]">
                                        <p class="font-semibold text-primary-deep">Sumber URL Embed Aktif:</p>
                                        @if (! empty($previewResolved['tabs']))
                                            <p class="mt-0.5 text-[11px] text-[#334155]">
                                                Otomatis menampilkan <strong>{{ count($previewResolved['tabs']) }} Tab Kategori SIMTEPA</strong> dari token <code>{{ $satker?->extractSimtepaToken() }}</code>.
                                            </p>
                                        @elseif (! empty($previewResolved['embed_url']))
                                            <p class="mt-0.5 break-all font-mono text-[11px] text-primary">{{ $previewResolved['embed_url'] }}</p>
                                        @else
                                            <p class="mt-0.5 text-[11px] text-amber-800">URL belum diatur di Pengaturan Satker (otomatis memakai Data Cadangan Lokal).</p>
                                        @endif
                                    </div>

                                    <div class="grid gap-3.5 sm:grid-cols-2">
                                        <flux:field>
                                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                                <flux:label class="mb-0!">URL Embed Khusus</flux:label>
                                                <span class="font-mono text-[11px] text-[#64748b]">Opsional</span>
                                            </div>
                                            <flux:input wire:model.live="blocks.{{ $index }}.custom_embed_url" aria-label="Override URL embed blok #{{ $index + 1 }}" placeholder="Kosongkan jika memakai URL otomatis..." />
                                        </flux:field>
                                        <flux:field>
                                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                                <flux:label class="mb-0!">Catatan Penjelas</flux:label>
                                                <span class="font-mono text-[11px] text-[#64748b]">Opsional</span>
                                            </div>
                                            <flux:input wire:model="blocks.{{ $index }}.note" aria-label="Catatan mode cadangan blok #{{ $index + 1 }}" placeholder="Keterangan singkat di atas tabel/embed" />
                                        </flux:field>
                                    </div>
                                @elseif (($block['type'] ?? '') === 'external_portal')
                                    <flux:field>
                                        <flux:label>URL Tujuan Portal</flux:label>
                                        <flux:input wire:model="blocks.{{ $index }}.default_url" aria-label="URL bawaan portal blok #{{ $index + 1 }}" placeholder="https://..." />
                                    </flux:field>
                                    <x-rich-editor
                                        wire:key="page-ep-{{ $editingId ?? 'new' }}-{{ $index }}"
                                        model="blocks.{{ $index }}.description"
                                        label="Penjelasan portal blok #{{ $index + 1 }}"
                                        placeholder="Penjelasan portal, panduan layanan, gambar, atau dokumen PDF..."
                                        :min-height="180"
                                        :compact="true"
                                    />
                                @elseif (($block['type'] ?? '') === 'map_yurisdiksi')
                                    <div class="grid gap-3.5 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label>Latitude</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.latitude" aria-label="Latitude peta blok #{{ $index + 1 }}" placeholder="-6.9175" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Longitude</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.longitude" aria-label="Longitude peta blok #{{ $index + 1 }}" placeholder="107.6191" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Zoom (1-18)</flux:label>
                                            <flux:input type="number" wire:model="blocks.{{ $index }}.zoom" aria-label="Level zoom peta blok #{{ $index + 1 }}" min="1" max="18" placeholder="11" />
                                        </flux:field>
                                    </div>
                                @elseif (($block['type'] ?? '') === 'satker_contact')
                                    <p class="rounded-lg border border-[#e2e8e5] bg-white p-3 text-xs text-[#334155]">
                                        Menampilkan identitas alamat lengkap, kontak resmi, dan foto gedung dari Pengaturan Satker secara otomatis.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right 4 Columns (~33%): Tabbed Inspector (SEO & Slug | Publikasi | SINOVA) -->
            <aside
                x-data="{ inspectorTab: 'seo' }"
                class="studio-card space-y-4 p-4 sm:p-5 xl:sticky xl:top-20 xl:col-span-4"
            >
                <!-- 3-Segment Tab Switcher -->
                <div class="grid grid-cols-3 gap-1 rounded-lg border border-[#e2e8e5] bg-[#f5f7f6] p-1">
                    <button
                        type="button"
                        x-on:click="inspectorTab = 'seo'"
                        x-bind:class="inspectorTab === 'seo' ? 'bg-white text-primary font-bold shadow-2xs' : 'text-[#64748b] font-medium hover:text-[#0f172a]'"
                        class="h-8 cursor-pointer rounded-md text-xs transition-colors"
                    >
                        SEO &amp; Slug
                    </button>
                    <button
                        type="button"
                        x-on:click="inspectorTab = 'publish'"
                        x-bind:class="inspectorTab === 'publish' ? 'bg-white text-primary font-bold shadow-2xs' : 'text-[#64748b] font-medium hover:text-[#0f172a]'"
                        class="h-8 cursor-pointer rounded-md text-xs transition-colors"
                    >
                        Publikasi
                    </button>
                    <button
                        type="button"
                        x-on:click="inspectorTab = 'sinova'"
                        x-bind:class="inspectorTab === 'sinova' ? 'bg-white text-primary font-bold shadow-2xs' : 'text-[#64748b] font-medium hover:text-[#0f172a]'"
                        class="h-8 cursor-pointer rounded-md text-xs transition-colors"
                    >
                        SINOVA
                    </button>
                </div>

                <!-- Tab 1: SEO & Slug -->
                <div x-show="inspectorTab === 'seo'" class="space-y-4">
                    <div class="rounded-xl border border-[#e2e8e5] bg-[#f9fbfa] p-3.5 text-xs">
                        <p class="font-mono text-[10px] font-bold tracking-wider text-primary uppercase">Pratinjau Hasil Google</p>
                        <p class="mt-1.5 truncate text-[11px] font-medium text-primary-deep">
                            {{ url('/halaman/' . ($slug ?: 'slug-halaman')) }}
                        </p>
                        <p class="mt-0.5 truncate text-[13.5px] font-bold text-[#1d4ed8]">
                            {{ $title ?: 'Judul Halaman Resmi Satuan Kerja' }}
                        </p>
                        <p class="mt-1 line-clamp-2 text-[11.5px] leading-relaxed text-[#334155]">
                            {{ trim($meta_description) !== '' ? $meta_description : (trim($summary) !== '' ? $summary : 'Deskripsi SEO Google otomatis akan diambil dari ringkasan atau isi blok pertama halaman.') }}
                        </p>
                    </div>

                    <flux:field>
                        <flux:label for="page_slug">Slug URL</flux:label>
                        <flux:input id="page_slug" wire:model.live="slug" required placeholder="layanan-hukum/hak-pencari-keadilan" />
                        <p class="mt-1 text-[11px] text-[#64748b]">Mengubah slug otomatis membuat Redirect 301.</p>
                        <flux:error name="slug" />
                    </flux:field>

                    <flux:field>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <flux:label for="page_meta_desc" class="mb-0!">Deskripsi SEO Google</flux:label>
                            <span class="font-mono text-[11px] {{ mb_strlen($meta_description) > 155 ? 'font-bold text-amber-700' : 'text-[#64748b]' }}">
                                {{ mb_strlen($meta_description) }}/160
                            </span>
                        </div>
                        <flux:textarea id="page_meta_desc" wire:model.live="meta_description" rows="3" maxlength="160" placeholder="Ringkasan maks. 160 karakter untuk hasil pencarian Google..." />
                        <flux:error name="meta_description" />
                    </flux:field>
                </div>

                <!-- Tab 2: Publikasi -->
                <div x-show="inspectorTab === 'publish'" x-cloak class="space-y-4">
                    <flux:field>
                        <flux:label for="page_tingkat">Cakupan Tingkat</flux:label>
                        <flux:select id="page_tingkat" wire:model="tingkat_scope">
                            <option value="all">Semua Tingkat (Pertama &amp; Banding)</option>
                            <option value="pertama">Khusus Tingkat Pertama</option>
                            <option value="banding">Khusus Tingkat Banding</option>
                        </flux:select>
                    </flux:field>

                    <div class="rounded-lg border border-[#c7e6da] bg-[#edf7f3] px-3.5 py-2.5">
                        <label class="flex min-h-9 cursor-pointer items-center gap-2.5 text-xs font-semibold text-primary-deep">
                            <input type="checkbox" wire:model="is_published" class="size-4 rounded border-[#cbd5e1] text-primary">
                            <span>Publikasikan di Portal Publik</span>
                        </label>
                    </div>
                </div>

                <!-- Tab 3: SINOVA & KMA -->
                <div x-show="inspectorTab === 'sinova'" x-cloak class="space-y-4">
                    <flux:field>
                        <flux:label for="page_cat">Klasifikasi KMA 2-144</flux:label>
                        <flux:select id="page_cat" wire:model="category_tag">
                            <option value="berkala">Informasi Berkala</option>
                            <option value="serta_merta">Informasi Serta Merta</option>
                            <option value="setiap_saat">Informasi Setiap Saat</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="page_crit">Kode Kriteria SINOVA</flux:label>
                        <flux:select id="page_crit" wire:model="criteria_code">
                            <option value="">-- Tidak terikat kriteria khusus --</option>
                            @foreach ($criteriaOptions as $cOpt)
                                <option value="{{ $cOpt->code }}">{{ $cOpt->code }} - {{ $cOpt->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <!-- Always-Accessible Quick Status & Save Footer -->
                <div class="space-y-3 border-t border-[#e2e8e5] pt-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[#64748b]">Status Halaman</span>
                        <span class="font-semibold {{ $is_published ? 'text-primary' : 'text-[#64748b]' }}">
                            {{ $is_published ? 'Tayang di Portal' : 'Draf Internal' }}
                        </span>
                    </div>
                    <flux:button type="submit" variant="primary" class="h-10 w-full cursor-pointer bg-primary text-xs font-semibold text-white hover:bg-primary-hover">
                        {{ $editingId ? 'Simpan Perubahan Halaman' : 'Simpan Halaman Baru' }}
                    </flux:button>
                </div>
            </aside>
        </form>
    </div>
    @else
    <!-- Clean Editorial Directory Table View -->
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-serif text-2xl font-bold tracking-tight text-[#0f172a]">Halaman Website</h2>
                <p class="mt-0.5 text-xs text-[#64748b] sm:text-[13px]">
                    Kelola {{ $pages->count() }} halaman profil, layanan hukum, transparansi SAKIP, dan informasi publik menggunakan Page Builder modular.
                </p>
            </div>
            <button
                type="button"
                wire:click="createNew"
                class="inline-flex h-10 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-primary-hover"
            >
                + Buat Halaman Baru
            </button>
        </div>

        <div class="studio-card overflow-hidden">
            <!-- Toolbar: Section Tabs + Search -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e2e8e5] p-4 sm:px-5">
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['' => 'Semua Bagian', 'profil' => 'Profil', 'layanan-hukum' => 'Layanan Hukum', 'transparansi' => 'Transparansi & SAKIP', 'layanan-publik' => 'Layanan Publik & PPID'] as $secKey => $secLabel)
                        <button
                            type="button"
                            wire:click="$set('sectionFilter', '{{ $secKey }}')"
                            @class([
                                'h-8 cursor-pointer rounded-md px-3 text-xs font-semibold transition-colors',
                                'bg-primary text-white' => $sectionFilter === $secKey,
                                'border border-[#e2e8e5] bg-[#f5f7f6] text-[#334155] hover:bg-[#edf7f3] hover:text-primary' => $sectionFilter !== $secKey,
                            ])
                        >
                            {{ $secLabel }}
                        </button>
                    @endforeach
                </div>

                <div class="w-full sm:w-64">
                    <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari halaman atau kode kriteria" placeholder="Cari judul halaman atau slug..." />
                </div>
            </div>

            @if ($pages->isEmpty())
                <p class="py-12 text-center text-sm text-[#64748b]">Tidak ada halaman yang cocok dengan filter atau pencarian tersebut.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#e2e8e5] bg-[#f9fbfa] font-mono text-[10.5px] font-bold tracking-wider text-[#64748b] uppercase">
                                <th class="py-3 pl-5 pr-3">Judul Halaman &amp; Slug</th>
                                <th class="px-3 py-3">Blok</th>
                                <th class="px-3 py-3">Kriteria</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="py-3 pl-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eef2f0]">
                            @foreach ($pages as $pg)
                                <tr wire:key="pg-{{ $pg->id }}" class="transition-colors hover:bg-[#f9fbfa]">
                                    <td class="py-3.5 pl-5 pr-3 align-top">
                                        <div class="max-w-lg">
                                            <p class="break-words text-[13px] leading-snug font-semibold text-[#0f172a]">{{ $pg->title }}</p>
                                            <p class="mt-0.5 break-all font-mono text-[11px] text-[#64748b] line-clamp-1" title="/halaman/{{ $pg->slug }}">/halaman/{{ $pg->slug }}</p>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 align-top font-mono text-[11.5px] text-[#334155]">
                                        {{ (int) ($pg->blocks_count ?? (is_array($pg->blocks) ? count($pg->blocks) : 0)) }} blok
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 align-top">
                                        @if ($pg->criteria_code)
                                            <span class="rounded-md bg-[#edf7f3] px-2 py-0.5 font-mono text-[11px] font-semibold text-primary">
                                                {{ $pg->criteria_code }}
                                            </span>
                                        @else
                                            <span class="text-[#94a3b8]">-</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 align-top">
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                            'bg-[#ecfdf5] text-[#047857]' => $pg->is_published,
                                            'bg-[#f1f5f9] text-[#64748b]' => ! $pg->is_published,
                                        ])>
                                            {{ $pg->is_published ? '● Tayang' : '○ Draf' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap py-3.5 pl-3 pr-5 text-right align-top">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a
                                                href="{{ route('public.page', ['slug' => $pg->slug]) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex h-8 items-center rounded-md border border-[#e2e8e5] bg-white px-2.5 text-xs font-semibold text-[#334155] hover:border-primary hover:text-primary"
                                            >
                                                Lihat
                                            </a>
                                            <button
                                                type="button"
                                                wire:click="edit({{ $pg->id }})"
                                                x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                                class="inline-flex h-8 cursor-pointer items-center rounded-md border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary hover:text-white"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="delete({{ $pg->id }})"
                                                wire:confirm="Hapus halaman ini?"
                                                class="inline-flex h-8 cursor-pointer items-center rounded-md border border-red-200 bg-white px-2.5 text-xs font-semibold text-red-700 hover:bg-[#fef2f2]"
                                            >
                                                Hapus
                                            </button>
                                        </div>
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
