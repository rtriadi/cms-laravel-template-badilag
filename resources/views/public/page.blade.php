@php
    $fallbackContentSnippet = '';
    foreach ($blocks as $blk) {
        if (($blk['type'] ?? '') === 'richtext' && ! empty($blk['content'])) {
            $fallbackContentSnippet = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $blk['content'])));
            if ($fallbackContentSnippet !== '') {
                break;
            }
        }
    }
    $pageMetaDescription = $page->meta_description
        ?: ($page->summary ?: ($fallbackContentSnippet !== '' ? \App\Support\SeoText::excerpt($fallbackContentSnippet, 155) : "Informasi resmi {$resolvedTitle} pada " . ($satker?->nama_satker ?? 'Portal Resmi Peradilan Agama') . '.'));
    $pageCanonical = route('public.page', ['slug' => $page->slug]);

    $pageSchemas = [
        [
            '@type' => 'WebPage',
            '@id' => $pageCanonical . '#webpage',
            'url' => $pageCanonical,
            'name' => $resolvedTitle,
            'description' => $pageMetaDescription,
            'inLanguage' => 'id-ID',
            'isPartOf' => ['@id' => route('home') . '#website'],
            'datePublished' => ($page->published_at ?? $page->created_at)?->toAtomString(),
            'dateModified' => $page->updated_at?->toAtomString(),
        ],
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
                    'name' => $sectionLabel ?? 'Informasi & Layanan',
                    'item' => route('public.sitemap'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $resolvedTitle,
                    'item' => $pageCanonical,
                ],
            ],
        ],
    ];
@endphp
<x-layouts.public
    :title="$resolvedTitle"
    :description="$pageMetaDescription"
    :canonical="$pageCanonical"
    :json-ld="$pageSchemas"
