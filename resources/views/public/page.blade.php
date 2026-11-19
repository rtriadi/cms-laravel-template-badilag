<x-layouts.public :title="$resolvedTitle" :description="$page->meta_description">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Navigasi Remah Roti" class="mb-4 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
            <a href="{{ route('home') }}" class="font-semibold text-primary hover:underline">Beranda</a>
            <span aria-hidden="true">/</span>
            <span class="font-medium text-ink">{{ $resolvedTitle }}</span>
        </nav>

        <article class="rounded-xl border border-hairline bg-white p-6 shadow-xs sm:p-8">
            <header class="border-b border-hairline pb-5">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if ($page->category_tag)
                        <span class="rounded bg-primary-soft px-2.5 py-0.5 font-semibold text-primary uppercase">
                            KMA 2-144: {{ str_replace('_', ' ', $page->category_tag) }}
                        </span>
                    @endif
                    @if ($page->criteria_code)
                        <span class="rounded border border-hairline bg-mist px-2.5 py-0.5 font-mono font-semibold text-ink">
                            Kriteria SINOVA {{ $page->criteria_code }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-3 font-serif text-2xl font-bold text-ink sm:text-3xl">
                    {{ $resolvedTitle }}
                </h1>

                @if ($page->summary)
                    <p class="mt-2 text-sm leading-relaxed text-ink-body">{{ $page->summary }}</p>
                @endif
            </header>

            <div class="mt-6 space-y-8">
                @forelse ($blocks as $block)
                    @php $type = $block['type'] ?? 'richtext'; @endphp
                    <section class="space-y-3">
                        @if (! empty($block['heading']))
                            <h2 class="font-serif text-xl font-bold text-primary-deep">
                                {{ $block['heading'] }}
                            </h2>
                        @endif

                        @if ($type === 'richtext')
                            <div class="prose max-w-none space-y-3 text-sm leading-relaxed text-ink-body [&_a]:font-semibold [&_a]:text-primary [&_a]:underline [&_h3]:mt-4 [&_h3]:font-serif [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-ink [&_li]:ml-5 [&_li]:list-disc">
                                {!! \App\Support\SafeHtml::clean($block['content'] ?? '') !!}
                            </div>
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

