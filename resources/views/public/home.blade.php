@php
    $firstSlide = $sliders->first();
    $firstSlideImage = $firstSlide && ! empty($firstSlide->image_path) ? $firstSlide->image_path : null;
    $homeOgImage = $firstSlideImage ? asset('storage/' . $firstSlideImage) : null;
    $heroSizes = '(max-width: 1024px) calc(100vw - 32px), 720px';
    $firstSlideImgAttrs = $firstSlideImage
        ? \App\Services\ImageOptimizerService::responsiveAttributes($firstSlideImage, [480, 800], $heroSizes, null, 1280)
        : null;
    $homeDescription = $satker
        ? "Portal Resmi {$satker->nama_satker}. Layanan perkara SIPP, jadwal sidang harian, e-Court, Prodeo & Posbakum, serta transparansi peradilan."
        : null;
    $transparentPixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
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

    <!-- Band 1: Obsidian-Emerald Editorial Hero Showcase Stage -->
    <section
        aria-label="Sorotan Utama Portal Resmi"
        class="pub-hero-grid-pattern relative overflow-hidden border-b-4 border-gold bg-obsidian text-white"
    >
        <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8 lg:py-10">
            @if ($sliders->isNotEmpty())
                @php
                    $defaultSlideSubtitle = 'Informasi resmi, layanan perkara elektronik, dan transparansi peradilan pada ' . ($satker?->nama_satker ?? 'Portal Resmi Peradilan Agama') . '.';
                @endphp
                <div
                    data-hero-slider
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Informasi Utama Satuan Kerja"
                    tabindex="0"
                    class="relative focus:outline-none"
                >
                    <div class="grid items-center gap-7 lg:grid-cols-12 lg:gap-10">
                        <!-- Left 5 Columns: Persistent Editorial Header, Active Slide Copy & Stable Quick Search -->
                        <div class="flex flex-col justify-between space-y-5 lg:col-span-5">
                            <div class="space-y-3.5">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="inline-flex items-center rounded-md bg-gold px-2.5 py-1 font-mono text-2xs font-bold tracking-wide text-ink uppercase">
                                        {{ $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama' }}
                                    </span>
                                    @if ($sliders->count() > 1)
                                        <span data-slider-counter class="font-mono text-xs font-semibold text-emerald-200">
                                            01 / {{ str_pad((string) $sliders->count(), 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                    @endif
                                </div>

                                <div aria-live="polite" aria-atomic="false" class="min-h-[150px] sm:min-h-[168px]">
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
                                            class="space-y-3"
                                        >
                                            @if ($slideIndex === 0)
                                                <h1 class="font-serif text-2xl leading-[1.18] font-bold tracking-tight text-white sm:text-3xl lg:text-[2.15rem]">
                                                    {{ $slide->title }}
                                                </h1>
                                            @else
                                                <h2 class="font-serif text-2xl leading-[1.18] font-bold tracking-tight text-white sm:text-3xl lg:text-[2.15rem]">
                                                    {{ $slide->title }}
                                                </h2>
                                            @endif

                                            <p class="max-w-lg text-sm leading-relaxed text-emerald-100/95">
                                                {{ filled($slide->subtitle) ? $slide->subtitle : $defaultSlideSubtitle }}
                                            </p>

                                            <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                                @if ($slide->cta_label && $ctaTarget)
                                                    <a
                                                        href="{{ $ctaTarget }}"
                                                        class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-lg bg-gold px-4.5 py-2 text-xs font-bold text-ink shadow-xs hover:opacity-95"
                                                    >
                                                        <span>{{ $slide->cta_label }}</span>
                                                        <span aria-hidden="true">&rarr;</span>
                                                    </a>
                                                @endif
                                                <a
                                                    href="{{ route('public.page', ['slug' => $isProdeoCta ? 'layanan-hukum/jadwal-sidang' : 'layanan-hukum/hak-pencari-keadilan']) }}"
                                                    class="pub-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-white/30 bg-white/8 px-4 py-2 text-xs font-semibold text-white backdrop-blur-xs hover:border-white/50 hover:bg-white/15"
                                                >
                                                    <span>{{ $isProdeoCta ? 'Jadwal Sidang Hari Ini' : 'Panduan Prodeo & Posbakum' }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Persistent Citizen Quick-Find Bar (Never flickers on slide rotation) -->
                            <div class="border-t border-white/12 pt-4">
                                <form action="{{ route('public.search') }}" method="GET" role="search" class="flex flex-col gap-2 rounded-xl border border-white/15 bg-black/20 p-1.5 backdrop-blur-xs sm:flex-row sm:items-center">
                                    <label for="hero-search-input" class="sr-only">Cari layanan perkara, jadwal sidang, atau dokumen resmi</label>
                                    <div class="relative flex flex-1 items-center">
                                        <svg class="pointer-events-none absolute left-3 size-4 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <path stroke-linecap="round" d="m21 21-4.35-4.35"></path>
                                        </svg>
                                        <input
                                            id="hero-search-input"
                                            type="search"
                                            name="q"
                                            minlength="2"
                                            required
                                            placeholder="Cari jadwal sidang, Prodeo, LKjIP, DIPA..."
                                            class="min-h-11 w-full rounded-lg border-0 bg-white py-2 pr-3 pl-9 text-xs text-ink placeholder:text-ink-muted focus:ring-2 focus:ring-gold focus:outline-none"
                                        />
                                    </div>
                                    <button type="submit" class="pub-btn min-h-11 shrink-0 cursor-pointer rounded-lg bg-gold px-4.5 text-xs font-bold text-ink hover:opacity-95">
                                        Cari Informasi
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Right 7 Columns: Framed 16:9 Showcase Stage + Edge Arrows + Progress Bar -->
                        <div class="lg:col-span-7">
                            <div class="group relative overflow-hidden rounded-2xl border border-white/15 bg-[#011c14] shadow-[0_24px_56px_-18px_rgba(0,0,0,0.6)] ring-1 ring-gold/20">
                                <div class="relative aspect-[16/9] w-full overflow-hidden bg-[#011c14]">
                                    @foreach ($sliders as $slideIndex => $slide)
                                        <div
                                            data-hero-media-slide
                                            @if ($slideIndex !== 0) hidden @endif
                                            class="h-full w-full"
                                        >
                                            @if ($slide->image_path)
                                                @php
                                                    $slideImg = \App\Services\ImageOptimizerService::responsiveAttributes($slide->image_path, [480, 800], $heroSizes, null, 1280);
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
                                                @if ($slideIndex === 0)
                                                    <div data-hero-3d-stage class="relative flex h-full w-full items-center justify-center bg-obsidian/70">
                                                        <div data-hero-3d-fallback aria-hidden="true" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center transition-opacity duration-500">
                                                            <svg class="pub-crest-float size-36 text-gold/80 sm:size-44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M12 3v18" />
                                                                <path d="M5 7h14" />
                                                                <path d="M8 21h8" />
                                                                <path d="M5 7l-3 6h6l-3-6z" />
                                                                <path d="M19 7l-3 6h6l-3-6z" />
                                                            </svg>
                                                            <span class="mt-2 font-mono text-2xs font-bold tracking-wider text-emerald-200 uppercase">Peradilan Agama Terbuka</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div aria-hidden="true" class="flex h-full w-full items-center justify-center bg-obsidian/70">
                                                        <svg class="pub-crest-float size-36 text-amber-300/75" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                                            <path d="M12 2 3 7l9 5 9-5-9-5z"></path>
                                                            <path d="M3 12l9 5 9-5"></path>
                                                            <path d="M3 17l9 5 9-5"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if ($sliders->count() > 1)
                                    <!-- Subtle Edge Arrow Controls (Desktop / Tablet) -->
                                    <button
                                        type="button"
                                        data-slider-prev
                                        aria-label="Slide sebelumnya"
                                        class="pub-btn absolute top-1/2 left-3 z-10 hidden size-11 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/25 bg-obsidian/70 text-white shadow-md backdrop-blur-md hover:border-gold hover:bg-obsidian/90 hover:text-amber-300 sm:inline-flex"
                                    >
                                        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        data-slider-next
                                        aria-label="Slide berikutnya"
                                        class="pub-btn absolute top-1/2 right-3 z-10 hidden size-11 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/25 bg-obsidian/70 text-white shadow-md backdrop-blur-md hover:border-gold hover:bg-obsidian/90 hover:text-amber-300 sm:inline-flex"
                                    >
                                        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <!-- Thin Gold Autoplay Progress Bar -->
                                    <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-[3px] bg-white/15">
                                        <div data-slider-progress class="pub-slider-progress-bar h-full w-full bg-gold"></div>
                                    </div>
                                @endif
                            </div>

                            @if ($sliders->count() > 1)
                                <!-- Minimalist Capsule Dock beneath Showcase -->
                                <div class="mt-3.5 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex min-w-0 items-center gap-1.5">
                                        @foreach ($sliders as $dotIndex => $dotSlide)
                                            <button
                                                type="button"
                                                data-slider-dot="{{ $dotIndex }}"
                                                aria-label="Pilih slide {{ $dotIndex + 1 }}: {{ $dotSlide->title }}"
                                                aria-current="{{ $dotIndex === 0 ? 'true' : 'false' }}"
                                                class="group/dot inline-flex min-h-11 cursor-pointer items-center justify-center px-1 focus:outline-none"
                                            >
                                                <span
                                                    data-slider-pill
                                                    @class([
                                                        'block h-2 rounded-full transition-all duration-300',
                                                        'w-8 bg-gold' => $dotIndex === 0,
                                                        'w-2.5 bg-white/30 group-hover/dot:bg-white/65' => $dotIndex !== 0,
                                                    ])
                                                ></span>
                                            </button>
                                        @endforeach
                                        <span data-slider-caption class="ml-2 hidden max-w-[260px] truncate font-mono text-2xs text-emerald-100/80 md:inline">
                                            {{ $firstSlide?->title }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            data-slider-prev
                                            aria-label="Slide sebelumnya"
                                            class="pub-btn inline-flex size-10 cursor-pointer items-center justify-center rounded-lg border border-white/25 bg-white/8 text-white hover:bg-white/15 sm:hidden"
                                        >
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            data-slider-next
                                            aria-label="Slide berikutnya"
                                            class="pub-btn inline-flex size-10 cursor-pointer items-center justify-center rounded-lg border border-white/25 bg-white/8 text-white hover:bg-white/15 sm:hidden"
                                        >
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            data-slider-pause
                                            aria-pressed="false"
                                            class="pub-btn inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg border border-white/25 bg-white/8 px-3 text-xs font-semibold text-emerald-50 hover:border-white/45 hover:bg-white/15"
                                        >
                                            <svg data-slider-pause-icon class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" />
                                            </svg>
                                            <span data-slider-pause-label>Jeda</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="grid items-center gap-8 lg:grid-cols-12">
                    <div class="space-y-4 lg:col-span-8">
                        <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Portal Resmi</span>
                        <h1 class="font-serif text-3xl font-bold text-white sm:text-4xl">{{ $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama' }}</h1>
                        <p class="max-w-2xl text-sm text-emerald-100 sm:text-base">Pelayanan informasi peradilan yang terbuka, cepat, transparan, dan mudah diakses pencari keadilan.</p>
                    </div>
                    <div data-hero-3d-stage class="relative hidden h-64 items-center justify-center rounded-xl border border-white/15 bg-obsidian/50 lg:col-span-4 lg:flex">
                        <div data-hero-3d-fallback aria-hidden="true" class="flex flex-col items-center justify-center transition-opacity duration-500">
                            <svg class="size-32 text-gold/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Band 2: 3-Channel Citizen Pathway Strip + Custom 2-Click Shortcut Rail -->
        <section aria-label="Akses Layanan Cepat Pencari Keadilan" class="space-y-4">
            <div data-gsap-stagger class="grid gap-4 md:grid-cols-3">
                <a
                    data-gsap-item
                    href="{{ route('public.page', ['slug' => 'layanan-hukum/sipp']) }}"
                    class="pub-card group flex flex-col justify-between rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs hover:border-primary"
                >
                    <div>
                        <span class="rounded bg-primary-soft px-2.5 py-0.5 font-mono text-2xs font-bold text-primary uppercase">Saluran 01 &middot; SIPP</span>
                        <h2 class="mt-2.5 font-serif text-lg font-bold text-ink group-hover:text-primary">Penelusuran Perkara</h2>
                        <p class="mt-1 text-xs leading-relaxed text-ink-body">Cek nomor perkara, tahapan sidang, riwayat mediasi, dan status putusan pengadilan.</p>
                    </div>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-primary">
                        <span>Buka Penelusuran SIPP</span>
                        <span aria-hidden="true" class="pub-chevron">&rarr;</span>
                    </span>
                </a>

                <a
                    data-gsap-item
                    href="{{ route('public.page', ['slug' => 'layanan-hukum/ecourt']) }}"
                    class="pub-card group flex flex-col justify-between rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-5 shadow-2xs hover:border-primary"
                >
                    <div>
                        <span class="rounded bg-amber-100 px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Saluran 02 &middot; e-Court</span>
                        <h2 class="mt-2.5 font-serif text-lg font-bold text-ink group-hover:text-primary">Pendaftaran Perkara Online</h2>
                        <p class="mt-1 text-xs leading-relaxed text-ink-body">Layanan e-Filing, e-Payment, panggilan elektronik, dan persidangan e-Litigation MARI.</p>
                    </div>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-primary">
                        <span>Akses Portal e-Court</span>
                        <span aria-hidden="true" class="pub-chevron">&rarr;</span>
                    </span>
                </a>

                <a
                    data-gsap-item
                    href="{{ route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']) }}"
                    class="pub-card group flex flex-col justify-between rounded-xl border border-hairline border-t-4 border-t-obsidian bg-white p-5 shadow-2xs hover:border-primary"
                >
                    <div>
                        <span class="rounded bg-primary-soft px-2.5 py-0.5 font-mono text-2xs font-bold text-primary-deep uppercase">Saluran 03 &middot; Bantuan Hukum</span>
                        <h2 class="mt-2.5 font-serif text-lg font-bold text-ink group-hover:text-primary">Prodeo &amp; Posbakum Gratis</h2>
                        <p class="mt-1 text-xs leading-relaxed text-ink-body">Pembebasan biaya perkara bagi masyarakat kurang mampu serta layanan konsultasi hukum gratis.</p>
                    </div>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-primary">
                        <span>Baca Panduan &amp; Syarat</span>
                        <span aria-hidden="true" class="pub-chevron">&rarr;</span>
                    </span>
                </a>
            </div>

            <!-- 2-Click Dynamic Shortcuts Rail -->
            <div aria-label="Akses Layanan Cepat" class="rounded-xl border border-hairline bg-white p-4 shadow-2xs">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-2.5">
                    <h2 class="font-serif text-sm font-bold text-ink">Akses Layanan Cepat (Maksimal 2 Klik)</h2>
                    <span class="font-mono text-2xs text-ink-muted">Jalur Pintas Portal</span>
                </div>
                <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($shortcuts as $shortcut)
                        <li>
                            <a
                                href="{{ $shortcut['url'] }}"
                                class="pub-link-card flex min-h-11 items-center justify-between gap-2 rounded-lg border border-hairline bg-ivory px-3 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span class="truncate">{{ $shortcut['label'] }}</span>
                                <span aria-hidden="true" class="pub-chevron shrink-0 text-primary">&rarr;</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <!-- Band 3: Asymmetric 5/7 Operational Hub (Agenda Persidangan & Jam PTSP | Service Ledger 01-06) -->
        <section aria-label="Pusat Operasional Persidangan dan Layanan Hukum" class="grid gap-6 lg:grid-cols-12">
            <!-- Left 5 Cols: Agenda Persidangan + PTSP Hours -->
            <div class="space-y-5 lg:col-span-5">
                <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between border-b border-hairline pb-3">
                        <div>
                            <span class="font-mono text-2xs font-bold text-primary uppercase">Kepaniteraan</span>
                            <h2 class="font-serif text-lg font-bold text-ink">
                                {{ $hasUpcomingSchedules ? 'Agenda Persidangan Terjadwal' : 'Agenda Persidangan Terbaru' }}
                            </h2>
                        </div>
                        <a href="{{ route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']) }}" class="text-xs font-semibold text-primary hover:underline">
                            Semua Sidang &rarr;
                        </a>
                    </div>

                    @if ($todaySchedules->isEmpty())
                        <div class="mt-4 rounded-lg border border-hairline bg-ivory p-4 text-xs text-ink-muted">
                            Jadwal persidangan lokal belum dicatat. Masyarakat dapat memeriksa agenda terbaru langsung melalui tautan SIPP satuan kerja.
                        </div>
                    @else
                        <ul class="mt-3 divide-y divide-hairline">
                            @foreach ($todaySchedules as $sch)
                                <li class="py-3 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono font-bold text-primary-deep">{{ $sch->nomor_perkara }}</span>
                                        <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-2xs font-semibold text-primary">
                                            {{ \App\Support\Tanggal::format($sch->tanggal_sidang, 'd M Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-ink-body">
                                        {{ $sch->agenda ?: 'Persidangan Sesuai Jadwal' }}
                                        @if ($sch->ruang_sidang)
                                            &middot; Ruang: <strong>{{ $sch->ruang_sidang }}</strong>
                                        @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- PTSP Service Hours Card -->
                @php
                    $sh = config('satker.service_hours', []);
                    $fmtH = static fn (string $v): string => str_replace(':', '.', $v);
                    $shTz = (string) ($sh['timezone_label'] ?? 'WITA');
                @endphp
                <div class="pub-reveal rounded-xl border border-emerald-200 bg-primary-soft/80 p-5 text-xs shadow-2xs">
                    <h2 class="font-serif text-base font-bold text-primary-deep">Jam Pelayanan Pengadilan (PTSP)</h2>
                    <dl class="mt-2.5 space-y-1.5 font-mono text-2xs text-ink-body">
                        <div class="flex justify-between gap-2 border-b border-emerald-200/70 pb-1">
                            <dt class="font-semibold text-ink">Senin - Kamis</dt>
                            <dd>{{ $fmtH((string) ($sh['mon_thu_open'] ?? '08:00')) }} - {{ $fmtH((string) ($sh['mon_thu_close'] ?? '16:30')) }} {{ $shTz }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-emerald-200/70 pb-1">
                            <dt class="font-semibold text-ink">Jumat</dt>
                            <dd>{{ $fmtH((string) ($sh['fri_open'] ?? '08:00')) }} - {{ $fmtH((string) ($sh['fri_close'] ?? '17:00')) }} {{ $shTz }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="font-semibold text-ink">Sabtu - Minggu</dt>
                            <dd>Libur Nasional / Tutup</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Right 7 Cols: Editorial Service Ledger (01 - 06) -->
            <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs lg:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-3">
                    <div>
                        <span class="font-mono text-2xs font-bold text-primary uppercase">Pelayanan Terpadu Satu Pintu</span>
                        <h2 class="font-serif text-xl font-bold text-ink">Informasi Layanan Hukum &amp; Kepaniteraan</h2>
                    </div>
                    <a href="{{ route('public.page', ['slug' => 'layanan-publik/standar-maklumat-pelayanan']) }}" class="text-xs font-semibold text-primary hover:underline">
                        Standar Pelayanan &rarr;
                    </a>
                </div>

                <ul class="mt-3 divide-y divide-hairline">
                    @forelse ($servicePages as $sPage)
                        <li>
                            <a
                                href="{{ route('public.page', ['slug' => $sPage->slug]) }}"
                                class="pub-link-card flex min-h-12 items-center justify-between gap-3 rounded-lg px-2 py-2.5 text-sm font-semibold text-ink hover:bg-primary-soft hover:text-primary"
                            >
                                <span class="flex items-center gap-3">
                                    <span class="inline-flex size-7 shrink-0 items-center justify-center rounded border border-emerald-200 bg-primary-soft font-mono text-2xs font-bold text-primary-deep">
                                        {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span>{{ $sPage->title }}</span>
                                </span>
                                <span aria-hidden="true" class="pub-chevron shrink-0 text-primary">&rarr;</span>
                            </a>
                        </li>
                    @empty
                        <li class="py-6 text-xs text-ink-muted">Halaman layanan hukum belum tersedia.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <!-- Band 3B: Indeks Survei Pelayanan (SKM, SPAK, SPKP) & Media Layanan Publik (Pedoman Pengawasan A-6) -->
        @php
            $safeSkmHomeUrl = \App\Support\SafeHtml::cleanUrl($satker?->skm_url);
            $safeVideoProfilUrl = \App\Support\SafeHtml::cleanUrl($satker?->video_profil_url);
            $safeVideoPtspUrl = \App\Support\SafeHtml::cleanUrl($satker?->video_ptsp_url);
            $safeVideoKebijakanUrl = \App\Support\SafeHtml::cleanUrl($satker?->video_kebijakan_ma_url);
        @endphp
        <section aria-label="Indeks Survei Pelayanan dan Media Informasi Layanan" class="grid gap-6 lg:grid-cols-12">
            <!-- Indeks SKM, SPAK, SPKP & SI SURTI (A-6-6 & A-6-7) -->
            <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs lg:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-3">
                    <div>
                        <span class="font-mono text-2xs font-bold text-primary uppercase">Survei Pelayanan Masyarakat (SI SURTI)</span>
                        <h2 class="font-serif text-xl font-bold text-ink">Indeks Kepuasan &amp; Integritas Layanan</h2>
                    </div>
                    @if ($satker?->survei_periode)
                        <span class="rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink">
                            {{ $satker->survei_periode }}
                        </span>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-hairline bg-ivory p-4">
                        <p class="font-mono text-2xs font-bold text-ink-muted uppercase">IKM / SKM</p>
                        <p class="mt-1 font-serif text-2xl font-bold text-primary-deep">
                            {{ $satker?->nilai_skm ?: 'Tersedia' }}
                        </p>
                        <p class="mt-1 text-2xs text-ink-muted">Indeks Kepuasan Masyarakat</p>
                    </div>
                    <div class="rounded-lg border border-hairline bg-ivory p-4">
                        <p class="font-mono text-2xs font-bold text-ink-muted uppercase">IPAK / SPAK</p>
                        <p class="mt-1 font-serif text-2xl font-bold text-primary-deep">
                            {{ $satker?->nilai_spak ?: 'Tersedia' }}
                        </p>
                        <p class="mt-1 text-2xs text-ink-muted">Indeks Persepsi Anti Korupsi</p>
                    </div>
                    <div class="rounded-lg border border-hairline bg-ivory p-4">
                        <p class="font-mono text-2xs font-bold text-ink-muted uppercase">IPKL / SPKP</p>
                        <p class="mt-1 font-serif text-2xl font-bold text-primary-deep">
                            {{ $satker?->nilai_spkp ?: 'Tersedia' }}
                        </p>
                        <p class="mt-1 text-2xs text-ink-muted">Indeks Persepsi Kualitas Layanan</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2.5">
                    <a
                        href="{{ $safeSkmHomeUrl ?? route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']) }}"
                        @if ($safeSkmHomeUrl) target="_blank" rel="noopener noreferrer" @endif
                        class="pub-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-primary px-4 text-xs font-bold text-white hover:bg-primary-hover"
                    >
                        <span>Isi Survei SI SURTI</span>
                        <span aria-hidden="true">{{ $safeSkmHomeUrl ? '↗' : '→' }}</span>
                    </a>
                    <a
                        href="{{ route('public.page', ['slug' => 'layanan-publik/survei-pelayanan']) }}"
                        class="pub-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-primary bg-white px-4 text-xs font-bold text-primary hover:bg-primary-soft"
                    >
                        <span>Lihat Laporan SKM, SPAK &amp; SPKP</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Tautan Video Profil, Video PTSP & Infografis Kebijakan MA (A-6-14 s.d. A-6-16) -->
            <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs lg:col-span-5" style="--stagger-delay: 50ms">
                <div class="border-b border-hairline pb-3">
                    <span class="font-mono text-2xs font-bold text-primary uppercase">Media &amp; Sosialisasi Kebijakan</span>
                    <h2 class="font-serif text-xl font-bold text-ink">Video Layanan &amp; Infografis MA</h2>
                </div>

                <ul class="mt-3 space-y-2.5">
                    <li>
                        <a
                            href="{{ $safeVideoProfilUrl ?? route('public.page', ['slug' => 'profil/visi-misi']) }}"
                            @if ($safeVideoProfilUrl) target="_blank" rel="noopener noreferrer" @endif
                            class="pub-link-card flex min-h-12 items-center justify-between gap-3 rounded-lg border border-hairline bg-ivory px-3.5 py-2.5 hover:border-primary hover:bg-primary-soft"
                        >
                            <span class="min-w-0">
                                <span class="block text-xs font-bold text-ink">Video Profil Pengadilan</span>
                                <span class="block text-2xs text-ink-muted">Gambaran tugas, layanan, dan profil resmi satuan kerja</span>
                            </span>
                            <span aria-hidden="true" class="pub-chevron shrink-0 font-bold text-primary">{{ $safeVideoProfilUrl ? '↗' : '→' }}</span>
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ $safeVideoPtspUrl ?? route('public.page', ['slug' => 'layanan-publik/fasilitas-dan-ptsp']) }}"
                            @if ($safeVideoPtspUrl) target="_blank" rel="noopener noreferrer" @endif
                            class="pub-link-card flex min-h-12 items-center justify-between gap-3 rounded-lg border border-hairline bg-ivory px-3.5 py-2.5 hover:border-primary hover:bg-primary-soft"
                        >
                            <span class="min-w-0">
                                <span class="block text-xs font-bold text-ink">Video Pelayanan Terpadu (PTSP)</span>
                                <span class="block text-2xs text-ink-muted">Alur pelayanan meja PTSP dan fasilitas publik pengadilan</span>
                            </span>
                            <span aria-hidden="true" class="pub-chevron shrink-0 font-bold text-primary">{{ $safeVideoPtspUrl ? '↗' : '→' }}</span>
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ $safeVideoKebijakanUrl ?? route('public.posts', ['kategori' => 'infografis']) }}"
                            @if ($safeVideoKebijakanUrl) target="_blank" rel="noopener noreferrer" @endif
                            class="pub-link-card flex min-h-12 items-center justify-between gap-3 rounded-lg border border-hairline bg-ivory px-3.5 py-2.5 hover:border-primary hover:bg-primary-soft"
                        >
                            <span class="min-w-0">
                                <span class="block text-xs font-bold text-ink">Infografis &amp; Kebijakan Terbaru MA</span>
                                <span class="block text-2xs text-ink-muted">Sosialisasi e-Court, Gugatan Sederhana, dan layanan peradilan</span>
                            </span>
                            <span aria-hidden="true" class="pub-chevron shrink-0 font-bold text-primary">{{ $safeVideoKebijakanUrl ? '↗' : '→' }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Band 4: Berita Magazine Cards & Pengumuman Timeline (with Views & Comments Counts) -->
        <section aria-label="Berita dan Pengumuman" class="grid gap-6 lg:grid-cols-12">
            <div class="pub-reveal rounded-xl border border-hairline bg-white p-6 shadow-2xs lg:col-span-7" style="--stagger-delay: 40ms">
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h2 class="font-serif text-xl font-bold text-ink">Berita &amp; Inovasi Layanan</h2>
                    <a href="{{ route('public.posts') }}" class="text-xs font-semibold text-primary hover:underline">
                        Semua Berita &rarr;
                    </a>
                </div>

                @if ($latestNews->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada berita pelayanan yang diterbitkan.</p>
                @else
                    <div data-gsap-stagger class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($latestNews as $news)
                            <article data-gsap-item class="pub-card flex flex-col rounded-lg border border-hairline bg-ivory p-4">
                                @if ($news->featured_image_path)
                                    @php
                                        $newsImg = \App\Services\ImageOptimizerService::responsiveAttributes($news->featured_image_path, [400, 480, 720], '(max-width: 640px) calc(100vw - 64px), 340px', null, 1000);
                                    @endphp
                                    <div class="mb-3 overflow-hidden rounded-md">
                                        <img
                                            src="{{ $newsImg['src'] }}"
                                            @if ($newsImg['srcset']) srcset="{{ $newsImg['srcset'] }}" sizes="{{ $newsImg['sizes'] }}" @endif
                                            alt="{{ $news->featured_image_alt ?: $news->title }}"
                                            width="360"
                                            height="200"
                                            loading="lazy"
                                            decoding="async"
                                            class="pub-img-zoom h-36 w-full object-cover"
                                        />
                                    </div>
                                @else
                                    <div aria-hidden="true" class="mb-3 flex h-24 items-center justify-between overflow-hidden rounded-md border border-emerald-900/15 bg-gradient-to-r from-obsidian to-primary px-4 text-white">
                                        <div>
                                            <span class="block font-mono text-2xs font-bold text-amber-300 uppercase">Publikasi Resmi</span>
                                            <span class="font-serif text-xs font-semibold text-emerald-50">{{ $satker?->nama_satker ?? 'Peradilan Agama' }}</span>
                                        </div>
                                        <svg class="size-10 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                                        </svg>
                                    </div>
                                @endif
                                <span class="w-fit rounded bg-primary-soft px-2 py-0.5 text-2xs font-semibold text-primary">
                                    {{ \App\Models\Post::categoryLabels()[$news->category] ?? $news->category }}
                                </span>
                                <h3 class="mt-2 font-serif text-base font-bold text-ink">
                                    <a href="{{ route('public.posts.show', ['slug' => $news->slug]) }}" class="hover:text-primary hover:underline">
                                        {{ $news->title }}
                                    </a>
                                </h3>
                                @if ($news->excerpt && trim(strip_tags($news->excerpt)) !== '')
                                    <p class="mt-1.5 text-xs leading-relaxed text-ink-body">{{ \Illuminate\Support\Str::limit(trim(strip_tags($news->excerpt)), 105) }}</p>
                                @endif
                                <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-hairline/70 pt-3 font-mono text-2xs text-ink-muted">
                                    <span>{{ \App\Support\Tanggal::format($news->published_at ?? $news->created_at, 'd M Y') }}</span>
                                    <span class="flex items-center gap-2.5">
                                        <span>{{ number_format((int) ($news->views_count ?? 0)) }} dilihat</span>
                                        <span>{{ number_format((int) ($news->approved_comments_count ?? 0)) }} komentar</span>
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pub-reveal rounded-xl border border-hairline bg-white p-6 shadow-2xs lg:col-span-5" style="--stagger-delay: 85ms">
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h2 class="font-serif text-xl font-bold text-ink">Pengumuman Resmi</h2>
                    <a href="{{ route('public.posts', ['kategori' => 'pengumuman']) }}" class="text-xs font-semibold text-primary hover:underline">
                        Arsip Pengumuman &rarr;
                    </a>
                </div>

                @if ($announcements->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada pengumuman resmi yang diterbitkan.</p>
                @else
                    <ul class="mt-4 divide-y divide-hairline">
                        @foreach ($announcements as $ann)
                            @php $annDate = $ann->published_at ?? $ann->created_at; @endphp
                            <li class="flex items-start gap-3 py-3">
                                <div class="flex w-14 shrink-0 flex-col items-center rounded-md border border-emerald-200 bg-primary-soft px-1.5 py-1.5 text-center">
                                    <span class="font-mono text-sm leading-none font-bold text-primary-deep">{{ $annDate?->format('d') ?? '--' }}</span>
                                    <span class="mt-0.5 font-mono text-2xs leading-tight font-semibold text-primary uppercase">{{ \App\Support\Tanggal::format($annDate, 'M Y') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('public.posts.show', ['slug' => $ann->slug]) }}" class="block text-sm font-semibold text-ink hover:text-primary hover:underline">
                                        {{ $ann->title }}
                                    </a>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-2xs text-ink-muted">
                                        <span>Diunggah: {{ \App\Support\Tanggal::format($annDate, 'd M Y') }}</span>
                                        <span>{{ number_format((int) ($ann->views_count ?? 0)) }} dilihat</span>
                                        <span>{{ number_format((int) ($ann->approved_comments_count ?? 0)) }} komentar</span>
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <!-- Band 5: Deep Obsidian SAKIP & Kinerja Showcase -->
        <section aria-label="Transparansi Kinerja dan Anggaran SAKIP" class="pub-reveal overflow-hidden rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-sm sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-white/15 pb-4">
                <div>
                    <span class="font-mono text-2xs font-bold tracking-wider text-amber-300 uppercase">Akuntabilitas Kinerja &amp; Anggaran (SAKIP)</span>
                    <h2 class="mt-1 font-serif text-xl font-bold text-white sm:text-2xl">
                        Transparansi Kinerja &amp; Anggaran (SAKIP)
                    </h2>
                </div>
                <a href="{{ route('public.sitemap') }}" class="text-xs font-semibold text-amber-300 hover:underline">
                    Lihat Semua Direktori Dokumen &rarr;
                </a>
            </div>

            <ul data-gsap-stagger class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($transparencyPages as $tPage)
                    <li data-gsap-item>
                        <a
                            href="{{ route('public.page', ['slug' => $tPage->slug]) }}"
                            class="pub-link-card flex min-h-12 items-center justify-between gap-2 rounded-lg border border-white/15 bg-primary-deep/80 px-4 py-3 text-xs font-semibold text-white hover:border-gold hover:bg-primary"
                        >
                            <span>{{ $tPage->title }}</span>
                            <span aria-hidden="true" class="pub-chevron text-amber-300">&rarr;</span>
                        </a>
                    </li>
                @empty
                    <li class="col-span-full text-xs text-emerald-100">Halaman transparansi belum tersedia.</li>
                @endforelse
            </ul>
        </section>

        <!-- Band 5B: Jejaring Tautan Instansi, Kanal Sosial Media & Access CCTV Online (ACO) -->
        @php
            $relatedLinks = $satker?->resolvedRelatedLinks() ?? [];
            $socialChannels = $satker?->socialChannels() ?? [];
            $safeCctvUrl = \App\Support\SafeHtml::cleanUrl($satker?->cctv_aco_url);
            $cctvLabel = $satker?->cctv_aco_label ?: 'Access CCTV Online (ACO)';
            $hasLeftTautan = $relatedLinks !== [];
            $hasRightChannels = $safeCctvUrl !== null || $socialChannels !== [];
        @endphp
        @if ($hasLeftTautan || $hasRightChannels)
            <section aria-label="Tautan Instansi, Media Sosial, dan CCTV Online" class="grid gap-6 lg:grid-cols-12">
                @if ($hasLeftTautan)
                    <div @class([
                        'pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs',
                        'lg:col-span-7' => $hasRightChannels,
                        'lg:col-span-12' => ! $hasRightChannels,
                    ])>
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-3">
                            <div>
                                <span class="font-mono text-2xs font-bold text-primary uppercase">Jejaring Kelembagaan</span>
                                <h2 class="font-serif text-xl font-bold text-ink">Tautan Instansi Terkait</h2>
                            </div>
                            <span class="font-mono text-2xs text-ink-muted">{{ count($relatedLinks) }} portal resmi</span>
                        </div>

                        <ul class="mt-4 divide-y divide-hairline">
                            @foreach ($relatedLinks as $relLink)
                                <li>
                                    <a
                                        href="{{ $relLink['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="pub-link-card group flex min-h-14 items-center justify-between gap-3 rounded-lg px-2.5 py-3 hover:bg-primary-soft/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                    >
                                        <span class="flex min-w-0 items-center gap-3.5">
                                            <span class="inline-flex h-10 min-w-16 shrink-0 items-center justify-center rounded-md border border-emerald-200 bg-primary-soft px-2 font-mono text-2xs font-bold tracking-tight text-primary-deep uppercase">
                                                {{ $relLink['code'] }}
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-bold text-ink group-hover:text-primary">{{ $relLink['title'] }}</span>
                                                @if ($relLink['subtitle'] !== '')
                                                    <span class="mt-0.5 block text-xs text-ink-muted">{{ $relLink['subtitle'] }}</span>
                                                @endif
                                            </span>
                                        </span>
                                        <span aria-hidden="true" class="pub-chevron shrink-0 text-xs font-bold text-primary">&nearr;</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($hasRightChannels)
                    <div @class([
                        'space-y-6',
                        'lg:col-span-5' => $hasLeftTautan,
                        'lg:col-span-12' => ! $hasLeftTautan,
                    ])>
                        @if ($safeCctvUrl)
                            <div class="pub-reveal overflow-hidden rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs">
                                <div class="flex items-start gap-4">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-primary-deep text-amber-300" aria-hidden="true">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.5 14.5 5l3 6.5-11.5 3.5L3 8.5Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m17.5 11.5 3.5-1.1v3.2l-3.5-1.1" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.5V19h4.5" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-mono text-2xs font-bold tracking-wider text-amber-300 uppercase">Pengawasan Layanan Publik</span>
                                        <h2 class="mt-0.5 font-serif text-lg font-bold text-white">{{ $cctvLabel }}</h2>
                                        <p class="mt-1 text-xs leading-relaxed text-emerald-100">
                                            Akses pantauan kamera ruang pelayanan publik {{ $satker?->nama_satker ?? 'Pengadilan' }} secara daring melalui portal resmi ACO Ditjen Badilag.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a
                                        href="{{ $safeCctvUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="pub-btn inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-gold px-4 text-xs font-bold text-ink hover:opacity-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                    >
                                        <span>Buka Portal Access CCTV Online</span>
                                        <span aria-hidden="true">&nearr;</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($socialChannels !== [])
                            <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs" style="--stagger-delay: 50ms">
                                <div class="border-b border-hairline pb-3">
                                    <span class="font-mono text-2xs font-bold text-primary uppercase">Informasi Terkini</span>
                                    <h2 class="font-serif text-xl font-bold text-ink">Sosial Media Resmi</h2>
                                </div>

                                <ul class="mt-3 space-y-2">
                                    @foreach ($socialChannels as $soc)
                                        <li>
                                            <a
                                                href="{{ $soc['url'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="pub-link-card group flex min-h-12 items-center justify-between gap-3 rounded-lg border border-hairline bg-ivory px-3.5 py-2.5 hover:border-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                            >
                                                <span class="flex min-w-0 items-center gap-3">
                                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-emerald-200 bg-white text-primary" aria-hidden="true">
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
                                                    <span class="min-w-0">
                                                        <span class="block text-xs font-bold text-ink group-hover:text-primary">{{ $soc['label'] }}</span>
                                                        <span class="block truncate text-2xs text-ink-muted">{{ $soc['description'] }}</span>
                                                    </span>
                                                </span>
                                                <span aria-hidden="true" class="pub-chevron shrink-0 text-xs font-bold text-primary">&nearr;</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        <!-- Band 6: Accessibility & Disability Spotlight -->
        <section aria-label="Layanan Portal Ramah Disabilitas" class="pub-reveal rounded-xl border border-hairline bg-white p-6 shadow-2xs sm:p-8">
            <div class="grid items-center gap-6 lg:grid-cols-12">
                <div class="space-y-2 lg:col-span-8">
                    <span class="font-mono text-2xs font-bold text-primary uppercase">Inklusivitas &amp; Aksesibilitas Digital</span>
                    <h2 class="font-serif text-xl font-bold text-ink sm:text-2xl">
                        Portal Ramah Penyandang Disabilitas &amp; Beragam Kebutuhan Baca
                    </h2>
                    <p class="text-xs leading-relaxed text-ink-body sm:text-sm">
                        Setiap pengunjung dapat mengaktifkan Pembaca Suara Bahasa Indonesia (Web Speech API id-ID), memperbesar ukuran huruf hingga 200%, beralih ke Mode Kontras Tinggi WCAG AAA, serta menghentikan animasi kapan saja.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5 lg:col-span-4 lg:justify-end">
                    <button
                        type="button"
                        data-open-a11y
                        class="pub-btn min-h-11 cursor-pointer rounded-lg bg-primary px-4 text-xs font-bold text-white hover:bg-primary-hover"
                    >
                        Buka Widget Aksesibilitas
                    </button>
                    <a
                        href="{{ route('public.accessibility') }}"
                        class="pub-btn inline-flex min-h-11 items-center rounded-lg border border-primary bg-white px-4 text-xs font-bold text-primary hover:bg-primary-soft"
                    >
                        Panduan Aksesibilitas
                    </a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.public>
