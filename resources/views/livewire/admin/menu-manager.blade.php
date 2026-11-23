<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Menu Form -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">
                    {{ $editingId ? 'Ubah Item Menu' : 'Tambah Item Menu' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="text-xs font-semibold text-primary">Buat Baru</button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="menu_title">Label Menu</flux:label>
                    <flux:input id="menu_title" wire:model="title" required placeholder="Contoh: Layanan Mediasi" />
                    <flux:error name="title" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="menu_location">Lokasi</flux:label>
                        <flux:select id="menu_location" wire:model.live="location">
                            <option value="header">Header Utama</option>
                            <option value="footer">Footer</option>
                            <option value="shortcut">Shortcut Beranda</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="menu_tingkat">Tingkat Satker</flux:label>
                        <flux:select id="menu_tingkat" wire:model="tingkat_scope">
                            <option value="all">Semua Tingkat</option>
                            <option value="pertama">Tingkat Pertama</option>
                            <option value="banding">Tingkat Banding</option>
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="menu_parent">Menu Induk (Maks. 3 Level)</flux:label>
                    <flux:select id="menu_parent" wire:model="parent_id">
                        <option value="">-- Menu Tingkat Atas --</option>
                        @foreach ($parentOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->parent ? '-- ' : '' }}{{ $opt->title }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="parent_id" />
                </flux:field>

                <flux:field>
                    <flux:label for="menu_type">Tipe Tujuan</flux:label>
                    <flux:select id="menu_type" wire:model.live="type">
                        <option value="page">Halaman CMS</option>
                        <option value="group">Grup Dropdown (Tanpa Link)</option>
                        <option value="external">URL Eksternal / Rute Langsung</option>
                        <option value="category">Kategori Berita</option>
                        <option value="collection">Koleksi / Modul</option>
                    </flux:select>
                </flux:field>

                @if ($type === 'page')
                    <flux:field>
                        <flux:label for="menu_page">Pilih Halaman</flux:label>
                        <flux:select id="menu_page" wire:model="page_id">
                            <option value="">-- Pilih halaman --</option>
                            @foreach ($pages as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->title }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="page_id" />
                    </flux:field>
                @elseif ($type === 'external')
                    <flux:field>
                        <flux:label for="menu_url">URL Tujuan</flux:label>
                        <flux:input id="menu_url" wire:model="url" placeholder="https://... atau /publikasi" />
                        <flux:error name="url" />
                    </flux:field>
                @elseif (in_array($type, ['category', 'collection'], true))
                    <flux:field>
                        <flux:label for="menu_col">Slug Kategori / Koleksi</flux:label>
                        <flux:input id="menu_col" wire:model="collection_slug" placeholder="Contoh: berita atau pengumuman" />
                        <flux:error name="collection_slug" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label for="menu_criteria">Tag Kode Kriteria SINOVA (Opsional)</flux:label>
                    <flux:input id="menu_criteria" wire:model="criteria_code" placeholder="Contoh: 1.a.2.2" />
                </flux:field>

                <div class="flex flex-wrap gap-4 text-xs">
                    <label class="inline-flex items-center gap-2 font-medium text-ink">
                        <input type="checkbox" wire:model="is_visible" class="size-4 rounded border-hairline text-primary">
                        <span>Tampilkan di website</span>
                    </label>
                    <label class="inline-flex items-center gap-2 font-medium text-ink">
                        <input type="checkbox" wire:model="open_in_new_tab" class="size-4 rounded border-hairline text-primary">
                        <span>Buka di tab baru</span>
                    </label>
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Perbarui Menu' : 'Tambah Menu' }}
                </flux:button>
            </form>
        </div>

        <!-- Menu Tree -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-3">
                <div>
                    <h2 class="font-serif text-lg font-bold text-ink">Struktur Menu Aktif</h2>
                    @if ($locationFilter === 'header')
                        @php $visibleRoots = $rootMenus->where('is_visible', true)->count(); @endphp
                        <p class="mt-0.5 font-mono text-2xs {{ $visibleRoots > 6 ? 'font-bold text-red-700' : 'text-ink-muted' }}">
                            Menu Utama Header: {{ $visibleRoots }} / 6 slot SINOVA (maksimal 6 grup teratas, kedalaman 3 tingkat).
                        </p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach (['header' => 'Header', 'footer' => 'Footer', 'shortcut' => 'Shortcut'] as $loc => $lbl)
                        <button
                            type="button"
                            wire:click="$set('locationFilter', '{{ $loc }}')"
                            aria-pressed="{{ $locationFilter === $loc ? 'true' : 'false' }}"
                            @class([
                                'min-h-11 cursor-pointer rounded px-3 text-xs font-semibold',
                                'bg-primary text-white' => $locationFilter === $loc,
                                'border border-hairline bg-mist text-ink' => $locationFilter !== $loc,
                            ])
                        >
                            {{ $lbl }}
                        </button>
                    @endforeach
                    <a href="{{ route('admin.sliders') }}" class="inline-flex min-h-11 items-center rounded border border-primary/40 bg-primary-soft px-3 text-xs font-semibold text-primary hover:bg-primary hover:text-white">
                        Slider Beranda &rarr;
                    </a>
                </div>
            </div>

            @if ($rootMenus->isEmpty())
                <p class="py-8 text-center text-sm text-ink-muted">Belum ada menu untuk lokasi ini.</p>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($rootMenus as $menu)
                        <li wire:key="menu-{{ $menu->id }}" class="rounded-lg border border-hairline bg-mist p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="text-sm font-bold text-ink">{{ $menu->title }}</span>
                                    <span class="ml-2 font-mono text-2xs text-ink-muted">{{ $menu->resolvedUrl() }}</span>
                                    @unless ($menu->is_visible)
                                        <span class="ml-2 rounded bg-mist px-2 py-0.5 text-2xs text-ink-muted">Nonaktif</span>
                                    @endunless
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                    <button type="button" wire:click="moveUp({{ $menu->id }})" aria-label="Naikkan menu {{ $menu->title }}" class="min-h-11 cursor-pointer rounded border border-hairline bg-white px-2.5">Naik</button>
                                    <button type="button" wire:click="moveDown({{ $menu->id }})" aria-label="Turunkan menu {{ $menu->title }}" class="min-h-11 cursor-pointer rounded border border-hairline bg-white px-2.5">Turun</button>
                                    <button type="button" wire:click="toggleVisible({{ $menu->id }})" aria-label="{{ $menu->is_visible ? 'Sembunyikan' : 'Tampilkan' }} menu {{ $menu->title }}" class="min-h-11 cursor-pointer rounded border border-hairline bg-white px-2.5">{{ $menu->is_visible ? 'Sembunyi' : 'Tampil' }}</button>
                                    <button type="button" wire:click="edit({{ $menu->id }})" aria-label="Edit menu {{ $menu->title }}" class="min-h-11 cursor-pointer rounded border border-primary bg-white px-2.5 font-semibold text-primary">Edit</button>
                                    <button type="button" wire:click="delete({{ $menu->id }})" wire:confirm="Hapus menu ini beserta submenunya?" aria-label="Hapus menu {{ $menu->title }}" class="min-h-11 cursor-pointer rounded border border-red-200 bg-white px-2.5 text-red-700">Hapus</button>
                                </div>
                            </div>

                            @if ($menu->children->isNotEmpty())
                                <ul class="mt-2 space-y-1.5 border-s-2 border-primary/30 ps-3">
                                    @foreach ($menu->children as $child)
                                        <li wire:key="menu-child-{{ $child->id }}" class="space-y-1.5 rounded border border-hairline bg-white px-3 py-2 text-xs">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <span class="font-semibold text-ink">{{ $child->title }}</span>
                                                    <span class="ml-2 font-mono text-2xs text-ink-muted">{{ $child->resolvedUrl() }}</span>
                                                    @if ($child->criteria_code)
                                                        <span class="ml-2 rounded bg-primary-soft px-2 py-0.5 font-mono text-2xs text-primary">{{ $child->criteria_code }}</span>
                                                    @endif
                                                    @unless ($child->is_visible)
                                                        <span class="ml-2 rounded bg-mist px-2 py-0.5 text-2xs text-ink-muted">Nonaktif</span>
                                                    @endunless
                                                </div>
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <button type="button" wire:click="moveUp({{ $child->id }})" aria-label="Naikkan submenu {{ $child->title }}" class="min-h-11 cursor-pointer rounded border border-hairline px-2.5">Naik</button>
                                                    <button type="button" wire:click="moveDown({{ $child->id }})" aria-label="Turunkan submenu {{ $child->title }}" class="min-h-11 cursor-pointer rounded border border-hairline px-2.5">Turun</button>
                                                    <button type="button" wire:click="toggleVisible({{ $child->id }})" aria-label="{{ $child->is_visible ? 'Sembunyikan' : 'Tampilkan' }} submenu {{ $child->title }}" class="min-h-11 cursor-pointer rounded border border-hairline px-2.5">{{ $child->is_visible ? 'Sembunyi' : 'Tampil' }}</button>
                                                    <button type="button" wire:click="edit({{ $child->id }})" aria-label="Edit submenu {{ $child->title }}" class="min-h-11 cursor-pointer rounded border border-primary px-2.5 font-semibold text-primary">Edit</button>
                                                    <button type="button" wire:click="delete({{ $child->id }})" wire:confirm="Hapus submenu ini?" aria-label="Hapus submenu {{ $child->title }}" class="min-h-11 cursor-pointer rounded border border-red-200 px-2.5 text-red-700">Hapus</button>
                                                </div>
                                            </div>

                                            @if ($child->children->isNotEmpty())
                                                <ul class="space-y-1 border-s-2 border-hairline ps-3">
                                                    @foreach ($child->children as $grandchild)
                                                        <li wire:key="menu-grand-{{ $grandchild->id }}" class="flex flex-wrap items-center justify-between gap-2 rounded border border-hairline bg-mist px-2.5 py-1.5 text-xs">
                                                            <div>
                                                                <span class="font-medium text-ink">{{ $grandchild->title }}</span>
                                                                <span class="ml-2 font-mono text-2xs text-ink-muted">{{ $grandchild->resolvedUrl() }}</span>
                                                                @unless ($grandchild->is_visible)
                                                                    <span class="ml-2 rounded bg-mist px-2 py-0.5 text-2xs text-ink-muted">Nonaktif</span>
                                                                @endunless
                                                            </div>
                                                            <div class="flex flex-wrap items-center gap-1">
                                                                <button type="button" wire:click="moveUp({{ $grandchild->id }})" aria-label="Naikkan sub-submenu {{ $grandchild->title }}" class="min-h-11 cursor-pointer rounded border border-hairline bg-white px-2.5">Naik</button>
                                                                <button type="button" wire:click="moveDown({{ $grandchild->id }})" aria-label="Turunkan sub-submenu {{ $grandchild->title }}" class="min-h-11 cursor-pointer rounded border border-hairline bg-white px-2.5">Turun</button>
                                                                <button type="button" wire:click="toggleVisible({{ $grandchild->id }})" aria-label="{{ $grandchild->is_visible ? 'Sembunyikan' : 'Tampilkan' }} sub-submenu {{ $grandchild->title }}" class="min-h-11 cursor-pointer rounded border border-hairline bg-white px-2.5">{{ $grandchild->is_visible ? 'Sembunyi' : 'Tampil' }}</button>
                                                                <button type="button" wire:click="edit({{ $grandchild->id }})" aria-label="Edit sub-submenu {{ $grandchild->title }}" class="min-h-11 cursor-pointer rounded border border-primary bg-white px-2.5 font-semibold text-primary">Edit</button>
                                                                <button type="button" wire:click="delete({{ $grandchild->id }})" wire:confirm="Hapus sub-submenu ini?" aria-label="Hapus sub-submenu {{ $grandchild->title }}" class="min-h-11 cursor-pointer rounded border border-red-200 bg-white px-2.5 text-red-700">Hapus</button>
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
    </div>
</div>
