@php
    $visibleRoots = $rootMenus->where('is_visible', true)->count();
@endphp
<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Menu Navigasi</h1>
            <p class="mt-1 text-sm text-ink-body">
                Susun menu utama dan submenu website maksimal 3 tingkat. Standar SINOVA membatasi maksimal 6 menu tingkat pertama di header agar pengunjung mudah bernavigasi.
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
            <a href="{{ route('admin.sliders') }}" class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10">
                Kelola Slider Beranda
            </a>
            <button type="button" wire:click="createNew" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-10">
                + Tambah Item Menu
            </button>
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Visual Tree (7 cols) -->
        <section class="studio-card overflow-hidden lg:col-span-7">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline px-4 pt-2 sm:px-5">
                <div class="flex gap-1 overflow-x-auto" role="tablist" aria-label="Lokasi menu">
                    @foreach (['header' => 'Header Utama', 'footer' => 'Tautan Footer', 'shortcut' => 'Shortcut Beranda'] as $loc => $lbl)
                        <button
                            type="button"
                            role="tab"
                            aria-selected="{{ $locationFilter === $loc ? 'true' : 'false' }}"
                            wire:click="$set('locationFilter', '{{ $loc }}')"
                            @class([
                                'inline-flex min-h-11 shrink-0 cursor-pointer items-center border-b-2 px-3 text-sm transition-colors',
                                'border-primary font-bold text-primary' => $locationFilter === $loc,
                                'border-transparent font-medium text-ink-body hover:text-ink' => $locationFilter !== $loc,
                            ])
                        >
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>

                @if ($locationFilter === 'header')
                    <span @class([
                        'mb-2 inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 font-mono text-xs font-semibold',
                        'bg-emerald-50 text-emerald-800' => $visibleRoots <= 6,
                        'bg-red-50 text-red-700' => $visibleRoots > 6,
                    ])>
                        Batas Header: {{ $visibleRoots }} / 6
                    </span>
                @endif
            </div>

            <div class="p-4 sm:p-5">
                @if ($rootMenus->isEmpty())
                    <div class="py-10 text-center">
                        <p class="text-sm font-bold text-ink">Belum ada menu pada lokasi ini</p>
                        <p class="mt-1 text-xs text-ink-muted">Gunakan formulir di samping untuk menambahkan item menu baru.</p>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($rootMenus as $menu)
                            <li wire:key="menu-{{ $menu->id }}" class="rounded-xl border border-hairline bg-mist p-3.5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-sm font-bold text-ink">{{ $loop->iteration }}. {{ $menu->title }}</span>
                                            @if ($menu->criteria_code)
                                                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-semibold text-primary">{{ $menu->criteria_code }}</span>
                                            @endif
                                            @unless ($menu->is_visible)
                                                <span class="rounded bg-white px-2 py-0.5 text-[11px] font-medium text-ink-muted">Tersembunyi</span>
                                            @endunless
                                            @if ($menu->type === 'page' && $menu->page === null)
                                                <span class="rounded bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700">Halaman belum ditautkan</span>
                                            @elseif ($menu->type === 'page' && ! $menu->page->is_published)
                                                <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900">Halaman draf</span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 truncate font-mono text-xs text-ink-muted">{{ $menu->resolvedUrl() }}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                        <button type="button" wire:click="moveUp({{ $menu->id }})" aria-label="Naikkan menu {{ $menu->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-8">&uarr;</button>
                                        <button type="button" wire:click="moveDown({{ $menu->id }})" aria-label="Turunkan menu {{ $menu->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-8">&darr;</button>
                                        <button type="button" wire:click="toggleVisible({{ $menu->id }})" aria-label="{{ $menu->is_visible ? 'Sembunyikan' : 'Tampilkan' }} menu {{ $menu->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 font-semibold text-ink-body hover:bg-mist sm:min-h-8">{{ $menu->is_visible ? 'Sembunyi' : 'Tampil' }}</button>
                                        <button type="button" wire:click="edit({{ $menu->id }})" aria-label="Edit menu {{ $menu->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary sm:min-h-8">Ubah</button>
                                        <button type="button" wire:click="delete({{ $menu->id }})" wire:confirm="Hapus menu ini beserta submenunya?" aria-label="Hapus menu {{ $menu->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">Hapus</button>
                                    </div>
                                </div>

                                @if ($menu->children->isNotEmpty())
                                    <ul class="mt-3 space-y-2 border-s-2 border-primary/30 ps-3">
                                        @foreach ($menu->children as $child)
                                            <li wire:key="menu-child-{{ $child->id }}" class="space-y-2 rounded-lg border border-hairline bg-white p-3 text-xs">
                                                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <span class="font-semibold text-ink">{{ $child->title }}</span>
                                                            @if ($child->criteria_code)
                                                                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-semibold text-primary">{{ $child->criteria_code }}</span>
                                                            @endif
                                                            @unless ($child->is_visible)
                                                                <span class="rounded bg-mist px-2 py-0.5 text-[11px] text-ink-muted">Tersembunyi</span>
                                                            @endunless
                                                            @if ($child->type === 'page' && $child->page === null)
                                                                <span class="rounded bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700">Halaman belum ditautkan</span>
                                                            @elseif ($child->type === 'page' && ! $child->page->is_published)
                                                                <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900">Halaman draf</span>
                                                            @endif
                                                        </div>
                                                        <p class="mt-0.5 truncate font-mono text-[11px] text-ink-muted">{{ $child->resolvedUrl() }}</p>
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-1">
                                                        <button type="button" wire:click="moveUp({{ $child->id }})" aria-label="Naikkan submenu {{ $child->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-hairline bg-white px-2 font-semibold sm:min-h-8">&uarr;</button>
                                                        <button type="button" wire:click="moveDown({{ $child->id }})" aria-label="Turunkan submenu {{ $child->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-hairline bg-white px-2 font-semibold sm:min-h-8">&darr;</button>
                                                        <button type="button" wire:click="toggleVisible({{ $child->id }})" aria-label="{{ $child->is_visible ? 'Sembunyikan' : 'Tampilkan' }} submenu {{ $child->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-hairline bg-white px-2 font-semibold sm:min-h-8">{{ $child->is_visible ? 'Sembunyi' : 'Tampil' }}</button>
                                                        <button type="button" wire:click="edit({{ $child->id }})" aria-label="Edit submenu {{ $child->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-hairline bg-white px-2.5 font-semibold text-primary sm:min-h-8">Ubah</button>
                                                        <button type="button" wire:click="delete({{ $child->id }})" wire:confirm="Hapus submenu ini?" aria-label="Hapus submenu {{ $child->title }}" class="inline-flex min-h-10 cursor-pointer items-center rounded-md border border-red-200 bg-white px-2.5 font-semibold text-red-700 sm:min-h-8">Hapus</button>
                                                    </div>
                                                </div>

                                                @if ($child->children->isNotEmpty())
                                                    <ul class="space-y-1.5 border-s-2 border-hairline ps-3">
                                                        @foreach ($child->children as $grandchild)
                                                            <li wire:key="menu-grand-{{ $grandchild->id }}" class="flex flex-col gap-2 rounded-lg border border-hairline bg-mist p-2.5 text-xs sm:flex-row sm:items-center sm:justify-between">
                                                                <div class="min-w-0">
                                                                    <span class="font-medium text-ink">{{ $grandchild->title }}</span>
                                                                    <p class="truncate font-mono text-[11px] text-ink-muted">{{ $grandchild->resolvedUrl() }}</p>
                                                                </div>
                                                                <div class="flex flex-wrap items-center gap-1">
                                                                    <button type="button" wire:click="moveUp({{ $grandchild->id }})" aria-label="Naikkan sub-submenu {{ $grandchild->title }}" class="inline-flex min-h-9 cursor-pointer items-center rounded border border-hairline bg-white px-2 sm:min-h-7">&uarr;</button>
                                                                    <button type="button" wire:click="moveDown({{ $grandchild->id }})" aria-label="Turunkan sub-submenu {{ $grandchild->title }}" class="inline-flex min-h-9 cursor-pointer items-center rounded border border-hairline bg-white px-2 sm:min-h-7">&darr;</button>
                                                                    <button type="button" wire:click="toggleVisible({{ $grandchild->id }})" aria-label="{{ $grandchild->is_visible ? 'Sembunyikan' : 'Tampilkan' }} sub-submenu {{ $grandchild->title }}" class="inline-flex min-h-9 cursor-pointer items-center rounded border border-hairline bg-white px-2 sm:min-h-7">{{ $grandchild->is_visible ? 'Sembunyi' : 'Tampil' }}</button>
                                                                    <button type="button" wire:click="edit({{ $grandchild->id }})" aria-label="Edit sub-submenu {{ $grandchild->title }}" class="inline-flex min-h-9 cursor-pointer items-center rounded border border-hairline bg-white px-2 font-semibold text-primary sm:min-h-7">Ubah</button>
                                                                    <button type="button" wire:click="delete({{ $grandchild->id }})" wire:confirm="Hapus sub-submenu ini?" aria-label="Hapus sub-submenu {{ $grandchild->title }}" class="inline-flex min-h-9 cursor-pointer items-center rounded border border-red-200 bg-white px-2 text-red-700 sm:min-h-7">Hapus</button>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <!-- Form Item Menu (5 cols) -->
        <aside class="studio-card p-5 sm:p-6 lg:sticky lg:top-20 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3.5">
                <div>
                    <h2 class="text-base font-bold text-ink">
                        {{ $editingId ? 'Ubah Item Menu' : 'Tambah / Ubah Item Menu' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Pilih induk menu bila ingin menambahkan submenu tingkat 2 atau 3.</p>
                </div>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="min-h-10 cursor-pointer text-xs font-semibold text-primary hover:underline">
                        + Buat Baru
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="menu_title">Label menu <span class="text-red-700">*</span></flux:label>
                    <flux:input id="menu_title" wire:model="title" required placeholder="Contoh: Layanan Mediasi" />
                    <flux:error name="title" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="menu_location">Lokasi</flux:label>
                        <x-admin.select id="menu_location" wire:model.live="location">
                            <option value="header">Header Utama</option>
                            <option value="footer">Footer</option>
                            <option value="shortcut">Shortcut Beranda</option>
                        </x-admin.select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="menu_tingkat">Tingkat satker</flux:label>
                        <x-admin.select id="menu_tingkat" wire:model="tingkat_scope">
                            <option value="all">Semua Tingkat</option>
                            <option value="pertama">Tingkat Pertama</option>
                            <option value="banding">Tingkat Banding</option>
                        </x-admin.select>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="menu_parent">Induk menu (maks. 3 tingkat)</flux:label>
                    <x-admin.select id="menu_parent" wire:model="parent_id" search-placeholder="Cari induk menu...">
                        <option value="">Menu Utama (Tingkat 1)</option>
                        @foreach ($parentOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->parent ? '└ ' : '' }}{{ $opt->title }}</option>
                        @endforeach
                    </x-admin.select>
                    <flux:error name="parent_id" />
                </flux:field>

                <flux:field>
                    <flux:label for="menu_type">Jenis tujuan</flux:label>
                    <x-admin.select id="menu_type" wire:model.live="type">
                        <option value="page">Halaman Website Internal</option>
                        <option value="group">Grup Dropdown (Tanpa Tautan)</option>
                        <option value="external">URL Eksternal / Rute Langsung</option>
                        <option value="category">Kategori Berita</option>
                        <option value="collection">Koleksi / Modul</option>
                    </x-admin.select>
                </flux:field>

                @if ($type === 'page')
                    <flux:field>
                        <flux:label for="menu_page">Pilih halaman tujuan</flux:label>
                        <x-admin.select id="menu_page" wire:model="page_id" search-placeholder="Cari judul halaman...">
                            <option value="">Pilih halaman...</option>
                            @foreach ($pages as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->title }}</option>
                            @endforeach
                        </x-admin.select>
                        <flux:error name="page_id" />
                    </flux:field>
                @elseif ($type === 'external')
                    <flux:field>
                        <flux:label for="menu_url">URL tujuan</flux:label>
                        <flux:input id="menu_url" wire:model="url" placeholder="https://... atau /publikasi" class="font-mono" />
                        <flux:error name="url" />
                    </flux:field>
                @elseif (in_array($type, ['category', 'collection'], true))
                    <flux:field>
                        <flux:label for="menu_col">Slug kategori / koleksi</flux:label>
                        <flux:input id="menu_col" wire:model="collection_slug" placeholder="Contoh: berita atau pengumuman" class="font-mono" />
                        <flux:error name="collection_slug" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label for="menu_criteria">Kode kriteria SINOVA (opsional)</flux:label>
                    <flux:input id="menu_criteria" wire:model="criteria_code" placeholder="Contoh: 1.a.2.b" class="font-mono" />
                </flux:field>

                <div class="space-y-1 rounded-xl border border-hairline bg-mist p-3">
                    <label class="flex min-h-10 cursor-pointer items-center gap-2.5 text-sm font-medium text-ink">
                        <input type="checkbox" wire:model="is_visible" class="size-4.5 rounded border-[#768780] text-primary">
                        <span>Tampilkan di navigasi</span>
                    </label>
                    <label class="flex min-h-10 cursor-pointer items-center gap-2.5 text-sm font-medium text-ink">
                        <input type="checkbox" wire:model="open_in_new_tab" class="size-4.5 rounded border-[#768780] text-primary">
                        <span>Buka di tab baru</span>
                    </label>
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Menu' : 'Simpan Item Menu' }}
                </flux:button>
            </form>
        </aside>
    </div>
</div>
