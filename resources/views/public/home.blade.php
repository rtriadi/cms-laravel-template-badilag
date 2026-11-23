@php
    $firstSlide = $sliders->first();
    $firstSlideImage = $firstSlide && ! empty($firstSlide->image_path) ? $firstSlide->image_path : null;
    $homeOgImage = $firstSlideImage ? asset('storage/' . $firstSlideImage) : null;
    $heroSizes = '(max-width: 1024px) calc(100vw - 32px), 520px';
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

    <!-- Band 1: Obsidian-Emerald Civic Hero Stage + Progressive Three.js / SVG Visual -->
    <section
        aria-label="Sorotan Utama Portal Resmi"
        class="pub-hero-grid-pattern relative overflow-hidden border-b-4 border-gold bg-gradient-to-br from-obsidian via-primary-deep to-primary text-white"
    >
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-11">
            @if ($sliders->isNotEmpty())
                <div
                    data-hero-slider
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Informasi Utama Satuan Kerja"
                    class="relative"
                >
                    <div aria-live="polite" aria-atomic="false">
                        @foreach ($sliders as $slideIndex => $slide)
                            <div
                                data-hero-slide
                                role="group"
                                aria-roledescription="slide"
                                aria-label="Slide {{ $slideIndex + 1 }} dari {{ $sliders->count() }}"
                                @if ($slideIndex !== 0) hidden @endif
                                class="grid items-center gap-8 lg:grid-cols-12"
                            >
                                <!-- Left 7 Columns: Authoritative Copy + Inline Quick Search + CTA -->
                                <div data-hero-copy class="space-y-4 lg:col-span-7">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                                            {{ $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama' }}
                                        </span>
                                        <span class="rounded border border-white/25 bg-black/25 px-2.5 py-0.5 font-mono text-2xs font-semibold text-emerald-100">
                                            Slide {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) $sliders->count(), 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </div>

                                    @if ($slideIndex === 0)
                                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-4xl lg:text-[2.6rem]">
                                            {{ $slide->title }}
                                        </h1>
                                    @else
                                        <h2 class="font-serif text-2xl leading-tight font-bold text-white sm:text-4xl lg:text-[2.6rem]">
                                            {{ $slide->title }}
                                        </h2>
                                    @endif

                                    @if ($slide->subtitle)
                                        <p class="max-w-2xl text-sm leading-relaxed text-emerald-50 sm:text-base">
                                            {{ $slide->subtitle }}
                                        </p>
                                    @endif

                                    <!-- Direct Citizen Quick-Find Form -->
                                    <form action="{{ route('public.search') }}" method="GET" role="search" class="max-w-xl rounded-xl border border-white/20 bg-obsidian/70 p-2 backdrop-blur-xs sm:flex sm:items-center sm:gap-2">
                                        <label for="hero-search-input-{{ $slideIndex }}" class="sr-only">Cari layanan perkara, jadwal sidang, atau dokumen resmi</label>
                                        <input
                                            id="hero-search-input-{{ $slideIndex }}"
                                            type="search"
                                            name="q"
                                            minlength="2"
                                            required
                                            placeholder="Cari informasi perkara, jadwal sidang, Prodeo, atau LKJIP..."
                                            class="min-h-11 w-full rounded-lg border border-white/15 bg-white px-3.5 text-xs text-ink placeholder:text-ink-muted focus:border-gold focus:outline-none sm:flex-1"
                                        />
                                        <button type="submit" class="pub-btn mt-2 min-h-11 w-full shrink-0 cursor-pointer rounded-lg bg-gold px-5 text-xs font-bold text-ink hover:opacity-95 sm:mt-0 sm:w-auto">
                                            Cari Informasi
                                        </button>
                                    </form>

                                    @php
                                        $ctaTarget = $slide->cta_url ? (str_starts_with($slide->cta_url, 'http') ? $slide->cta_url : url($slide->cta_url)) : null;
                                        $isProdeoCta = $ctaTarget !== null && str_contains($ctaTarget, 'hak-pencari-keadilan');
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-3 pt-1">
                                        @if ($slide->cta_label && $ctaTarget)
                                            <a
                                                href="{{ $ctaTarget }}"
                                                class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-lg bg-gold px-4 py-2 text-xs font-bold text-ink shadow-2xs hover:opacity-95"
                                            >
                                                <span>{{ $slide->cta_label }}</span>
                                                <span aria-hidden="true">&rarr;</span>
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('public.page', ['slug' => $isProdeoCta ? 'layanan-hukum/jadwal-sidang' : 'layanan-hukum/hak-pencari-keadilan']) }}"
                                            class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-lg border border-white/35 bg-white/10 px-4 py-2 text-xs font-semibold text-white hover:bg-white/20"
                                        >
                                            <span>{{ $isProdeoCta ? 'Jadwal Sidang Hari Ini' : 'Panduan Prodeo & Posbakum' }}</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- Right 5 Columns: Uploaded Slide Media OR Progressive Three.js / SVG Judicial Stage -->
                                <div class="lg:col-span-5">
                                    @if ($slide->image_path)
                                        @php
                                            $slideImg = \App\Services\ImageOptimizerService::responsiveAttributes($slide->image_path, [480, 800], $heroSizes, null, 1280);
                                        @endphp
                                        <div class="relative overflow-hidden rounded-xl border border-white/20 bg-obsidian shadow-xl">
                                            @if ($slideIndex === 0)
                                                <img
                                                    src="{{ $slideImg['src'] }}"
                                                    @if ($slideImg['srcset']) srcset="{{ $slideImg['srcset'] }}" sizes="{{ $slideImg['sizes'] }}" @endif
                                                    alt="{{ $slide->image_alt }}"
                                                    width="520"
                                                    height="320"
                                                    decoding="async"
                                                    fetchpriority="high"
                                                    class="h-64 w-full object-cover sm:h-72 lg:h-80"
                                                />
                                            @else
                                                <img
                                                    src="{{ $transparentPixel }}"
                                                    data-lazy-src="{{ $slideImg['src'] }}"
                                                    @if ($slideImg['srcset']) data-lazy-srcset="{{ $slideImg['srcset'] }}" data-lazy-sizes="{{ $slideImg['sizes'] }}" @endif
                                                    alt="{{ $slide->image_alt }}"
                                                    width="520"
                                                    height="320"
                                                    decoding="async"
                                                    class="h-64 w-full object-cover sm:h-72 lg:h-80"
                                                />
                                            @endif
                                        </div>
                                    @else
                                        <div class="sr-only">{{ $slide->image_alt }}</div>
                                        @if ($slideIndex === 0)
                                            <div
                                                data-hero-3d-stage
                                                class="relative flex h-64 items-center justify-center overflow-hidden rounded-xl border border-white/15 bg-obsidian/55 shadow-inner sm:h-72 lg:h-80"
                                            >
                                                <!-- Instant 0ms SVG Emblem Fallback (never blocks LCP) -->
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
                                            <div aria-hidden="true" class="relative flex h-64 items-center justify-center overflow-hidden rounded-xl border border-white/15 bg-obsidian/55 sm:h-72 lg:h-80">
                                                <svg class="pub-crest-float size-36 text-amber-300/75" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                                    <path d="M12 2 3 7l9 5 9-5-9-5z"></path>
                                                    <path d="M3 12l9 5 9-5"></path>
                                                    <path d="M3 17l9 5 9-5"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($sliders->count() > 1)
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-white/15 pt-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    data-slider-pause
                                    aria-pressed="false"
                                    class="pub-btn min-h-11 cursor-pointer rounded-md border border-white/30 bg-obsidian/50 px-3.5 text-xs font-semibold text-white hover:bg-white/15"
                                >
                                    Jeda
                                </button>
                                <button
                                    type="button"
                                    data-slider-prev
                                    class="pub-btn min-h-11 cursor-pointer rounded-md border border-white/30 bg-obsidian/50 px-3.5 text-xs font-semibold text-white hover:bg-white/15"
                                >
                                    Sebelumnya
                                </button>
                                <button
                                    type="button"
                                    data-slider-next
                                    class="pub-btn min-h-11 cursor-pointer rounded-md border border-white/30 bg-obsidian/50 px-3.5 text-xs font-semibold text-white hover:bg-white/15"
                                >
                                    Berikutnya
                                </button>
                            </div>

                            <div class="flex items-center gap-2">
                                @foreach ($sliders as $dotIndex => $dotSlide)
                                    <button
                                        type="button"
                                        data-slider-dot="{{ $dotIndex }}"
                                        aria-label="Pilih slide {{ $dotIndex + 1 }}"
                                        aria-current="{{ $dotIndex === 0 ? 'true' : 'false' }}"
                                        class="pub-btn min-h-11 min-w-11 cursor-pointer rounded-full border border-white/40 px-2 font-mono text-xs font-bold text-white"
                                    >
                                        {{ $dotIndex + 1 }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
                <div class="pub-reveal rounded-xl border border-emerald-200 bg-primary-soft/80 p-5 text-xs shadow-2xs">
                    <h2 class="font-serif text-base font-bold text-primary-deep">Jam Pelayanan Pengadilan (PTSP)</h2>
                    <dl class="mt-2.5 space-y-1.5 font-mono text-2xs text-ink-body">
                        <div class="flex justify-between gap-2 border-b border-emerald-200/70 pb-1">
                            <dt class="font-semibold text-ink">Senin - Kamis</dt>
                            <dd>08.00 - 16.30</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-emerald-200/70 pb-1">
                            <dt class="font-semibold text-ink">Jumat</dt>
                            <dd>08.00 - 17.00</dd>
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
