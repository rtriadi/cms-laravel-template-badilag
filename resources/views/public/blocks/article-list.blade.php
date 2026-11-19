@php
    $catKey = (string) ($block['post_category'] ?? 'layanan_hukum');
    $catLabel = \App\Models\Post::categoryLabels()[$catKey] ?? 'Publikasi';
@endphp

<div class="space-y-4">
    @auth
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-primary-soft px-4 py-2.5 text-xs">
            <span class="font-medium text-ink">
                Menampilkan artikel dari kategori: <strong>{{ $catLabel }}</strong>
            </span>
            <a
                href="{{ route('admin.posts', ['kategori' => $catKey]) }}"
                class="inline-flex min-h-11 items-center rounded-md bg-primary px-3.5 font-semibold text-white hover:bg-primary-hover"
            >
                + Tulis Artikel Baru di Kategori Ini
            </a>
        </div>
    @endauth

    @if ($posts->isEmpty())
        <div class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
            Belum ada artikel tambahan yang diterbitkan pada bagian ini.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($posts as $article)
                <article class="flex flex-col rounded-lg border border-hairline bg-mist p-4">
                    @if ($article->featured_image_path)
                        <img
                            src="{{ asset('storage/' . $article->featured_image_path) }}"
                            alt="{{ $article->featured_image_alt ?: $article->title }}"
                            width="400"
                            height="210"
                            class="mb-3 h-40 w-full rounded object-cover"
                        />
                    @endif
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <span class="rounded bg-primary-soft px-2 py-0.5 font-semibold text-primary">
                            {{ \App\Models\Post::categoryLabels()[$article->category] ?? $article->category }}
                        </span>
                        <span class="font-mono text-ink-muted">
                            {{ $article->published_at?->format('d M Y') ?? $article->created_at?->format('d M Y') }}
                        </span>
                    </div>
                    <h3 class="mt-2 font-serif text-base font-bold text-ink">
                        <a href="{{ route('public.posts.show', ['slug' => $article->slug]) }}" class="hover:text-primary hover:underline">
                            {{ $article->title }}
                        </a>
                    </h3>
                    @php
                        $cleanSummary = trim(strip_tags((string) ($article->excerpt ?: $article->body)));
                    @endphp
                    @if ($cleanSummary !== '')
                        <p class="mt-1.5 text-xs leading-relaxed text-ink-body">
                            {{ \Illuminate\Support\Str::limit($cleanSummary, 140) }}
                        </p>
                    @endif
                    <div class="mt-auto flex items-center justify-between pt-3 text-xs">
                        <a href="{{ route('public.posts.show', ['slug' => $article->slug]) }}" class="font-semibold text-primary hover:underline">
                            Baca Artikel Lengkap
                        </a>
                        @auth
                            <a href="{{ route('admin.posts', ['edit' => $article->id]) }}" class="font-semibold text-ink-muted hover:text-primary hover:underline">
                                Edit Artikel
                            </a>
                        @endauth
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
