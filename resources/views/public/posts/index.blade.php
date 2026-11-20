@php
    $activeCategoryLabel = $activeCategory !== '' ? ($categories[$activeCategory] ?? $activeCategory) : null;
    $postsTitle = $activeCategoryLabel ? $activeCategoryLabel . ' - Publikasi Resmi' : 'Berita & Publikasi Resmi';
    $postsDesc = $activeCategoryLabel
        ? "Daftar {$activeCategoryLabel} resmi dari " . ($satker?->nama_satker ?? 'Pengadilan Agama') . '.'
        : 'Informasi resmi berita pelayanan peradilan, pengumuman, inovasi satuan kerja, dan artikel layanan bantuan hukum.';
    $postsCanonical = $activeCategory !== '' ? route('public.posts', ['kategori' => $activeCategory]) : route('public.posts');
    $postsSchemas = [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Beranda',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $activeCategoryLabel ?: 'Berita & Publikasi Resmi',
                    'item' => $postsCanonical,
                ],
            ],
        ],
    ];
@endphp
<x-layouts.public :title="$postsTitle" :description="$postsDesc" :canonical="$postsCanonical" :json-ld="$postsSchemas">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Institutional Judicial Hero Banner -->
        <header class="pub-reveal relative overflow-hidden rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-primary-deep p-6 text-white shadow-xs sm:p-8">
            <svg aria-hidden="true" class="pub-crest-float pointer-events-none absolute -top-8 -right-8 size-56 text-white/6 sm:size-68" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
            </svg>
            <div class="relative z-10 space-y-4">
                <nav aria-label="Navigasi Remah Roti" class="flex flex-wrap items-center gap-2 text-xs text-emerald-100">
                    <a href="{{ route('home') }}" class="font-semibold text-white hover:underline">Beranda</a>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <span class="font-medium text-gold">Berita &amp; Publikasi Resmi</span>
                </nav>

                <div>
                    <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                        Pusat Informasi &amp; Publikasi
                    </span>
                    <h1 class="mt-2 font-serif text-2xl leading-tight font-bold text-white sm:text-3xl lg:text-4xl">
                        Berita, Pengumuman &amp; Publikasi Resmi
                    </h1>
                    <p class="mt-1.5 max-w-3xl text-sm leading-relaxed text-emerald-50">
                        Informasi resmi pelayanan peradilan, inovasi satuan kerja, artikel bantuan hukum, dan pengumuman terbuka.
                    </p>
                </div>

                <nav aria-label="Filter Kategori Publikasi" class="flex flex-wrap gap-2 border-t border-white/15 pt-4">
                    <a
                        href="{{ route('public.posts') }}"
                        @if ($activeCategory === '') aria-current="true" @endif
                        @class([
                            'pub-btn inline-flex min-h-10 items-center rounded-lg px-3.5 text-xs font-semibold',
                            'bg-gold text-ink shadow-2xs' => $activeCategory === '',
                            'border border-white/25 bg-white/10 text-emerald-50 hover:bg-white/20' => $activeCategory !== '',
                        ])
                    >
                        Semua Kategori
                    </a>
                    @foreach ($categories as $catKey => $catLabel)
                        <a
                            href="{{ route('public.posts', ['kategori' => $catKey]) }}"
                            @if ($activeCategory === $catKey) aria-current="true" @endif
                            @class([
                                'pub-btn inline-flex min-h-10 items-center rounded-lg px-3.5 text-xs font-semibold',
                                'bg-gold text-ink shadow-2xs' => $activeCategory === $catKey,
                                'border border-white/25 bg-white/10 text-emerald-50 hover:bg-white/20' => $activeCategory !== $catKey,
                            ])
                        >
                            {{ $catLabel }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        @if ($posts->isEmpty())
            <div class="rounded-xl border border-hairline bg-white p-10 text-center text-sm text-ink-muted">
                Belum ada publikasi untuk kategori yang dipilih.
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="pub-card pub-reveal flex flex-col rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs" style="--stagger-delay: {{ min(240, $loop->index * 40) }}ms">
                        @if ($post->featured_image_path)
                            <div class="mb-4 overflow-hidden rounded-lg">
                                <img
                                    src="{{ asset('storage/' . $post->featured_image_path) }}"
                                    alt="{{ $post->featured_image_alt ?: $post->title }}"
                                    width="400"
                                    height="220"
                                    loading="lazy"
                                    decoding="async"
                                    class="pub-img-zoom h-44 w-full object-cover"
                                />
                            </div>
                        @else
                            <div aria-hidden="true" class="mb-4 flex h-28 items-center justify-between overflow-hidden rounded-lg border border-emerald-900/15 bg-gradient-to-r from-primary-deep to-primary px-4 text-white">
                                <div>
                                    <span class="block font-mono text-2xs font-bold text-gold uppercase">Publikasi Resmi</span>
                                    <span class="font-serif text-xs font-semibold text-emerald-50">{{ $satker?->nama_satker ?? 'Peradilan Agama' }}</span>
                                </div>
                                <svg class="size-10 text-white/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                                </svg>
                            </div>
                        @endif
                        <span class="w-fit rounded bg-primary-soft px-2.5 py-0.5 text-2xs font-semibold text-primary">
                            {{ $categories[$post->category] ?? $post->category }}
                        </span>
                        <h2 class="mt-2 font-serif text-lg font-bold text-ink">
                            <a href="{{ route('public.posts.show', ['slug' => $post->slug]) }}" class="hover:text-primary hover:underline">
                                {{ $post->title }}
                            </a>
                        </h2>
                        @if ($post->excerpt && trim(strip_tags($post->excerpt)) !== '')
                            <p class="mt-2 text-xs leading-relaxed text-ink-body">{{ \Illuminate\Support\Str::limit(trim(strip_tags($post->excerpt)), 140) }}</p>
                        @endif
                        <p class="mt-auto pt-4 font-mono text-2xs text-ink-muted">
                            Diunggah: {{ \App\Support\Tanggal::format($post->published_at ?? $post->created_at, 'd M Y') }}
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>