>
    @php
        $headingBlocks = collect($blocks)->map(fn (array $b, int $i): array => [
            'number' => $i + 1,
            'heading' => trim((string) ($b['heading'] ?? '')),
        ])->filter(fn (array $item): bool => $item['heading'] !== '')->values();
    @endphp

    <!-- GPU-Composited Reading Progress Bar -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5 bg-transparent">
        <div data-read-progress class="pub-progress-bar h-full w-full bg-gold"></div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Institutional Judicial Page Hero Banner -->
        <header class="relative overflow-hidden rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <svg aria-hidden="true" class="pub-crest-float pointer-events-none absolute -top-8 -right-8 size-60 text-white/6 sm:size-72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
            </svg>
            <div class="relative z-10 flex flex-col gap-4">
                <nav aria-label="Navigasi Remah Roti" class="flex flex-wrap items-center gap-2 text-xs text-emerald-100">
                    <a href="{{ route('home') }}" class="font-semibold text-white hover:underline">Beranda</a>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <span class="text-emerald-200">{{ $sectionLabel ?? 'Informasi & Layanan' }}</span>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <span class="font-medium text-amber-300">{{ $resolvedTitle }}</span>
                </nav>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl space-y-2">
                        <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                            {{ $sectionLabel ?? 'Informasi Resmi' }}
                        </span>
                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-3xl lg:text-4xl">
                            {{ $resolvedTitle }}
                        </h1>
                        @if ($page->summary)
                            <p class="text-sm leading-relaxed text-emerald-50 sm:text-base">
                                {{ $page->summary }}
                            </p>
                        @endif
                    </div>

                    @auth
                        <a
                            href="{{ route('admin.pages', ['slug' => $page->slug]) }}"
                            class="pub-btn inline-flex min-h-11 shrink-0 items-center rounded-md bg-gold px-4 py-2 text-xs font-bold text-ink shadow-2xs hover:opacity-95"
                        >
                            Edit Halaman Ini
                        </a>
                    @endauth
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 border-t border-white/15 pt-3 font-mono text-2xs text-emerald-100">
                    <span>Diunggah: {{ \App\Support\Tanggal::format($page->published_at ?? $page->created_at, 'd M Y, H:i') }}</span>
                    @if ($page->updated_at)
                        <span>Terakhir diperbarui: {{ \App\Support\Tanggal::format($page->updated_at, 'd M Y, H:i') }}</span>
                    @endif
                </div>
            </div>
        </header>

        <!-- 2-Column Authoritative Judicial Editorial Layout (8/4) -->
        <div class="mt-6 grid gap-6 lg:grid-cols-12">
            <!-- Main Content Column (8 Columns) -->
            <article class="space-y-6 lg:col-span-8">
                @forelse ($blocks as $block)
                    @php $type = $block['type'] ?? 'richtext'; @endphp
                    <section
                        id="bagian-{{ $loop->iteration }}"
                        @if (empty($block['heading'])) aria-label="Bagian {{ $loop->iteration }}" @endif
                        @class([
                            'scroll-mt-24 space-y-4 rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs sm:p-7',
                            'pub-reveal' => ! $loop->first,
                        ])
                        @if (! $loop->first) style="--stagger-delay: {{ min(240, ($loop->index - 1) * 45) }}ms" @endif
                    >
                        @if (! empty($block['heading']))
                            <div class="border-b border-hairline pb-3">
                                @if (count($blocks) > 1)
                                    <span class="font-mono text-2xs font-bold text-primary uppercase">
                                        Bagian {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                @endif
                                <h2 class="mt-0.5 font-serif text-xl font-bold text-primary-deep sm:text-2xl">
                                    {{ $block['heading'] }}
                                </h2>
                            </div>
                        @endif

                        @if ($type === 'richtext')
                            <div class="prose max-w-none space-y-3 text-sm leading-relaxed text-ink-body">
                                {!! \App\Support\SafeHtml::clean($block['content'] ?? '') !!}
                            </div>
                        @elseif ($type === 'article_list')
                            @include('public.blocks.article-list', [
                                'block' => $block,
                                'posts' => $postsByCategory[$block['post_category'] ?? 'layanan_hukum'] ?? collect(),
                            ])
                        @elseif ($type === 'document_list')
                            @include('public.blocks.document-list', [
                                'block' => $block,
                                'documents' => $documentsByCollection[$block['collection_type'] ?? 'lkjip'] ?? collect(),
                                'years' => $yearsByCollection[$block['collection_type'] ?? 'lkjip'] ?? [],
                                'selectedYear' => $selectedYear,
                            ])
                        @elseif ($type === 'embed_fallback')
                            @include('public.blocks.embed-fallback', [
                                'block' => $block,
                                'forceFallback' => $forceFallback,
                                'officials' => $officials,
                                'orgOfficials' => $orgOfficials,
                                'schedules' => $schedules,
                                'statistics' => $statistics,
                            ])
                        @elseif ($type === 'external_portal')
                            @include('public.blocks.external-portal', ['block' => $block])
                        @elseif ($type === 'map_yurisdiksi')
                            @include('public.blocks.map-yurisdiksi', ['block' => $block])
                        @elseif ($type === 'satker_contact')
                            @include('public.blocks.satker-contact', ['satker' => $satker])
                        @endif
                    </section>
                @empty
                    <div class="rounded-xl border border-hairline bg-white p-8 text-center shadow-2xs">
                        <p class="text-sm text-ink-muted">Konten halaman belum ditambahkan.</p>
                    </div>
                @endforelse
            </article>

            <!-- Contextual Sticky Sidebar (4 Columns) -->
            <aside aria-label="Navigasi Bagian dan Informasi Layanan" class="lg:col-span-4">
                <div class="space-y-5 lg:sticky lg:top-20">
                    @if ($headingBlocks->count() > 1)
                        <nav aria-label="Daftar Isi Halaman Ini" class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-5 shadow-2xs">
                            <h2 class="border-b border-hairline pb-2.5 font-serif text-base font-bold text-ink">
                                Daftar Isi Halaman Ini
                            </h2>
                            <ol class="mt-3 space-y-1.5 text-xs">
                                @foreach ($headingBlocks as $item)
                                    <li>
                                        <a
                                            href="#bagian-{{ $item['number'] }}"
                                            class="pub-link-card flex min-h-10 items-center gap-2 rounded-md border border-hairline/70 bg-mist/60 px-2.5 py-1.5 font-medium text-ink-body hover:border-primary hover:bg-primary-soft hover:text-primary"
                                        >
                                            <span class="rounded bg-white px-1.5 py-0.5 font-mono text-2xs font-bold text-primary">
                                                {{ str_pad((string) $item['number'], 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <span class="line-clamp-2 flex-1">{{ $item['heading'] }}</span>
                                            <span aria-hidden="true" class="pub-chevron text-primary">&rarr;</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    @if (isset($relatedSectionPages) && $relatedSectionPages->count() > 1)
                        <nav aria-label="Menu {{ $sectionLabel ?? 'Bagian Terkait' }}" class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs">
                            <h2 class="border-b border-hairline pb-2.5 font-serif text-base font-bold text-ink">
                                Menu {{ $sectionLabel ?? 'Bagian Terkait' }}
                            </h2>
                            <ul class="mt-3 space-y-1.5 text-xs">
                                @foreach ($relatedSectionPages as $relPage)
                                    @php $isCurrent = $relPage->id === $page->id; @endphp
                                    <li>
                                        <a
                                            href="{{ route('public.page', ['slug' => $relPage->slug]) }}"
                                            @if ($isCurrent) aria-current="page" @endif
                                            @class([
                                                'pub-link-card flex min-h-10 items-center justify-between gap-2 rounded-lg px-3 py-2 font-semibold',
                                                'bg-primary text-white shadow-2xs' => $isCurrent,
                                                'border border-hairline bg-mist text-ink hover:border-primary hover:bg-primary-soft hover:text-primary' => ! $isCurrent,
                                            ])
                                        >
                                            <span>{{ $relPage->title }}</span>
                                            <span aria-hidden="true" class="pub-chevron">&rarr;</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif

                    <!-- Quick PTSP & Legal Aid Card -->
                    <div class="pub-reveal rounded-xl border border-emerald-200 bg-primary-soft/80 p-5 text-xs shadow-2xs">
                        <h2 class="font-serif text-base font-bold text-primary-deep">
                            Layanan PTSP &amp; Bantuan Hukum
                        </h2>
                        <p class="mt-1.5 leading-relaxed text-ink-body">
                            Pelayanan Terpadu Satu Pintu (PTSP) dan Pos Bantuan Hukum (Posbakum) siap melayani masyarakat pencari keadilan.
                        </p>
                        <div class="mt-3 rounded-lg border border-emerald-200 bg-white p-3 font-mono text-2xs text-ink">
                            <p class="font-bold text-primary-deep">Jam Pelayanan PTSP:</p>
                            <p class="mt-1">Senin - Kamis: 08.00 - 16.30</p>
                            <p>Jumat: 08.00 - 17.00</p>
                            @if ($satker?->telepon)
                                <p class="mt-1.5 border-t border-hairline pt-1.5">Telp: {{ $satker->telepon }}</p>
                            @endif
                        </div>
                        <div class="mt-3 flex flex-col gap-2">
                            <a
                                href="{{ route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']) }}"
                                class="pub-btn inline-flex min-h-10 items-center justify-center rounded-md bg-primary px-3.5 py-2 text-center text-xs font-bold text-white hover:bg-primary-hover"
                            >
                                Panduan Prodeo &amp; Posbakum
                            </a>
                            <a
                                href="{{ route('public.page', ['slug' => 'layanan-hukum/jadwal-sidang']) }}"
                                class="pub-btn inline-flex min-h-10 items-center justify-center rounded-md border border-primary bg-white px-3.5 py-2 text-center text-xs font-bold text-primary hover:bg-primary-soft"
                            >
                                Lihat Jadwal Sidang Hari Ini
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>

