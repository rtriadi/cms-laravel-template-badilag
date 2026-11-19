<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Form Publikasi -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">
                    {{ $editingId ? 'Edit Publikasi' : 'Tambah Berita / Pengumuman' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="cursor-pointer text-xs font-semibold text-primary hover:underline">
                        Buat Baru
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="post_title">Judul Publikasi</flux:label>
                    <flux:input id="post_title" wire:model.live="title" required placeholder="Contoh: Pengumuman Layanan Sidang Keliling" />
                    <flux:error name="title" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="post_slug">Slug URL</flux:label>
                        <flux:input id="post_slug" wire:model="slug" required placeholder="pengumuman-sidang-keliling" />
                        <flux:error name="slug" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="post_category">Kategori</flux:label>
                        <flux:select id="post_category" wire:model="category">
                            @foreach ($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="category" />
                    </flux:field>
                </div>

                @if ($category === 'serta_merta')
                    <div class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-medium text-ink">
                        Kategori Informasi Serta Merta akan ditampilkan sebagai pengumuman prioritas pada beranda portal publik sesuai KMA 2-144 Tahun 2022.
                    </div>
                @endif

                <flux:field>
                    <flux:label for="post_excerpt">Ringkasan Singkat (Opsional)</flux:label>
                    <x-rich-editor
                        id="post_excerpt"
                        model="excerpt"
                        label="Ringkasan singkat publikasi"
                        placeholder="Ringkasan untuk kartu berita..."
                        :min-height="150"
                        :compact="true"
                    />
                    <flux:error name="excerpt" />
                </flux:field>

                <flux:field>
                    <flux:label for="post_body">Isi Konten</flux:label>
                    <x-rich-editor
                        id="post_body"
                        model="body"
                        label="Isi konten publikasi"
                        placeholder="Tulis isi berita, pengumuman, atau artikel lengkap, serta sisipkan gambar atau dokumen PDF..."
                        :min-height="320"
                    />
                    <flux:error name="body" />
                </flux:field>

                <div class="space-y-3 rounded-lg border border-hairline bg-mist p-3.5">
                    <flux:field>
                        <flux:label for="post_image">Gambar Utama (Maks 4 MB)</flux:label>
                        <input id="post_image" type="file" wire:model="featured_image" accept="image/*" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-hairline file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary" />
                        <div wire:loading wire:target="featured_image" class="mt-1 text-xs text-primary">Mengunggah gambar...</div>
                        <flux:error name="featured_image" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="post_alt">Teks Alternatif Gambar (Alt Text WCAG)</flux:label>
                        <flux:input id="post_alt" wire:model="featured_image_alt" placeholder="Deskripsi isi gambar untuk pembaca layar" />
                        <flux:error name="featured_image_alt" />
                    </flux:field>
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-xs font-medium text-ink">
                        <input type="checkbox" wire:model="is_pinned" class="size-4 rounded border-hairline text-primary">
                        <span>Sematkan di bagian atas (Pinned)</span>
                    </label>

                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-xs font-medium text-ink">
                        <input type="checkbox" wire:model="is_published" class="size-4 rounded border-hairline text-primary">
                        <span>Publikasikan ke portal</span>
                    </label>
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Publikasi' }}
                </flux:button>
            </form>
        </div>

        <!-- Daftar Publikasi -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">Daftar Publikasi ({{ $posts->count() }})</h2>
                <div class="w-52">
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
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($posts as $post)
                        <li wire:key="post-{{ $post->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-primary-soft px-2 py-0.5 text-[11px] font-semibold text-primary">
                                        {{ $categories[$post->category] ?? $post->category }}
                                    </span>
                                    @if ($post->is_pinned)
                                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-ink">Disematkan</span>
                                    @endif
                                    <span @class([
                                        'rounded px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $post->is_published,
                                        'bg-gray-100 text-ink-muted' => ! $post->is_published,
                                    ])>
                                        {{ $post->is_published ? 'Tayang' : 'Draf' }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-ink">{{ $post->title }}</p>
                                <p class="truncate font-mono text-xs text-ink-muted">/publikasi/{{ $post->slug }}</p>
                            </div>

                            <div class="flex items-center gap-1.5 text-xs">
                                <button type="button" wire:click="edit({{ $post->id }})" class="min-h-11 cursor-pointer rounded border border-primary px-3 py-1 font-semibold text-primary hover:bg-primary-soft">
                                    Edit
                                </button>
                                <button type="button" wire:click="delete({{ $post->id }})" wire:confirm="Hapus publikasi ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-3 py-1 font-semibold text-red-700 hover:bg-red-50">
                                    Hapus
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
