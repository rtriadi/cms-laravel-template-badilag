<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <!-- Studio Mode Switcher Bar (Direktori Halaman <-> Studio Editor) -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-hairline bg-white p-3.5 shadow-2xs">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="closeEditor"
                @class([
                    'inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-bold transition-colors',
                    'bg-primary text-white shadow-2xs' => ! $editorOpen,
                    'border border-hairline bg-mist text-ink hover:bg-primary-soft hover:text-primary' => $editorOpen,
                ])
            >
                <span>1. Direktori Halaman Website ({{ $pages->count() }})</span>
            </button>
            <button
                type="button"
                wire:click="$set('editorOpen', true)"
                @class([
                    'inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-bold transition-colors',
                    'bg-primary text-white shadow-2xs' => $editorOpen,
                    'border border-hairline bg-mist text-ink hover:bg-primary-soft hover:text-primary' => ! $editorOpen,
                ])
            >
                <span>2. Studio Editor &amp; Page Builder</span>
                @if ($editingId)
                    <span class="rounded bg-gold px-1.5 py-0.5 font-mono text-[10px] font-bold text-ink">
                        #{{ $editingId }}
                    </span>
                @endif
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($editorOpen)
                <button
                    type="button"
                    wire:click="closeEditor"
                    class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-hairline bg-mist px-3 text-xs font-semibold text-ink hover:bg-primary-soft hover:text-primary"
                >
                    &larr; Kembali ke Direktori Halaman
                </button>
            @endif
            <button
                type="button"
                wire:click="createNew"
                class="inline-flex min-h-10 cursor-pointer items-center rounded-md bg-primary px-3.5 text-xs font-semibold text-white hover:bg-primary-hover"
            >
                + Buat Halaman Baru
            </button>
        </div>
    </div>

    <!-- Full-Width CMS Page Builder Studio Workspace (8/4 Inspector Layout) -->
    <div
        id="workspace-editor-halaman"
        x-show="$wire.editorOpen"
        class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-6"
    >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-4">
            <div>
                <span class="rounded bg-primary-soft px-2.5 py-0.5 font-mono text-[11px] font-bold text-primary uppercase">
                    {{ $editingId ? 'Mode Edit Halaman #' . $editingId : 'Mode Buat Halaman Baru' }}
                </span>
                <h2 class="mt-1 font-serif text-xl font-bold text-ink">
                    {{ $editingId ? 'Edit Isi Halaman: ' . $title : 'Buat Halaman Website Baru' }}
                </h2>
                <p class="text-xs text-ink-muted">
                    Susun isi artikel halaman, unggah gambar/PDF di dalam editor, atau tambahkan blok daftar dokumen dan artikel terkait.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="closeEditor"
                    class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-hairline bg-mist px-3.5 text-xs font-semibold text-ink hover:bg-primary-soft hover:text-primary"
                >
                    &larr; Kembali ke Direktori
                </button>
                @if ($editingId && $slug)
                    <a
                        href="{{ route('public.page', ['slug' => $slug]) }}"
                        target="_blank"
                        class="inline-flex min-h-11 items-center rounded-md border border-primary bg-primary-soft px-3.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white"
                    >
                        Lihat Halaman Publik &nearr;
                    </a>
                @endif
            </div>
        </div>

        <form wire:submit="save" class="mt-5 grid gap-6 xl:grid-cols-12">
            <!-- Left 8 Columns: Wide Content & Block Builder Canvas -->
            <div class="space-y-4 xl:col-span-8">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="page_title">Judul Halaman *</flux:label>
                        <flux:input id="page_title" wire:model.live="title" required placeholder="Contoh: Hak Pencari Keadilan (Prodeo & Posbakum)" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="page_summary">Ringkasan / Subjudul Halaman (Opsional)</flux:label>
                        <flux:input id="page_summary" wire:model="summary" placeholder="Deskripsi singkat di bawah judul halaman..." />
                    </flux:field>
                </div>

                <!-- Dynamic Blocks Canvas -->
                <div class="space-y-4 rounded-xl border border-hairline bg-mist p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-3">
                        <div>
                            <span class="text-sm font-bold text-ink">Kanvas Blok Konten Halaman ({{ count($blocks) }} Blok)</span>
                            <p class="text-[11px] text-ink-muted">Satu halaman dapat berisi gabungan artikel teks lengkap, daftar dokumen PDF, maupun daftar artikel berita.</p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['richtext' => '+ Teks / Artikel', 'document_list' => '+ Daftar Dokumen PDF', 'article_list' => '+ Daftar Artikel', 'embed_fallback' => '+ Embed/Cadangan', 'external_portal' => '+ Portal', 'map_yurisdiksi' => '+ Peta', 'satker_contact' => '+ Kontak'] as $bType => $bLabel)
                                <button
                                    type="button"
                                    wire:click="addBlock('{{ $bType }}')"
                                    class="min-h-9 cursor-pointer rounded-md border border-primary/30 bg-white px-2.5 py-1.5 text-xs font-semibold text-primary shadow-2xs hover:bg-primary hover:text-white"
                                >
                                    {{ $bLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($blocks as $index => $block)
                        <div wire:key="blk-{{ $editingId ?? 'new' }}-{{ $index }}-{{ $block['type'] ?? 'richtext' }}" class="space-y-3 rounded-lg border border-hairline bg-white p-4 shadow-2xs">
                            <div class="flex items-center justify-between border-b border-hairline pb-2">
                                <span class="rounded bg-primary-soft px-2.5 py-0.5 font-mono text-xs font-bold text-primary">
                                    Blok #{{ $index + 1 }} · {{ strtoupper((string) ($block['type'] ?? 'richtext')) }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="removeBlock({{ $index }})"
                                    class="cursor-pointer rounded border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50"
                                >
                                    Hapus Blok
                                </button>
                            </div>

                            <flux:field>
                                <flux:label>Judul Subbagian Blok #{{ $index + 1 }}</flux:label>
                                <flux:input wire:model="blocks.{{ $index }}.heading" aria-label="Judul subbagian blok #{{ $index + 1 }}" placeholder="Contoh: 1. Layanan Pembebasan Biaya Perkara (Prodeo)" />
                            </flux:field>

                            @if (($block['type'] ?? '') === 'richtext')
                                <x-rich-editor
                                    model="blocks.{{ $index }}.content"
                                    label="Konten blok teks #{{ $index + 1 }}"
                                    placeholder="Tulis isi konten halaman, sisipkan tabel, gambar, atau lampiran PDF..."
                                    :min-height="300"
                                />
                            @elseif (($block['type'] ?? '') === 'document_list')
                                <flux:field>
                                    <flux:label>Pilih Koleksi Dokumen PDF yang Ditampilkan</flux:label>
                                    <flux:select wire:model="blocks.{{ $index }}.collection_type" aria-label="Koleksi dokumen blok #{{ $index + 1 }}">
                                        @foreach ($collectionTypes as $cKey => $cLabel)
                                            <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                        @endforeach
                                    </flux:select>
                                    <p class="mt-1 text-[11px] text-ink-muted">Dokumen PDF yang diunggah pada menu Arsip Dokumen PDF dengan koleksi ini akan otomatis tampil beserta filter tahun.</p>
                                </flux:field>
                            @elseif (($block['type'] ?? '') === 'article_list')
                                <flux:field>
                                    <flux:label>Pilih Kategori Artikel yang Ditampilkan</flux:label>
                                    <flux:select wire:model="blocks.{{ $index }}.post_category" aria-label="Kategori artikel blok #{{ $index + 1 }}">
                                        @foreach ($postCategories as $pKey => $pLabel)
                                            <option value="{{ $pKey }}">{{ $pLabel }}</option>
                                        @endforeach
                                        <option value="semua">Semua Kategori Publikasi</option>
                                    </flux:select>
                                    <p class="mt-1 text-[11px] text-ink-muted">Otomatis menampilkan daftar artikel dari menu Berita, Artikel &amp; Pengumuman sesuai kategori ini.</p>
                                </flux:field>
                            @elseif (($block['type'] ?? '') === 'embed_fallback')
                                @php
                                    $srcKey = (string) ($block['source_key'] ?? 'simtepa_pegawai');
                                    $customEmbed = (string) ($block['custom_embed_url'] ?? '');
                                    $previewResolved = $satker?->resolveEmbedSource($srcKey, $customEmbed) ?? ['embed_url' => null, 'tabs' => []];
                                @endphp
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Sumber Embed Eksternal (Otomatis dari Pengaturan Satker)</flux:label>
                                        <flux:select wire:model.live="blocks.{{ $index }}.source_key" aria-label="Sumber embed blok #{{ $index + 1 }}">
                                            <optgroup label="SIPP (Otomatis dari URL SIPP Pengaturan Satker)">
                                                <option value="sipp_jadwal">SIPP Jadwal Sidang (/list_jadwal_sidang)</option>
                                                <option value="sipp_perkara">SIPP Daftar / Penelusuran Perkara (/)</option>
                                                <option value="sipp_statistik">SIPP Statistik Perkara (/statistik_perkara)</option>
                                            </optgroup>
                                            <optgroup label="SIMTEPA Gabungan (7 Tab Otomatis dari Token SIMTEPA)">
                                                <option value="simtepa_pegawai">SIMTEPA Semua Profil Pegawai (7 Tab: Ketua s.d. Pelaksana)</option>
                                                <option value="simtepa_struktur">SIMTEPA Struktur Pejabat (6 Tab: Tanpa Pelaksana)</option>
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
                                        <flux:label>Mode Data Cadangan Lokal</flux:label>
                                        <flux:select wire:model="blocks.{{ $index }}.fallback_mode" aria-label="Mode cadangan lokal blok #{{ $index + 1 }}">
                                            <option value="officials_table">Cadangan Tabel Pegawai</option>
                                            <option value="org_chart">Cadangan Struktur Organisasi</option>
                                            <option value="court_schedule">Cadangan Jadwal Sidang</option>
                                            <option value="case_statistics">Cadangan Statistik Perkara</option>
                                        </flux:select>
                                    </flux:field>
                                </div>

                                <div class="rounded-md border border-emerald-200 bg-primary-soft/60 px-3 py-2 text-xs text-ink">
                                    <p class="font-semibold text-primary-deep">Sumber URL Embed Aktif:</p>
                                    @if (! empty($previewResolved['tabs']))
                                        <p class="mt-0.5 text-[11px] text-ink-body">
                                            Otomatis menampilkan <strong>{{ count($previewResolved['tabs']) }} Tab Kategori SIMTEPA</strong> dari token <code>{{ $satker?->extractSimtepaToken() }}</code> (Ketua, Wakil, Hakim, Kepaniteraan, Kesekretariatan, Fungsional{{ count($previewResolved['tabs']) === 7 ? ', Pelaksana' : '' }}).
                                        </p>
                                    @elseif (! empty($previewResolved['embed_url']))
                                        <p class="mt-0.5 break-all font-mono text-[11px] text-primary">{{ $previewResolved['embed_url'] }}</p>
                                    @else
                                        <p class="mt-0.5 text-[11px] text-amber-800">URL belum diatur di menu Pengaturan Satker (akan otomatis menampilkan Data Cadangan Lokal).</p>
                                    @endif
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Override URL Embed Khusus Blok Ini (Opsional)</flux:label>
                                        <flux:input wire:model.live="blocks.{{ $index }}.custom_embed_url" aria-label="Override URL embed blok #{{ $index + 1 }}" placeholder="Kosongkan jika menggunakan URL otomatis di atas..." />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Catatan Penjelas di Atas Tabel/Embed (Opsional)</flux:label>
                                        <flux:input wire:model="blocks.{{ $index }}.note" aria-label="Catatan mode cadangan blok #{{ $index + 1 }}" placeholder="Catatan penjelas (opsional)" />
                                    </flux:field>
                                </div>
                            @elseif (($block['type'] ?? '') === 'external_portal')
                                <flux:field>
                                    <flux:label>URL Tujuan Portal</flux:label>
                                    <flux:input wire:model="blocks.{{ $index }}.default_url" aria-label="URL bawaan portal blok #{{ $index + 1 }}" placeholder="https://..." />
                                </flux:field>
                                <x-rich-editor
                                    model="blocks.{{ $index }}.description"
                                    label="Penjelasan portal blok #{{ $index + 1 }}"
                                    placeholder="Penjelasan portal, panduan layanan, gambar, atau dokumen PDF..."
                                    :min-height="180"
                                    :compact="true"
                                />
                            @elseif (($block['type'] ?? '') === 'map_yurisdiksi')
                                <div class="grid gap-3 sm:grid-cols-3">
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
                                <p class="rounded-md border border-hairline bg-mist p-3 text-xs text-ink-body">
                                    Menampilkan identitas alamat lengkap, kontak resmi, dan foto gedung dari Pengaturan Satker secara otomatis.
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right 4 Columns: Inspector Sidebar (Slug, Metadata, Save CTA) -->
            <aside class="space-y-4 rounded-xl border border-hairline bg-mist p-4 xl:col-span-4">
                <h3 class="border-b border-hairline pb-2.5 font-serif text-base font-bold text-ink">
                    Pengaturan &amp; Publikasi Halaman
                </h3>

                <flux:field>
                    <flux:label for="page_slug">Slug URL (/halaman/...)</flux:label>
                    <flux:input id="page_slug" wire:model="slug" required placeholder="layanan-hukum/hak-pencari-keadilan" />
                    <p class="mt-1 text-[11px] text-ink-muted">Gunakan awalan <code>profil/</code>, <code>layanan-hukum/</code>, <code>transparansi/</code>, atau <code>layanan-publik/</code>. Mengubah slug otomatis membuat Redirect 301.</p>
                    <flux:error name="slug" />
                </flux:field>

                <flux:field>
                    <flux:label for="page_cat">Klasifikasi Informasi (KMA 2-144)</flux:label>
                    <flux:select id="page_cat" wire:model="category_tag">
                        <option value="berkala">Informasi Berkala</option>
                        <option value="serta_merta">Informasi Serta Merta</option>
                        <option value="setiap_saat">Informasi Setiap Saat</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label for="page_crit">Tautan Kode Kriteria (Internal Admin)</flux:label>
                    <flux:select id="page_crit" wire:model="criteria_code">
                        <option value="">-- Tidak terikat kriteria khusus --</option>
                        @foreach ($criteriaOptions as $cOpt)
                            <option value="{{ $cOpt->code }}">{{ $cOpt->code }} - {{ $cOpt->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label for="page_tingkat">Cakupan Tingkat Peradilan</flux:label>
                    <flux:select id="page_tingkat" wire:model="tingkat_scope">
                        <option value="all">Semua Tingkat (Pertama &amp; Banding)</option>
                        <option value="pertama">Khusus Tingkat Pertama</option>
                        <option value="banding">Khusus Tingkat Banding</option>
                    </flux:select>
                </flux:field>

                <div class="rounded-lg border border-hairline bg-white p-3">
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 text-xs font-semibold text-ink">
                        <input type="checkbox" wire:model="is_published" class="size-4 rounded border-hairline text-primary">
                        <span>Publikasikan halaman ini di portal publik</span>
                    </label>
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Halaman' : 'Simpan Halaman Baru' }}
                </flux:button>
            </aside>
        </form>
    </div>

    <!-- Full-Width Directory of All Pages with Section Tabs -->
    <div x-show="!$wire.editorOpen" class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-4">
            <div>
                <h2 class="font-serif text-lg font-bold text-ink">Direktori Seluruh Halaman Website ({{ $pages->count() }})</h2>
                <p class="text-xs text-ink-muted">Pilih tab bagian di bawah ini, lalu klik <strong>Edit Isi</strong> untuk membuka Studio Editor &amp; Page Builder.</p>
            </div>
            <div class="w-full sm:w-72">
                <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari halaman atau kode kriteria" placeholder="Cari judul halaman atau slug..." />
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 border-b border-hairline pb-3">
            @foreach (['' => 'Semua Bagian', 'profil' => 'Profil Pengadilan', 'layanan-hukum' => 'Layanan Hukum (Prodeo, Posbakum, SIPP, Biaya)', 'transparansi' => 'Transparansi & SAKIP', 'layanan-publik' => 'Layanan Publik & PPID'] as $secKey => $secLabel)
                <button
                    type="button"
                    wire:click="$set('sectionFilter', '{{ $secKey }}')"
                    @class([
                        'min-h-10 cursor-pointer rounded-md px-3 py-1.5 text-xs font-semibold',
                        'bg-primary text-white' => $sectionFilter === $secKey,
                        'border border-hairline bg-mist text-ink hover:bg-primary-soft' => $sectionFilter !== $secKey,
                    ])
                >
                    {{ $secLabel }}
                </button>
            @endforeach
        </div>

        @if ($pages->isEmpty())
            <p class="py-10 text-center text-sm text-ink-muted">Tidak ada halaman yang cocok dengan filter atau pencarian tersebut.</p>
        @else
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($pages as $pg)
                    <div
                        wire:key="pg-{{ $pg->id }}"
                        @class([
                            'flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3.5',
                            'border-primary bg-primary-soft/50' => $editingId === $pg->id,
                            'border-hairline bg-mist/60 hover:border-primary/40 hover:bg-white' => $editingId !== $pg->id,
                        ])
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span @class([
                                    'rounded px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-emerald-50 text-emerald-800' => $pg->is_published,
                                    'bg-gray-100 text-ink-muted' => ! $pg->is_published,
                                ])>
                                    {{ $pg->is_published ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <span class="rounded border border-hairline bg-white px-2 py-0.5 font-mono text-[11px] text-ink-muted">
                                    {{ is_array($pg->blocks) ? count($pg->blocks) : 0 }} blok
                                </span>
                                @if ($pg->criteria_code)
                                    <span class="rounded bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-primary">
                                        [{{ $pg->criteria_code }}]
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1.5 text-sm font-bold text-ink">{{ $pg->title }}</p>
                            <p class="font-mono text-xs text-ink-muted">/halaman/{{ $pg->slug }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <a
                                href="{{ route('public.page', ['slug' => $pg->slug]) }}"
                                target="_blank"
                                class="inline-flex min-h-10 items-center rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-primary-soft"
                            >
                                Lihat
                            </a>
                            <button
                                type="button"
                                wire:click="edit({{ $pg->id }})"
                                x-on:click="document.getElementById('workspace-editor-halaman')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="inline-flex min-h-10 cursor-pointer items-center rounded-md bg-primary px-3 font-semibold text-white hover:bg-primary-hover"
                            >
                                Edit Isi
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $pg->id }})"
                                wire:confirm="Hapus halaman ini?"
                                class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-red-50"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
