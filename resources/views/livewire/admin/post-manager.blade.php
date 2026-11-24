<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-[#edf7f3] px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @if ($editorOpen)
    <!-- Article & News Studio v3 Workspace (Unified Action Bar + 67/33 Canvas) -->
    <div id="workspace-editor-publikasi" class="space-y-4">
        <div class="studio-card flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    wire:click="closeEditor"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-[#e2e8e5] bg-[#f5f7f6] px-3 text-xs font-semibold text-[#334155] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary"
                >
                    <span>&larr; Daftar Publikasi</span>
                </button>
                <span @class([
                    'inline-flex items-center rounded-md px-2.5 py-1 font-mono text-[10px] font-bold uppercase',
                    'bg-[#edf7f3] text-primary' => $is_published,
                    'bg-[#f1f5f9] text-[#64748b]' => ! $is_published,
                ])>
                    {{ $is_published ? '● Tayang' : '○ Draf' }}
                </span>
                <span class="truncate font-mono text-xs text-[#64748b]">
                    /publikasi/{{ $slug ?: 'slug-publikasi' }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($editingId)
                    <button
                        type="button"
                        wire:click="createNew"
                        class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-[#e2e8e5] bg-white px-3 text-xs font-semibold text-[#334155] hover:border-primary/40 hover:text-primary"
                    >
                        + Tulis Baru
                    </button>
                @endif
                <button
                    type="submit"
                    form="post-studio-form"
                    class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-primary-hover"
                >
                    {{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Publikasi' }}
                </button>
            </div>
        </div>

        <form id="post-studio-form" wire:submit="save" class="grid items-start gap-5 xl:grid-cols-12">
            <!-- Left 8 Columns: Clean Paper Writing Canvas -->
            <div class="studio-card space-y-5 p-5 sm:p-6 xl:col-span-8">
                <flux:field>
                    <flux:label for="post_title">Judul Publikasi *</flux:label>
                    <flux:input id="post_title" wire:model.live="title" required placeholder="Contoh: Panduan Layanan Pembebasan Biaya Perkara (Prodeo) dan Posbakum" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="post_body">Isi Konten Lengkap *</flux:label>
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
                        <flux:label for="post_excerpt" class="mb-0!">Ringkasan Singkat</flux:label>
                        <span class="font-mono text-[11px] text-[#64748b]">Opsional</span>
                    </div>
                    <x-rich-editor
                        wire:key="post-excerpt-editor-{{ $editingId ?? 'new' }}"
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

            <!-- Right 4 Columns: Inspector Sidebar -->
            <aside class="studio-card space-y-4 p-4 sm:p-5 xl:sticky xl:top-20 xl:col-span-4">
                <h3 class="border-b border-[#e2e8e5] pb-2.5 font-serif text-[15px] font-bold text-[#0f172a]">
                    Kategori, Sampul &amp; Penerbitan
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
                    <div class="rounded-lg border border-amber-200 bg-[#fff7e6] px-3 py-2 text-xs font-medium text-[#92400e]">
                        Kategori <strong>Informasi Serta Merta</strong> tampil sebagai pita pengumuman prioritas di bagian atas beranda.
                    </div>
                @elseif ($category === 'layanan_hukum')
                    <div class="rounded-lg border border-[#c7e6da] bg-[#edf7f3] px-3 py-2 text-xs font-medium text-primary-deep">
                        Artikel <strong>Layanan &amp; Bantuan Hukum</strong> otomatis tampil pada halaman <em>Prodeo &amp; Posbakum</em>.
                    </div>
                @endif

                <flux:field>
                    <flux:label for="post_slug">Slug URL</flux:label>
                    <flux:input id="post_slug" wire:model="slug" required placeholder="panduan-layanan-prodeo-posbakum" />
                    <flux:error name="slug" />
                </flux:field>

                <div class="space-y-3 rounded-xl border border-[#e2e8e5] bg-[#f9fbfa] p-3.5">
                    <flux:field>
                        <flux:label for="post_image">Thumbnail Kartu Berita (Maks. 4 MB)</flux:label>
                        <p class="mb-2 text-[11px] leading-relaxed text-[#64748b]">
                            Dipakai sebagai thumbnail kartu di beranda, daftar publikasi, &amp; pratinjau tautan (tidak ditampilkan ulang sebagai foto banner di atas isi berita).
                        </p>
                        @if ($existingFeaturedImagePath)
                            <div class="mb-2.5 overflow-hidden rounded-lg border border-[#e2e8e5] bg-white p-1.5">
                                <img
                                    src="{{ asset('storage/' . $existingFeaturedImagePath) }}"
                                    alt="{{ $featured_image_alt ?: $title }}"
                                    class="h-28 w-full rounded object-cover"
                                />
                            </div>
                        @endif
                        <input id="post_image" type="file" wire:model="featured_image" accept="image/*" class="block w-full cursor-pointer text-xs text-[#0f172a] file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-[#cbd5e1] file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary" />
                        <div wire:loading wire:target="featured_image" class="mt-1 text-xs text-primary">Mengunggah gambar...</div>
                        <flux:error name="featured_image" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="post_alt">Teks Alternatif (Alt Text)</flux:label>
                        <flux:input id="post_alt" wire:model="featured_image_alt" placeholder="Deskripsi isi gambar untuk pembaca layar" />
                        <flux:error name="featured_image_alt" />
                    </flux:field>
                </div>

                <div class="space-y-1 rounded-xl border border-[#e2e8e5] bg-[#f9fbfa] p-3">
                    <label class="flex min-h-9 cursor-pointer items-center gap-2.5 text-xs font-semibold text-[#0f172a]">
                        <input type="checkbox" wire:model="is_pinned" class="size-4 rounded border-[#cbd5e1] text-primary">
                        <span>Sematkan di urutan teratas</span>
                    </label>
                    <label class="flex min-h-9 cursor-pointer items-center gap-2.5 text-xs font-semibold text-[#0f172a]">
                        <input type="checkbox" wire:model="is_published" class="size-4 rounded border-[#cbd5e1] text-primary">
                        <span>Publikasikan langsung ke portal</span>
                    </label>
                    <label class="flex min-h-9 cursor-pointer items-center gap-2.5 text-xs font-semibold text-[#0f172a]">
                        <input type="checkbox" wire:model="comments_enabled" class="size-4 rounded border-[#cbd5e1] text-primary">
                        <span>Aktifkan komentar termoderasi</span>
                    </label>
                </div>

                <flux:button type="submit" variant="primary" class="h-10 w-full cursor-pointer bg-primary text-xs font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan' : 'Terbitkan Publikasi' }}
                </flux:button>
            </aside>
        </form>
    </div>
    @else
    <!-- Clean Editorial Table View -->
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-serif text-2xl font-bold tracking-tight text-[#0f172a]">Publikasi &amp; Berita Resmi</h2>
                <p class="mt-0.5 text-xs text-[#64748b] sm:text-[13px]">
                    Kelola berita pelayanan, pengumuman, artikel bantuan hukum, statistik pembaca, dan antrean komentar.
                </p>
            </div>
            <button
                type="button"
                wire:click="createNew"
                class="inline-flex h-10 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-primary-hover"
            >
                + Tulis Publikasi
            </button>
        </div>

        <div class="studio-card overflow-hidden">
            <!-- Unified Toolbar: Status Pills + Search + Category -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e2e8e5] p-4 sm:px-5">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ([
                        '' => 'Semua (' . $statusCounts['all'] . ')',
                        'published' => 'Tayang (' . $statusCounts['published'] . ')',
                        'draft' => 'Draf (' . $statusCounts['draft'] . ')',
                        'pinned' => 'Disematkan (' . $statusCounts['pinned'] . ')',
                    ] as $stKey => $stLabel)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $stKey }}')"
                            @class([
                                'h-8 cursor-pointer rounded-md px-3 text-xs font-semibold transition-colors',
                                'bg-primary text-white' => $statusFilter === $stKey,
                                'border border-[#e2e8e5] bg-[#f5f7f6] text-[#334155] hover:bg-[#edf7f3] hover:text-primary' => $statusFilter !== $stKey,
                            ])
                        >
                            {{ $stLabel }}
                        </button>
                    @endforeach
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <div class="w-full sm:w-52">
                        <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari judul publikasi" placeholder="Cari judul atau slug..." />
                    </div>
                    <div class="w-full sm:w-44">
                        <label for="post_filter" class="sr-only">Filter kategori</label>
                        <flux:select id="post_filter" wire:model.live="categoryFilter">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>

            @if (! empty($selectedIds))
                <form wire:submit="applyBulkAction" class="flex flex-wrap items-center justify-between gap-3 border-b border-[#c7e6da] bg-[#edf7f3] px-5 py-2.5 text-xs">
                    <span class="font-bold text-primary-deep">{{ count($selectedIds) }} publikasi dipilih</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <select wire:model="bulkAction" aria-label="Pilih aksi massal publikasi" class="h-8 rounded-md border border-[#cbd5e1] bg-white px-2.5 text-xs font-semibold text-[#0f172a]">
                            <option value="">-- Pilih Aksi Massal --</option>
                            <option value="publish">Terbitkan ke Portal</option>
                            <option value="draft">Jadikan Draf</option>
                            <option value="pin">Sematkan di Atas</option>
                            <option value="unpin">Lepas Sematan</option>
                            <option value="delete">Hapus Permanen</option>
                        </select>
                        <button type="submit" class="h-8 cursor-pointer rounded-md bg-primary px-3.5 font-semibold text-white hover:bg-primary-hover">
                            Terapkan
                        </button>
                    </div>
                </form>
            @endif

            @if ($posts === null || $posts->isEmpty())
                <p class="py-12 text-center text-sm text-[#64748b]">Belum ada publikasi pada filter kategori atau status ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#e2e8e5] bg-[#f9fbfa] font-mono text-[10.5px] font-bold tracking-wider text-[#64748b] uppercase">
                                <th class="w-10 py-3 pl-5 pr-2"><span class="sr-only">Pilih</span></th>
                                <th class="px-3 py-3">Judul Publikasi</th>
                                <th class="px-3 py-3">Kategori</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Statistik</th>
                                <th class="py-3 pl-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eef2f0]">
                            @foreach ($posts as $post)
                                <tr wire:key="post-{{ $post->id }}" class="transition-colors hover:bg-[#f9fbfa]">
                                    <td class="py-3.5 pl-5 pr-2 align-middle">
                                        <input
                                            type="checkbox"
                                            value="{{ $post->id }}"
                                            wire:model.live="selectedIds"
                                            aria-label="Pilih publikasi {{ $post->title }}"
                                            class="size-4 rounded border-[#cbd5e1] text-primary"
                                        >
                                    </td>
                                    <td class="px-3 py-3.5 align-top">
                                        <div class="flex max-w-lg items-start gap-3">
                                            @if ($post->featured_image_path)
                                                <img
                                                    src="{{ asset('storage/' . $post->featured_image_path) }}"
                                                    alt="{{ $post->featured_image_alt ?: $post->title }}"
                                                    width="56"
                                                    height="42"
                                                    class="mt-0.5 h-10.5 w-14 shrink-0 rounded-md border border-[#e2e8e5] object-cover"
                                                />
                                            @else
                                                <div class="mt-0.5 flex h-10.5 w-14 shrink-0 items-center justify-center rounded-md border border-[#e2e8e5] bg-[#edf7f3] font-mono text-[9.5px] font-bold text-primary">
                                                    IMG
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <p class="break-words text-[13px] leading-snug font-semibold text-[#0f172a]">{{ $post->title }}</p>
                                                    @if ($post->is_pinned)
                                                        <span class="shrink-0 rounded bg-[#fff7e6] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#92400e]">PIN</span>
                                                    @endif
                                                    @if ($post->featured_image_path && empty($post->featured_image_alt))
                                                        <span class="shrink-0 rounded bg-[#fef2f2] px-1.5 py-0.5 text-[10px] font-semibold text-red-700">Alt Kosong</span>
                                                    @endif
                                                </div>
                                                <p class="mt-1 break-all font-mono text-[11px] leading-snug text-[#64748b] line-clamp-1" title="/publikasi/{{ $post->slug }}">/publikasi/{{ $post->slug }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-md bg-[#edf7f3] px-2.5 py-0.5 text-[11px] font-semibold text-primary">
                                            {{ $categories[$post->category] ?? $post->category }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 align-top">
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold',
                                            'bg-[#ecfdf5] text-[#047857]' => $post->is_published,
                                            'bg-[#f1f5f9] text-[#64748b]' => ! $post->is_published,
                                        ])>
                                            {{ $post->is_published ? '● Tayang' : '○ Draf' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 align-top font-mono text-[11px]">
                                        <p class="font-medium text-[#0f172a]">{{ number_format((int) $post->views_count) }} dilihat</p>
                                        <p class="mt-0.5 text-[#64748b]">
                                            {{ number_format((int) $post->approved_comments_count) }} komentar
                                            @if ((int) $post->pending_comments_count > 0)
                                                <a href="{{ route('admin.comments', ['status' => 'pending']) }}" class="font-bold text-[#b45309] hover:underline">
                                                    ({{ $post->pending_comments_count }} pending)
                                                </a>
                                            @endif
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap py-3.5 pl-3 pr-5 text-right align-top">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if ($post->is_published)
                                                <a
                                                    href="{{ route('public.posts.show', ['slug' => $post->slug]) }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex h-8 items-center rounded-md border border-[#e2e8e5] bg-white px-2.5 text-xs font-semibold text-[#334155] hover:border-primary hover:text-primary"
                                                >
                                                    Lihat
                                                </a>
                                            @endif
                                            <button
                                                type="button"
                                                wire:click="edit({{ $post->id }})"
                                                x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                                class="inline-flex h-8 cursor-pointer items-center rounded-md border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary hover:text-white"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="delete({{ $post->id }})"
                                                wire:confirm="Hapus publikasi ini?"
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

                <div class="border-t border-[#e2e8e5] px-5 py-3">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
