<x-layouts.public title="Beranda">
    {{-- Banner Informasi Serta Merta --}}
    @if ($sertaMerta->isNotEmpty())
        <section aria-label="Informasi Serta Merta" class="border-b border-amber-400 bg-gold text-ink">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-3 sm:px-6 lg:px-8">
                @foreach ($sertaMerta as $alertPost)
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-semibold">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-ink px-2 py-0.5 font-mono text-[11px] font-bold text-white uppercase">
                                Informasi Serta Merta
                            </span>
                            <a href="{{ route('public.posts.show', ['slug' => $alertPost->slug]) }}" class="underline-offset-2 hover:underline">
                                {{ $alertPost->title }}
                            </a>
                        </div>
                        <a href="{{ route('public.posts.show', ['slug' => $alertPost->slug]) }}" class="underline">
                            Baca Selengkapnya
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Hero 8/4 Grid: Slider (3-5 Slide Tanpa Video) + Direktori Akses Cepat -->
        <section aria-label="Sorotan Utama dan Akses Cepat" class="grid gap-6 lg:grid-cols-12">
            <div class="pub-reveal lg:col-span-8" style="--stagger-delay: 0ms">
                @if ($sliders->isNotEmpty())
                    <div
                        data-hero-slider
                        role="region"
                        aria-roledescription="carousel"
                        aria-label="Informasi Utama Satuan Kerja"
                        class="relative overflow-hidden rounded-xl border border-hairline bg-primary-deep text-white shadow-sm"
                    >
                        <div class="relative min-h-80 sm:min-h-96">
                            @foreach ($sliders as $slideIndex => $slide)
                                <div
                                    data-hero-slide
                                    @if ($slideIndex !== 0) hidden @endif
                                    class="flex min-h-80 flex-col justify-end p-6 sm:min-h-96 sm:p-8"
                                >
                                    @if ($slide->image_path)
                                        <img
                                            src="{{ asset('storage/' . $slide->image_path) }}"
                                            alt="{{ $slide->image_alt }}"
                                            width="860"
                                            height="420"
                                            @if ($slideIndex === 0) fetchpriority="high" @else loading="lazy" @endif
                                            class="absolute inset-0 h-full w-full object-cover"
                                        />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/15" aria-hidden="true"></div>
                                    @else
                                        <div class="sr-only">{{ $slide->image_alt }}</div>
                                        <!-- Architectural Judicial Pattern & Scales Crest Backdrop -->
                                        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden bg-gradient-to-br from-primary-deep via-primary-deep to-primary">
                                            <svg class="absolute -top-10 -right-10 size-72 text-white/6 sm:size-96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                                <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                                            </svg>
                                            <div class="absolute inset-x-0 bottom-0 h-1 bg-gold/60"></div>
                                        </div>
                                    @endif

                                    <div class="relative z-10 max-w-2xl space-y-3.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-[11px] font-bold text-ink uppercase">
                                                Sorotan #{{ $loop->iteration }}
                                            </span>
                                            <span class="rounded border border-white/25 bg-white/10 px-2.5 py-0.5 font-mono text-[11px] font-medium text-emerald-100">
                                                {{ $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama' }}
                                            </span>
                                        </div>
                                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-3xl lg:text-4xl">
                                            {{ $slide->title }}
                                        </h1>
                                        @if ($slide->subtitle)
                                            <p class="text-sm leading-relaxed text-emerald-50 sm:text-base">
                                                {{ $slide->subtitle }}
                                            </p>
                                        @endif
                                        @if ($slide->cta_label && $slide->cta_url)
                                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                                <a
                                                    href="{{ str_starts_with($slide->cta_url, 'http') ? $slide->cta_url : url($slide->cta_url) }}"
                                                    class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-md bg-gold px-4 py-2 text-xs font-bold text-ink shadow-2xs hover:opacity-95"
                                                >
                                                    <span>{{ $slide->cta_label }}</span>
                                                    <span aria-hidden="true">&rarr;</span>
                                                </a>
                                                <a
                                                    href="{{ route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']) }}"
                                                    class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-md border border-white/35 bg-white/10 px-4 py-2 text-xs font-semibold text-white hover:bg-white/20"
                                                >
                                                    <span>Jadwal Sidang Hari Ini</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($sliders->count() > 1)
                            <div class="relative z-10 flex flex-wrap items-center justify-between gap-2 border-t border-white/15 bg-black/40 px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        data-slider-prev
                                        class="pub-btn min-h-11 cursor-pointer rounded border border-white/30 px-3 text-xs font-semibold text-white hover:bg-white/10"
                                    >
                                        Sebelumnya
                                    </button>
                                    <button
                                        type="button"
                                        data-slider-next
                                        class="pub-btn min-h-11 cursor-pointer rounded border border-white/30 px-3 text-xs font-semibold text-white hover:bg-white/10"
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
                    <div class="rounded-xl border border-hairline bg-primary-deep p-8 text-white">
                        <h1 class="font-serif text-3xl font-bold text-white">{{ $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama' }}</h1>
                        <p class="mt-2 text-sm text-emerald-100">Pelayanan informasi peradilan yang terbuka, cepat, dan mudah diakses.</p>
                    </div>
                @endif
            </div>

            <aside aria-label="Akses Layanan Cepat" class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs lg:col-span-4" style="--stagger-delay: 55ms">
                <div class="border-b border-hairline pb-3">
                    <h2 class="font-serif text-lg font-bold text-ink">Akses Layanan Cepat</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Jalur langsung maksimal dua klik menuju informasi inti.</p>
                </div>

                <ul class="mt-4 space-y-2">
                    @foreach ($shortcuts as $shortcut)
                        <li>
                            <a
                                href="{{ $shortcut['url'] }}"
                                class="pub-link-card flex min-h-11 items-center justify-between gap-2 rounded-lg border border-hairline bg-mist px-3.5 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span>{{ $shortcut['label'] }}</span>
                                <span class="flex items-center gap-1.5">
                                    <span class="rounded bg-white px-2 py-0.5 font-mono text-[10px] font-medium text-ink-muted">
                                        {{ $shortcut['tag'] }}
                                    </span>
                                    <span aria-hidden="true" class="pub-chevron text-primary">&rarr;</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 rounded-lg border border-emerald-200 bg-primary-soft/70 p-3 text-xs">
                    <p class="font-bold text-primary-deep">Jam Pelayanan Pengadilan (PTSP)</p>
                    <p class="mt-1 font-mono text-[11px] text-ink-body">Senin - Kamis: 08.00 - 16.30</p>
                    <p class="font-mono text-[11px] text-ink-body">Jumat: 08.00 - 17.00 (Sabtu - Minggu Libur)</p>
                </div>
            </aside>
        </section>

        <!-- 6 Pilar Layanan Utama Pencari Keadilan -->
        @php
            $pilarLayanan = [
                [
                    'title' => 'Penelusuran Perkara (SIPP)',
                    'desc' => 'Cek tahapan perkara, riwayat sidang, dan status putusan secara transparan melalui SIPP.',
                    'badge' => 'SIPP',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/penelusuran-perkara-sipp']),
                    'icon' => 'M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z',
                ],
                [
                    'title' => 'Jadwal Persidangan Harian',
                    'desc' => 'Lihat agenda ruang sidang, perkara yang disidangkan hari ini, serta jadwal pemanggilan.',
                    'badge' => 'Sidang',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']),
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                ],
                [
                    'title' => 'Pendaftaran Online (e-Court)',
                    'desc' => 'Layanan e-Filing, e-Payment, e-Summons, dan e-Litigation Mahkamah Agung RI.',
                    'badge' => 'e-Court',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/ecourt']),
                    'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                ],
                [
                    'title' => 'Hak Pencari Keadilan (Prodeo & Posbakum)',
                    'desc' => 'Panduan pembebasan biaya perkara (Prodeo), konsultasi hukum gratis Posbakum, dan sidang keliling.',
                    'badge' => 'Bantuan Hukum',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']),
                    'icon' => 'M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z',
                ],
                [
                    'title' => 'Prosedur & Biaya Berperkara',
                    'desc' => 'Standar pelayanan peradilan, radius biaya panjar perkara resmi, dan mekanisme pengembalian sisa panjar.',
                    'badge' => 'Panjar & SOP',
                    'url' => route('public.page', ['slug' => 'layanan-hukum/biaya-perkara']),
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
                [
                    'title' => 'Pengaduan SIWAS & Layanan PPID',
                    'desc' => 'Saluran resmi pengaduan masyarakat (SIWAS MARI), permohonan informasi publik (e-PPID), dan survei layanan.',
                    'badge' => 'PPID & SIWAS',
                    'url' => route('public.page', ['slug' => 'layanan-publik/pengaduan']),
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                ],
            ];
        @endphp
        <section aria-label="Pilar Layanan Utama Pencari Keadilan" class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <span class="font-mono text-xs font-bold text-primary uppercase">Pelayanan Terpadu Satu Pintu</span>
                    <h2 class="font-serif text-xl font-bold text-ink sm:text-2xl">Pilar Layanan Utama Pencari Keadilan</h2>
                </div>
                <a href="{{ route('public.page', ['slug' => 'layanan-publik/standar-pelayanan']) }}" class="text-xs font-semibold text-primary hover:underline">
                    Lihat Standar Pelayanan Pengadilan &rarr;
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pilarLayanan as $pilar)
                    <a
                        href="{{ $pilar['url'] }}"
                        class="pub-card pub-reveal group flex flex-col justify-between rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs hover:border-primary"
                        style="--stagger-delay: {{ $loop->index * 40 }}ms"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex size-11 items-center justify-center rounded-lg border border-emerald-200 bg-primary-soft text-primary group-hover:bg-primary group-hover:text-white">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="{{ $pilar['icon'] }}" />
                                    </svg>
                                </span>
                                <span class="rounded border border-hairline bg-mist px-2.5 py-0.5 font-mono text-[11px] font-semibold text-primary-deep">
                                    {{ $pilar['badge'] }}
                                </span>
                            </div>
                            <h3 class="mt-3.5 font-serif text-base font-bold text-ink group-hover:text-primary">
                                {{ $pilar['title'] }}
                            </h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-ink-body">
                                {{ $pilar['desc'] }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-hairline pt-3 text-xs font-semibold text-primary">
                            <span>Buka Layanan</span>
                            <span aria-hidden="true" class="pub-chevron">&rarr;</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Berita & Pengumuman Resmi (7/5 Grid) -->
        <section aria-label="Berita dan Pengumuman" class="grid gap-6 lg:grid-cols-12">
            <div class="pub-reveal rounded-xl border border-hairline bg-white p-6 shadow-2xs lg:col-span-7" style="--stagger-delay: 40ms">
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h2 class="font-serif text-xl font-bold text-ink">Berita &amp; Inovasi Layanan</h2>
                    <a href="{{ route('public.posts') }}" class="text-xs font-semibold text-primary hover:underline">
                        Semua Berita
                    </a>
                </div>

                @if ($latestNews->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada berita pelayanan yang diterbitkan.</p>
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($latestNews as $news)
                            <article class="pub-card flex flex-col rounded-lg border border-hairline bg-mist p-4">
                                @if ($news->featured_image_path)
                                    <div class="mb-3 overflow-hidden rounded-md">
                                        <img
                                            src="{{ asset('storage/' . $news->featured_image_path) }}"
                                            alt="{{ $news->featured_image_alt ?: $news->title }}"
                                            width="360"
                                            height="200"
                                            loading="lazy"
                                            class="pub-img-zoom h-36 w-full object-cover"
                                        />
                                    </div>
                                @else
                                    <div aria-hidden="true" class="mb-3 flex h-24 items-center justify-between overflow-hidden rounded-md border border-emerald-900/15 bg-gradient-to-r from-primary-deep to-primary px-4 text-white">
                                        <div>
                                            <span class="block font-mono text-[10px] font-bold text-gold uppercase">Publikasi Resmi</span>
                                            <span class="font-serif text-xs font-semibold text-emerald-50">{{ $satker?->nama_satker ?? 'Peradilan Agama' }}</span>
                                        </div>
                                        <svg class="size-10 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                                        </svg>
                                    </div>
                                @endif
                                <span class="w-fit rounded bg-primary-soft px-2 py-0.5 text-[11px] font-semibold text-primary">
                                    {{ \App\Models\Post::categoryLabels()[$news->category] ?? $news->category }}
                                </span>
                                <h3 class="mt-2 font-serif text-base font-bold text-ink">
                                    <a href="{{ route('public.posts.show', ['slug' => $news->slug]) }}" class="hover:text-primary hover:underline">
                                        {{ $news->title }}
                                    </a>
                                </h3>
                                @if ($news->excerpt && trim(strip_tags($news->excerpt)) !== '')
                                    <p class="mt-1.5 text-xs leading-relaxed text-ink-body">{{ \Illuminate\Support\Str::limit(trim(strip_tags($news->excerpt)), 110) }}</p>
                                @endif
                                <p class="mt-auto pt-3 font-mono text-[11px] text-ink-muted">
                                    {{ $news->published_at?->format('d M Y') ?? $news->created_at?->format('d M Y') }}
                                </p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pub-reveal rounded-xl border border-hairline bg-white p-6 shadow-2xs lg:col-span-5" style="--stagger-delay: 85ms">
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h2 class="font-serif text-xl font-bold text-ink">Pengumuman Resmi</h2>
                    <a href="{{ route('public.posts', ['kategori' => 'pengumuman']) }}" class="text-xs font-semibold text-primary hover:underline">
                        Arsip Pengumuman
                    </a>
                </div>

                @if ($announcements->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada pengumuman resmi yang diterbitkan.</p>
                @else
                    <ul class="mt-4 divide-y divide-hairline">
                        @foreach ($announcements as $ann)
                            @php $annDate = $ann->published_at ?? $ann->created_at; @endphp
                            <li class="flex items-start gap-3 py-3">
                                <div class="flex w-13 shrink-0 flex-col items-center rounded-md border border-emerald-200 bg-primary-soft px-1.5 py-1 text-center">
                                    <span class="font-mono text-sm leading-none font-bold text-primary-deep">{{ $annDate?->format('d') ?? '--' }}</span>
                                    <span class="mt-0.5 font-mono text-[10px] font-semibold text-primary uppercase">{{ $annDate?->format('M Y') ?? '' }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('public.posts.show', ['slug' => $ann->slug]) }}" class="block text-sm font-semibold text-ink hover:text-primary hover:underline">
                                        {{ $ann->title }}
                                    </a>
                                    <p class="mt-0.5 font-mono text-[11px] text-ink-muted">
                                        Diunggah: {{ $annDate?->format('d M Y') }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <!-- Direktori Transparansi & Layanan Hukum (2 Panel) -->
        <section aria-label="Direktori Transparansi dan Layanan Hukum" class="grid gap-6 md:grid-cols-2">
            <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs" style="--stagger-delay: 40ms">
                <h2 class="border-b border-hairline pb-3 font-serif text-xl font-bold text-ink">
                    Transparansi Kinerja &amp; Anggaran (SAKIP)
                </h2>
                <ul class="mt-4 space-y-2">
                    @forelse ($transparencyPages as $tPage)
                        <li>
                            <a
                                href="{{ route('public.page', ['slug' => $tPage->slug]) }}"
                                class="pub-link-card flex min-h-11 items-center justify-between rounded-md border border-hairline bg-mist px-3.5 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span>{{ $tPage->title }}</span>
                                <span aria-hidden="true" class="pub-chevron text-primary">&rarr;</span>
                            </a>
                        </li>
                    @empty
                        <li class="text-xs text-ink-muted">Halaman transparansi belum tersedia.</li>
                    @endforelse
                </ul>
            </div>

            <div class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs" style="--stagger-delay: 85ms">
                <h2 class="border-b border-hairline pb-3 font-serif text-xl font-bold text-ink">
                    Informasi Layanan Hukum &amp; Kepaniteraan
                </h2>
                <ul class="mt-4 space-y-2">
                    @forelse ($servicePages as $sPage)
                        <li>
                            <a
                                href="{{ route('public.page', ['slug' => $sPage->slug]) }}"
                                class="pub-link-card flex min-h-11 items-center justify-between rounded-md border border-hairline bg-mist px-3.5 py-2 text-xs font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                            >
                                <span>{{ $sPage->title }}</span>
                                <span aria-hidden="true" class="pub-chevron text-primary">&rarr;</span>
                            </a>
                        </li>
                    @empty
                        <li class="text-xs text-ink-muted">Halaman layanan hukum belum tersedia.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    @if ($sliders->count() > 1)
        <script>
            (() => {
                const container = document.querySelector('[data-hero-slider]');
                if (!container) {
                    return;
                }
                const slides = Array.from(container.querySelectorAll('[data-hero-slide]'));
                const dots = Array.from(container.querySelectorAll('[data-slider-dot]'));
                const prevBtn = container.querySelector('[data-slider-prev]');
                const nextBtn = container.querySelector('[data-slider-next]');
                let index = 0;
                let autoTimer = null;

                const showSlide = (nextIdx) => {
                    index = (nextIdx + slides.length) % slides.length;
                    slides.forEach((el, i) => {
                        el.hidden = i !== index;
                    });
                    dots.forEach((dot, i) => {
                        dot.classList.toggle('bg-gold', i === index);
                        dot.classList.toggle('text-ink', i === index);
                    });
                };

                const startAuto = () => {
                    stopAuto();
                    const reduced = (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)
                        || document.documentElement.classList.contains('a11y-no-motion');
                    if (!reduced && slides.length > 1) {
                        autoTimer = window.setInterval(() => showSlide(index + 1), 7000);
                    }
                };

                const stopAuto = () => {
                    if (autoTimer) {
                        window.clearInterval(autoTimer);
                        autoTimer = null;
                    }
                };

                prevBtn?.addEventListener('click', () => {
                    showSlide(index - 1);
                    startAuto();
                });
                nextBtn?.addEventListener('click', () => {
                    showSlide(index + 1);
                    startAuto();
                });
                dots.forEach((dot) => {
                    dot.addEventListener('click', () => {
                        showSlide(Number(dot.getAttribute('data-slider-dot')));
                        startAuto();
                    });
                });

                container.addEventListener('mouseenter', stopAuto);
                container.addEventListener('mouseleave', startAuto);
                container.addEventListener('focusin', stopAuto);
                container.addEventListener('focusout', startAuto);

                showSlide(0);
                startAuto();
            })();
        </script>
    @endif
</x-layouts.public>

