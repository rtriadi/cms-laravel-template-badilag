@php
    $postCanonical = route('public.posts.show', ['slug' => $post->slug]);
    $rawPostDesc = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($post->excerpt ?: $post->body))));
    $postDescription = $rawPostDesc !== ''
        ? \App\Support\SeoText::excerpt($rawPostDesc, 155)
        : $post->title;
    $postOgImage = $post->featured_image_path ? asset('storage/' . $post->featured_image_path) : null;
    $postSchemaImage = $postOgImage
        ?: ($satker?->foto_gedung_path ? asset('storage/' . $satker->foto_gedung_path) : ($satker?->logo_path ? asset('storage/' . $satker->logo_path) : null));
    $articleImgSizes = '(max-width: 1024px) calc(100vw - 32px), 896px';
    $featuredImgAttrs = $post->featured_image_path
        ? \App\Services\ImageOptimizerService::responsiveAttributes($post->featured_image_path, [400, 480, 720, 800], $articleImgSizes, null, 1000)
        : null;
    $publishedIso = ($post->published_at ?? $post->created_at)?->toAtomString();
    $modifiedIso = ($post->updated_at ?? $post->published_at ?? $post->created_at)?->toAtomString();
    $categoryLabel = $categories[$post->category] ?? $post->category;

    $postSchemas = [
        array_filter([
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $postDescription,
            'articleSection' => $categoryLabel,
            'inLanguage' => 'id-ID',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $postCanonical,
            ],
            'image' => $postSchemaImage ? [$postSchemaImage] : null,
            'datePublished' => $publishedIso,
            'dateModified' => $modifiedIso,
            'author' => [
                '@type' => 'Organization',
                '@id' => route('home') . '#organization',
                'name' => $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama',
                'url' => route('home'),
            ],
            'publisher' => [
                '@id' => route('home') . '#organization',
            ],
        ], static fn (mixed $v): bool => $v !== null),
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
                    'name' => 'Publikasi',
                    'item' => route('public.posts'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $post->title,
                    'item' => $postCanonical,
                ],
            ],
        ],
    ];
@endphp
<x-layouts.public
    :title="$post->title"
    :description="$postDescription"
    :canonical="$postCanonical"
    og-type="article"
    :og-image="$postOgImage"
    :og-image-path="$post->featured_image_path"
    :og-image-alt="$post->featured_image_alt ?: $post->title"
    :preload-image="$featuredImgAttrs['src'] ?? null"
    :preload-image-srcset="$featuredImgAttrs['srcset'] ?? null"
    :preload-image-sizes="$featuredImgAttrs['sizes'] ?? null"
    :published-at="$publishedIso"
    :modified-at="$modifiedIso"
    :json-ld="$postSchemas"
>
    <!-- GPU-Composited Reading Progress Bar -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5 bg-transparent">
        <div data-read-progress class="pub-progress-bar h-full w-full bg-gold"></div>
    </div>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Institutional Judicial Hero Banner -->
        <header class="relative overflow-hidden rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-primary-deep p-6 text-white shadow-xs sm:p-8">
            <svg aria-hidden="true" class="pub-crest-float pointer-events-none absolute -top-8 -right-8 size-56 text-white/6 sm:size-68" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
            </svg>
            <div class="relative z-10 flex flex-col gap-4">
                <nav aria-label="Navigasi Remah Roti" class="flex flex-wrap items-center gap-2 text-xs text-emerald-100">
                    <a href="{{ route('home') }}" class="font-semibold text-white hover:underline">Beranda</a>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <a href="{{ route('public.posts') }}" class="font-semibold text-white hover:underline">Publikasi</a>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <span class="font-medium text-gold">{{ $post->title }}</span>
                </nav>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl space-y-2">
                        <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                            {{ $categories[$post->category] ?? $post->category }}
                        </span>
                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-3xl">
                            {{ $post->title }}
                        </h1>
                    </div>
                    @auth
                        <a
                            href="{{ route('admin.posts', ['edit' => $post->id]) }}"
                            class="pub-btn inline-flex min-h-11 shrink-0 items-center rounded-md bg-gold px-4 py-2 text-xs font-bold text-ink shadow-2xs hover:opacity-95"
                        >
                            Edit Artikel Ini
                        </a>
                    @endauth
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 border-t border-white/15 pt-3 font-mono text-2xs text-emerald-100">
                    <span>Diunggah: {{ \App\Support\Tanggal::format($post->published_at ?? $post->created_at, 'd M Y, H:i') }}</span>
                    @if ($post->updated_at && $post->created_at && $post->updated_at->gt($post->created_at))
                        <span>Diperbarui: {{ \App\Support\Tanggal::format($post->updated_at, 'd M Y, H:i') }}</span>
                    @endif
                </div>
            </div>
        </header>

        <article class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs sm:p-8">
            @if ($post->featured_image_path && $featuredImgAttrs)
                <img
                    src="{{ $featuredImgAttrs['src'] }}"
                    @if ($featuredImgAttrs['srcset']) srcset="{{ $featuredImgAttrs['srcset'] }}" sizes="{{ $featuredImgAttrs['sizes'] }}" @endif
                    alt="{{ $post->featured_image_alt ?: $post->title }}"
                    width="820"
                    height="440"
                    fetchpriority="high"
                    decoding="async"
                    class="mb-6 max-h-96 w-full rounded-lg object-cover"
                />
            @endif

            <div class="prose max-w-none space-y-4 text-sm leading-relaxed text-ink-body">
                {!! \App\Support\SafeHtml::cleanBody($post->body) !!}
            </div>
        </article>

        @if ($related->isNotEmpty())
            <aside aria-label="Publikasi Terkait" class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs" style="--stagger-delay: 90ms">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Publikasi Terkait</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($related as $rel)
                        <li>
                            <a href="{{ route('public.posts.show', ['slug' => $rel->slug]) }}" class="pub-link-card flex min-h-10 items-center justify-between gap-2 rounded-lg border border-hairline bg-mist px-3.5 py-2 text-sm font-semibold text-primary hover:border-primary hover:bg-primary-soft">
                                <span>{{ $rel->title }}</span>
                                <span aria-hidden="true" class="pub-chevron">&rarr;</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</x-layouts.public>

