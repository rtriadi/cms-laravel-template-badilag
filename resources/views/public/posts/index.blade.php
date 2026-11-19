<x-layouts.public title="Berita & Publikasi Resmi">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-hairline bg-white p-6">
            <h1 class="font-serif text-2xl font-bold text-ink sm:text-3xl">Berita, Pengumuman &amp; Publikasi Resmi</h1>
            <p class="mt-1 text-sm text-ink-body">Informasi resmi pelayanan peradilan, inovasi satker, dan pengumuman terbuka.</p>

            <nav aria-label="Filter Kategori Publikasi" class="mt-4 flex flex-wrap gap-2">
                <a
                    href="{{ route('public.posts') }}"
                    @class([
                        'inline-flex min-h-11 items-center rounded-md px-3.5 text-xs font-semibold',
                        'bg-primary text-white' => $activeCategory === '',
                        'border border-hairline bg-mist text-ink hover:bg-primary-soft' => $activeCategory !== '',
                    ])
                >
                    Semua Kategori
                </a>
                @foreach ($categories as $catKey => $catLabel)
                    <a
                        href="{{ route('public.posts', ['kategori' => $catKey]) }}"
                        @class([
                            'inline-flex min-h-11 items-center rounded-md px-3.5 text-xs font-semibold',
                            'bg-primary text-white' => $activeCategory === $catKey,
                            'border border-hairline bg-mist text-ink hover:bg-primary-soft' => $activeCategory !== $catKey,
                        ])
                    >
                        {{ $catLabel }}
                    </a>
                @endforeach
            </nav>
        </header>

        @if ($posts->isEmpty())
            <div class="rounded-xl border border-hairline bg-white p-10 text-center text-sm text-ink-muted">
                Belum ada publikasi untuk kategori yang dipilih.
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="flex flex-col rounded-xl border border-hairline bg-white p-5">
                        @if ($post->featured_image_path)
                            <img
                                src="{{ asset('storage/' . $post->featured_image_path) }}"
                                alt="{{ $post->featured_image_alt ?: $post->title }}"
                                width="400"
                                height="220"
                                class="mb-4 h-44 w-full rounded-lg object-cover"
                            />
                        @endif
                        <span class="w-fit rounded bg-primary-soft px-2.5 py-0.5 text-[11px] font-semibold text-primary">
                            {{ $categories[$post->category] ?? $post->category }}
                        </span>
                        <h2 class="mt-2 font-serif text-lg font-bold text-ink">
                            <a href="{{ route('public.posts.show', ['slug' => $post->slug]) }}" class="hover:text-primary hover:underline">
                                {{ $post->title }}
                            </a>
                        </h2>
                        @if ($post->excerpt)
                            <p class="mt-2 text-xs leading-relaxed text-ink-body">{{ \Illuminate\Support\Str::limit($post->excerpt, 140) }}</p>
                        @endif
                        <p class="mt-auto pt-4 font-mono text-[11px] text-ink-muted">
                            {{ $post->published_at?->format('d M Y') ?? $post->created_at?->format('d M Y') }}
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

