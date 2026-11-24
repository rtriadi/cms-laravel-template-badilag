@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'prevUrl' => null,
    'nextUrl' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'ogImagePath' => null,
    'ogImageAlt' => null,
    'preloadImage' => null,
    'preloadImageSrcset' => null,
    'preloadImageSizes' => null,
    'robots' => 'index, follow',
    'publishedAt' => null,
    'modifiedAt' => null,
    'jsonLd' => [],
])
@php
    $satker = \App\Models\SatkerSetting::current();
    $activeTingkat = $satker?->tingkat ?? 'pertama';
    $satkerName = $satker?->nama_satker ?: 'Satuan Kerja Peradilan Agama';
    $tingkatLabel = config("satker.tingkat.{$activeTingkat}", 'Tingkat Pertama');

    $cachedMenus = \App\Models\MenuItem::cachedPublicMenus($activeTingkat);
    $headerMenus = $cachedMenus['header'];
    $footerMenus = $cachedMenus['footer'];

    $pageTitle = \App\Support\SeoText::pageTitle($title, $satkerName);
    $rawDescription = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($description ?? ''))));
    $metaDescription = $rawDescription !== ''
        ? \App\Support\SeoText::excerpt($rawDescription, 155)
        : \App\Support\SeoText::excerpt("Portal Informasi Resmi {$satkerName}. Layanan perkara SIPP, jadwal sidang harian, e-Court, Prodeo & Posbakum, serta transparansi peradilan.", 155);
    $canonicalUrl = $canonical ?: url()->current();

    $logoUrl = $satker?->logo_path ? asset('storage/' . $satker->logo_path) : null;
    $logoImgAttrs = $satker?->logo_path
        ? \App\Services\ImageOptimizerService::responsiveAttributes($satker->logo_path, [128], '52px', 128, 512)
        : null;
    $faviconUrl = $satker?->favicon_path
        ? asset('storage/' . $satker->favicon_path)
        : $logoUrl;

    $resolvedOgImagePath = $ogImagePath
        ?: ($ogImage === null ? ($satker?->foto_gedung_path ?: $satker?->logo_path) : null);
    $resolvedOgImage = $ogImage
        ?: ($satker?->foto_gedung_path ? asset('storage/' . $satker->foto_gedung_path) : $logoUrl);
    $resolvedOgImageDims = $resolvedOgImagePath
        ? \App\Services\ImageOptimizerService::imageDimensions($resolvedOgImagePath)
        : null;
    $resolvedOgImageAlt = trim((string) ($ogImageAlt ?? '')) !== ''
        ? trim((string) $ogImageAlt)
        : $pageTitle;

    $homeUrl = route('home');
    $orgId = $homeUrl . '#organization';
    $websiteId = $homeUrl . '#website';

    $serviceHours = config('satker.service_hours', []);
    $monThuOpen = (string) ($serviceHours['mon_thu_open'] ?? '08:00');
    $monThuClose = (string) ($serviceHours['mon_thu_close'] ?? '16:30');
    $friOpen = (string) ($serviceHours['fri_open'] ?? '08:00');
    $friClose = (string) ($serviceHours['fri_close'] ?? '17:00');
    $tzLabel = (string) ($serviceHours['timezone_label'] ?? 'WITA');
    $fmtJam = static fn (string $hhmm): string => str_replace(':', '.', $hhmm);

    $orgSchema = array_filter([
        '@type' => ['Courthouse', 'GovernmentOrganization'],
        '@id' => $orgId,
        'name' => $satkerName,
        'url' => $homeUrl,
        'logo' => $logoUrl,
        'image' => $satker?->foto_gedung_path ? asset('storage/' . $satker->foto_gedung_path) : $logoUrl,
        'telephone' => $satker?->telepon ?: null,
        'email' => $satker?->email ?: null,
        'parentOrganization' => [
            '@type' => 'GovernmentOrganization',
            'name' => 'Direktorat Jenderal Badan Peradilan Agama Mahkamah Agung RI',
            'url' => 'https://badilag.mahkamahagung.go.id',
        ],
        'address' => $satker ? array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $satker->alamat_jalan ?: null,
            'addressLocality' => $satker->kabupaten_kota ?: null,
            'addressRegion' => $satker->provinsi ?: null,
            'postalCode' => $satker->kode_pos ?: null,
            'addressCountry' => 'ID',
        ]) : null,
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                'opens' => $monThuOpen,
                'closes' => $monThuClose,
            ],
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Friday'],
                'opens' => $friOpen,
                'closes' => $friClose,
            ],
        ],
    ], static fn (mixed $v): bool => $v !== null && $v !== []);

    $websiteSchema = [
        '@type' => 'WebSite',
        '@id' => $websiteId,
        'url' => $homeUrl,
        'name' => $satkerName,
        'inLanguage' => 'id-ID',
        'publisher' => ['@id' => $orgId],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('public.search') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $graphNodes = [$orgSchema, $websiteSchema];
    if (is_array($jsonLd) && $jsonLd !== []) {
        foreach ($jsonLd as $node) {
            if (is_array($node) && $node !== []) {
                $graphNodes[] = $node;
            }
        }
    }

    $structuredDataJson = json_encode(
        [
            '@context' => 'https://schema.org',
            '@graph' => $graphNodes,
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    $safeEcourtUrl = \App\Support\SafeHtml::cleanUrl($satker?->ecourt_url) ?? route('public.page', ['slug' => 'layanan-hukum/ecourt']);
    $safeSippUrl = \App\Support\SafeHtml::cleanUrl($satker?->sipp_url) ?? route('public.page', ['slug' => 'layanan-hukum/sipp']);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <meta name="theme-color" content="#022c20">
    <title>{{ $pageTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($prevUrl)
        <link rel="prev" href="{{ $prevUrl }}">
    @endif
    @if ($nextUrl)
        <link rel="next" href="{{ $nextUrl }}">
    @endif
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @endif
    @if ($preloadImage)
        <link
            rel="preload"
            as="image"
            href="{{ $preloadImage }}"
            @if ($preloadImageSrcset) imagesrcset="{{ $preloadImageSrcset }}" @endif
            @if ($preloadImageSizes) imagesizes="{{ $preloadImageSizes }}" @endif
            fetchpriority="high"
        >
    @endif

    <!-- Open Graph / WhatsApp / Social Preview -->
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $satkerName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($resolvedOgImage)
        <meta property="og:image" content="{{ $resolvedOgImage }}">
        <meta property="og:image:alt" content="{{ $resolvedOgImageAlt }}">
        @if ($resolvedOgImageDims)
            <meta property="og:image:width" content="{{ $resolvedOgImageDims['width'] }}">
            <meta property="og:image:height" content="{{ $resolvedOgImageDims['height'] }}">
        @endif
    @endif
    @if ($ogType === 'article' && $publishedAt)
        <meta property="article:published_time" content="{{ $publishedAt }}">
    @endif
    @if ($ogType === 'article' && $modifiedAt)
        <meta property="article:modified_time" content="{{ $modifiedAt }}">
    @endif

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="{{ $resolvedOgImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if ($resolvedOgImage)
        <meta name="twitter:image" content="{{ $resolvedOgImage }}">
        <meta name="twitter:image:alt" content="{{ $resolvedOgImageAlt }}">
    @endif

    @if (is_string($structuredDataJson))
        <script type="application/ld+json">{!! $structuredDataJson !!}</script>
    @endif

    <script>try{const r=localStorage.getItem('satkercms_a11y_v1');if(r){const s=JSON.parse(r),d=document.documentElement;d.style.fontSize=Math.min(200,Math.max(100,Number(s.scale)||100))+'%';if(s.hc)d.classList.add('a11y-hc');if(s.dyslexia)d.classList.add('a11y-dyslexia');if(s.links)d.classList.add('a11y-highlight-links');if(s.motion)d.classList.add('a11y-no-motion')}}catch(e){}</script>
    @if (is_file(public_path('build/fonts-manifest.json')) || \Illuminate\Support\Facades\Vite::isRunningHot())
        {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @endif
    @vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body class="flex min-h-screen flex-col bg-ivory pb-20 font-sans text-ink antialiased lg:pb-0" @auth data-admin-prefix="{{ '/'.trim((string) config('satker.admin_path', 'panel-admin'), '/') }}" @endauth>
    <a href="#konten-utama" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Lewati ke konten utama
    </a>

    @auth
        <div class="border-b border-emerald-900 bg-ink text-xs text-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-1.5 sm:px-6 lg:px-8">
                <span class="font-semibold text-amber-300">Mode Pengelola Aktif ({{ auth()->user()?->name }})</span>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="font-semibold hover:underline">Dashboard Studio</a>
                    <a href="{{ route('admin.pages') }}" class="font-semibold hover:underline">Kelola Halaman</a>
                    <a href="{{ route('admin.posts') }}" class="font-semibold hover:underline">Tulis Publikasi</a>
                    <a href="{{ route('admin.comments') }}" class="font-semibold hover:underline">Moderasi Komentar</a>
                    <a href="{{ route('admin.documents') }}" class="font-semibold hover:underline">Dokumen PDF</a>
                </div>
            </div>
        </div>
    @endauth

    <!-- Obsidian Utility Bar -->
    <div class="bg-obsidian text-xs text-emerald-50">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3.5 whitespace-nowrap">
                <span class="shrink-0 font-semibold text-amber-300">
                    <span class="sm:hidden">MA RI &middot; Ditjen Badilag</span>
                    <span class="hidden sm:inline">Mahkamah Agung RI &middot; Ditjen Badilag</span>
                </span>
                @if ($satker?->telepon)
                    <span class="hidden shrink-0 text-emerald-100 md:inline">Telp: {{ $satker->telepon }}</span>
                @endif
                @if ($satker?->email)
                    <span class="hidden min-w-0 truncate text-emerald-100 lg:inline">Surel: {{ $satker->email }}</span>
                @endif
            </div>
            <nav aria-label="Tautan Kepatuhan Cepat" class="flex shrink-0 items-center gap-3.5 whitespace-nowrap">
                <a href="{{ route('public.accessibility') }}" class="font-semibold text-amber-300 underline-offset-4 transition-colors duration-150 hover:text-amber-200 hover:underline">Aksesibilitas</a>
                <a href="{{ route('public.sitemap') }}" class="font-medium text-emerald-50 underline-offset-4 transition-colors duration-150 hover:text-white hover:underline">Peta Situs</a>
                <a href="{{ route('public.visitor-stats') }}" class="hidden font-medium text-emerald-50 underline-offset-4 transition-colors duration-150 hover:text-white hover:underline lg:inline">Statistik Pengunjung</a>
                <a href="{{ route('public.system-status') }}" class="hidden font-medium text-emerald-50 underline-offset-4 transition-colors duration-150 hover:text-white hover:underline sm:inline">Status Sistem</a>
            </nav>
        </div>
    </div>

    <!-- 3px Institutional Gold Ribbon -->
    <div class="h-[3px] w-full bg-gold shadow-sm" aria-hidden="true"></div>

    <!-- Main Identity, Search Trigger & Action Header -->
    <header class="sticky top-0 z-30 border-b border-hairline bg-white/95 backdrop-blur-sm shadow-2xs lg:static lg:bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-2.5 px-4 py-3 sm:gap-4 sm:px-6 sm:py-3.5 lg:px-8">
            <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center gap-2.5 sm:gap-3.5">
                @if ($satker?->logo_path && $logoImgAttrs)
                    <img
                        src="{{ $logoImgAttrs['src'] }}"
                        @if ($logoImgAttrs['srcset']) srcset="{{ $logoImgAttrs['srcset'] }}" sizes="{{ $logoImgAttrs['sizes'] }}" @endif
                        alt="Logo Resmi {{ $satkerName }}"
                        width="48"
                        height="48"
                        decoding="async"
                        class="h-10 w-10 shrink-0 rounded-lg border border-hairline bg-white object-contain p-1 shadow-2xs sm:h-12 sm:w-12"
                    />
                @else
                    <div aria-hidden="true" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border-2 border-gold bg-obsidian text-gold shadow-2xs sm:h-12 sm:w-12">
                        <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v18" />
                            <path d="M5 7h14" />
                            <path d="M8 21h8" />
                            <path d="M5 7l-3 6h6l-3-6z" />
                            <path d="M19 7l-3 6h6l-3-6z" />
                        </svg>
                    </div>
                @endif

                <div class="min-w-0">
                    <p class="line-clamp-2 font-serif text-sm leading-snug font-bold text-ink sm:truncate sm:text-xl sm:leading-tight">
                        {{ $satkerName }}
                    </p>
                    <p class="mt-0.5 truncate text-[11px] font-medium text-ink-muted sm:text-xs">
                        {{ $tingkatLabel }}
                        @if ($satker?->kode_satker)
                            <span class="hidden sm:inline">&middot; Kode Satker: <span class="font-mono">{{ $satker->kode_satker }}</span></span>
                        @endif
                    </p>
                </div>
            </a>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <!-- Desktop Search Command Button (opens Ctrl+K Search Overlay) -->
                <button
                    type="button"
                    data-open-public-search
                    aria-expanded="false"
                    aria-controls="public-search-modal"
                    aria-label="Cari perkara, jadwal, atau dokumen"
                    class="pub-btn hidden min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-hairline bg-ivory px-3.5 text-xs font-medium text-ink-muted hover:border-primary hover:bg-primary-soft hover:text-ink sm:inline-flex"
                >
                    <svg class="size-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path stroke-linecap="round" d="m21 21-4.35-4.35"></path>
                    </svg>
                    <span class="hidden md:inline">Cari perkara, jadwal, dokumen...</span>
                    <span class="md:hidden">Cari</span>
                    <kbd class="hidden rounded border border-hairline bg-white px-1.5 py-0.5 font-mono text-2xs font-semibold text-ink lg:inline">Ctrl K</kbd>
                </button>

                <!-- Mobile Search Button (>=44x44px touch target) -->
                <button
                    type="button"
                    data-open-public-search
                    aria-expanded="false"
                    aria-controls="public-search-modal"
                    aria-label="Buka pencarian portal"
                    class="pub-btn inline-flex min-h-11 min-w-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-ivory text-xs font-bold text-ink hover:bg-primary-soft sm:hidden"
                >
                    <svg class="size-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path stroke-linecap="round" d="m21 21-4.35-4.35"></path>
                    </svg>
                    <span class="sr-only">Cari</span>
                </button>

                <!-- Desktop e-Court Action Pill -->
                <a
                    href="{{ $safeEcourtUrl }}"
                    @if (str_starts_with($safeEcourtUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif
                    class="pub-btn hidden min-h-11 shrink-0 items-center gap-1.5 rounded-lg bg-primary px-4 text-xs font-bold text-white shadow-2xs hover:bg-primary-hover lg:inline-flex"
                >
                    <span>e-Court MARI</span>
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"></path>
                    </svg>
                </a>

                <button
                    id="mobile-menu-open"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobile-nav-drawer"
                    class="pub-btn inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-hairline bg-white px-3.5 text-xs font-bold text-ink hover:bg-mist lg:hidden"
                >
                    <svg class="size-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <span>Menu</span>
                </button>
            </div>
        </div>
    </header>

    @php
        $currentPath = '/' . ltrim(request()->path(), '/');
        $isMenuActive = function (\App\Models\MenuItem $item) use (&$isMenuActive, $currentPath): bool {
            $url = $item->resolvedUrl();
            if ($url !== '#' && ! str_starts_with($url, 'http')) {
                $norm = '/' . ltrim((string) parse_url($url, PHP_URL_PATH), '/');
                if ($norm === '/' && request()->routeIs('home')) {
                    return true;
                }
                if ($norm !== '/' && str_starts_with($currentPath, $norm)) {
                    return true;
                }
            }
            foreach ($item->children as $child) {
                if ($isMenuActive($child)) {
                    return true;
                }
            }
            return false;
        };
    @endphp

    <!-- Primary 6-Menu Sticky Navigation Bar (Desktop) -->
    <nav id="desktop-primary-nav" aria-label="Navigasi Utama Website" class="sticky top-0 z-40 hidden border-b border-primary-hover bg-primary text-white shadow-xs lg:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 sm:px-6 lg:px-8">
            <ul class="flex flex-wrap items-center gap-1">
                @foreach ($headerMenus as $topMenu)
                    @php
                        $hasChildren = $topMenu->children->isNotEmpty();
                        $menuHref = $topMenu->resolvedUrl();
                        $isTopActive = $isMenuActive($topMenu);
                    @endphp
                    <li class="relative" data-nav-group>
                        @if ($hasChildren)
                            <button
                                type="button"
                                aria-expanded="false"
                                data-nav-trigger
                                @class([
                                    'pub-nav-link inline-flex min-h-12 cursor-pointer items-center gap-1.5 px-3.5 text-sm font-semibold hover:bg-primary-hover',
                                    'is-active bg-primary-deep text-amber-300' => $isTopActive,
                                    'text-white' => ! $isTopActive,
                                ])
                            >
                                <span>{{ $topMenu->title }}</span>
                                <svg class="pub-nav-chevron size-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
                                        <ul class="mb-1 ml-4 border-s-2 border-hairline ps-3">
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
                                @if ($isTopActive) aria-current="page" @endif
                                @if ($topMenu->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                @class([
                                    'pub-nav-link inline-flex min-h-12 items-center px-3.5 text-sm font-semibold hover:bg-primary-hover',
                                    'is-active bg-primary-deep text-amber-300' => $isTopActive,
                                    'text-white' => ! $isTopActive,
                                ])
                            >
                                {{ $topMenu->title }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            <a
                href="{{ route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']) }}"
                class="pub-btn hidden shrink-0 items-center rounded-md border border-gold/60 bg-obsidian px-3 py-1.5 font-mono text-2xs font-semibold text-amber-300 hover:bg-primary-deep xl:inline-flex"
            >
                PTSP &amp; Posbakum Terintegrasi
            </a>
        </div>
    </nav>

    <!-- Public Search Overlay Modal (Ctrl+K) -->
    <div
        id="public-search-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="public-search-modal-title"
        hidden
        class="fixed inset-0 z-50 flex items-start justify-center bg-obsidian/70 p-4 pt-16 backdrop-blur-xs sm:pt-24"
    >
        <div data-search-modal-panel class="w-full max-w-2xl overflow-hidden rounded-xl border border-hairline bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-hairline bg-ivory px-5 py-3.5">
                <h2 id="public-search-modal-title" class="font-serif text-base font-bold text-ink">
                    Pencarian Cepat Portal Layanan
                </h2>
                <button
                    id="public-search-close"
                    type="button"
                    class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                >
                    Tutup (Esc)
                </button>
            </div>
            <div class="p-5">
                <form action="{{ route('public.search') }}" method="GET" role="search" class="flex flex-col gap-2 sm:flex-row">
                    <label for="modal-search-input" class="sr-only">Kata kunci pencarian informasi dan dokumen</label>
                    <input
                        id="modal-search-input"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        minlength="2"
                        required
                        placeholder="Ketik judul layanan, jadwal sidang, LKJIP, DIPA, atau berita..."
                        class="min-h-11 flex-1 rounded-lg border border-hairline bg-mist px-4 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:bg-white focus:outline-none"
                    />
                    <button type="submit" class="pub-btn min-h-11 shrink-0 cursor-pointer rounded-lg bg-primary px-6 text-xs font-bold text-white hover:bg-primary-hover">
                        Cari Sekarang
                    </button>
                </form>

                <div class="mt-4 border-t border-hairline pt-4">
                    <p class="font-mono text-2xs font-bold text-ink-muted uppercase">Pintasan Pencari Keadilan</p>
                    <div class="mt-2.5 flex flex-wrap gap-2 text-xs">
                        <a href="{{ route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']) }}" class="pub-link-card inline-flex min-h-10 items-center rounded-md border border-hairline bg-ivory px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary">Jadwal Sidang</a>
                        <a href="{{ route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']) }}" class="pub-link-card inline-flex min-h-10 items-center rounded-md border border-hairline bg-ivory px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary">Prodeo &amp; Posbakum</a>
                        <a href="{{ route('public.page', ['slug' => 'transparansi/lkjip']) }}" class="pub-link-card inline-flex min-h-10 items-center rounded-md border border-hairline bg-ivory px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary">Arsip LKjIP</a>
                        <a href="{{ route('public.posts', ['kategori' => 'pengumuman']) }}" class="pub-link-card inline-flex min-h-10 items-center rounded-md border border-hairline bg-ivory px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary">Pengumuman Resmi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div
        id="mobile-nav-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="Menu Navigasi Utama"
        hidden
        class="fixed inset-0 z-50 flex justify-end bg-black/50 lg:hidden"
    >
        <div data-mobile-drawer-panel class="flex h-full w-full max-w-sm flex-col overflow-y-auto bg-white p-5 shadow-2xl">
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

            <nav id="mobile-nav-content" aria-label="Navigasi Seluler" class="mt-4 space-y-4"></nav>
        </div>
    </div>

    <main id="konten-utama" tabindex="-1" class="flex-1">
        {{ $slot }}
    </main>

    <!-- Civic Footer (3 Columns) -->
    <footer class="mt-12 border-t-4 border-gold bg-obsidian text-white">
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
                    <div class="mt-2 flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
                        <dt class="shrink-0 font-semibold text-amber-300">Jam Kerja:</dt>
                        <dd class="font-mono text-2xs leading-relaxed text-amber-200">
                            <span class="whitespace-nowrap">Senin - Kamis {{ $fmtJam($monThuOpen) }}-{{ $fmtJam($monThuClose) }}</span>
                            <span aria-hidden="true" class="mx-1 text-amber-300/60">|</span>
                            <span class="whitespace-nowrap">Jumat {{ $fmtJam($friOpen) }}-{{ $fmtJam($friClose) }} {{ $tzLabel }}</span>
                        </dd>
                    </div>
                </dl>
                @php $footerSocials = $satker?->socialChannels() ?? []; @endphp
                @if ($footerSocials !== [])
                    <div class="mt-4">
                        <p class="font-mono text-2xs font-bold tracking-wider text-amber-300 uppercase">Sosial Media Resmi</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($footerSocials as $fSoc)
                                <a
                                    href="{{ $fSoc['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="{{ $fSoc['description'] }}"
                                    class="inline-flex min-h-10 items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-2.5 text-2xs font-semibold text-white hover:border-gold hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold"
                                >
                                    <span>{{ $fSoc['label'] }}</span>
                                    <span aria-hidden="true">&nearr;</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <h2 class="font-serif text-base font-bold text-amber-300">Informasi &amp; Layanan Portal</h2>
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
                <h2 class="font-serif text-base font-bold text-amber-300">Portal Layanan Nasional &amp; MARI</h2>
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
                    @php $safePtaUrl = \App\Support\SafeHtml::cleanUrl($satker?->pta_url); @endphp
                    @if ($safePtaUrl)
                        <li>
                            <a href="{{ $safePtaUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                {{ $satker->pta_nama ?: 'Pengadilan Tinggi Agama (Tingkat Banding)' }}
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ \App\Support\SafeHtml::cleanUrl($satker?->sipp_url) ?? 'https://sipp.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Penelusuran Perkara (SIPP)
                        </a>
                    </li>
                    <li>
                        <a href="{{ \App\Support\SafeHtml::cleanUrl($satker?->ecourt_url) ?? 'https://ecourt.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Layanan Elektronik (e-Court MARI)
                        </a>
                    </li>
                    <li>
                        <a href="{{ \App\Support\SafeHtml::cleanUrl($satker?->direktori_putusan_url) ?? 'https://putusan3.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Direktori Putusan Mahkamah Agung RI
                        </a>
                    </li>
                    <li>
                        <a href="{{ \App\Support\SafeHtml::cleanUrl($satker?->siwas_url) ?? 'https://siwas.mahkamahagung.go.id' }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            Pengawasan &amp; Pengaduan (SIWAS MARI)
                        </a>
                    </li>
                    @php
                        $safeSkmUrl = \App\Support\SafeHtml::cleanUrl($satker?->skm_url);
                        $safeFooterCctvUrl = \App\Support\SafeHtml::cleanUrl($satker?->cctv_aco_url);
                    @endphp
                    <li>
                        <a href="{{ $safeSkmUrl ?? route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']) }}" @if($safeSkmUrl) target="_blank" rel="noopener noreferrer" @endif class="hover:underline">
                            Survei Kepuasan &amp; Anti Korupsi (SI SURTI / SKM &amp; SPAK)
                        </a>
                    </li>
                    @if ($safeFooterCctvUrl)
                        <li>
                            <a href="{{ $safeFooterCctvUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                {{ $satker?->cctv_aco_label ?: 'Access CCTV Online (ACO)' }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="border-t border-emerald-900/70 bg-black/25">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-3 text-2xs text-emerald-100 sm:px-6 lg:px-8">
                <p>Hak Cipta &copy; {{ date('Y') }} {{ $satkerName }}.</p>
                <p class="font-mono">SatkerCMS v{{ config('satker.cms_version', '1.0.0-dev') }}</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Bottom Quick-Action Bar (WCAG Touch Targets >= 44px) -->
    <nav
        aria-label="Navigasi Cepat Seluler"
        data-mobile-bottom-bar
        class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-hairline bg-white/95 backdrop-blur-sm shadow-lg lg:hidden"
    >
        <a
            href="{{ route('home') }}"
            @class([
                'flex min-h-14 flex-col items-center justify-center gap-0.5 text-2xs font-semibold',
                'text-primary bg-primary-soft/60' => request()->routeIs('home'),
                'text-ink-muted hover:text-primary' => ! request()->routeIs('home'),
            ])
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"></path>
            </svg>
            <span>Beranda</span>
        </a>
        <a
            href="{{ route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']) }}"
            class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-2xs font-semibold text-ink-muted hover:text-primary"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span>Sidang</span>
        </a>
        <a
            href="{{ $safeSippUrl }}"
            @if (str_starts_with($safeSippUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif
            class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-2xs font-semibold text-ink-muted hover:text-primary"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <path stroke-linecap="round" d="m21 21-4.35-4.35"></path>
            </svg>
            <span>SIPP</span>
        </a>
        <button
            type="button"
            data-open-a11y
            class="flex min-h-14 cursor-pointer flex-col items-center justify-center gap-0.5 text-2xs font-semibold text-primary hover:bg-primary-soft"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="4.5" r="2"></circle>
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8.5h14M12 8.5v5.5m0 0-3 6m3-6 3 6"></path>
            </svg>
            <span>Akses</span>
        </button>
    </nav>

    <x-accessibility-widget />
</body>
</html>
