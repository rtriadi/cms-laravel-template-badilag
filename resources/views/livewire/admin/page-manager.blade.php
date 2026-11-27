<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @if ($editorOpen)
        <!-- Editor Halaman & Blok (v4 2-Kolom Bersih) -->
        <div id="workspace-editor-halaman" class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        wire:click="closeEditor"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink transition-colors hover:border-primary hover:bg-primary-soft hover:text-primary sm:min-h-9"
                    >
                        &larr; Daftar Halaman
                    </button>
                    <h1 class="truncate font-serif text-xl font-bold text-ink sm:text-2xl">
                        {{ $title ?: ($editingId ? 'Ubah Halaman' : 'Halaman Baru') }}
                    </h1>
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-50 text-emerald-800' => $is_published,
                        'bg-mist text-ink-body' => ! $is_published,
                    ])>
                        <span class="size-1.5 rounded-full {{ $is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                        {{ $is_published ? 'Tayang' : 'Draf' }}
                    </span>
                    @if ($criteria_code)
                        <span class="rounded-md bg-primary-soft px-2 py-1 font-mono text-xs font-semibold text-primary">
                            Kriteria {{ $criteria_code }}
                        </span>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
                    @if ($editingId && $slug)
                        <a
                            href="{{ route('public.page', ['slug' => $slug]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10"
                        >
                            Pratinjau Halaman
                        </a>
                    @endif
                    <button
                        type="submit"
                        form="page-builder-form"
                        class="inline-flex min-h-11 flex-1 shrink-0 cursor-pointer items-center justify-center whitespace-nowrap rounded-lg bg-primary px-5 text-xs font-semibold text-white transition-colors hover:bg-primary-hover sm:min-h-10 sm:flex-none"
                    >
                        {{ $editingId ? 'Simpan Perubahan' : 'Simpan Halaman Baru' }}
                    </button>
                </div>
            </div>

            <form id="page-builder-form" wire:submit="save" class="grid items-start gap-6 xl:grid-cols-12">
                <!-- Left 8 Columns: Document & Blocks Canvas -->
                <div class="space-y-5 xl:col-span-8">
                    <div class="studio-card space-y-4 p-5 sm:p-6">
                        <flux:field>
                            <flux:label for="page_title">Judul halaman <span class="text-red-700">*</span></flux:label>
                            <flux:input id="page_title" wire:model.live="title" required placeholder="Contoh: Hak Pencari Keadilan (Prodeo & Posbakum)" />
                            <p class="mt-1 font-mono text-xs text-ink-muted">Alamat halaman: /halaman/{{ $slug ?: 'slug-baru' }}</p>
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <flux:label for="page_summary" class="mb-0!">Ringkasan halaman</flux:label>
                                <span class="text-xs text-ink-muted">(opsional)</span>
                            </div>
                            <flux:input id="page_summary" wire:model="summary" placeholder="Deskripsi singkat yang tampil tepat di bawah judul halaman..." />
                        </flux:field>
                    </div>

                    <!-- Friendly Block Inserter -->
                    <div class="rounded-xl border border-primary/25 bg-primary-soft p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-bold text-primary">Tambahkan bagian ke halaman ini ({{ count($blocks) }} blok)</p>
                            <p class="text-xs text-ink-muted">Urutan bisa diatur dengan tombol Naik / Turun</p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ([
                                'richtext' => '+ Teks & Tabel',
                                'document_list' => '+ Daftar Dokumen PDF',
                                'article_list' => '+ Daftar Berita Terkait',
                                'embed_fallback' => '+ Embed SIPP / SIMTEPA',
                                'external_portal' => '+ Portal Eksternal',
                                'map_yurisdiksi' => '+ Peta Lokasi Kantor',
                                'satker_contact' => '+ Kontak Satker',
                            ] as $bType => $bLabel)
                                <button
                                    type="button"
                                    wire:click="addBlock('{{ $bType }}')"
                                    class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink transition-colors hover:border-primary hover:bg-primary hover:text-white"
                                >
                                    {{ $bLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modular Block Stack -->
                    <div class="space-y-4">
                        @foreach ($blocks as $index => $block)
                            @php
                                $bTypeLabel = match ($block['type'] ?? 'richtext') {
                                    'document_list' => 'Dokumen PDF',
                                    'article_list' => 'Daftar Berita',
                                    'embed_fallback' => 'Integrasi SIPP/SIMTEPA',
                                    'external_portal' => 'Portal Eksternal',
                                    'map_yurisdiksi' => 'Peta Lokasi',
                                    'satker_contact' => 'Kontak Satker',
                                    default => 'Teks & Tabel',
                                };
                            @endphp
                            <div
                                wire:key="blk-{{ $editingId ?? 'new' }}-{{ $index }}-{{ $block['type'] ?? 'richtext' }}"
                                x-data="{ open: {{ $index === 0 || count($blocks) <= 2 ? 'true' : 'false' }} }"
                                class="studio-card overflow-hidden"
                            >
                                <div class="flex flex-col gap-2.5 border-b border-hairline bg-mist px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                    <button
                                        type="button"
                                        x-on:click="open = !open"
                                        class="flex min-h-10 min-w-0 flex-1 cursor-pointer items-center gap-2.5 text-left"
                                    >
                                        <span class="shrink-0 rounded-md bg-primary-soft px-2 py-0.5 font-mono text-xs font-semibold text-primary">
                                            {{ $index + 1 }} &middot; {{ $bTypeLabel }}
                                        </span>
                                        <span class="truncate text-sm font-semibold text-ink">
                                            {{ trim((string) ($block['heading'] ?? '')) ?: 'Tanpa subjudul bagian' }}
                                        </span>
                                    </button>

                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <button type="button" wire:click="moveBlockUp({{ $index }})" @disabled($index === 0) aria-label="Naikkan blok #{{ $index + 1 }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary disabled:opacity-40 sm:min-h-8">
                                            &uarr; Naik
                                        </button>
                                        <button type="button" wire:click="moveBlockDown({{ $index }})" @disabled($index >= count($blocks) - 1) aria-label="Turunkan blok #{{ $index + 1 }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary disabled:opacity-40 sm:min-h-8">
                                            &darr; Turun
                                        </button>
                                        <button type="button" wire:click="duplicateBlock({{ $index }})" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-primary hover:bg-primary-soft sm:min-h-8">
                                            Duplikat
                                        </button>
                                        <button type="button" wire:click="removeBlock({{ $index }})" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 text-xs font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">
                                            Hapus
                                        </button>
                                        <button type="button" x-on:click="open = !open" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg px-2 text-xs font-semibold text-primary hover:bg-primary-soft sm:min-h-8">
                                            <span x-text="open ? 'Tutup' : 'Buka'">Buka</span>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="open" class="space-y-4 p-4 sm:p-5">
                                    <flux:field>
                                        <flux:label>Subjudul bagian</flux:label>
                                        <flux:input wire:model="blocks.{{ $index }}.heading" aria-label="Judul subbagian blok #{{ $index + 1 }}" placeholder="Contoh: Syarat Pengajuan Pembebasan Biaya Perkara" />
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
                                            <flux:label>Sumber koleksi dokumen PDF</flux:label>
                                            <x-admin.select wire:model="blocks.{{ $index }}.collection_type" aria-label="Koleksi dokumen blok #{{ $index + 1 }}" search-placeholder="Cari koleksi dokumen...">
                                                @foreach ($collectionTypes as $cKey => $cLabel)
                                                    <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                                @endforeach
                                            </x-admin.select>
                                            <p class="mt-1 text-xs text-ink-muted">Dokumen PDF dari menu Dokumen PDF pada koleksi ini otomatis ditampilkan beserta filter tahun.</p>
                                        </flux:field>
                                    @elseif (($block['type'] ?? '') === 'article_list')
                                        <flux:field>
                                            <flux:label>Kategori publikasi</flux:label>
                                            <x-admin.select wire:model="blocks.{{ $index }}.post_category" aria-label="Kategori artikel blok #{{ $index + 1 }}" search-placeholder="Cari kategori publikasi...">
                                                @foreach ($postCategories as $pKey => $pLabel)
                                                    <option value="{{ $pKey }}">{{ $pLabel }}</option>
                                                @endforeach
                                                <option value="semua">Semua Kategori Publikasi</option>
                                            </x-admin.select>
                                            <p class="mt-1 text-xs text-ink-muted">Otomatis menampilkan daftar artikel dari menu Publikasi sesuai kategori ini.</p>
                                        </flux:field>
                                    @elseif (($block['type'] ?? '') === 'embed_fallback')
                                        @php
                                            $srcKey = (string) ($block['source_key'] ?? 'simtepa_pegawai');
                                            $customEmbed = (string) ($block['custom_embed_url'] ?? '');
                                            $previewResolved = $satker?->resolveEmbedSource($srcKey, $customEmbed) ?? ['embed_url' => null, 'tabs' => []];
                                        @endphp
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:field>
                                                <flux:label>Sumber Embed SIPP / SIMTEPA</flux:label>
                                                <x-admin.select wire:model.live="blocks.{{ $index }}.source_key" aria-label="Sumber embed blok #{{ $index + 1 }}" search-placeholder="Cari sumber SIPP / SIMTEPA...">
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
                                                </x-admin.select>
                                            </flux:field>
                                            <flux:field>
                                                <flux:label>Mode Cadangan Lokal</flux:label>
                                                <x-admin.select wire:model="blocks.{{ $index }}.fallback_mode" aria-label="Mode cadangan lokal blok #{{ $index + 1 }}">
                                                    <option value="officials_table">Cadangan Tabel Pegawai</option>
                                                    <option value="org_chart">Cadangan Struktur Organisasi</option>
                                                    <option value="court_schedule">Cadangan Jadwal Sidang</option>
                                                    <option value="case_statistics">Cadangan Statistik Perkara</option>
                                                </x-admin.select>
                                            </flux:field>
                                        </div>

                                        <div class="rounded-lg border border-emerald-200 bg-primary-soft px-3.5 py-2.5 text-xs text-ink">
                                            <p class="font-bold text-primary-deep">Sumber URL Embed Aktif:</p>
                                            @if (! empty($previewResolved['tabs']))
                                                <p class="mt-0.5 text-ink-body">
                                                    Otomatis menampilkan <strong>{{ count($previewResolved['tabs']) }} Tab Kategori SIMTEPA</strong> dari token <code>{{ $satker?->extractSimtepaToken() }}</code>.
                                                </p>
                                            @elseif (! empty($previewResolved['embed_url']))
                                                <p class="mt-0.5 break-all font-mono text-primary">{{ $previewResolved['embed_url'] }}</p>
                                            @else
                                                <p class="mt-0.5 text-amber-900">URL belum diatur di Pengaturan Satker (otomatis memakai Data Cadangan Lokal).</p>
                                            @endif
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:field>
                                                <flux:label>URL Embed Khusus (opsional)</flux:label>
                                                <flux:input wire:model.live="blocks.{{ $index }}.custom_embed_url" aria-label="Override URL embed blok #{{ $index + 1 }}" placeholder="Kosongkan jika memakai URL otomatis..." />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label>Catatan Penjelas (opsional)</flux:label>
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
                                        <div class="grid gap-4 sm:grid-cols-3">
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
                                        <p class="rounded-lg border border-hairline bg-mist p-3.5 text-xs text-ink-body">
                                            Menampilkan identitas alamat lengkap, kontak resmi, dan foto gedung dari Pengaturan Satker secara otomatis.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right 4 Columns: Tabbed Settings Inspector -->
                <aside x-data="{ inspectorTab: 'pengaturan' }" class="studio-card overflow-hidden xl:sticky xl:top-20 xl:col-span-4">
                    <div class="studio-tabs px-4 pt-2" role="tablist" aria-label="Pengaturan halaman">
                        <button type="button" role="tab" x-bind:aria-selected="inspectorTab === 'pengaturan'" x-on:click="inspectorTab = 'pengaturan'" x-bind:class="inspectorTab === 'pengaturan' ? 'border-primary font-bold text-primary' : 'border-transparent font-medium text-ink-body'" class="min-h-11 flex-1 cursor-pointer border-b-2 px-2 text-xs transition-colors">
                            Pengaturan
                        </button>
                        <button type="button" role="tab" x-bind:aria-selected="inspectorTab === 'seo'" x-on:click="inspectorTab = 'seo'" x-bind:class="inspectorTab === 'seo' ? 'border-primary font-bold text-primary' : 'border-transparent font-medium text-ink-body'" class="min-h-11 flex-1 cursor-pointer border-b-2 px-2 text-xs transition-colors">
                            SEO &amp; Google
                        </button>
                        <button type="button" role="tab" x-bind:aria-selected="inspectorTab === 'sinova'" x-on:click="inspectorTab = 'sinova'" x-bind:class="inspectorTab === 'sinova' ? 'border-primary font-bold text-primary' : 'border-transparent font-medium text-ink-body'" class="min-h-11 flex-1 cursor-pointer border-b-2 px-2 text-xs transition-colors">
                            SINOVA
                        </button>
                    </div>

                    <div class="space-y-4 p-5">
                        <!-- Tab 1: Pengaturan -->
                        <div x-show="inspectorTab === 'pengaturan'" class="space-y-4">
                            <div class="rounded-xl border border-emerald-200 bg-primary-soft px-3.5 py-2.5">
                                <label class="flex min-h-10 cursor-pointer items-center gap-2.5 text-xs font-semibold text-primary-deep">
                                    <input type="checkbox" wire:model="is_published" class="size-4.5 rounded border-[#768780] text-primary">
                                    <span>Tampilkan di website publik</span>
                                </label>
                            </div>

                            <flux:field>
                                <flux:label for="page_slug">Alamat halaman (slug)</flux:label>
                                <flux:input id="page_slug" wire:model.live="slug" required placeholder="layanan-hukum/hak-pencari-keadilan" class="font-mono" />
                                <p class="mt-1 text-xs text-ink-muted">Mengubah slug otomatis membuat pengalihan Redirect 301.</p>
                                <flux:error name="slug" />
                            </flux:field>

                            <flux:field>
                                <flux:label for="page_tingkat">Cakupan tingkat peradilan</flux:label>
                                <x-admin.select id="page_tingkat" wire:model="tingkat_scope">
                                    <option value="all">Semua Tingkat (Pertama &amp; Banding)</option>
                                    <option value="pertama">Khusus Tingkat Pertama</option>
                                    <option value="banding">Khusus Tingkat Banding</option>
                                </x-admin.select>
                            </flux:field>
                        </div>

                        <!-- Tab 2: SEO & Google -->
                        <div x-show="inspectorTab === 'seo'" x-cloak class="space-y-4">
                            <div class="rounded-xl border border-hairline bg-mist p-3.5 text-xs">
                                <p class="font-semibold text-primary">Pratinjau di Google</p>
                                <p class="mt-1 truncate font-mono text-[11px] text-ink-muted">{{ url('/halaman/' . ($slug ?: 'slug-halaman')) }}</p>
                                <p class="mt-0.5 truncate text-sm font-bold text-[#1a0dab]">{{ $title ?: 'Judul Halaman Resmi Satuan Kerja' }}</p>
                                <p class="mt-1 line-clamp-2 leading-relaxed text-ink-body">
                                    {{ trim($meta_description) !== '' ? $meta_description : (trim($summary) !== '' ? $summary : 'Deskripsi SEO Google otomatis diambil dari ringkasan atau isi blok pertama.') }}
                                </p>
                            </div>

                            <flux:field>
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <flux:label for="page_meta_desc" class="mb-0!">Deskripsi SEO Google</flux:label>
                                    <span class="font-mono text-xs {{ mb_strlen($meta_description) > 155 ? 'font-bold text-amber-800' : 'text-ink-muted' }}">
                                        {{ mb_strlen($meta_description) }}/160
                                    </span>
                                </div>
                                <flux:textarea id="page_meta_desc" wire:model.live="meta_description" rows="3" maxlength="160" placeholder="Ringkasan maks. 160 karakter untuk hasil pencarian Google..." />
                                <flux:error name="meta_description" />
                            </flux:field>
                        </div>

                        <!-- Tab 3: SINOVA & KMA -->
                        <div x-show="inspectorTab === 'sinova'" x-cloak class="space-y-4">
                            <flux:field>
                                <flux:label for="page_cat">Klasifikasi KMA 2-144</flux:label>
                                <x-admin.select id="page_cat" wire:model="category_tag">
                                    <option value="berkala">Informasi Berkala</option>
                                    <option value="serta_merta">Informasi Serta Merta</option>
                                    <option value="setiap_saat">Informasi Setiap Saat</option>
                                </x-admin.select>
                            </flux:field>

                            <flux:field>
                                <flux:label for="page_crit">Terhubung ke kriteria SINOVA</flux:label>
                                <x-admin.select id="page_crit" wire:model="criteria_code" search-placeholder="Cari kode atau nama kriteria...">
                                    <option value="">Tidak terikat kriteria khusus</option>
                                    @foreach ($criteriaOptions as $cOpt)
                                        <option value="{{ $cOpt->code }}">{{ $cOpt->code }} - {{ $cOpt->name }}</option>
                                    @endforeach
                                </x-admin.select>
                            </flux:field>
                        </div>

                        <div class="border-t border-hairline pt-4">
                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                {{ $editingId ? 'Simpan Perubahan Halaman' : 'Simpan Halaman Baru' }}
                            </flux:button>
                        </div>
                    </div>
                </aside>
            </form>
        </div>
    @else
        <!-- Direktori Halaman Website (v4 + Mobile Cards) -->
        <div class="space-y-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="min-w-0 max-w-2xl">
                    <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Halaman Website</h1>
                    <p class="mt-1 text-sm text-ink-body">
                        Kelola {{ $pages->count() }} halaman profil, layanan hukum, transparansi SAKIP, dan informasi publik menggunakan editor blok.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="createNew"
                    class="inline-flex min-h-11 shrink-0 cursor-pointer items-center justify-center whitespace-nowrap rounded-lg bg-primary px-4 text-sm font-semibold text-white transition-colors hover:bg-primary-hover sm:min-h-10"
                >
                    + Buat Halaman Baru
                </button>
            </div>

            <div class="studio-card overflow-hidden">
                <div class="studio-tabs px-4 pt-2 sm:px-6" role="tablist" aria-label="Filter bagian halaman">
                    @foreach (['' => 'Semua Bagian', 'profil' => 'Profil', 'layanan-hukum' => 'Layanan Hukum', 'transparansi' => 'Transparansi & SAKIP', 'layanan-publik' => 'Layanan Publik & PPID'] as $secKey => $secLabel)
                        <button
                            type="button"
                            role="tab"
                            aria-selected="{{ $sectionFilter === $secKey ? 'true' : 'false' }}"
                            wire:click="$set('sectionFilter', '{{ $secKey }}')"
                            @class([
                                'inline-flex min-h-11 shrink-0 cursor-pointer items-center border-b-2 px-3 text-sm transition-colors',
                                'border-primary font-bold text-primary' => $sectionFilter === $secKey,
                                'border-transparent font-medium text-ink-body hover:text-ink' => $sectionFilter !== $secKey,
                            ])
                        >
                            {{ $secLabel }}
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 border-b border-hairline/70 p-4 sm:flex-row sm:items-center sm:px-6">
                    <div class="flex-1 sm:max-w-sm">
                        <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari halaman atau kode kriteria" placeholder="Cari judul halaman, slug, atau kode kriteria..." />
                    </div>
                    <div wire:loading wire:target="search,sectionFilter" role="status" aria-live="polite" class="text-xs font-medium text-primary">
                        Memuat daftar halaman...
                    </div>
                </div>

                @if ($pages->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-base font-bold text-ink">Tidak ada halaman yang cocok</p>
                        <p class="mt-1 text-sm text-ink-muted">Coba ubah kata kunci pencarian atau pilih tab Semua Bagian.</p>
                    </div>
                @else
                    <!-- Mobile Cards (< md) -->
                    <ul class="divide-y divide-hairline md:hidden">
                        @foreach ($pages as $pg)
                            <li wire:key="pg-mob-{{ $pg->id }}" class="space-y-3 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $pg->is_published,
                                        'bg-mist text-ink-body' => ! $pg->is_published,
                                    ])>
                                        <span class="size-1.5 rounded-full {{ $pg->is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                                        {{ $pg->is_published ? 'Tayang' : 'Draf' }}
                                    </span>
                                    @if ($pg->criteria_code)
                                        <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-xs font-semibold text-primary">{{ $pg->criteria_code }}</span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-ink">{{ $pg->title }}</p>
                                    <p class="mt-0.5 truncate font-mono text-xs text-ink-muted">/halaman/{{ $pg->slug }}</p>
                                    <p class="mt-0.5 text-xs text-ink-muted">{{ (int) ($pg->blocks_count ?? (is_array($pg->blocks) ? count($pg->blocks) : 0)) }} blok konten</p>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" wire:click="edit({{ $pg->id }})" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary-soft">
                                        Ubah
                                    </button>
                                    <a href="{{ route('public.page', ['slug' => $pg->slug]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist">
                                        Lihat
                                    </a>
                                    <button type="button" wire:click="delete({{ $pg->id }})" wire:confirm="Hapus halaman ini?" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 hover:bg-red-50">
                                        Hapus
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Desktop Table (>= md) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-hairline bg-mist text-xs font-semibold text-ink-muted">
                                    <th class="py-3 pr-3 pl-6">Judul halaman &amp; alamat</th>
                                    <th class="px-3 py-3">Blok</th>
                                    <th class="px-3 py-3">Kriteria</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="py-3 pr-6 pl-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline/70">
                                @foreach ($pages as $pg)
                                    <tr wire:key="pg-{{ $pg->id }}" class="transition-colors hover:bg-mist/70">
                                        <td class="py-4 pr-3 pl-6 align-middle">
                                            <div class="max-w-lg">
                                                <p class="break-words text-sm font-semibold text-ink">{{ $pg->title }}</p>
                                                <p class="mt-0.5 truncate font-mono text-xs text-ink-muted" title="/halaman/{{ $pg->slug }}">/halaman/{{ $pg->slug }}</p>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 align-middle font-mono text-xs text-ink-body">
                                            {{ (int) ($pg->blocks_count ?? (is_array($pg->blocks) ? count($pg->blocks) : 0)) }} blok
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 align-middle">
                                            @if ($pg->criteria_code)
                                                <span class="rounded-md bg-primary-soft px-2 py-0.5 font-mono text-xs font-semibold text-primary">
                                                    {{ $pg->criteria_code }}
                                                </span>
                                            @else
                                                <span class="text-ink-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 align-middle">
                                            <span @class([
                                                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold',
                                                'bg-emerald-50 text-emerald-800' => $pg->is_published,
                                                'bg-mist text-ink-body' => ! $pg->is_published,
                                            ])>
                                                <span class="size-1.5 rounded-full {{ $pg->is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                                                {{ $pg->is_published ? 'Tayang' : 'Draf' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap py-4 pr-6 pl-3 text-right align-middle">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a href="{{ route('public.page', ['slug' => $pg->slug]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink-body hover:border-primary hover:text-primary">
                                                    Lihat
                                                </a>
                                                <button type="button" wire:click="edit({{ $pg->id }})" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary">
                                                    Ubah
                                                </button>
                                                <button type="button" wire:click="delete({{ $pg->id }})" wire:confirm="Hapus halaman ini?" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 text-xs font-semibold text-red-700 hover:bg-red-50">
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
