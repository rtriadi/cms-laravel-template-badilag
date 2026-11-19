@php
    $satker = \App\Models\SatkerSetting::current();
    $user = auth()->user();
    $tingkatLabel = $satker?->tingkat === 'banding' ? 'Tingkat Banding' : 'Tingkat Pertama';

    $commands = collect([
        ['label' => 'Dashboard Kepatuhan', 'url' => route('admin.dashboard'), 'show' => true],
        ['label' => 'Link Kriteria SINOVA', 'url' => route('admin.sinova-links'), 'show' => $user?->can('manage-sinova-links') || $user?->hasRole('super_admin')],
        ['label' => 'Menu Navigasi', 'url' => route('admin.menus'), 'show' => $user?->can('manage-menus') || $user?->hasRole('super_admin')],
        ['label' => 'Halaman & Blok', 'url' => route('admin.pages'), 'show' => $user?->can('manage-pages') || $user?->hasRole('super_admin')],
        ['label' => 'Berita & Publikasi', 'url' => route('admin.posts'), 'show' => $user?->can('manage-ppid-humas') || $user?->hasRole('super_admin') || $user?->hasRole('admin_konten')],
        ['label' => 'Dokumen SAKIP & Keuangan', 'url' => route('admin.documents'), 'show' => $user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasRole('super_admin')],
        ['label' => 'Kepaniteraan & Pegawai', 'url' => route('admin.kepaniteraan'), 'show' => $user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasRole('super_admin')],
        ['label' => 'Slider Beranda', 'url' => route('admin.sliders'), 'show' => $user?->can('manage-pages') || $user?->hasRole('super_admin')],
        ['label' => 'Pengaturan Satker', 'url' => route('admin.settings'), 'show' => $user?->can('manage-satker-settings') || $user?->hasRole('super_admin')],
        ['label' => 'Manajemen Kriteria', 'url' => route('admin.criteria'), 'show' => $user?->can('manage-criteria') || $user?->hasRole('super_admin')],
        ['label' => 'Pengguna & Peran (RBAC)', 'url' => route('admin.users'), 'show' => $user?->can('manage-users') || $user?->hasRole('super_admin')],
        ['label' => 'Audit Tautan & Log', 'url' => route('admin.audit'), 'show' => $user?->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal'])],
        ['label' => 'Keamanan 2FA', 'url' => route('admin.two-factor.setup'), 'show' => true],
    ])->filter(fn (array $item): bool => (bool) $item['show'])->values();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Admin' }} - {{ $satker?->nama_satker ?? 'SatkerCMS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body
    class="min-h-screen bg-mist font-sans text-ink antialiased"
    x-data="{
        cmdOpen: false,
        cmdQuery: '',
        items: @js($commands),
        get filteredItems() {
            const q = this.cmdQuery.trim().toLowerCase();
            if (! q) {
                return this.items;
            }
            return this.items.filter(i => i.label.toLowerCase().includes(q));
        },
        openCmd() {
            this.cmdOpen = true;
            this.cmdQuery = '';
            this.$nextTick(() => this.$refs.cmdInput?.focus());
        }
    }"
    x-on:keydown.window="if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openCmd(); } else if (event.key === 'Escape' && cmdOpen) { cmdOpen = false; }"
