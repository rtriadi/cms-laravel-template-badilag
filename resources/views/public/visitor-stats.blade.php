<x-layouts.public title="Statistik Pengunjung Portal">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-hairline bg-white p-6">
            <h1 class="font-serif text-2xl font-bold text-ink">Statistik Pengunjung Portal</h1>
            <p class="mt-1 text-sm text-ink-body">Pencatatan kunjungan agregat tanpa pelacakan cookie pribadi (privacy-first analytics).</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-hairline bg-mist p-4">
                    <p class="text-xs font-semibold text-ink-muted uppercase">Total Kunjungan Tercatat</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-primary">{{ number_format($totalVisits) }}</p>
                </div>
                <div class="rounded-lg border border-hairline bg-mist p-4">
                    <p class="text-xs font-semibold text-ink-muted uppercase">Kunjungan 30 Hari Terakhir</p>
                    <p class="mt-1 font-mono text-2xl font-bold text-primary">{{ number_format($last30Days) }}</p>
                </div>
            </div>
        </header>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-xl border border-hairline bg-white p-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Kunjungan Harian Terbaru</h2>
                @if ($dailyStats->isEmpty())
                    <p class="py-6 text-center text-xs text-ink-muted">Belum ada data kunjungan harian.</p>
                @else
                    <ul class="mt-3 divide-y divide-hairline text-xs">
                        @foreach ($dailyStats as $day)
                            <li class="flex justify-between py-2">
                                <span class="font-mono text-ink">{{ $day->visit_date instanceof \Carbon\CarbonInterface ? $day->visit_date->format('Y-m-d') : $day->visit_date }}</span>
                                <span class="font-mono font-bold text-primary">{{ number_format((int) $day->total) }} kunjungan</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-xl border border-hairline bg-white p-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Halaman Paling Banyak Diakses</h2>
                @if ($topPaths->isEmpty())
                    <p class="py-6 text-center text-xs text-ink-muted">Belum ada statistik jalur halaman.</p>
                @else
                    <ul class="mt-3 divide-y divide-hairline text-xs">
                        @foreach ($topPaths as $row)
                            <li class="flex justify-between gap-3 py-2">
                                <span class="truncate font-mono text-ink">{{ $row->path }}</span>
                                <span class="shrink-0 font-mono font-bold text-primary">{{ number_format((int) $row->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layouts.public>

