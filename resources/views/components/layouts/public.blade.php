@props([
    'title' => null,
    'description' => null,
])
@php
    $satker = \App\Models\SatkerSetting::current();
    $activeTingkat = $satker?->tingkat ?? 'pertama';
    $satkerName = $satker?->nama_satker ?: 'Satuan Kerja Peradilan Agama';
    $tingkatLabel = config("satker.tingkat.{$activeTingkat}", 'Tingkat Pertama');

    $cachedMenus = \App\Models\MenuItem::cachedPublicMenus($activeTingkat);
    $headerMenus = $cachedMenus['header'];
    $footerMenus = $cachedMenus['footer'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?: 'Portal Informasi Resmi ' . $satkerName }}">
    <title>{{ $title ? $title . ' - ' . $satkerName : $satkerName }}</title>
    <script>
        try {
            const rawA11y = localStorage.getItem('satkercms_a11y_v1');
            if (rawA11y) {
                const s = JSON.parse(rawA11y);
                const scale = Math.min(200, Math.max(100, Number(s.scale) || 100));
                document.documentElement.style.fontSize = scale + '%';
                if (s.hc) document.documentElement.classList.add('a11y-hc');
                if (s.dyslexia) document.documentElement.classList.add('a11y-dyslexia');
                if (s.links) document.documentElement.classList.add('a11y-highlight-links');
                if (s.motion) document.documentElement.classList.add('a11y-no-motion');
            }
        } catch (e) {}
    </script>
    @vite(['resources/css/public.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-mist font-sans text-ink antialiased">
    <a href="#konten-utama" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    @auth
        <div class="border-b border-emerald-800 bg-ink text-xs text-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-1.5 sm:px-6 lg:px-8">
                <span class="font-semibold text-amber-300">Mode Pengelola Aktif ({{ auth()->user()?->name }})</span>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="font-semibold hover:underline">Dashboard Admin</a>
                    <a href="{{ route('admin.pages') }}" class="font-semibold hover:underline">Kelola Halaman</a>
                    <a href="{{ route('admin.posts') }}" class="font-semibold hover:underline">Tulis Berita / Artikel</a>
                    <a href="{{ route('admin.documents') }}" class="font-semibold hover:underline">Unggah Dokumen PDF</a>
                </div>
            </div>
        </div>
    @endauth

    <!-- Utility Top Bar -->
    <div class="bg-primary-deep text-xs text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-4">
                <span>Portal Resmi Mahkamah Agung RI</span>
                @if ($satker?->telepon)
                    <span class="hidden sm:inline">Telp: {{ $satker->telepon }}</span>
                @endif
                @if ($satker?->email)
                    <span class="hidden md:inline">Surel: {{ $satker->email }}</span>
                @endif
            </div>
            <nav aria-label="Tautan Kepatuhan Cepat" class="flex flex-wrap items-center gap-4">
                <a href="{{ route('public.accessibility') }}" class="underline-offset-4 hover:underline">Aksesibilitas</a>
                <a href="{{ route('public.sitemap') }}" class="underline-offset-4 hover:underline">Peta Situs</a>
                <a href="{{ route('public.system-status') }}" class="underline-offset-4 hover:underline">Status Sistem</a>
            </nav>
        </div>
    </div>

    <!-- 4px Institutional Gold Ribbon -->
    <div class="h-1 w-full bg-gold" aria-hidden="true"></div>

    <!-- Main Identity & Search Bar -->
    <header class="border-b border-hairline bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3.5">
                @if ($satker?->logo_path)
                    <img
                        src="{{ asset('storage/' . $satker->logo_path) }}"
                        alt="Logo Resmi {{ $satkerName }}"
                        width="52"
                        height="52"
                        class="h-13 w-13 shrink-0 rounded-lg border border-hairline bg-white object-contain p-1 shadow-2xs"
                    />
                @else
                    <div aria-hidden="true" class="relative flex h-13 w-13 shrink-0 items-center justify-center rounded-xl border-2 border-gold bg-primary-deep text-gold shadow-2xs">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v18" />
                            <path d="M5 7h14" />
                            <path d="M8 21h8" />
                            <path d="M5 7l-3 6h6l-3-6z" />
                            <path d="M19 7l-3 6h6l-3-6z" />
                        </svg>
                    </div>
                @endif

                <div class="min-w-0">
                    <p class="font-serif text-lg leading-tight font-bold text-ink sm:text-xl">
                        {{ $satkerName }}
                    </p>
                    <p class="mt-0.5 text-xs font-medium text-ink-muted">
                        {{ $tingkatLabel }}
                        @if ($satker?->kode_satker)
                            | Kode Satker: <span class="font-mono">{{ $satker->kode_satker }}</span>
                        @endif
                    </p>
                </div>
            </a>

            <div class="flex flex-1 items-center justify-end gap-2 sm:flex-none">
                <form action="{{ route('public.search') }}" method="GET" role="search" class="flex w-full max-w-xs items-center gap-1.5 sm:w-auto">
                    <label for="header-search-input" class="sr-only">Cari informasi dan dokumen di portal</label>
                    <input
                        id="header-search-input"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari informasi atau dokumen..."
                        class="min-h-11 w-full rounded-md border border-hairline bg-mist px-3 text-xs text-ink placeholder:text-ink-muted sm:w-56"
                    />
                    <button type="submit" class="min-h-11 shrink-0 cursor-pointer rounded-md bg-primary px-3.5 text-xs font-semibold text-white hover:bg-primary-hover">
                        Cari
                    </button>
                </form>

                <button
                    id="mobile-menu-open"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobile-nav-drawer"
                    class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-1.5 rounded-md border border-hairline bg-white px-3.5 text-xs font-bold text-ink hover:bg-mist lg:hidden"
                >
                    <span>Menu</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Primary 6-Menu Sticky Navigation Bar (Desktop) -->
    <nav aria-label="Navigasi Utama Website" class="sticky top-0 z-40 hidden border-b border-primary-hover bg-primary text-white shadow-xs lg:block">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <ul class="flex flex-wrap items-center gap-1">
                @foreach ($headerMenus as $topMenu)
                    @php
                        $hasChildren = $topMenu->children->isNotEmpty();
                        $menuHref = $topMenu->resolvedUrl();
                    @endphp
                    <li class="relative" data-nav-group>
                        @if ($hasChildren)
                            <button
                                type="button"
                                aria-expanded="false"
                                data-nav-trigger
                                class="inline-flex min-h-12 cursor-pointer items-center gap-1.5 px-3.5 text-sm font-semibold text-white hover:bg-primary-hover"
                            >
                                <span>{{ $topMenu->title }}</span>
                                <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div
                                data-nav-submenu
                                hidden
                                class="absolute top-full left-0 z-30 max-h-[75vh] w-72 overflow-y-auto rounded-b-lg border border-hairline bg-white py-1.5 text-ink shadow-xl"
                            >
                                @foreach ($topMenu->children as $childMenu)
                                    @if ($childMenu->type === 'group')
                                        <p class="px-4 py-2 text-xs font-bold text-primary-deep uppercase">
                                            {{ $childMenu->title }}
                                        </p>
                                    @else
                                        <a
                                            href="{{ $childMenu->resolvedUrl() }}"
                                            @if ($childMenu->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                            class="flex min-h-11 items-center px-4 py-2 text-xs font-medium text-ink hover:bg-primary-soft hover:text-primary"
                                        >
                                            {{ $childMenu->title }}
                                        </a>
                                    @endif

                                    @if ($childMenu->children->isNotEmpty())
                                        <ul class="border-s-2 border-hairline ps-3 ml-4 mb-1">
                                            @foreach ($childMenu->children as $grandChild)
                                                <li>
                                                    <a
                                                        href="{{ $grandChild->resolvedUrl() }}"
                                                        @if ($grandChild->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                                        class="flex min-h-11 items-center px-3 py-1.5 text-xs text-ink-body hover:bg-primary-soft hover:text-primary"
                                                    >
                                                        {{ $grandChild->title }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <a
                                href="{{ $menuHref }}"
                                @if ($topMenu->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                class="inline-flex min-h-12 items-center px-3.5 text-sm font-semibold text-white hover:bg-primary-hover"
                            >
                                {{ $topMenu->title }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div
        id="mobile-nav-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="Menu Navigasi Utama"
        hidden
        class="fixed inset-0 z-50 flex justify-end bg-black/50 lg:hidden"
    >
        <div class="flex h-full w-full max-w-sm flex-col overflow-y-auto bg-white p-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <span class="font-serif text-base font-bold text-primary-deep">Menu Navigasi</span>
                <button
                    id="mobile-menu-close"
                    type="button"
                    class="min-h-11 cursor-pointer rounded-md border border-hairline px-3.5 text-xs font-semibold text-ink hover:bg-mist"
                >
                    Tutup
                </button>
            </div>

            <nav aria-label="Navigasi Seluler" class="mt-4 space-y-4">
                @foreach ($headerMenus as $topMenu)
                    <div class="border-b border-hairline pb-3">
                        @if ($topMenu->children->isNotEmpty())
                            <p class="font-serif text-sm font-bold text-primary-deep">{{ $topMenu->title }}</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($topMenu->children as $childMenu)
                                    <li>
                                        @if ($childMenu->type === 'group')
                                            <span class="block px-2 py-2 text-xs font-bold text-ink-muted uppercase">{{ $childMenu->title }}</span>
                                        @else
                                            <a
                                                href="{{ $childMenu->resolvedUrl() }}"
                                                @if ($childMenu->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                                class="flex min-h-11 items-center rounded px-2 text-xs font-medium text-ink hover:bg-primary-soft hover:text-primary"
                                            >
                                                {{ $childMenu->title }}
                                            </a>
                                        @endif

                                        @if ($childMenu->children->isNotEmpty())
                                            <ul class="border-s-2 border-hairline ps-3 ml-2 space-y-1">
                                                @foreach ($childMenu->children as $grandChild)
                                                    <li>
                                                        <a
                                                            href="{{ $grandChild->resolvedUrl() }}"
                                                            @if ($grandChild->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                                            class="flex min-h-11 items-center rounded px-2 text-xs text-ink-body hover:bg-primary-soft hover:text-primary"
                                                        >
                                                            {{ $grandChild->title }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <a
                                href="{{ $topMenu->resolvedUrl() }}"
                                @if ($topMenu->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                class="flex min-h-11 items-center font-serif text-sm font-bold text-primary-deep hover:text-primary"
                            >
                                {{ $topMenu->title }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </nav>
        </div>
    </div>

    <main id="konten-utama" tabindex="-1" class="flex-1">
        {{ $slot }}
    </main>

    <!-- Civic Footer (3 Columns) -->
    <footer class="mt-12 border-t-4 border-gold bg-primary-deep text-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <h2 class="font-serif text-lg font-bold text-white">{{ $satkerName }}</h2>
                <p class="mt-2 text-xs leading-relaxed text-emerald-100">
                    {{ $satker?->formattedAddress() ?: 'Alamat kantor satuan kerja belum dikonfigurasi.' }}
                </p>
                <dl class="mt-3 space-y-1 text-xs text-emerald-100">
                    @if ($satker?->telepon)
                        <div><dt class="inline font-semibold">Telepon:</dt> <dd class="inline">{{ $satker->telepon }}</dd></div>
                    @endif
                    @if ($satker?->email)
                        <div><dt class="inline font-semibold">Surel:</dt> <dd class="inline">{{ $satker->email }}</dd></div>
                    @endif
                    <div><dt class="inline font-semibold">Jam Kerja:</dt> <dd class="inline">Senin - Kamis 08.00-16.30 | Jumat 08.00-17.00</dd></div>
                </dl>
            </div>

            <div>
                <h2 class="font-serif text-base font-bold text-white">Informasi &amp; Layanan Portal</h2>
                <ul class="mt-3 space-y-2 text-xs text-emerald-100">
                    <li><a href="{{ route('public.accessibility') }}" class="hover:underline">Pernyataan &amp; Panduan Aksesibilitas</a></li>
                    <li><a href="{{ route('public.sitemap') }}" class="hover:underline">Peta Situs Portal</a></li>
                    <li><a href="{{ route('public.visitor-stats') }}" class="hover:underline">Statistik Pengunjung</a></li>
                    <li><a href="{{ route('public.system-status') }}" class="hover:underline">Status Keamanan &amp; Versi Sistem</a></li>
                    <li><a href="{{ route('public.search') }}" class="hover:underline">Pencarian Informasi Publik</a></li>
                    @foreach ($footerMenus as $fMenu)
                        <li>
                            <a
                                href="{{ $fMenu->resolvedUrl() }}"
                                @if ($fMenu->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                class="hover:underline"
                            >
                                {{ $fMenu->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="font-serif text-base font-bold text-white">Portal Layanan Nasional &amp; MARI</h2>
                <ul class="mt-3 space-y-2 text-xs text-emerald-100">
                    <li>
                        <a href="https://www.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Mahkamah Agung Republik Indonesia
                        </a>
                    </li>
                    <li>
                        <a href="https://badilag.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Direktorat Jenderal Badilag MARI
                        </a>
                    </li>
                    <li>
                        <a href="{{ $satker?->sipp_url ?: 'https://sipp.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Penelusuran Perkara (SIPP)
                        </a>
                    </li>
                    <li>
                        <a href="{{ $satker?->ecourt_url ?: 'https://ecourt.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Layanan Elektronik (e-Court MARI)
                        </a>
                    </li>
                    <li>
                        <a href="{{ $satker?->direktori_putusan_url ?: 'https://putusan3.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Direktori Putusan Mahkamah Agung RI
                        </a>
                    </li>
                    <li>
                        <a href="{{ $satker?->siwas_url ?: 'https://siwas.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Pengawasan &amp; Pengaduan (SIWAS MARI)
                        </a>
                    </li>
                    <li>
                        <a href="{{ $satker?->skm_url ?: route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']) }}" @if($satker?->skm_url) target="_blank" rel="noopener noreferrer" @endif class="hover:underline">
                            Survei Kepuasan &amp; Anti Korupsi (SI SURTI / SKM &amp; SPAK)
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-emerald-900 bg-black/20">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-3 text-[11px] text-emerald-100 sm:px-6 lg:px-8">
                <p>Hak Cipta &copy; {{ date('Y') }} {{ $satkerName }}.</p>
                <p class="font-mono">SatkerCMS v{{ config('satker.cms_version', '1.0.0-dev') }}</p>
            </div>
        </div>
    </footer>

    <x-accessibility-widget />

    <script>
        (() => {
            const navGroups = document.querySelectorAll('[data-nav-group]');
            const closeAllSubmenus = () => {
                navGroups.forEach((group) => {
                    const btn = group.querySelector('[data-nav-trigger]');
                    const submenu = group.querySelector('[data-nav-submenu]');
                    if (btn && submenu) {
                        btn.setAttribute('aria-expanded', 'false');
                        submenu.hidden = true;
                    }
                });
            };

            navGroups.forEach((group) => {
                const btn = group.querySelector('[data-nav-trigger]');
                const submenu = group.querySelector('[data-nav-submenu]');
                if (!btn || !submenu) {
                    return;
                }

                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const isCurrentlyOpen = !submenu.hidden;
                    closeAllSubmenus();
                    if (!isCurrentlyOpen) {
                        submenu.hidden = false;
                        btn.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-nav-group]')) {
                    closeAllSubmenus();
                }
            });

            const mobileOpen = document.getElementById('mobile-menu-open');
            const mobileClose = document.getElementById('mobile-menu-close');
            const mobileDrawer = document.getElementById('mobile-nav-drawer');

            const closeDrawer = (restoreFocus = false) => {
                if (!mobileDrawer || !mobileOpen) {
                    return;
                }
                mobileDrawer.hidden = true;
                mobileOpen.setAttribute('aria-expanded', 'false');
                if (restoreFocus) {
                    mobileOpen.focus();
                }
            };

            mobileOpen?.addEventListener('click', () => {
                if (!mobileDrawer) {
                    return;
                }
                mobileDrawer.hidden = false;
                mobileOpen.setAttribute('aria-expanded', 'true');
                mobileClose?.focus();
            });

            mobileClose?.addEventListener('click', () => closeDrawer(true));
            mobileDrawer?.addEventListener('click', (event) => {
                if (event.target === mobileDrawer) {
                    closeDrawer(true);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAllSubmenus();
                    if (mobileDrawer && !mobileDrawer.hidden) {
                        closeDrawer(true);
                    }
                }
            });

            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const revealItems = Array.from(document.querySelectorAll('.pub-reveal'));

            if (!prefersReducedMotion && !document.documentElement.classList.contains('a11y-no-motion') && 'IntersectionObserver' in window && revealItems.length > 0) {
                document.documentElement.classList.add('pub-motion-ready');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: '0px 0px -32px 0px', threshold: 0.05 });

                revealItems.forEach((el) => observer.observe(el));
            } else {
                revealItems.forEach((el) => el.classList.add('is-visible'));
            }

            // Instant Hover & Touch Link Prefetcher for Internal Public Routes
            const prefetched = new Set();
            const canPrefetch = (anchor) => {
                if (!anchor || !anchor.href || anchor.target === '_blank' || anchor.hasAttribute('download')) {
                    return false;
                }
                try {
                    const u = new URL(anchor.href, window.location.origin);
                    if (u.origin !== window.location.origin || u.href === window.location.href || u.hash) {
                        return false;
                    }
                    if (u.pathname.startsWith('/panel-admin') || u.pathname.startsWith('/setup') || u.pathname.startsWith('/storage')) {
                        return false;
                    }
                    return !prefetched.has(u.href);
                } catch (e) {
                    return false;
                }
            };
            const triggerPrefetch = (href) => {
                if (prefetched.has(href) || prefetched.size > 20) {
                    return;
                }
                prefetched.add(href);
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = href;
                link.as = 'document';
                document.head.appendChild(link);
            };
            let hoverTimer = null;
            document.addEventListener('mouseover', (e) => {
                const a = e.target.closest && e.target.closest('a[href]');
                if (!canPrefetch(a)) {
                    return;
                }
                clearTimeout(hoverTimer);
                hoverTimer = setTimeout(() => triggerPrefetch(a.href), 65);
            }, { passive: true });
            document.addEventListener('touchstart', (e) => {
                const a = e.target.closest && e.target.closest('a[href]');
                if (canPrefetch(a)) {
                    triggerPrefetch(a.href);
                }
            }, { passive: true });
        })();
    </script>
</body>
</html>