>
    <div class="min-h-screen lg:flex">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-hairline bg-white lg:w-64">
            <flux:sidebar.header class="flex flex-col items-stretch gap-2 border-b border-hairline pb-3">
                <div class="flex items-center justify-between gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="font-serif text-lg font-bold text-primary-deep">
                        SatkerCMS
                    </a>
                    <flux:sidebar.collapse class="hidden lg:inline-flex" />
                    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
                </div>
                <div class="space-y-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                    <p class="truncate text-xs font-semibold text-ink">
                        {{ $satker?->nama_satker ?? 'Satuan Kerja' }}
                    </p>
                    <span class="inline-flex items-center rounded border border-emerald-200 bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-medium text-primary">
                        {{ $tingkatLabel }}
                    </span>
                </div>
            </flux:sidebar.header>

            <flux:sidebar.nav class="flex-1 space-y-4 overflow-y-auto py-2">
                <flux:sidebar.group heading="Utama">
                    <flux:sidebar.item
                        icon="squares-2x2"
                        href="{{ route('admin.dashboard') }}"
                        :current="request()->routeIs('admin.dashboard')"
                        class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                    >
                        Dashboard Kepatuhan
                    </flux:sidebar.item>

                    @if ($user?->can('manage-sinova-links') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="link"
                            href="{{ route('admin.sinova-links') }}"
                            :current="request()->routeIs('admin.sinova-links')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Link Kriteria SINOVA
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Konten Website">
                    @if ($user?->can('manage-menus') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="bars-3-bottom-left"
                            href="{{ route('admin.menus') }}"
                            :current="request()->routeIs('admin.menus')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Menu Navigasi
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-pages') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="document-duplicate"
                            href="{{ route('admin.pages') }}"
                            :current="request()->routeIs('admin.pages')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Halaman &amp; Blok
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="photo"
                            href="{{ route('admin.sliders') }}"
                            :current="request()->routeIs('admin.sliders')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Slider Beranda
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="newspaper"
                            href="{{ route('admin.posts') }}"
                            :current="request()->routeIs('admin.posts')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Berita &amp; Publikasi
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="folder-open"
                            href="{{ route('admin.documents') }}"
                            :current="request()->routeIs('admin.documents')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Dokumen &amp; SAKIP
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="scale"
                            href="{{ route('admin.kepaniteraan') }}"
                            :current="request()->routeIs('admin.kepaniteraan')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Kepaniteraan &amp; Pegawai
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Sistem &amp; Keamanan">
                    @if ($user?->can('manage-satker-settings') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="building-library"
                            href="{{ route('admin.settings') }}"
                            :current="request()->routeIs('admin.settings')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Pengaturan Satker
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-criteria') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="clipboard-document-check"
                            href="{{ route('admin.criteria') }}"
                            :current="request()->routeIs('admin.criteria')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Manajemen Kriteria
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-users') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="users"
                            href="{{ route('admin.users') }}"
                            :current="request()->routeIs('admin.users')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Pengguna &amp; Peran
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal']))
                        <flux:sidebar.item
                            icon="shield-check"
                            href="{{ route('admin.audit') }}"
                            :current="request()->routeIs('admin.audit')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Audit &amp; Redirect 301
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item
                        icon="key"
                        href="{{ route('admin.two-factor.setup') }}"
                        :current="request()->routeIs('admin.two-factor.setup')"
                        class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                    >
                        Keamanan 2FA
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <div class="mt-auto border-t border-hairline pt-3 in-data-flux-sidebar-collapsed-desktop:hidden">
                <div class="rounded-md border border-hairline bg-mist p-3">
                    <p class="truncate text-xs font-semibold text-ink">{{ $user?->name }}</p>
                    <p @class([
                        'mt-0.5 font-mono text-[11px]',
                        'text-emerald-700' => $user?->two_factor_confirmed_at !== null,
                        'text-amber-700' => $user?->two_factor_confirmed_at === null,
                    ])>
                        {{ $user?->two_factor_confirmed_at !== null ? '2FA TOTP Aktif' : '2FA Belum Aktif' }}
                    </p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="inline-flex min-h-9 w-full cursor-pointer items-center justify-center rounded border border-hairline bg-white px-2 text-xs font-semibold text-ink hover:bg-gray-50">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </flux:sidebar>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-3 border-b border-hairline bg-white px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        data-flux-sidebar-toggle
                        aria-label="Buka atau tutup menu navigasi admin"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-md border border-hairline bg-white px-3 text-sm font-semibold text-ink lg:hidden"
                        x-on:click="$dispatch('flux-sidebar-toggle')"
                    >
                        <span>Menu</span>
                    </button>
                    <h1 class="font-serif text-lg font-bold text-ink sm:text-xl">
                        {{ $title ?? 'Dashboard Kepatuhan' }}
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="openCmd()"
                        aria-label="Buka pencarian cepat perintah admin"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-md border border-hairline bg-mist px-3 text-xs font-medium text-ink-muted hover:text-ink"
                    >
                        <span>Cari cepat...</span>
                        <kbd class="hidden rounded border border-hairline bg-white px-1.5 py-0.5 font-mono text-[11px] text-ink sm:inline">Ctrl+K</kbd>
                    </button>

                    <a
                        href="{{ route('home') }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex min-h-11 items-center rounded-md border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary-soft"
                    >
                        Lihat Website
                    </a>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Command Palette (Ctrl+K) -->
    <div
        x-cloak
        x-show="cmdOpen"
        role="dialog"
        aria-modal="true"
        aria-label="Navigasi Cepat Admin"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 pt-20"
        x-on:click.self="cmdOpen = false"
    >
        <div class="w-full max-w-lg rounded-xl border border-hairline bg-white p-4 shadow-lg">
            <div class="flex items-center gap-2 border-b border-hairline pb-3">
                <label for="admin-cmd-input" class="sr-only">Cari menu admin</label>
                <input
                    id="admin-cmd-input"
                    x-ref="cmdInput"
                    x-model="cmdQuery"
                    type="text"
                    placeholder="Ketik nama menu admin..."
                    class="min-h-11 w-full rounded-md border border-hairline px-3 text-sm text-ink focus:border-primary focus:outline-none"
                >
                <button
                    type="button"
                    x-on:click="cmdOpen = false"
                    class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-hairline px-3 text-xs font-semibold text-ink-muted hover:text-ink"
                >
                    Esc
                </button>
            </div>
            <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto">
                <template x-for="item in filteredItems" :key="item.url">
                    <li>
                        <a
                            :href="item.url"
                            class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm font-medium text-ink hover:bg-primary-soft hover:text-primary"
                            x-text="item.label"
                        ></a>
                    </li>
                </template>
                <li x-show="filteredItems.length === 0" class="px-3 py-4 text-center text-sm text-ink-muted">
                    Tidak ada menu yang cocok dengan kata kunci tersebut.
                </li>
            </ul>
        </div>
    </div>

    @fluxScripts
</body>
</html>
