@php
    $sitemapDesc = 'Peta situs lengkap seluruh halaman informasi publik, layanan hukum, transparansi SAKIP, dan publikasi resmi ' . ($satker?->nama_satker ?? 'Peradilan Agama') . '.';
@endphp
<x-layouts.public title="Peta Situs (Sitemap)" :description="$sitemapDesc" :canonical="route('public.sitemap')">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Kepatuhan &amp; Indeks</span>
                    <h1 class="mt-2 font-serif text-2xl font-bold text-white sm:text-3xl">Peta Situs Portal Resmi</h1>
                    <p class="mt-1 text-sm text-emerald-100">Daftar lengkap seluruh halaman keterbukaan informasi dan layanan publik.</p>
                </div>
                <a href="{{ route('public.sitemap.xml') }}" class="pub-btn inline-flex min-h-11 items-center rounded-lg bg-gold px-4 text-xs font-bold text-ink hover:opacity-95">
                    Unduh XML Sitemap (/sitemap.xml)
                </a>
            </div>
        </header>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Halaman Informasi &amp; Layanan</h2>
                <ul class="mt-2 divide-y divide-hairline/60 text-xs">
                    <li><a href="{{ route('home') }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">Beranda Utama</a></li>
                    <li><a href="{{ route('public.posts') }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">Berita &amp; Pengumuman Resmi</a></li>
                    <li><a href="{{ route('public.search') }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">Pencarian Informasi Publik</a></li>
                    <li><a href="{{ route('public.accessibility') }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">Pernyataan &amp; Panduan Aksesibilitas</a></li>
                    <li><a href="{{ route('public.system-status') }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">Status Versi &amp; Keamanan Sistem</a></li>
                    <li><a href="{{ route('public.visitor-stats') }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">Statistik Pengunjung Portal</a></li>
                    @foreach ($pages as $pg)
                        <li>
                            <a href="{{ route('public.page', ['slug' => $pg->slug]) }}" class="flex min-h-10 items-center py-1.5 text-primary hover:underline">{{ $pg->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Publikasi Terbaru</h2>
                @if ($posts->isEmpty())
                    <p class="py-6 text-xs text-ink-muted">Belum ada publikasi terbit.</p>
                @else
                    <ul class="mt-2 divide-y divide-hairline/60 text-xs">
                        @foreach ($posts as $pst)
                            <li>
                                <a href="{{ route('public.posts.show', ['slug' => $pst->slug]) }}" class="flex min-h-10 items-center py-1.5 font-semibold text-primary hover:underline">
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
