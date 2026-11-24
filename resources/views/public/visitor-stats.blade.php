@php
    $statsDesc = 'Ringkasan kunjungan masyarakat ke portal resmi ' . ($satker?->nama_satker ?? 'Peradilan Agama') . ' secara agregat tanpa pelacakan data pribadi.';
    $fmtNum = static fn (int $n): string => number_format($n, 0, ',', '.');
    $chartDays = $dailyStats->reverse()->values();
    $maxDaily = max(1, (int) $chartDays->max('total'));

    // Skala bar halaman konten dihitung dari konten non-beranda agar perbandingan halaman layanan tetap terbaca jelas.
    $nonHomeMax = max(1, (int) $topPaths->reject(fn ($r) => $r->path === '/')->max('total'));
    $allTopMax = max(1, (int) $topPaths->max('total'));
@endphp
<x-layouts.public title="Statistik Pengunjung Portal" :description="$statsDesc" :canonical="route('public.visitor-stats')">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Ringkasan Utama Kunjungan -->
        <header class="relative overflow-hidden rounded-2xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <div class="relative z-10 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-2xl">
                        <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                            Transparansi Kunjungan Portal
                        </span>
                        <h1 class="mt-2 font-serif text-2xl font-bold text-white sm:text-3xl">
                            Statistik Pengunjung Portal
                        </h1>
                        <p class="mt-1.5 text-sm leading-relaxed text-emerald-100">
                            Ringkasan kunjungan masyarakat ke portal resmi {{ $satker?->nama_satker ?? 'Pengadilan Agama' }} secara agregat tanpa pelacakan data pribadi atau cookie.
                        </p>
                    </div>
                    @if ($avgDaily14 > 0)
                        <div class="rounded-xl border border-white/15 bg-white/8 px-4 py-3 text-right">
                            <p class="text-[11px] font-semibold text-emerald-100">Rata-Rata Harian (14 Hari)</p>
                            <p class="mt-0.5 font-mono text-xl font-bold text-amber-300">{{ $fmtNum($avgDaily14) }}</p>
                        </div>
                    @endif
                </div>

                <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-white/15 bg-primary-deep/85 p-4">
                        <p class="text-xs font-semibold text-emerald-100">Kunjungan Hari Ini</p>
                        <p class="mt-1.5 font-mono text-2xl font-bold text-white">{{ $fmtNum($todayVisits) }}</p>
                        <p class="mt-1 text-[11px] text-emerald-200">{{ \App\Support\Tanggal::format(now(), 'd M Y') }}</p>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-primary-deep/85 p-4">
                        <p class="text-xs font-semibold text-emerald-100">7 Hari Terakhir</p>
                        <p class="mt-1.5 font-mono text-2xl font-bold text-white">{{ $fmtNum($last7Days) }}</p>
                        <p class="mt-1 text-[11px] text-emerald-200">Akumulasi sepekan terakhir</p>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-primary-deep/85 p-4">
                        <p class="text-xs font-semibold text-emerald-100">30 Hari Terakhir</p>
                        <p class="mt-1.5 font-mono text-2xl font-bold text-amber-300">{{ $fmtNum($last30Days) }}</p>
                        <p class="mt-1 text-[11px] text-emerald-200">Akumulasi sebulan terakhir</p>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-primary-deep/85 p-4">
                        <p class="text-xs font-semibold text-emerald-100">Total Kunjungan</p>
                        <p class="mt-1.5 font-mono text-2xl font-bold text-amber-300">{{ $fmtNum($totalVisits) }}</p>
                        <p class="mt-1 text-[11px] text-emerald-200">
                            {{ $earliestLabel ? 'Sejak ' . $earliestLabel : 'Sejak portal aktif' }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Grafik Tren Kunjungan 14 Hari Terakhir -->
        @if ($chartDays->isNotEmpty())
            <section aria-label="Grafik Tren Kunjungan 14 Hari Terakhir" class="rounded-2xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-6">
                <div class="flex flex-wrap items-end justify-between gap-2 border-b border-hairline pb-3.5">
                    <div>
                        <p class="font-mono text-2xs font-bold tracking-wider text-primary uppercase">Tren Harian</p>
                        <h2 class="mt-0.5 font-serif text-lg font-bold text-ink sm:text-xl">
                            Grafik Kunjungan 14 Hari Terakhir
                        </h2>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-ink-muted">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block size-2.5 rounded-sm bg-primary" aria-hidden="true"></span>
                            Kunjungan Harian
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block size-2.5 rounded-sm bg-gold" aria-hidden="true"></span>
                            Tertinggi / Hari Ini
                        </span>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto pb-1">
                    <div class="grid min-w-[600px] items-end gap-2 pt-6" style="grid-template-columns: repeat({{ $chartDays->count() }}, minmax(0, 1fr));">
                        @foreach ($chartDays as $idx => $day)
                            @php
                                $pct = max(6, (int) round(((int) $day->total / $maxDaily) * 100));
                                $isHighlight = ((int) $day->total === $maxDaily) || ($idx === $chartDays->count() - 1);
                            @endphp
                            <div class="flex flex-col items-center gap-1.5">
                                <span class="font-mono text-[10px] font-semibold text-ink-muted">
                                    {{ $fmtNum((int) $day->total) }}
                                </span>
                                <div class="flex h-44 w-full items-end rounded-lg bg-mist/80 p-1">
                                    <div
                                        title="{{ $day->label_full }}: {{ $fmtNum((int) $day->total) }} kunjungan"
                                        @class([
                                            'w-full rounded-md transition-all duration-300',
                                            'bg-gold' => $isHighlight,
                                            'bg-primary' => ! $isHighlight,
                                        ])
                                        style="height: {{ $pct }}%;"
                                    ></div>
                                </div>
                                <span class="text-center text-[11px] font-medium whitespace-nowrap text-ink-body">
                                    {{ $day->label_short }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Rincian Harian & Halaman Populer -->
        <div class="grid items-start gap-6 lg:grid-cols-12">
            <section class="rounded-2xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-6 lg:col-span-5">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">
                    Rincian Kunjungan Harian
                </h2>
                @if ($dailyStats->isEmpty())
                    <p class="py-8 text-center text-xs text-ink-muted">Belum ada data kunjungan harian.</p>
                @else
                    <ul class="mt-2 divide-y divide-hairline text-xs">
                        @foreach ($dailyStats as $day)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <span class="font-medium text-ink">{{ $day->label_full }}</span>
                                <span class="shrink-0 rounded-md bg-primary-soft px-2.5 py-1 font-mono font-bold text-primary-deep">
                                    {{ $fmtNum((int) $day->total) }} kunjungan
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-2xl border border-hairline border-t-4 border-t-gold bg-white p-5 shadow-2xs sm:p-6 lg:col-span-7">
                <div class="border-b border-hairline pb-3">
                    <h2 class="font-serif text-lg font-bold text-ink">
                        Halaman &amp; Konten Paling Banyak Dikunjungi
                    </h2>
                    <p class="mt-0.5 text-xs text-ink-muted">
                        Klik salah satu judul di bawah untuk langsung membuka halaman informasi terkait.
                    </p>
                </div>

                @if ($topPaths->isEmpty())
                    <p class="py-8 text-center text-xs text-ink-muted">Belum ada catatan kunjungan halaman.</p>
                @else
                    <ol class="mt-2 divide-y divide-hairline">
                        @foreach ($topPaths as $row)
                            @php
                                $isHomePath = $row->path === '/';
                                $denom = $isHomePath ? $allTopMax : $nonHomeMax;
                                $barPct = max(8, min(100, (int) round(((int) $row->total / $denom) * 100)));
                            @endphp
                            <li class="py-3 first:pt-2 last:pb-0">
                                <a
                                    href="{{ url($row->path) }}"
                                    class="group block rounded-lg px-2 py-1 transition-colors hover:bg-ivory"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-start gap-2.5">
                                            <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-md bg-primary-soft font-mono text-[11px] font-bold text-primary-deep">
                                                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="line-clamp-2 text-xs leading-snug font-bold text-ink group-hover:text-primary sm:text-sm">
                                                    {{ $row->title }}
                                                </p>
                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-ink-muted">
                                                    <span class="rounded bg-mist px-2 py-0.5 font-medium text-ink-body">
                                                        {{ $row->category }}
                                                    </span>
                                                    @if (! empty($row->note))
                                                        <span>{{ $row->note }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <span class="shrink-0 font-mono text-xs font-bold text-primary sm:text-sm">
                                            {{ $fmtNum((int) $row->total) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 ml-8.5 h-1.5 w-auto overflow-hidden rounded-full bg-mist">
                                        <div
                                            @class([
                                                'h-full rounded-full',
                                                'bg-gold' => $isHomePath,
                                                'bg-primary' => ! $isHomePath,
                                            ])
                                            style="width: {{ $barPct }}%;"
                                        ></div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        @if ($hasLegacyArchive)
            <div role="note" class="rounded-xl border border-hairline bg-white px-5 py-3.5 text-xs leading-relaxed text-ink-muted shadow-2xs">
                <strong class="text-ink">Catatan Sumber Data:</strong>
                Angka kunjungan sejak {{ $earliestLabel }} menyertakan akumulasi penghitung pengunjung dari website portal sebelumnya (yang sebelumnya hanya mencatat total harian situs secara keseluruhan pada Beranda). Mulai November 2026, SatkerCMS mencatat kunjungan per halaman secara otomatis sambil menyaring bot mesin pencari.
            </div>
        @endif
    </div>
</x-layouts.public>
