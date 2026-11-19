<x-layouts.public :title="$resolvedTitle" :description="$page->meta_description">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Navigasi Remah Roti" class="mb-4 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
            <a href="{{ route('home') }}" class="font-semibold text-primary hover:underline">Beranda</a>
            <span aria-hidden="true">/</span>
            <span class="font-medium text-ink">{{ $resolvedTitle }}</span>
        </nav>

        <article class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-xs sm:p-8">
            <header class="border-b border-hairline pb-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h1 class="font-serif text-2xl font-bold text-ink sm:text-3xl">
                        {{ $resolvedTitle }}
                    </h1>
                    @auth
                        <a
                            href="{{ route('admin.pages', ['slug' => $page->slug]) }}"
                            class="pub-btn inline-flex min-h-11 shrink-0 items-center rounded-md border border-primary bg-primary-soft px-3.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white"
                        >
                            Edit Halaman Ini
                        </a>
                    @endauth
                </div>

                @if ($page->summary)
                    <p class="mt-2 text-sm leading-relaxed text-ink-body">{{ $page->summary }}</p>
                @endif

                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 font-mono text-[11px] text-ink-muted">
                    <span>Diunggah: {{ ($page->published_at ?? $page->created_at)?->format('d M Y, H:i') ?? '-' }}</span>
                    @if ($page->updated_at)
                        <span>Terakhir diperbarui: {{ $page->updated_at->format('d M Y, H:i') }}</span>
                    @endif
                </div>
            </header>

            <div class="mt-6 space-y-8">
                @forelse ($blocks as $block)
                    @php $type = $block['type'] ?? 'richtext'; @endphp
                    <section class="pub-reveal space-y-3" style="--stagger-delay: {{ min(240, $loop->index * 45) }}ms">
                        @if (! empty($block['heading']))
                            <h2 class="font-serif text-xl font-bold text-primary-deep">
                                {{ $block['heading'] }}
                            </h2>
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
                    <p class="text-sm text-ink-muted">Konten halaman belum ditambahkan.</p>
                @endforelse
            </div>
        </article>
    </div>
</x-layouts.public>

