<x-layouts.public
    :title="$query !== '' ? 'Pencarian: ' . $query : 'Pencarian Informasi Publik'"
    description="Pencarian halaman informasi, berita, pengumuman, dan dokumen publik resmi pengadilan."
    :canonical="route('public.search')"
    robots="noindex, follow"
>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Direktori Pencarian Portal</span>
            <h1 class="mt-2 font-serif text-2xl font-bold text-white sm:text-3xl">Pencarian Informasi &amp; Dokumen</h1>
            <form action="{{ route('public.search') }}" method="GET" role="search" class="mt-4 flex flex-wrap gap-2">
                <label for="page-search-q" class="sr-only">Kata kunci pencarian</label>
                <input
                    id="page-search-q"
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    minlength="2"
                    required
                    placeholder="Ketik kata kunci (minimal 2 karakter), misalnya: LKJIP, DIPA, sidang..."
                    class="min-h-11 flex-1 rounded-lg border border-white/20 bg-white px-4 text-sm text-ink placeholder:text-ink-muted focus:border-gold focus:outline-none"
                />
                <button type="submit" class="pub-btn min-h-11 cursor-pointer rounded-lg bg-gold px-6 text-xs font-bold text-ink hover:opacity-95">
                    Cari Informasi
                </button>
            </form>
        </header>

        @if ($query !== '')
            <div class="space-y-6">
                <p class="text-xs text-ink-muted">
                    Hasil pencarian untuk kata kunci: <strong class="text-ink">{{ $query }}</strong>
                </p>

                <section class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs">
                    <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Halaman Informasi ({{ $pages->count() }})</h2>
                    @if ($pages->isEmpty())
                        <p class="py-4 text-xs text-ink-muted">Tidak ditemukan halaman yang cocok.</p>
                    @else
                        <ul class="mt-3 divide-y divide-hairline">
                            @foreach ($pages as $pg)
                                <li class="py-2.5">
                                    <a href="{{ route('public.page', ['slug' => $pg->slug]) }}" class="text-sm font-semibold text-primary hover:underline">
                                        {{ $pg->title }}
                                    </a>
                                    <p class="font-mono text-xs text-ink-muted">/halaman/{{ $pg->slug }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs">
                    <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Berita &amp; Pengumuman ({{ $posts->count() }})</h2>
                    @if ($posts->isEmpty())
                        <p class="py-4 text-xs text-ink-muted">Tidak ditemukan publikasi yang cocok.</p>
                    @else
                        <ul class="mt-3 divide-y divide-hairline">
                            @foreach ($posts as $pst)
                                <li class="py-2.5">
                                    <a href="{{ route('public.posts.show', ['slug' => $pst->slug]) }}" class="text-sm font-semibold text-primary hover:underline">
                                        {{ $pst->title }}
                                    </a>
                                    <p class="text-xs text-ink-muted">{{ $categories[$pst->category] ?? $pst->category }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs">
                    <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Dokumen Publik ({{ $documents->count() }})</h2>
                    @if ($documents->isEmpty())
                        <p class="py-4 text-xs text-ink-muted">Tidak ditemukan dokumen yang cocok.</p>
                    @else
                        <ul class="mt-3 divide-y divide-hairline">
                            @foreach ($documents as $doc)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-xs">
                                    <div>
                                        <p class="text-sm font-semibold text-ink">{{ $doc->title }}</p>
                                        <p class="text-ink-muted">{{ $collections[$doc->collection_type] ?? $doc->collection_type }} ({{ $doc->year }})</p>
                                        <p class="mt-0.5 font-mono text-2xs text-ink-muted">
                                            Diunggah: {{ \App\Support\Tanggal::format($doc->created_at, 'd M Y, H:i') }}
                                            @if ($doc->updated_at && $doc->created_at && $doc->updated_at->gt($doc->created_at))
                                                &middot; Diperbarui: {{ \App\Support\Tanggal::format($doc->updated_at, 'd M Y, H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                    @if ($doc->file_path)
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" rel="noopener noreferrer" class="pub-btn inline-flex min-h-11 items-center rounded bg-primary px-3 font-semibold text-white hover:bg-primary-hover">Unduh PDF</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        @endif
    </div>
</x-layouts.public>
