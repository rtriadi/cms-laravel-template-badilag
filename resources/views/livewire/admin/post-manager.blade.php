<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <!-- Studio Mode Switcher Bar (Daftar Publikasi <-> Studio Penulisan) -->
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
                <span>1. Daftar Berita, Artikel &amp; Pengumuman ({{ $posts->count() }})</span>
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
                <span>2. Studio Penulisan Artikel &amp; Berita</span>
                @if ($editingId)
                    <span class="rounded bg-gold px-1.5 py-0.5 font-mono text-2xs font-bold text-ink">
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
                    &larr; Kembali ke Daftar Publikasi
                </button>
            @endif
            <button
                type="button"
                wire:click="createNew"
                class="inline-flex min-h-10 cursor-pointer items-center rounded-md bg-primary px-3.5 text-xs font-semibold text-white hover:bg-primary-hover"
            >
                + Tulis Artikel Baru
            </button>
        </div>
    </div>

    <!-- Full-Width CMS Article & News Studio Workspace (8/4 Inspector Layout) -->
    <div
        id="workspace-editor-publikasi"
        x-show="$wire.editorOpen"
        x-cloak
        class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-6"
    >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-4">
            <div>
                <span class="rounded bg-primary-soft px-2.5 py-0.5 font-mono text-2xs font-bold text-primary uppercase">
                    {{ $editingId ? 'Mode Edit Artikel #' . $editingId : 'Mode Tulis Publikasi Baru' }}
                </span>
                <h2 class="mt-1 font-serif text-xl font-bold text-ink">
                    {{ $editingId ? 'Edit Publikasi: ' . $title : 'Tulis Berita, Pengumuman, atau Artikel Layanan Hukum' }}
                </h2>
                <p class="text-xs text-ink-muted">
                    Gunakan editor di bawah ini untuk menulis berita kegiatan, pengumuman, maupun artikel bantuan hukum (Prodeo &amp; Posbakum) lengkap dengan gambar dan lampiran PDF.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="closeEditor"
                    class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-hairline bg-mist px-3.5 text-xs font-semibold text-ink hover:bg-primary-soft hover:text-primary"
                >
                    &larr; Kembali ke Daftar
                </button>
                @if ($editingId)
                    <button
                        type="button"
                        wire:click="createNew"
                        class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-primary bg-white px-3.5 text-xs font-semibold text-primary hover:bg-primary-soft"
                    >
                        + Tulis Artikel Baru
                    </button>
                @endif
            </div>
        </div>

        <form wire:submit="save" class="mt-5 grid gap-6 xl:grid-cols-12">
            <!-- Left 8 Columns: Wide Article Editor Canvas -->
            <div class="space-y-4 xl:col-span-8">
                <flux:field>
                    <flux:label for="post_title">Judul Berita / Artikel / Pengumuman *</flux:label>
                    <flux:input id="post_title" wire:model.live="title" required placeholder="Contoh: Panduan Layanan Pembebasan Biaya Perkara (Prodeo) dan Posbakum" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="post_body">Isi Konten Lengkap *</flux:label>
                    <x-rich-editor
                        id="post_body"
                        model="body"
                        label="Isi konten publikasi"
                        placeholder="Tulis isi berita, pengumuman, atau artikel lengkap, serta sisipkan gambar atau dokumen PDF..."
                        :min-height="360"
                    />
                    <flux:error name="body" />
                </flux:field>

                <flux:field>
                    <flux:label for="post_excerpt">Ringkasan Singkat untuk Kartu Berita (Opsional)</flux:label>
                    <x-rich-editor
                        id="post_excerpt"
                        model="excerpt"
                        label="Ringkasan singkat publikasi"
                        placeholder="Ringkasan singkat 1-2 kalimat untuk pratinjau kartu..."
                        :min-height="140"
                        :compact="true"
                    />
                    <flux:error name="excerpt" />
                </flux:field>
            </div>

            <!-- Right 4 Columns: Inspector Sidebar (Category, Slug, Featured Image, Publish CTA) -->
            <aside class="space-y-4 rounded-xl border border-hairline bg-mist p-4 xl:col-span-4">
                <h3 class="border-b border-hairline pb-2.5 font-serif text-base font-bold text-ink">
                    Kategori, Gambar &amp; Penerbitan
                </h3>

                <flux:field>
                    <flux:label for="post_category">Kategori Publikasi</flux:label>
                    <flux:select id="post_category" wire:model.live="category">
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category" />
                </flux:field>

                @if ($category === 'serta_merta')
                    <div class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-medium text-ink">
                        Kategori <strong>Informasi Serta Merta</strong> akan ditampilkan sebagai pita pengumuman prioritas di bagian paling atas beranda portal publik.
                    </div>
                @elseif ($category === 'layanan_hukum')
                    <div class="rounded-md border border-emerald-200 bg-primary-soft px-3 py-2 text-xs font-medium text-primary-deep">
                        Artikel kategori <strong>Layanan &amp; Bantuan Hukum</strong> otomatis tampil pada halaman <em>Hak Pencari Keadilan (Prodeo &amp; Posbakum)</em> serta direktori berita.
                    </div>
                @endif

                <flux:field>
                    <flux:label for="post_slug">Slug URL (/publikasi/...)</flux:label>
                    <flux:input id="post_slug" wire:model="slug" required placeholder="panduan-layanan-prodeo-posbakum" />
                    <flux:error name="slug" />
                </flux:field>

                <div class="space-y-3 rounded-lg border border-hairline bg-white p-3.5">
                    <flux:field>
                        <flux:label for="post_image">Gambar Sampul Utama (Maks 4 MB)</flux:label>
                        <input id="post_image" type="file" wire:model="featured_image" accept="image/*" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-hairline file:bg-mist file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary" />
                        <div wire:loading wire:target="featured_image" class="mt-1 text-xs text-primary">Mengunggah gambar...</div>
                        <flux:error name="featured_image" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="post_alt">Teks Alternatif Gambar (Alt Text)</flux:label>
                        <flux:input id="post_alt" wire:model="featured_image_alt" placeholder="Deskripsi isi gambar untuk pembaca layar" />
                        <flux:error name="featured_image_alt" />
                    </flux:field>
                </div>

                <div class="space-y-2 rounded-lg border border-hairline bg-white p-3.5">
                    <label class="flex min-h-10 cursor-pointer items-center gap-2.5 text-xs font-semibold text-ink">
                        <input type="checkbox" wire:model="is_pinned" class="size-4 rounded border-hairline text-primary">
                        <span>Sematkan di urutan teratas (Pinned)</span>
                    </label>

                    <label class="flex min-h-10 cursor-pointer items-center gap-2.5 text-xs font-semibold text-ink">
                        <input type="checkbox" wire:model="is_published" class="size-4 rounded border-hairline text-primary">
                        <span>Publikasikan langsung ke portal</span>
                    </label>
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Publikasi' }}
                </flux:button>
            </aside>
        </form>
    </div>

    <!-- Full-Width List of All Posts -->
    <div x-show="!$wire.editorOpen" class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-4">
            <div>
                <h2 class="font-serif text-lg font-bold text-ink">Daftar Berita, Artikel &amp; Pengumuman ({{ $posts->count() }})</h2>
                <p class="text-xs text-ink-muted">Filter berdasarkan kategori publikasi di bawah ini, atau klik <strong>+ Tulis Artikel Baru</strong> di atas untuk membuat artikel.</p>
            </div>
            <div class="w-full sm:w-64">
                <label for="post_filter" class="sr-only">Filter kategori</label>
                <flux:select id="post_filter" wire:model.live="categoryFilter">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $catKey => $catLabel)
                        <option value="{{ $catKey }}">{{ $catLabel }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if ($posts->isEmpty())
            <p class="py-10 text-center text-sm text-ink-muted">Belum ada publikasi pada filter kategori ini.</p>
        @else
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($posts as $post)
                    <div
                        wire:key="post-{{ $post->id }}"
                        @class([
                            'flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3.5',
                            'border-primary bg-primary-soft/50' => $editingId === $post->id,
                            'border-hairline bg-mist/60 hover:border-primary/40 hover:bg-white' => $editingId !== $post->id,
                        ])
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded bg-primary-soft px-2 py-0.5 text-2xs font-semibold text-primary">
                                    {{ $categories[$post->category] ?? $post->category }}
                                </span>
                                @if ($post->is_pinned)
                                    <span class="rounded bg-amber-100 px-2 py-0.5 text-2xs font-semibold text-ink">Disematkan</span>
                                @endif
                                <span @class([
                                    'rounded px-2 py-0.5 text-2xs font-semibold',
                                    'bg-emerald-50 text-emerald-800' => $post->is_published,
                                    'bg-mist text-ink-muted' => ! $post->is_published,
                                ])>
                                    {{ $post->is_published ? 'Tayang' : 'Draf' }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-sm font-bold text-ink">{{ $post->title }}</p>
                            <p class="font-mono text-xs text-ink-muted">/publikasi/{{ $post->slug }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            @if ($post->is_published)
                                <a
                                    href="{{ route('public.posts.show', ['slug' => $post->slug]) }}"
                                    target="_blank"
                                    class="inline-flex min-h-10 items-center rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-primary-soft"
                                >
                                    Lihat
                                </a>
                            @endif
                            <button
                                type="button"
                                wire:click="edit({{ $post->id }})"
                                x-on:click="document.getElementById('workspace-editor-publikasi')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="inline-flex min-h-10 cursor-pointer items-center rounded-md bg-primary px-3 font-semibold text-white hover:bg-primary-hover"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $post->id }})"
                                wire:confirm="Hapus publikasi ini?"
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
