@php
    $firstSlide = $sliders->first();
    $firstSlideImage = $firstSlide && ! empty($firstSlide->image_path) ? $firstSlide->image_path : null;
    $homeOgImage = $firstSlideImage ? asset('storage/' . $firstSlideImage) : null;
    $heroSizes = '(max-width: 640px) calc(100vw - 32px), (max-width: 1280px) calc(100vw - 48px), 1216px';
    $firstSlideImgAttrs = $firstSlideImage
        ? \App\Services\ImageOptimizerService::responsiveAttributes($firstSlideImage, [480, 800, 1280], $heroSizes, null, 1280)
        : null;
    $homeDescription = $satker
        ? "Portal Resmi {$satker->nama_satker}. Layanan perkara SIPP, jadwal sidang harian, e-Court, Prodeo & Posbakum, serta transparansi peradilan."
        : null;
    $transparentPixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    $sh = config('satker.service_hours', []);
    $fmtH = static fn (string $v): string => str_replace(':', '.', $v);
    $shTz = (string) ($sh['timezone_label'] ?? 'WITA');

    $defaultDockChannels = [
        [
            'tag' => 'SIPP',
            'title' => 'Penelusuran Perkara',
            'subtitle' => 'Status & nomor perkara',
            'url' => route('public.page', ['slug' => 'layanan-hukum/sipp']),
        ],
        [
            'tag' => 'SIDANG',
            'title' => 'Jadwal Persidangan',
            'subtitle' => 'Agenda ruang sidang harian',
            'url' => route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']),
        ],
        [
            'tag' => 'E-COURT',
            'title' => 'Daftar Perkara Online',
            'subtitle' => 'e-Filing & e-Litigation MARI',
            'url' => route('public.page', ['slug' => 'layanan-hukum/ecourt']),
        ],
        [
            'tag' => 'GRATIS',
            'title' => 'Prodeo & Posbakum',
            'subtitle' => 'Bantuan hukum bagi warga',
            'url' => route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']),
        ],
    ];

    $dockChannels = ($hasCustomShortcuts ?? false)
        ? collect($shortcuts)->take(4)->map(fn (array $item): array => [
            'tag' => strtoupper((string) ($item['tag'] ?? 'LAYANAN')),
            'title' => (string) $item['label'],
            'subtitle' => 'Akses cepat layanan resmi',
            'url' => (string) $item['url'],
        ])->all()
        : $defaultDockChannels;

    $extraShortcuts = ($hasCustomShortcuts ?? false) ? array_slice($shortcuts, 4) : [];

    $safeSkmHomeUrl = \App\Support\SafeHtml::cleanUrl($satker?->skm_url);
    $safeVideoProfilUrl = \App\Support\SafeHtml::cleanUrl($satker?->video_profil_url);
    $safeVideoPtspUrl = \App\Support\SafeHtml::cleanUrl($satker?->video_ptsp_url);
    $safeVideoKebijakanUrl = \App\Support\SafeHtml::cleanUrl($satker?->video_kebijakan_ma_url);

    $leadNews = $latestNews->first();
    $secondaryNews = $latestNews->slice(1);

    $relatedLinks = $satker?->resolvedRelatedLinks() ?? [];
    $socialChannels = $satker?->socialChannels() ?? [];
    $safeCctvUrl = \App\Support\SafeHtml::cleanUrl($satker?->cctv_aco_url);
    $cctvLabel = $satker?->cctv_aco_label ?: 'Access CCTV Online (ACO)';
    $hasTautan = $relatedLinks !== [];
    $hasMidColumn = $safeCctvUrl !== null || $socialChannels !== [];
@endphp
<x-layouts.public
    title="Beranda"
    :description="$homeDescription"
    :canonical="route('home')"
    :og-image="$homeOgImage"
    :og-image-path="$firstSlideImage"
    :og-image-alt="$firstSlide?->image_alt"
    :preload-image="$firstSlideImgAttrs['src'] ?? null"
    :preload-image-srcset="$firstSlideImgAttrs['srcset'] ?? null"
    :preload-image-sizes="$firstSlideImgAttrs['sizes'] ?? null"
    :flush-footer="true"
