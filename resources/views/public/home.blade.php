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
                                    @endif

                                    <div class="relative z-10 max-w-2xl space-y-3">
                                        <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-[11px] font-bold text-ink uppercase">
                                            Sorotan #{{ $loop->iteration }}
                                        </span>
                                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-3xl">
                                            {{ $slide->title }}
                                        </h1>
                                        @if ($slide->subtitle)
                                            <p class="text-sm leading-relaxed text-emerald-50">
                                                {{ $slide->subtitle }}
                                            </p>
                                        @endif
                                        @if ($slide->cta_label && $slide->cta_url)
                                            <div class="pt-1">
                                                <a
                                                    href="{{ str_starts_with($slide->cta_url, 'http') ? $slide->cta_url : url($slide->cta_url) }}"
                                                    class="pub-btn inline-flex min-h-11 items-center gap-2 rounded-md bg-gold px-4 py-2 text-xs font-bold text-ink hover:opacity-95"
                                                >
                                                    <span>{{ $slide->cta_label }}</span>
                                                    <span aria-hidden="true">&rarr;</span>
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
            </aside>
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
                                    <div class="mb-3 overflow-hidden rounded">
                                        <img
                                            src="{{ asset('storage/' . $news->featured_image_path) }}"
                                            alt="{{ $news->featured_image_alt ?: $news->title }}"
                                            width="360"
                                            height="200"
                                            loading="lazy"
                                            class="pub-img-zoom h-36 w-full object-cover"
                                        />
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
                            <li class="py-3">
                                <p class="font-mono text-[11px] text-ink-muted">
                                    {{ $ann->published_at?->format('d M Y') ?? $ann->created_at?->format('d M Y') }}
                                </p>
                                <a href="{{ route('public.posts.show', ['slug' => $ann->slug]) }}" class="mt-0.5 block text-sm font-semibold text-ink hover:text-primary hover:underline">
                                    {{ $ann->title }}
                                </a>
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

