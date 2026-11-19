<x-layouts.public title="Peta Situs (Sitemap)">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-hairline bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-serif text-2xl font-bold text-ink">Peta Situs Portal Resmi</h1>
                    <p class="mt-1 text-sm text-ink-body">Daftar lengkap seluruh halaman keterbukaan informasi dan layanan publik.</p>
                </div>
                <a href="{{ route('public.sitemap.xml') }}" class="inline-flex min-h-11 items-center rounded-md border border-primary bg-primary-soft px-4 text-xs font-semibold text-primary hover:bg-primary hover:text-white">
                    Unduh XML Sitemap (/sitemap.xml)
                </a>
            </div>
        </header>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-xl border border-hairline bg-white p-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Halaman Informasi &amp; Layanan</h2>
                <ul class="mt-3 space-y-2 text-xs">
                    <li><a href="{{ route('home') }}" class="font-semibold text-primary hover:underline">Beranda Utama</a></li>
                    <li><a href="{{ route('public.posts') }}" class="font-semibold text-primary hover:underline">Berita &amp; Pengumuman Resmi</a></li>
                    <li><a href="{{ route('public.search') }}" class="font-semibold text-primary hover:underline">Pencarian Informasi Publik</a></li>
                    <li><a href="{{ route('public.accessibility') }}" class="font-semibold text-primary hover:underline">Pernyataan &amp; Panduan Aksesibilitas</a></li>
                    <li><a href="{{ route('public.system-status') }}" class="font-semibold text-primary hover:underline">Status Versi &amp; Keamanan Sistem</a></li>
                    <li><a href="{{ route('public.visitor-stats') }}" class="font-semibold text-primary hover:underline">Statistik Pengunjung Portal</a></li>
                    @foreach ($pages as $pg)
                        <li class="flex items-center justify-between gap-2 pt-1">
                            <a href="{{ route('public.page', ['slug' => $pg->slug]) }}" class="text-primary hover:underline">{{ $pg->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-xl border border-hairline bg-white p-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Publikasi Terbaru</h2>
                @if ($posts->isEmpty())
                    <p class="py-6 text-xs text-ink-muted">Belum ada publikasi terbit.</p>
                @else
                    <ul class="mt-3 space-y-2 text-xs">
                        @foreach ($posts as $pst)
                            <li>
                                <a href="{{ route('public.posts.show', ['slug' => $pst->slug]) }}" class="font-semibold text-primary hover:underline">
                                    {{ $pst->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layouts.public>