>
    {{-- Banner Informasi Serta Merta --}}
    @if ($sertaMerta->isNotEmpty())
        <section aria-label="Informasi Serta Merta" class="border-b border-amber-300 bg-amber-50 text-ink">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-2.5 sm:px-6 lg:px-8">
                @foreach ($sertaMerta as $alertPost)
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-semibold">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="rounded bg-obsidian px-2.5 py-0.5 font-mono text-2xs font-bold text-amber-300 uppercase">
                                Informasi Serta Merta
                            </span>
                            <a href="{{ route('public.posts.show', ['slug' => $alertPost->slug]) }}" class="text-ink underline-offset-2 hover:text-primary-deep hover:underline">
                                {{ $alertPost->title }}
                            </a>
                        </div>
                        <a href="{{ route('public.posts.show', ['slug' => $alertPost->slug]) }}" class="inline-flex items-center gap-1 font-bold text-primary-deep hover:underline">
                            <span>Baca Selengkapnya</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Seksi 1: Full-Width Visual Banner Slider + Integrated Citizen Action Dock --}}
    <section aria-label="Sorotan Utama dan Akses Layanan Cepat" class="border-b border-hairline/70 bg-ivory">
        <div class="mx-auto max-w-7xl px-4 pt-5 pb-8 sm:px-6 lg:px-8 lg:pt-6 lg:pb-10">
            @if ($sliders->isNotEmpty())
                @php
                    $defaultSlideSubtitle = 'Portal resmi ' . ($satker?->nama_satker ?? 'Peradilan Agama') . ' untuk penelusuran perkara SIPP, agenda persidangan harian, layanan bantuan hukum Prodeo & Posbakum, serta keterbukaan informasi publik.';
                    $heroStageSizes = '(max-width: 1024px) calc(100vw - 64px), 620px';
                @endphp
                <div
                    data-hero-slider
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Informasi Utama Satuan Kerja"
                    tabindex="0"
                    class="pub-hero-stage relative overflow-hidden rounded-2xl border border-emerald-900/40 text-white shadow-[0_24px_56px_-22px_rgba(2,44,32,0.58)] focus:outline-none"
                >
                    {{-- Subtle Top Gold Crown Accent --}}
                    <div aria-hidden="true" class="h-[3px] w-full bg-gradient-to-r from-gold via-amber-300 to-gold/40"></div>

                    {{-- Subtle Architectural Scales Crest Watermark --}}
                    <svg aria-hidden="true" class="pointer-events-none absolute -bottom-10 -left-10 size-64 text-white/[0.04] sm:size-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.9">
                        <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                    </svg>

                    <div class="relative z-10 grid items-center gap-7 p-5 sm:p-8 lg:grid-cols-12 lg:gap-10 lg:px-11 lg:pt-9 lg:pb-8">
                        {{-- Left 5 Columns: Elevated Editorial Story & Actions --}}
                        <div class="min-w-0 space-y-5 lg:col-span-5">
                            <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.08] px-3.5 py-1.5 backdrop-blur-xs">
                                <span class="inline-block size-2 rounded-full bg-gold" aria-hidden="true"></span>
                                <span class="font-mono text-[10px] font-bold tracking-[0.14em] text-emerald-100 uppercase">Sorotan Resmi</span>
                                @if ($sliders->count() > 1)
                                    <span aria-hidden="true" class="h-3 w-px bg-white/20"></span>
                                    <span data-slider-counter class="font-mono text-[11px] font-bold text-amber-300">
                                        01 / {{ str_pad((string) $sliders->count(), 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                @endif
                            </div>

                            <div aria-live="polite" aria-atomic="false">
                                @foreach ($sliders as $slideIndex => $slide)
                                    @php
                                        $ctaTarget = $slide->cta_url ? (str_starts_with($slide->cta_url, 'http') ? $slide->cta_url : url($slide->cta_url)) : null;
                                        $isProdeoCta = $ctaTarget !== null && str_contains($ctaTarget, 'hak-pencari-keadilan');
                                    @endphp
                                    <div
                                        data-hero-slide
                                        data-slide-title="{{ $slide->title }}"
                                        role="group"
                                        aria-roledescription="slide"
                                        aria-label="Slide {{ $slideIndex + 1 }} dari {{ $sliders->count() }}"
                                        @if ($slideIndex !== 0) hidden @endif
                                        class="space-y-4"
                                    >
                                        @if ($slideIndex === 0)
                                            <h1 class="font-serif text-2xl leading-[1.14] font-bold tracking-tight text-white sm:text-3xl lg:text-[2.3rem]">
                                                {{ $slide->title }}
                                            </h1>
                                        @else
                                            <h2 class="font-serif text-2xl leading-[1.14] font-bold tracking-tight text-white sm:text-3xl lg:text-[2.3rem]">
                                                {{ $slide->title }}
                                            </h2>
                                        @endif

                                        <div aria-hidden="true" class="h-[3px] w-12 rounded-full bg-gold"></div>

                                        <p class="max-w-lg text-xs leading-relaxed text-emerald-50/90 sm:text-sm">
                                            {{ filled($slide->subtitle) ? $slide->subtitle : $defaultSlideSubtitle }}
                                        </p>

                                        <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                            @if ($slide->cta_label && $ctaTarget)
                                                <a
                                                    href="{{ $ctaTarget }}"
                                                    class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-lg bg-gold px-5 text-xs font-bold text-ink shadow-[0_10px_24px_-8px_rgba(255,153,0,0.55)] hover:opacity-95"
                                                >
                                                    <span>{{ $slide->cta_label }}</span>
                                                    <span aria-hidden="true">&rarr;</span>
                                                </a>
                                            @endif
                                            <a
                                                href="{{ route('public.page', ['slug' => $isProdeoCta ? 'layanan-hukum/jadwal-sidang' : 'layanan-hukum/hak-pencari-keadilan']) }}"
                                                class="pub-btn inline-flex min-h-11 items-center rounded-lg border border-white/30 bg-white/[0.08] px-4 text-xs font-semibold text-white backdrop-blur-xs hover:border-white/60 hover:bg-white/15"
                                            >
                                                {{ $isProdeoCta ? 'Jadwal Sidang Hari Ini' : 'Panduan Prodeo & Posbakum' }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Right 7 Columns: Illuminated Glass-Bezel 16:9 Visual Stage (100% Unobstructed Image) --}}
                        <div class="relative min-w-0 lg:col-span-7">
                            {{-- Subtle Ambient Gold/Emerald Halo Behind Visual Frame --}}
                            <div aria-hidden="true" class="pointer-events-none absolute -inset-3 rounded-3xl bg-radial from-gold/18 via-emerald-400/10 to-transparent blur-2xl"></div>

                            <div class="relative rounded-2xl border border-white/20 bg-white/[0.08] p-1.5 shadow-[0_26px_60px_-16px_rgba(0,0,0,0.65)] backdrop-blur-sm sm:p-2">
                                <div class="relative aspect-[16/9] w-full overflow-hidden rounded-xl bg-[#011c14] ring-1 ring-black/25">
                                    @foreach ($sliders as $slideIndex => $slide)
                                        <div
                                            data-hero-media-slide
                                            @if ($slideIndex !== 0) hidden @endif
                                            class="h-full w-full"
                                        >
                                            @if ($slide->image_path)
                                                @php
                                                    $slideImg = \App\Services\ImageOptimizerService::responsiveAttributes($slide->image_path, [480, 800, 1280], $heroStageSizes, null, 1280);
                                                @endphp
                                                @if ($slideIndex === 0)
                                                    <img
                                                        src="{{ $slideImg['src'] }}"
                                                        @if ($slideImg['srcset']) srcset="{{ $slideImg['srcset'] }}" sizes="{{ $slideImg['sizes'] }}" @endif
                                                        alt="{{ $slide->image_alt }}"
                                                        width="1280"
                                                        height="720"
                                                        decoding="async"
                                                        fetchpriority="high"
                                                        class="h-full w-full object-cover"
                                                    />
                                                @else
                                                    <img
                                                        src="{{ $transparentPixel }}"
                                                        data-lazy-src="{{ $slideImg['src'] }}"
                                                        @if ($slideImg['srcset']) data-lazy-srcset="{{ $slideImg['srcset'] }}" data-lazy-sizes="{{ $slideImg['sizes'] }}" @endif
                                                        alt="{{ $slide->image_alt }}"
                                                        width="1280"
                                                        height="720"
                                                        decoding="async"
                                                        class="h-full w-full object-cover"
                                                    />
                                                @endif
                                            @else
                                                <div class="sr-only">{{ $slide->image_alt }}</div>
                                                <div class="flex h-full w-full flex-col items-center justify-center bg-obsidian/70 px-6 text-center" aria-hidden="true">
                                                    <svg class="size-24 text-gold/75 sm:size-32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 3v18" />
                                                        <path d="M5 7h14" />
                                                        <path d="M8 21h8" />
                                                        <path d="M5 7l-3 6h6l-3-6z" />
                                                        <path d="M19 7l-3 6h6l-3-6z" />
                                                    </svg>
                                                    <span class="mt-2 font-mono text-2xs font-bold tracking-wider text-emerald-200 uppercase">{{ $satker?->nama_satker ?? 'Peradilan Agama' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if ($sliders->count() > 1)
                                        {{-- Edge Frosted-Glass Arrows (Clear of Center Banner Content) --}}
                                        <button
                                            type="button"
                                            data-slider-prev
                                            aria-label="Slide sebelumnya"
                                            class="pub-btn absolute top-1/2 left-2.5 z-10 hidden size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/25 bg-obsidian/70 text-white shadow-md backdrop-blur-sm hover:border-gold hover:bg-gold hover:text-ink sm:inline-flex"
                                        >
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            data-slider-next
                                            aria-label="Slide berikutnya"
                                            class="pub-btn absolute top-1/2 right-2.5 z-10 hidden size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/25 bg-obsidian/70 text-white shadow-md backdrop-blur-sm hover:border-gold hover:bg-gold hover:text-ink sm:inline-flex"
                                        >
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($sliders->count() > 1)
                        {{-- Interactive Editorial Slide Selector Rail (Desktop) + Compact Controls (Mobile) --}}
                        <div class="relative z-10 border-t border-white/12 bg-black/22 backdrop-blur-md">
                            {{-- Desktop Named Slide Tabs --}}
                            <div class="hidden items-stretch md:flex">
                                <div class="grid flex-1 divide-x divide-white/10" style="grid-template-columns: repeat({{ $sliders->count() }}, minmax(0, 1fr));" role="group" aria-label="Pilih sorotan utama">
                                    @foreach ($sliders as $tabIdx => $tabSlide)
                                        <button
                                            type="button"
                                            data-slider-tab="{{ $tabIdx }}"
                                            aria-label="Pilih slide {{ $tabIdx + 1 }}: {{ $tabSlide->title }}"
                                            aria-current="{{ $tabIdx === 0 ? 'true' : 'false' }}"
                                            @class([
                                                'group/tab relative flex min-h-14 min-w-0 cursor-pointer flex-col justify-center px-4 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:outline-gold',
                                                'bg-white/10 text-white' => $tabIdx === 0,
                                                'text-emerald-100/75 hover:bg-white/[0.06] hover:text-white' => $tabIdx !== 0,
                                            ])
                                        >
                                            <span aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-[3px] overflow-hidden bg-white/10">
                                                <span
                                                    data-slider-tab-progress
                                                    @class([
                                                        'block h-full w-full bg-gold',
                                                        'pub-slider-progress-bar' => $tabIdx === 0,
                                                        'opacity-0' => $tabIdx !== 0,
                                                    ])
                                                ></span>
                                            </span>
                                            <span
                                                data-slider-tab-num
                                                @class([
                                                    'font-mono text-[10px] font-bold tracking-wider',
                                                    'text-amber-300' => $tabIdx === 0,
                                                    'text-emerald-200/70 group-hover/tab:text-amber-200' => $tabIdx !== 0,
                                                ])
                                            >
                                                {{ str_pad((string) ($tabIdx + 1), 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <span class="mt-0.5 line-clamp-1 text-xs font-semibold">
                                                {{ $tabSlide->title }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="flex shrink-0 items-center border-l border-white/12 px-3">
                                    <button
                                        type="button"
                                        data-slider-pause
                                        aria-pressed="false"
                                        class="pub-btn inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg border border-white/15 bg-white/[0.06] px-3 text-xs font-semibold text-emerald-50 hover:border-white/40 hover:bg-white/15"
                                    >
                                        <span data-slider-pause-label>Jeda</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Mobile Compact Controls Dock --}}
                            <div class="flex items-center justify-between gap-2 px-4 py-2.5 md:hidden">
                                <div class="flex items-center gap-1" role="group" aria-label="Pilih slide">
                                    @foreach ($sliders as $dotIndex => $dotSlide)
                                        <button
                                            type="button"
                                            data-slider-dot="{{ $dotIndex }}"
                                            aria-label="Pilih slide {{ $dotIndex + 1 }}: {{ $dotSlide->title }}"
                                            aria-current="{{ $dotIndex === 0 ? 'true' : 'false' }}"
                                            class="group/dot inline-flex min-h-10 cursor-pointer items-center justify-center px-0.5 focus-visible:outline-2 focus-visible:outline-gold"
                                        >
                                            <span
                                                data-slider-pill
                                                @class([
                                                    'block h-2 rounded-full transition-all duration-300',
                                                    'w-8 bg-gold' => $dotIndex === 0,
                                                    'w-2.5 bg-white/30 group-hover/dot:bg-white/70' => $dotIndex !== 0,
                                                ])
                                            ></span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        data-slider-prev
                                        aria-label="Slide sebelumnya"
                                        class="pub-btn inline-flex size-9 cursor-pointer items-center justify-center rounded-md border border-white/15 text-white hover:bg-white/15"
                                    >
                                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        data-slider-next
                                        aria-label="Slide berikutnya"
                                        class="pub-btn inline-flex size-9 cursor-pointer items-center justify-center rounded-md border border-white/15 text-white hover:bg-white/15"
                                    >
                                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        data-slider-pause
                                        aria-pressed="false"
                                        class="pub-btn inline-flex min-h-9 cursor-pointer items-center rounded-md border border-white/15 px-2.5 text-2xs font-semibold text-emerald-50 hover:bg-white/15"
                                    >
                                        <span data-slider-pause-label>Jeda</span>
                                    </button>
                                </div>
                            </div>

                            <div aria-hidden="true" class="h-[2px] w-full bg-white/10 md:hidden">
                                <div data-slider-progress class="pub-slider-progress-bar h-full w-full bg-gold"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="pub-hero-grid-pattern overflow-hidden rounded-2xl border border-emerald-950/15 p-7 text-white sm:p-10">
                    <h1 class="font-serif text-2xl font-bold text-white sm:text-4xl">
                        Portal Resmi {{ $satker?->nama_satker ?? 'Peradilan Agama' }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-emerald-100">
                        Pelayanan informasi peradilan yang terbuka, cepat, transparan, dan mudah diakses pencari keadilan.
                    </p>
                </div>
            @endif

            {{-- Integrated Citizen Action Dock (5 Jalur Utama Pencari Keadilan) --}}
            <div class="mt-4 rounded-2xl border border-hairline bg-white p-2 shadow-[0_12px_28px_-14px_rgba(2,44,32,0.14)]">
                <div class="grid gap-1.5 sm:grid-cols-2 lg:grid-cols-12 lg:items-center lg:gap-0">
                    @foreach ($dockChannels as $dockIndex => $channel)
                        <a
                            href="{{ $channel['url'] }}"
                            @class([
                                'group flex min-h-14 flex-col justify-center rounded-xl px-4 py-3 transition-colors hover:bg-primary-soft lg:col-span-2 lg:rounded-lg',
                                'lg:border-l lg:border-hairline/80' => $dockIndex > 0,
                            ])
                        >
                            <span class="font-mono text-[10px] font-bold tracking-wider text-primary uppercase">
                                {{ $channel['tag'] }}
                            </span>
                            <span class="mt-0.5 text-sm font-bold text-ink group-hover:text-primary">
                                {{ $channel['title'] }}
                            </span>
                            <span class="text-[11px] text-ink-muted">
                                {{ $channel['subtitle'] }}
                            </span>
                        </a>
                    @endforeach

                    {{-- Integrated Quick Search Slot --}}
                    <div class="border-t border-hairline/80 pt-2 sm:col-span-2 lg:col-span-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-3">
                        <form action="{{ route('public.search') }}" method="GET" role="search" class="flex items-center gap-2 px-1.5 py-1">
                            <label for="hero-search-input" class="sr-only">Cari perkara, jadwal sidang, biaya, atau LKjIP</label>
                            <input
                                id="hero-search-input"
                                type="search"
                                name="q"
                                minlength="2"
                                required
                                placeholder="Cari perkara, biaya, LKjIP..."
                                class="min-h-11 min-w-0 flex-1 rounded-lg border border-hairline bg-ivory px-3.5 text-xs text-ink placeholder:text-ink-muted focus:border-primary focus:bg-white focus:outline-none"
                            />
                            <button
                                type="submit"
                                class="pub-btn min-h-11 shrink-0 cursor-pointer rounded-lg bg-primary px-4 text-xs font-bold text-white hover:bg-primary-hover"
                            >
                                Cari
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if ($extraShortcuts !== [])
                <ul aria-label="Pintasan Tambahan" class="mt-3 flex flex-wrap items-center gap-2">
                    @foreach ($extraShortcuts as $extraItem)
                        <li>
                            <a
                                href="{{ $extraItem['url'] }}"
                                class="inline-flex min-h-10 items-center rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                {{ $extraItem['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Seksi 2: Layanan Hukum & PTSP | Agenda Persidangan & Indeks Survei (Asimetris 7/5, White Canvas) --}}
    <section aria-label="Layanan Hukum, Agenda Sidang, dan Indeks Survei" class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-14">
            {{-- Left 7 Columns: Clean Editorial Service Ledger (No Stacked Boxes) --}}
            <div class="flex flex-col justify-between lg:col-span-7">
                <div>
                    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-hairline pb-4">
                        <div>
                            <p class="font-mono text-2xs font-bold tracking-wider text-primary uppercase">
                                Pelayanan Terpadu Satu Pintu
                            </p>
                            <h2 class="mt-1 font-serif text-2xl font-bold text-ink sm:text-[1.7rem]">
                                Layanan Hukum &amp; Kepaniteraan
                            </h2>
                        </div>
                        <a
                            href="{{ route('public.page', ['slug' => 'layanan-publik/standar-maklumat-pelayanan']) }}"
                            class="inline-flex min-h-10 items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        >
                            <span>Standar &amp; Maklumat Pelayanan</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>

                    @if ($servicePages->isEmpty())
                        <p class="py-8 text-sm text-ink-muted">Daftar halaman layanan hukum belum tersedia.</p>
                    @else
                        <ul class="mt-2 grid gap-x-8 sm:grid-cols-2">
                            @foreach ($servicePages as $sPage)
                                <li class="border-b border-hairline/80">
                                    <a
                                        href="{{ route('public.page', ['slug' => $sPage->slug]) }}"
                                        class="group flex min-h-16 items-start gap-3.5 py-4 transition-colors"
                                    >
                                        <span class="mt-0.5 font-mono text-xs font-bold text-primary">
                                            {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-bold text-ink group-hover:text-primary">
                                                {{ $sPage->title }}
                                            </span>
                                            @if (filled($sPage->summary))
                                                <span class="mt-1 line-clamp-2 block text-xs leading-relaxed text-ink-muted">
                                                    {{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $sPage->summary)), 92) }}
                                                </span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Quiet Inline PTSP Service Hours Strip --}}
                <div class="mt-6 flex flex-col gap-1.5 rounded-xl border border-emerald-200/80 bg-primary-soft px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                    <h3 class="shrink-0 font-serif text-sm font-bold text-primary-deep">
                        Jam Pelayanan Pengadilan (PTSP)
                    </h3>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5 font-mono text-[11px] leading-relaxed text-ink-body">
                        <span class="whitespace-nowrap">Senin-Kamis {{ $fmtH((string) ($sh['mon_thu_open'] ?? '08:00')) }}-{{ $fmtH((string) ($sh['mon_thu_close'] ?? '16:30')) }} {{ $shTz }}</span>
                        <span aria-hidden="true" class="text-primary/40">&middot;</span>
                        <span class="whitespace-nowrap">Jumat {{ $fmtH((string) ($sh['fri_open'] ?? '08:00')) }}-{{ $fmtH((string) ($sh['fri_close'] ?? '17:00')) }} {{ $shTz }}</span>
                        <span aria-hidden="true" class="text-primary/40">&middot;</span>
                        <span class="whitespace-nowrap">Sabtu-Minggu Tutup</span>
                    </div>
                </div>
            </div>

            {{-- Right 5 Columns: Agenda Persidangan + Compact Indeks Survei --}}
            <div class="space-y-5 lg:col-span-5">
                {{-- Agenda Persidangan Panel --}}
                <div class="rounded-2xl border border-hairline bg-ivory p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-2 border-b border-hairline pb-3">
                        <h2 class="font-serif text-lg font-bold text-ink">
                            {{ $hasUpcomingSchedules ? 'Agenda Persidangan Terjadwal' : 'Agenda Persidangan' }}
                        </h2>
                        <a
                            href="{{ route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']) }}"
                            class="inline-flex min-h-10 items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        >
                            <span>Semua Jadwal</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>

                    @if ($todaySchedules->isEmpty())
                        <p class="mt-3 text-xs leading-relaxed text-ink-muted">
                            Jadwal persidangan lokal belum dicatat. Masyarakat dapat memeriksa agenda persidangan harian langsung melalui tautan SIPP satuan kerja.
                        </p>
                    @else
                        <ul class="mt-2 divide-y divide-hairline/80">
                            @foreach ($todaySchedules->take(3) as $sch)
                                <li class="py-3 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono font-bold text-primary-deep">{{ $sch->nomor_perkara }}</span>
                                        <span class="font-mono text-[11px] font-semibold text-primary">
                                            {{ \App\Support\Tanggal::format($sch->tanggal_sidang, 'd M Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-ink-body">
                                        {{ $sch->agenda ?: 'Persidangan Sesuai Jadwal' }}
                                        @if ($sch->ruang_sidang)
                                            &middot; Ruang {{ $sch->ruang_sidang }}
                                        @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Compact Indeks Survei Layanan (Pedoman Pengawasan A-6) --}}
                <div class="rounded-2xl border border-hairline bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-serif text-lg font-bold text-ink">Indeks Survei Layanan</h2>
                        @if ($satker?->survei_periode)
                            <span class="rounded bg-gold px-2.5 py-0.5 font-mono text-[11px] font-bold text-ink">
                                {{ $satker->survei_periode }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-2.5">
                        <div class="min-w-0 rounded-xl border border-hairline/80 bg-ivory p-2.5 sm:p-3">
                            <p class="font-mono text-[10px] font-bold text-ink-muted uppercase">IKM / SKM</p>
                            <p class="mt-1 truncate font-serif text-base font-bold text-primary-deep sm:text-xl">
                                {{ $satker?->nilai_skm ?: 'Tersedia' }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-ink-muted">Kepuasan</p>
                        </div>
                        <div class="min-w-0 rounded-xl border border-hairline/80 bg-ivory p-2.5 sm:p-3">
                            <p class="font-mono text-[10px] font-bold text-ink-muted uppercase">IPAK / SPAK</p>
                            <p class="mt-1 truncate font-serif text-base font-bold text-primary-deep sm:text-xl">
                                {{ $satker?->nilai_spak ?: 'Tersedia' }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-ink-muted">Anti Korupsi</p>
                        </div>
                        <div class="min-w-0 rounded-xl border border-hairline/80 bg-ivory p-2.5 sm:p-3">
                            <p class="font-mono text-[10px] font-bold text-ink-muted uppercase">IPKL / SPKP</p>
                            <p class="mt-1 truncate font-serif text-base font-bold text-primary-deep sm:text-xl">
                                {{ $satker?->nilai_spkp ?: 'Tersedia' }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-ink-muted">Kualitas</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <a
                            href="{{ $safeSkmHomeUrl ?? route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']) }}"
                            @if ($safeSkmHomeUrl) target="_blank" rel="noopener noreferrer" @endif
                            class="pub-btn inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-3.5 text-xs font-bold text-white hover:bg-primary-hover"
                        >
                            Isi Survei Pelayanan
                        </a>
                        <a
                            href="{{ route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']) }}"
                            class="pub-btn inline-flex min-h-11 items-center justify-center rounded-lg border border-primary bg-white px-3.5 text-xs font-bold text-primary hover:bg-primary-soft"
                        >
                            Laporan Survei
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Seksi 3: Berita Majalah Asimetris & Pengumuman + Video MA (8/4, Warm Ivory Canvas) --}}
    <section aria-label="Berita Terbaru, Pengumuman Resmi, dan Media Layanan" class="border-y border-hairline/70 bg-ivory">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-14">
            {{-- Left 8 Columns: Asymmetric Editorial Magazine News --}}
            <div class="lg:col-span-8">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-hairline pb-4">
                    <div>
                        <p class="font-mono text-2xs font-bold tracking-wider text-primary uppercase">
                            Kabar Peradilan &amp; Inovasi
                        </p>
                        <h2 class="mt-1 font-serif text-2xl font-bold text-ink sm:text-[1.7rem]">
                            Berita &amp; Publikasi Terbaru
                        </h2>
                    </div>
                    <a href="{{ route('public.posts') }}" class="inline-flex min-h-10 items-center gap-1 text-xs font-semibold text-primary hover:underline">
                        <span>Semua Berita</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                @if ($latestNews->isEmpty())
                    <div class="mt-6 rounded-2xl border border-hairline bg-white p-8 text-center text-sm text-ink-muted">
                        Belum ada berita pelayanan yang diterbitkan.
                    </div>
                @else
                    {{-- Featured Lead Story --}}
                    @if ($leadNews)
                        @php
                            $leadDate = $leadNews->published_at ?? $leadNews->created_at;
                            $leadCategory = \App\Models\Post::categoryLabels()[$leadNews->category] ?? $leadNews->category;
                        @endphp
                        <article class="pub-card mt-5 overflow-hidden rounded-2xl border border-hairline bg-white">
                            <div class="grid sm:grid-cols-12">
                                <div class="sm:col-span-5">
                                    @if ($leadNews->featured_image_path)
                                        @php
                                            $leadImg = \App\Services\ImageOptimizerService::responsiveAttributes($leadNews->featured_image_path, [400, 480, 720], '(max-width: 640px) calc(100vw - 32px), 360px', null, 1000);
                                        @endphp
                                        <a href="{{ route('public.posts.show', ['slug' => $leadNews->slug]) }}" class="block h-full overflow-hidden bg-obsidian">
                                            <img
                                                src="{{ $leadImg['src'] }}"
                                                @if ($leadImg['srcset']) srcset="{{ $leadImg['srcset'] }}" sizes="{{ $leadImg['sizes'] }}" @endif
                                                alt="{{ $leadNews->featured_image_alt ?: $leadNews->title }}"
                                                width="640"
                                                height="400"
                                                loading="lazy"
                                                decoding="async"
                                                class="pub-img-zoom aspect-[16/10] h-full w-full object-cover sm:aspect-auto sm:min-h-[230px]"
                                            />
                                        </a>
                                    @else
                                        <div aria-hidden="true" class="flex aspect-[16/10] h-full flex-col justify-between bg-obsidian p-6 text-white sm:aspect-auto sm:min-h-[230px]">
                                            <span class="font-mono text-2xs font-bold tracking-wider text-amber-300 uppercase">Publikasi Utama</span>
                                            <div>
                                                <p class="font-serif text-base font-bold text-white">{{ $satker?->nama_satker ?? 'Peradilan Agama' }}</p>
                                                <p class="mt-0.5 text-xs text-emerald-100">{{ $leadCategory }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col justify-between p-5 sm:col-span-7 sm:p-6">
                                    <div>
                                        <p class="font-mono text-[11px] font-bold tracking-wide text-primary uppercase">
                                            {{ $leadCategory }} &middot; {{ \App\Support\Tanggal::format($leadDate, 'd M Y') }}
                                        </p>
                                        <h3 class="mt-2 font-serif text-xl leading-snug font-bold text-ink">
                                            <a href="{{ route('public.posts.show', ['slug' => $leadNews->slug]) }}" class="hover:text-primary hover:underline">
                                                {{ $leadNews->title }}
                                            </a>
                                        </h3>
                                        @if ($leadNews->excerpt && trim(strip_tags($leadNews->excerpt)) !== '')
                                            <p class="mt-2.5 text-xs leading-relaxed text-ink-body sm:text-sm">
                                                {{ \Illuminate\Support\Str::limit(trim(strip_tags($leadNews->excerpt)), 155) }}
                                            </p>
                                        @endif
                                    </div>

                                    <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-hairline/70 pt-3 font-mono text-[11px] text-ink-muted">
                                        <span>{{ number_format((int) ($leadNews->views_count ?? 0)) }} dilihat</span>
                                        <span aria-hidden="true">&middot;</span>
                                        <span>{{ number_format((int) ($leadNews->approved_comments_count ?? 0)) }} komentar</span>
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endif

                    {{-- 3 Compact Supporting Stories --}}
                    @if ($secondaryNews->isNotEmpty())
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            @foreach ($secondaryNews as $news)
                                @php
                                    $newsDate = $news->published_at ?? $news->created_at;
                                    $newsCategory = \App\Models\Post::categoryLabels()[$news->category] ?? $news->category;
                                @endphp
                                <article class="pub-card flex flex-col justify-between rounded-xl border border-hairline bg-white p-4">
                                    <div>
                                        <p class="font-mono text-[10px] font-bold tracking-wide text-primary uppercase">
                                            {{ $newsCategory }}
                                        </p>
                                        <h3 class="mt-1.5 font-serif text-[15px] leading-snug font-bold text-ink">
                                            <a href="{{ route('public.posts.show', ['slug' => $news->slug]) }}" class="hover:text-primary hover:underline">
                                                {{ $news->title }}
                                            </a>
                                        </h3>
                                    </div>
                                    <p class="mt-3.5 border-t border-hairline/70 pt-2.5 font-mono text-[10.5px] text-ink-muted">
                                        {{ \App\Support\Tanggal::format($newsDate, 'd M Y') }}
                                        &middot; {{ number_format((int) ($news->views_count ?? 0)) }} dilihat
                                        &middot; {{ number_format((int) ($news->approved_comments_count ?? 0)) }} komentar
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- Right 4 Columns: Pengumuman Resmi Timeline + Compact Video Kebijakan MA --}}
            <div class="space-y-6 lg:col-span-4">
                <div>
                    <div class="flex items-end justify-between gap-2 border-b border-hairline pb-4">
                        <h2 class="font-serif text-xl font-bold text-ink">Pengumuman Resmi</h2>
                        <a
                            href="{{ route('public.posts', ['kategori' => 'pengumuman']) }}"
                            class="inline-flex min-h-10 items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        >
                            <span>Arsip</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>

                    @if ($announcements->isEmpty())
                        <p class="py-6 text-xs text-ink-muted">Belum ada pengumuman resmi yang diterbitkan.</p>
                    @else
                        <ul class="mt-2 divide-y divide-hairline/80">
                            @foreach ($announcements->take(4) as $ann)
                                @php $annDate = $ann->published_at ?? $ann->created_at; @endphp
                                <li class="py-1.5">
                                    <a href="{{ route('public.posts.show', ['slug' => $ann->slug]) }}" class="group flex min-h-12 items-start gap-3 rounded-lg py-2 transition-colors">
                                        <div class="flex w-12 shrink-0 flex-col items-center rounded-lg border border-hairline bg-white py-1.5 text-center transition-colors group-hover:border-primary/40">
                                            <span class="font-mono text-sm leading-none font-bold text-primary-deep">{{ $annDate?->format('d') ?? '--' }}</span>
                                            <span class="mt-0.5 font-mono text-[10px] leading-tight font-semibold text-primary uppercase">{{ \App\Support\Tanggal::format($annDate, 'M') }}</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <span class="block text-[13px] leading-snug font-semibold text-ink group-hover:text-primary group-hover:underline">
                                                {{ $ann->title }}
                                            </span>
                                            <p class="mt-1 font-mono text-[10.5px] text-ink-muted">
                                                {{ \App\Support\Tanggal::format($annDate, 'd M Y') }}
                                                &middot; {{ number_format((int) ($ann->views_count ?? 0)) }} dilihat
                                                &middot; {{ number_format((int) ($ann->approved_comments_count ?? 0)) }} komentar
                                            </p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Compact Media & Sosialisasi Kebijakan MA (Pedoman Pengawasan A-6, Non-Embed Ringan) --}}
                <div class="rounded-2xl border border-hairline bg-white p-4 sm:p-5">
                    <p class="font-mono text-[10px] font-bold tracking-wider text-primary uppercase">
                        Media &amp; Sosialisasi Kebijakan MA
                    </p>
                    <ul class="mt-3 space-y-2">
                        <li>
                            <a
                                href="{{ $safeVideoProfilUrl ?? route('public.page', ['slug' => 'profil/visi-misi']) }}"
                                @if ($safeVideoProfilUrl) target="_blank" rel="noopener noreferrer" @endif
                                class="flex min-h-11 items-center justify-between gap-2 rounded-lg border border-hairline/80 bg-ivory px-3.5 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span>Video Profil Pengadilan</span>
                                <span aria-hidden="true" class="text-primary">{{ $safeVideoProfilUrl ? '↗' : '→' }}</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ $safeVideoPtspUrl ?? route('public.page', ['slug' => 'layanan-publik/fasilitas-dan-ptsp']) }}"
                                @if ($safeVideoPtspUrl) target="_blank" rel="noopener noreferrer" @endif
                                class="flex min-h-11 items-center justify-between gap-2 rounded-lg border border-hairline/80 bg-ivory px-3.5 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span>Video Pelayanan Terpadu (PTSP)</span>
                                <span aria-hidden="true" class="text-primary">{{ $safeVideoPtspUrl ? '↗' : '→' }}</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ $safeVideoKebijakanUrl ?? route('public.posts', ['kategori' => 'infografis']) }}"
                                @if ($safeVideoKebijakanUrl) target="_blank" rel="noopener noreferrer" @endif
                                class="flex min-h-11 items-center justify-between gap-2 rounded-lg border border-hairline/80 bg-ivory px-3.5 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span>Infografis &amp; Kebijakan Terbaru MA</span>
                                <span aria-hidden="true" class="text-primary">{{ $safeVideoKebijakanUrl ? '↗' : '→' }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Seksi 4: Transparansi Kinerja & Anggaran SAKIP (Single Deep Obsidian Anchor) --}}
    <section aria-label="Transparansi Kinerja dan Anggaran SAKIP" class="bg-obsidian text-white">
        <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-14">
            <div class="space-y-3.5 lg:col-span-4">
                <p class="font-mono text-2xs font-bold tracking-wider text-amber-300 uppercase">
                    Akuntabilitas &amp; Keterbukaan Informasi
                </p>
                <h2 class="font-serif text-2xl leading-tight font-bold text-white sm:text-[1.75rem]">
                    Transparansi Kinerja &amp; Anggaran (SAKIP)
                </h2>
                <p class="text-xs leading-relaxed text-emerald-100 sm:text-sm">
                    Dokumen perencanaan, realisasi anggaran DIPA, laporan keuangan, serta laporan tahunan lima tahun terakhir tersedia terbuka dalam format PDF.
                </p>
                <div class="pt-1">
                    <a
                        href="{{ route('public.sitemap') }}"
                        class="pub-btn inline-flex min-h-11 items-center rounded-lg bg-gold px-5 text-xs font-bold text-ink hover:opacity-95"
                    >
                        Lihat Seluruh Direktori Dokumen
                    </a>
                </div>
            </div>

            <ul class="grid gap-3 sm:grid-cols-2 lg:col-span-8">
                @forelse ($transparencyPages as $tPage)
                    <li>
                        <a
                            href="{{ route('public.page', ['slug' => $tPage->slug]) }}"
                            class="pub-link-card flex min-h-12 items-center justify-between gap-3 rounded-xl border border-white/15 bg-white/[0.06] px-4 py-3 text-xs font-semibold text-white hover:border-gold hover:bg-primary"
                        >
                            <span>{{ $tPage->title }}</span>
                            <span aria-hidden="true" class="pub-chevron shrink-0 text-amber-300">&rarr;</span>
                        </a>
                    </li>
                @empty
                    <li class="col-span-full text-xs text-emerald-100">Halaman transparansi belum tersedia.</li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- Seksi 5: Penutup Terpadu — Tautan Instansi (5) | ACO CCTV & Sosial Media (4) | Aksesibilitas WCAG (3) --}}
    <section aria-label="Tautan Instansi, CCTV Online, Media Sosial, dan Aksesibilitas" class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:gap-9 lg:px-8 lg:py-14">
            @if ($hasTautan)
                <div @class([
                    'lg:col-span-5' => $hasMidColumn,
                    'lg:col-span-8' => ! $hasMidColumn,
                ])>
                    <p class="font-mono text-2xs font-bold tracking-wider text-primary uppercase">
                        Jejaring Kelembagaan
                    </p>
                    <h2 class="mt-1 border-b border-hairline pb-3 font-serif text-xl font-bold text-ink">
                        Tautan Instansi Terkait
                    </h2>

                    <ul class="mt-1 divide-y divide-hairline/80">
                        @foreach ($relatedLinks as $relLink)
                            <li>
                                <a
                                    href="{{ $relLink['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group flex min-h-13 items-center justify-between gap-3 py-2.5 transition-colors"
                                >
                                    <span class="flex min-w-0 items-center gap-3">
                                        <span class="inline-flex h-8 min-w-16 shrink-0 items-center justify-center rounded-md bg-primary-soft px-2 font-mono text-[10px] font-bold tracking-tight text-primary-deep uppercase">
                                            {{ $relLink['code'] }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold text-ink group-hover:text-primary sm:text-[13px]">
                                                {{ $relLink['title'] }}
                                            </span>
                                            @if ($relLink['subtitle'] !== '')
                                                <span class="block truncate text-[11px] text-ink-muted">
                                                    {{ $relLink['subtitle'] }}
                                                </span>
                                            @endif
                                        </span>
                                    </span>
                                    <span aria-hidden="true" class="shrink-0 text-xs font-bold text-primary">&nearr;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($hasMidColumn)
                <div @class([
                    'space-y-5',
                    'lg:col-span-4' => $hasTautan,
                    'lg:col-span-8' => ! $hasTautan,
                ])>
                    @if ($safeCctvUrl)
                        <div class="rounded-2xl bg-obsidian p-5 text-white">
                            <div class="flex items-start gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-primary-deep text-amber-300" aria-hidden="true">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.5 14.5 5l3 6.5-11.5 3.5L3 8.5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m17.5 11.5 3.5-1.1v3.2l-3.5-1.1" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.5V19h4.5" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-mono text-[10px] font-bold tracking-wider text-amber-300 uppercase">
                                        Pengawasan Layanan Publik
                                    </p>
                                    <h2 class="mt-0.5 font-serif text-lg font-bold text-white">{{ $cctvLabel }}</h2>
                                </div>
                            </div>
                            <p class="mt-2.5 text-xs leading-relaxed text-emerald-100">
                                Pantau ruang tunggu dan meja PTSP {{ $satker?->nama_satker ?? 'Pengadilan' }} secara daring melalui portal resmi ACO Ditjen Badilag.
                            </p>
                            <a
                                href="{{ $safeCctvUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pub-btn mt-4 inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-gold px-4 text-xs font-bold text-ink hover:opacity-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                            >
                                <span>Buka Portal ACO Badilag</span>
                                <span aria-hidden="true">&nearr;</span>
                            </a>
                        </div>
                    @endif

                    @if ($socialChannels !== [])
                        <div>
                            <h2 class="font-serif text-lg font-bold text-ink">Sosial Media Resmi</h2>
                            <ul class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($socialChannels as $soc)
                                    <li>
                                        <a
                                            href="{{ $soc['url'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="{{ $soc['description'] }}"
                                            class="pub-link-card flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl border border-hairline bg-ivory px-1.5 py-2 text-center text-[11px] font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                                        >
                                            <span class="text-primary" aria-hidden="true">
                                                @if ($soc['key'] === 'facebook')
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-8.2h2.8l.4-3.2h-3.2V7.5c0-.9.3-1.6 1.6-1.6h1.7V3.1c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.2v2.4H7.5v3.2h2.7V21h3.3Z"/></svg>
                                                @elseif ($soc['key'] === 'instagram')
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                                                @elseif ($soc['key'] === 'tiktok')
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 5.8A4.4 4.4 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-1.8-2.5V9.7a5.7 5.7 0 1 0 4.9 5.7V9.2a7.4 7.4 0 0 0 4.3 1.4V7.5a4.4 4.4 0 0 1-3.2-1.7Z"/></svg>
                                                @elseif ($soc['key'] === 'youtube')
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21.6 7.2a2.7 2.7 0 0 0-1.9-1.9C18 4.8 12 4.8 12 4.8s-6 0-7.7.5a2.7 2.7 0 0 0-1.9 1.9C2 8.9 2 12 2 12s0 3.1.4 4.8a2.7 2.7 0 0 0 1.9 1.9c1.7.5 7.7.5 7.7.5s6 0 7.7-.5a2.7 2.7 0 0 0 1.9-1.9c.4-1.7.4-4.8.4-4.8s0-3.1-.4-4.8ZM10 15.2V8.8l5.3 3.2-5.3 3.2Z"/></svg>
                                                @else
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.2 3h3l-6.6 7.5L22.4 21h-6.1l-4.8-6.3L6 21H3l7.1-8.1L2.6 3h6.2l4.3 5.7L18.2 3Zm-1.1 16.2h1.7L7.9 4.7H6.1l11 14.5Z"/></svg>
                                                @endif
                                            </span>
                                            <span class="truncate">{{ $soc['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Col 3 (3 Cols, or 4 Cols when one of left/mid empty): Portal Ramah Disabilitas WCAG --}}
            <div @class([
                'flex flex-col justify-between rounded-2xl border border-emerald-200/80 bg-primary-soft p-5 sm:p-6',
                'lg:col-span-3' => $hasTautan && $hasMidColumn,
                'lg:col-span-4' => $hasTautan xor $hasMidColumn,
                'lg:col-span-12' => ! $hasTautan && ! $hasMidColumn,
            ])>
                <div>
                    <p class="font-mono text-[10px] font-bold tracking-wider text-primary uppercase">
                        Aksesibilitas WCAG 2.2 AA
                    </p>
                    <h2 class="mt-1 font-serif text-xl font-bold text-primary-deep">
                        Portal Ramah Disabilitas
                    </h2>
                    <p class="mt-2 text-xs leading-relaxed text-ink-body">
                        Tersedia pembaca suara Bahasa Indonesia (Web Speech API id-ID), perbesar teks hingga 200%, kontras tinggi, serta navigasi keyboard penuh.
                    </p>
                </div>

                <div class="mt-5 space-y-2">
                    <button
                        type="button"
                        data-open-a11y
                        class="pub-btn inline-flex min-h-11 w-full cursor-pointer items-center justify-center rounded-lg bg-primary px-4 text-xs font-bold text-white hover:bg-primary-hover"
                    >
                        Buka Widget Aksesibilitas
                    </button>
                    <a
                        href="{{ route('public.accessibility') }}"
                        class="pub-btn inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-primary bg-white px-4 text-xs font-bold text-primary hover:bg-ivory"
                    >
                        Panduan Aksesibilitas
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
