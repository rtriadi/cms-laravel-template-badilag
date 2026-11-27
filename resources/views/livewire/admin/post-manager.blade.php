<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @if ($editorOpen)
        <!-- Editor Publikasi 2 Kolom (v4) -->
        <div id="workspace-editor-publikasi" class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        wire:click="closeEditor"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink transition-colors hover:border-primary hover:bg-primary-soft hover:text-primary sm:min-h-9"
                    >
                        &larr; Daftar Publikasi
                    </button>
                    <h1 class="font-serif text-xl font-bold text-ink sm:text-2xl">
                        {{ $editingId ? 'Ubah Publikasi' : 'Tulis Publikasi Baru' }}
                    </h1>
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-50 text-emerald-800' => $is_published,
                        'bg-mist text-ink-body' => ! $is_published,
                    ])>
                        <span class="size-1.5 rounded-full {{ $is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                        {{ $is_published ? 'Tayang' : 'Draf' }}
                    </span>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
                    @if ($editingId && $is_published && $slug)
                        <a
                            href="{{ route('public.posts.show', ['slug' => $slug]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10"
                        >
                            Pratinjau
                        </a>
                    @endif
                    @if ($editingId)
                        <button type="button" wire:click="createNew" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10">
                            + Tulis Baru
                        </button>
                    @endif
                    <button
                        type="submit"
                        form="post-studio-form"
                        class="inline-flex min-h-11 flex-1 shrink-0 cursor-pointer items-center justify-center whitespace-nowrap rounded-lg bg-primary px-5 text-xs font-semibold text-white transition-colors hover:bg-primary-hover sm:min-h-10 sm:flex-none"
                    >
                        {{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Publikasi' }}
                    </button>
                </div>
            </div>

            <form id="post-studio-form" wire:submit="save" class="grid items-start gap-6 xl:grid-cols-12">
                <!-- Left 8 Columns: Clean Writing Canvas -->
                <div class="space-y-5 xl:col-span-8">
                    <div class="studio-card space-y-5 p-5 sm:p-6">
                        <flux:field>
                            <flux:label for="post_title">Judul publikasi <span class="text-red-700">*</span></flux:label>
                            <flux:input id="post_title" wire:model.live="title" required placeholder="Contoh: Panduan Layanan Pembebasan Biaya Perkara (Prodeo)" />
                            <p class="mt-1 font-mono text-xs text-ink-muted">
                                Alamat otomatis: /publikasi/{{ $slug ?: 'slug-publikasi' }}
                            </p>
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="post_body">Isi publikasi <span class="text-red-700">*</span></flux:label>
                            <x-rich-editor
                                wire:key="post-body-editor-{{ $editingId ?? 'new' }}"
                                id="post_body"
                                model="body"
                                label="Isi konten publikasi"
                                placeholder="Tulis isi berita, pengumuman, atau artikel lengkap, serta sisipkan gambar atau dokumen PDF..."
                                :min-height="420"
                            />
                            <flux:error name="body" />
                        </flux:field>

                        <flux:field>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <flux:label for="post_excerpt" class="mb-0!">Ringkasan singkat</flux:label>
                                <span class="text-xs text-ink-muted">(opsional)</span>
                            </div>
                            <x-rich-editor
                                wire:key="post-excerpt-editor-{{ $editingId ?? 'new' }}"
                                id="post_excerpt"
                                model="excerpt"
                                label="Ringkasan singkat publikasi"
                                placeholder="Ringkasan 1-2 kalimat yang tampil di daftar berita dan hasil pencarian Google..."
                                :min-height="140"
                                :compact="true"
                            />
                            <flux:error name="excerpt" />
                        </flux:field>
                    </div>
                </div>

                <!-- Right 4 Columns: Publish, Category, Cover & Google Preview -->
                <aside class="space-y-4 xl:sticky xl:top-20 xl:col-span-4">
                    <div class="studio-card space-y-4 p-5">
                        <h2 class="text-base font-bold text-ink">Terbitkan</h2>

                        <flux:field>
                            <flux:label for="post_category">Kategori</flux:label>
                            <x-admin.select id="post_category" wire:model.live="category" search-placeholder="Cari kategori...">
                                @foreach ($categories as $catKey => $catLabel)
                                    <option value="{{ $catKey }}">{{ $catLabel }}</option>
                                @endforeach
                            </x-admin.select>
                            <flux:error name="category" />
                        </flux:field>

                        @if ($category === 'serta_merta')
                            <div class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2.5 text-xs font-medium text-amber-950">
                                Kategori <strong>Informasi Serta Merta</strong> tampil sebagai pita pengumuman prioritas di bagian atas beranda.
                            </div>
                        @elseif ($category === 'layanan_hukum')
                            <div class="rounded-lg border border-emerald-200 bg-primary-soft px-3 py-2.5 text-xs font-medium text-primary-deep">
                                Artikel <strong>Layanan &amp; Bantuan Hukum</strong> otomatis tampil pada halaman <em>Prodeo &amp; Posbakum</em>.
                            </div>
                        @endif

                        <flux:field>
                            <flux:label for="post_slug">Slug URL</flux:label>
                            <flux:input id="post_slug" wire:model="slug" required placeholder="panduan-layanan-prodeo" class="font-mono" />
                            <flux:error name="slug" />
                        </flux:field>

                        <div class="space-y-1 rounded-xl border border-hairline bg-mist p-3">
                            <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-xs font-semibold text-ink">
                                <input type="checkbox" wire:model="is_published" class="size-4.5 rounded border-[#768780] text-primary">
                                <span>Tayangkan langsung ke website</span>
                            </label>
                            <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-xs font-semibold text-ink">
                                <input type="checkbox" wire:model="is_pinned" class="size-4.5 rounded border-[#768780] text-primary">
                                <span>Sematkan paling atas di beranda</span>
                            </label>
                            <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-xs font-semibold text-ink">
                                <input type="checkbox" wire:model="comments_enabled" class="size-4.5 rounded border-[#768780] text-primary">
                                <span>Aktifkan komentar termoderasi</span>
                            </label>
                        </div>

                        <flux:field>
                            <flux:label for="post-expires-at">Kedaluwarsa tayang (opsional)</flux:label>
                            <input
                                id="post-expires-at"
                                type="datetime-local"
                                wire:model="expiresAt"
                                class="min-h-11 w-full rounded-lg border border-[#768780] bg-white px-3 text-sm text-ink"
                            >
                            <p class="mt-1 text-xs text-ink-muted">Publikasi otomatis disembunyikan setelah waktu ini.</p>
                            @error('expiresAt')
                                <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                            @enderror
                        </flux:field>

                        <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                            {{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Publikasi' }}
                        </flux:button>
                    </div>

                    <div class="studio-card space-y-3.5 p-5">
                        <h2 class="text-base font-bold text-ink">Gambar sampul</h2>
                        <p class="text-xs leading-relaxed text-ink-muted">
                            Dipakai sebagai thumbnail kartu di beranda dan daftar publikasi (JPG, PNG, atau WebP, maks. 4 MB).
                        </p>
                        @if ($existingFeaturedImagePath)
                            <div class="overflow-hidden rounded-lg border border-hairline bg-mist p-1.5">
                                <img
                                    src="{{ asset('storage/' . $existingFeaturedImagePath) }}"
                                    alt="{{ $featured_image_alt ?: $title }}"
                                    class="h-32 w-full rounded object-cover"
                                />
                            </div>
                        @endif
                        <input id="post_image" type="file" wire:model="featured_image" accept="image/*" aria-label="Pilih gambar sampul publikasi" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:min-h-10 file:cursor-pointer file:rounded-lg file:border file:border-hairline file:bg-mist file:px-3 file:text-xs file:font-semibold file:text-primary" />
                        <div wire:loading wire:target="featured_image" role="status" aria-live="polite" class="text-xs font-semibold text-primary">Mengunggah gambar...</div>
                        <flux:error name="featured_image" />

                        <flux:field>
                            <flux:label for="post_alt">Teks alternatif (Alt Text)</flux:label>
                            <flux:input id="post_alt" wire:model="featured_image_alt" placeholder="Jelaskan isi gambar untuk pembaca layar" />
                            <flux:error name="featured_image_alt" />
                        </flux:field>
                    </div>

                    <div class="studio-card space-y-1.5 p-5">
                        <h2 class="text-sm font-bold text-ink">Pratinjau di Google</h2>
                        <p class="truncate font-mono text-xs text-ink-muted">{{ url('/publikasi/' . ($slug ?: 'slug-publikasi')) }}</p>
                        <p class="line-clamp-2 text-base font-semibold text-[#1a0dab]">{{ $title ?: 'Judul Publikasi Resmi Satuan Kerja' }}</p>
                        <p class="line-clamp-2 text-xs leading-relaxed text-ink-body">
                            {{ trim(strip_tags((string) $excerpt)) ?: 'Ringkasan publikasi akan ditampilkan di sini pada hasil pencarian Google.' }}
                        </p>
                    </div>
                </aside>
            </form>
        </div>
    @else
        <!-- Daftar Publikasi (v4 + Mobile Cards) -->
        <div class="space-y-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="min-w-0 max-w-2xl">
                    <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Publikasi</h1>
                    <p class="mt-1 text-sm text-ink-body">
                        Kelola berita, pengumuman, dan artikel layanan hukum. Publikasi berstatus Tayang langsung tampil di website.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="createNew"
                    class="inline-flex min-h-11 shrink-0 cursor-pointer items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-primary px-4 text-sm font-semibold text-white transition-colors hover:bg-primary-hover sm:min-h-10"
                >
                    + Tulis Publikasi
                </button>
            </div>

            <div class="studio-card overflow-hidden">
                <!-- Underline Tabs -->
                <div class="studio-tabs px-4 pt-2 sm:px-6" role="tablist" aria-label="Filter status publikasi">
                    @foreach ([
                        '' => ['Semua', $statusCounts['all']],
                        'published' => ['Tayang', $statusCounts['published']],
                        'draft' => ['Draf', $statusCounts['draft']],
                        'pinned' => ['Disematkan', $statusCounts['pinned']],
                    ] as $stKey => [$stName, $stCount])
                        <button
                            type="button"
                            role="tab"
                            aria-selected="{{ $statusFilter === $stKey ? 'true' : 'false' }}"
                            wire:click="$set('statusFilter', '{{ $stKey }}')"
                            @class([
                                'inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 border-b-2 px-3 text-sm transition-colors',
                                'border-primary font-bold text-primary' => $statusFilter === $stKey,
                                'border-transparent font-medium text-ink-body hover:text-ink' => $statusFilter !== $stKey,
                            ])
                        >
                            <span>{{ $stName }}</span>
                            <span @class([
                                'rounded-md px-2 py-0.5 font-mono text-xs font-semibold',
                                'bg-primary-soft text-primary' => $statusFilter === $stKey,
                                'bg-mist text-ink-muted' => $statusFilter !== $stKey,
                            ])>{{ $stCount }}</span>
                        </button>
                    @endforeach
                </div>

                <!-- Search & Category Filter Toolbar -->
                <div class="flex flex-col gap-3 border-b border-hairline/70 p-4 sm:flex-row sm:items-center sm:px-6">
                    <div class="flex-1 sm:max-w-sm">
                        <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari judul publikasi" placeholder="Cari judul publikasi atau slug..." />
                    </div>
                    <div class="w-full sm:w-56">
                        <label for="post_filter" class="sr-only">Filter kategori</label>
                        <x-admin.select id="post_filter" wire:model.live="categoryFilter" search-placeholder="Cari kategori...">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </x-admin.select>
                    </div>
                    <div wire:loading wire:target="search,categoryFilter,statusFilter" role="status" aria-live="polite" class="text-xs font-medium text-primary">
                        Memuat daftar publikasi...
                    </div>
                </div>

                <!-- Contextual Obsidian Bulk Action Bar -->
                @if (! empty($selectedIds))
                    <form wire:submit="applyBulkAction" class="flex flex-col gap-3 bg-obsidian px-4 py-3 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <span class="text-sm font-semibold">{{ count($selectedIds) }} publikasi dipilih</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="w-48">
                                <x-admin.select wire:model="bulkAction" aria-label="Pilih aksi massal publikasi" :compact="true" search-placeholder="Cari aksi...">
                                    <option value="">Pilih aksi massal...</option>
                                    <option value="publish">Tayangkan ke Portal</option>
                                    <option value="draft">Jadikan Draf</option>
                                    <option value="pin">Sematkan di Atas</option>
                                    <option value="unpin">Lepas Sematan</option>
                                    <option value="delete">Hapus Permanen</option>
                                </x-admin.select>
                            </div>
                            <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-gold px-4 text-xs font-bold text-ink hover:opacity-90 sm:min-h-9">
                                Terapkan
                            </button>
                            <button type="button" wire:click="$set('selectedIds', [])" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-[#1a4d3e] bg-[#0d4434] px-3 text-xs font-semibold text-white hover:bg-primary sm:min-h-9">
                                Batal
                            </button>
                        </div>
                    </form>
                @endif

                @if ($posts === null || $posts->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <p class="text-base font-bold text-ink">Belum ada publikasi ditemukan</p>
                        <p class="mx-auto mt-1 max-w-md text-sm text-ink-muted">
                            {{ trim($search) !== '' || $categoryFilter !== '' || $statusFilter !== '' ? 'Tidak ada publikasi yang cocok dengan pencarian atau filter aktif.' : 'Mulai tulis berita, pengumuman, atau artikel layanan hukum pertama satuan kerja.' }}
                        </p>
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            @if (trim($search) !== '' || $categoryFilter !== '' || $statusFilter !== '')
                                <button type="button" wire:click="$set('search', ''); $set('categoryFilter', ''); $set('statusFilter', '')" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink hover:bg-mist">
                                    Reset Filter
                                </button>
                            @endif
                            <button type="button" wire:click="createNew" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover">
                                + Tulis Publikasi
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Mobile Card Stack (< md) -->
                    <ul class="divide-y divide-hairline md:hidden">
                        @foreach ($posts as $post)
                            <li wire:key="post-mob-{{ $post->id }}" class="space-y-3 p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="inline-flex min-h-8 cursor-pointer items-center gap-2">
                                        <input type="checkbox" value="{{ $post->id }}" wire:model.live="selectedIds" aria-label="Pilih publikasi {{ $post->title }}" class="size-4.5 rounded border-[#768780] text-primary">
                                        <span @class([
                                            'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-800' => $post->is_published,
                                            'bg-mist text-ink-body' => ! $post->is_published,
                                        ])>
                                            <span class="size-1.5 rounded-full {{ $post->is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                                            {{ $post->is_published ? 'Tayang' : 'Draf' }}
                                        </span>
                                        @if ($post->is_pinned)
                                            <span class="rounded bg-[#fff4e0] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#8a4b00]">PIN</span>
                                        @endif
                                    </label>
                                    <span class="text-xs text-ink-muted">{{ $categories[$post->category] ?? $post->category }}</span>
                                </div>

                                <div>
                                    <p class="text-sm leading-snug font-bold text-ink">{{ $post->title }}</p>
                                    <p class="mt-1 truncate font-mono text-xs text-ink-muted">/publikasi/{{ $post->slug }}</p>
                                    <p class="mt-1 font-mono text-xs text-ink-muted">
                                        {{ number_format((int) $post->views_count) }} dilihat &middot; {{ number_format((int) $post->approved_comments_count) }} komentar
                                    </p>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" wire:click="edit({{ $post->id }})" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary-soft">
                                        Ubah
                                    </button>
                                    @if ($post->is_published)
                                        <a href="{{ route('public.posts.show', ['slug' => $post->slug]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist">
                                            Lihat
                                        </a>
                                    @else
                                        <span class="inline-flex min-h-11 items-center justify-center rounded-lg border border-hairline/60 bg-mist px-3 text-xs text-ink-muted">Draf</span>
                                    @endif
                                    <button type="button" wire:click="delete({{ $post->id }})" wire:confirm="Hapus publikasi ini?" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 hover:bg-red-50">
                                        Hapus
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Desktop Editorial Table (>= md) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-hairline bg-mist text-xs font-semibold text-ink-muted">
                                    <th class="w-11 py-3 pr-2 pl-6"><span class="sr-only">Pilih</span></th>
                                    <th class="px-3 py-3">Judul &amp; alamat</th>
                                    <th class="px-3 py-3">Kategori</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3 text-right">Dilihat</th>
                                    <th class="px-3 py-3 text-right">Komentar</th>
                                    <th class="py-3 pr-6 pl-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline/70">
                                @foreach ($posts as $post)
                                    <tr wire:key="post-{{ $post->id }}" @class([
                                        'transition-colors hover:bg-mist/70',
                                        'bg-primary-soft/50' => in_array((string) $post->id, array_map('strval', $selectedIds), true),
                                    ])>
                                        <td class="py-4 pr-2 pl-6 align-middle">
                                            <input
                                                type="checkbox"
                                                value="{{ $post->id }}"
                                                wire:model.live="selectedIds"
                                                aria-label="Pilih publikasi {{ $post->title }}"
                                                class="size-4.5 rounded border-[#768780] text-primary"
                                            >
                                        </td>
                                        <td class="px-3 py-4 align-middle">
                                            <div class="flex max-w-lg items-center gap-3">
                                                @if ($post->featured_image_path)
                                                    <img
                                                        src="{{ asset('storage/' . $post->featured_image_path) }}"
                                                        alt="{{ $post->featured_image_alt ?: $post->title }}"
                                                        width="56"
                                                        height="42"
                                                        class="h-10.5 w-14 shrink-0 rounded-lg border border-hairline object-cover"
                                                    />
                                                @else
                                                    <div class="flex h-10.5 w-14 shrink-0 items-center justify-center rounded-lg border border-hairline bg-primary-soft font-mono text-[10px] font-bold text-primary">
                                                        IMG
                                                    </div>
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <p class="break-words text-sm leading-snug font-semibold text-ink">{{ $post->title }}</p>
                                                        @if ($post->is_pinned)
                                                            <span class="shrink-0 rounded bg-[#fff4e0] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#8a4b00]">Disematkan</span>
                                                        @endif
                                                        @if ($post->featured_image_path && empty($post->featured_image_alt))
                                                            <span class="shrink-0 rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">Alt Kosong</span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-0.5 truncate font-mono text-xs text-ink-muted" title="/publikasi/{{ $post->slug }}">/publikasi/{{ $post->slug }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 align-middle text-xs font-medium text-ink-body">
                                            {{ $categories[$post->category] ?? $post->category }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 align-middle">
                                            <span @class([
                                                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold',
                                                'bg-emerald-50 text-emerald-800' => $post->is_published,
                                                'bg-mist text-ink-body' => ! $post->is_published,
                                            ])>
                                                <span class="size-1.5 rounded-full {{ $post->is_published ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                                                {{ $post->is_published ? 'Tayang' : 'Draf' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right align-middle font-mono text-xs text-ink-body">
                                            {{ number_format((int) $post->views_count) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right align-middle font-mono text-xs text-ink-body">
                                            {{ number_format((int) $post->approved_comments_count) }}
                                            @if ((int) $post->pending_comments_count > 0)
                                                <a href="{{ route('admin.comments', ['status' => 'pending']) }}" class="ml-1 font-bold text-amber-800 hover:underline">
                                                    (+{{ $post->pending_comments_count }})
                                                </a>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap py-4 pr-6 pl-3 text-right align-middle">
                                            <div class="inline-flex items-center gap-1.5">
                                                @if ($post->is_published)
                                                    <a href="{{ route('public.posts.show', ['slug' => $post->slug]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink-body hover:border-primary hover:text-primary">
                                                        Lihat
                                                    </a>
                                                @endif
                                                <button type="button" wire:click="edit({{ $post->id }})" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary">
                                                    Ubah
                                                </button>
                                                <button type="button" wire:click="delete({{ $post->id }})" wire:confirm="Hapus publikasi ini?" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-hairline px-4 py-3.5 sm:px-6">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
