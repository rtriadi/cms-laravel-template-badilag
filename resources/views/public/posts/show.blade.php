<x-layouts.public :title="$post->title">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Navigasi Remah Roti" class="flex flex-wrap items-center gap-2 text-xs text-ink-muted">
            <a href="{{ route('home') }}" class="font-semibold text-primary hover:underline">Beranda</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('public.posts') }}" class="font-semibold text-primary hover:underline">Publikasi</a>
            <span aria-hidden="true">/</span>
            <span class="font-medium text-ink">{{ $post->title }}</span>
        </nav>

        <article class="rounded-xl border border-hairline bg-white p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded bg-primary-soft px-2.5 py-0.5 font-semibold text-primary">
                    {{ $categories[$post->category] ?? $post->category }}
                </span>
                <span class="font-mono text-ink-muted">
                    {{ $post->published_at?->format('d M Y') ?? $post->created_at?->format('d M Y') }}
                </span>
            </div>

            <h1 class="mt-3 font-serif text-2xl font-bold text-ink sm:text-3xl">{{ $post->title }}</h1>

            @if ($post->featured_image_path)
                <img
                    src="{{ asset('storage/' . $post->featured_image_path) }}"
                    alt="{{ $post->featured_image_alt ?: $post->title }}"
                    width="820"
                    height="440"
                    class="mt-5 max-h-96 w-full rounded-lg object-cover"
                />
            @endif

            <div class="prose mt-6 max-w-none space-y-4 text-sm leading-relaxed text-ink-body">
                {!! \App\Support\SafeHtml::clean(nl2br($post->body)) !!}
            </div>
        </article>

        @if ($related->isNotEmpty())
            <aside aria-label="Publikasi Terkait" class="rounded-xl border border-hairline bg-white p-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Publikasi Terkait</h2>
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($related as $rel)
                        <li class="py-2.5">
                            <a href="{{ route('public.posts.show', ['slug' => $rel->slug]) }}" class="text-sm font-semibold text-primary hover:underline">
                                {{ $rel->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</x-layouts.public>

