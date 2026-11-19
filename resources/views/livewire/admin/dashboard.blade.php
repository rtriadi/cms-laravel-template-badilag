<div class="space-y-6">
    @if ($phpNearEol)
        <div class="rounded-lg border border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Versi PHP saat ini ({{ PHP_VERSION }}) berada di bawah PHP 8.4. Segera perbarui versi PHP ke cabang stabil terbaru (PHP 8.5) sesuai syarat non-EOL SK Dirjen Badilag.
        </div>
    @endif

    @if ($auditMessage)
        <div class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $auditMessage }}
        </div>
    @endif

    <!-- Decision-Oriented SINOVA Readiness Strip -->
    <div class="rounded-lg border border-hairline bg-white p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="font-mono text-xs font-semibold text-ink-muted uppercase">Kesiapan Evaluasi SINOVA</p>
                <div class="mt-1 flex flex-wrap items-baseline gap-4">
                    <span class="font-serif text-3xl font-bold text-primary-deep">{{ $summary['weighted_score'] }} / 100</span>
                    <span class="font-mono text-sm font-semibold text-emerald-700">{{ $summary['filled'] }}/{{ $summary['total'] }} link terisi</span>
                    <span class="font-mono text-sm font-semibold text-red-700">{{ $summary['empty'] }} kosong</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.sinova-links') }}" class="inline-flex min-h-11 items-center rounded-md border border-primary bg-white px-4 text-sm font-semibold text-primary hover:bg-primary-soft">
                    Buka Link SINOVA
                </a>
                @if (auth()->user()?->can('manage-sinova-links') || auth()->user()?->hasRole('super_admin'))
                    <flux:button type="button" wire:click="runQuickAudit" variant="primary" class="min-h-11 bg-primary font-semibold text-white hover:bg-primary-hover">
                        <span wire:loading.remove wire:target="runQuickAudit">Periksa Kesiapan Sekarang</span>
                        <span wire:loading wire:target="runQuickAudit">Memeriksa tautan...</span>
                    </flux:button>
                @endif
            </div>
        </div>

        <!-- Three PDF Weighted Aspects -->
        <div class="mt-5 grid gap-4 border-t border-hairline pt-4 md:grid-cols-3">
            @foreach ($summary['aspects'] as $key => $aspect)
                <div wire:key="aspect-{{ $key }}" class="rounded-md border border-hairline bg-mist p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-ink">{{ $aspect['label'] }}</span>
                        <span class="font-mono text-xs font-bold text-primary">{{ $aspect['weight'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full bg-primary" style="width: {{ min(100, (int) $aspect['score_pct']) }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between font-mono text-xs text-ink-muted">
                        <span>Skor: {{ $aspect['score_pct'] }}%</span>
                        <span>{{ $aspect['healthy'] }} siap / {{ $aspect['warning'] }} revisi / {{ $aspect['broken'] + $aspect['empty'] }} nol</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Pintasan Cepat Pengisian Konten Website -->
    <section aria-label="Pintasan Pengisian Konten" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.pages') }}" class="flex flex-col justify-between rounded-lg border border-hairline border-t-4 border-t-primary bg-white p-4 shadow-2xs hover:border-primary">
            <div>
                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-bold text-primary">36+ HALAMAN</span>
                <h2 class="mt-2 font-serif text-base font-bold text-ink">Halaman Profil &amp; Layanan Hukum</h2>
                <p class="mt-1 text-xs leading-relaxed text-ink-muted">Edit isi halaman Prodeo &amp; Posbakum, Prosedur Berperkara, Biaya, Visi Misi, hingga Standar Pelayanan.</p>
            </div>
            <span class="mt-3 text-xs font-semibold text-primary">Kelola Halaman Website &rarr;</span>
        </a>

        <a href="{{ route('admin.posts') }}" class="flex flex-col justify-between rounded-lg border border-hairline border-t-4 border-t-gold bg-white p-4 shadow-2xs hover:border-primary">
            <div>
                <span class="rounded bg-amber-100 px-2 py-0.5 font-mono text-[11px] font-bold text-ink">ARTIKEL &amp; BERITA</span>
                <h2 class="mt-2 font-serif text-base font-bold text-ink">Berita, Artikel &amp; Pengumuman</h2>
                <p class="mt-1 text-xs leading-relaxed text-ink-muted">Tulis berita kegiatan, pengumuman, informasi serta merta, serta artikel edukasi bantuan hukum (Prodeo/Posbakum).</p>
            </div>
            <span class="mt-3 text-xs font-semibold text-primary">Tulis Artikel / Berita &rarr;</span>
        </a>

        <a href="{{ route('admin.documents') }}" class="flex flex-col justify-between rounded-lg border border-hairline border-t-4 border-t-primary bg-white p-4 shadow-2xs hover:border-primary">
            <div>
                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-bold text-primary">23 KOLEKSI PDF</span>
                <h2 class="mt-2 font-serif text-base font-bold text-ink">Arsip Dokumen PDF &amp; SAKIP</h2>
                <p class="mt-1 text-xs leading-relaxed text-ink-muted">Unggah PDF LKjIP, Renstra, DIPA, LRA, LHKPN, Panggilan Ghaib, SK Biaya Perkara, dan Brosur Posbakum.</p>
            </div>
            <span class="mt-3 text-xs font-semibold text-primary">Unggah Dokumen PDF &rarr;</span>
        </a>

        <a href="{{ route('admin.kepaniteraan') }}" class="flex flex-col justify-between rounded-lg border border-hairline border-t-4 border-t-primary-deep bg-white p-4 shadow-2xs hover:border-primary">
            <div>
                <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-bold text-primary-deep">DATA PERADILAN</span>
                <h2 class="mt-2 font-serif text-base font-bold text-ink">Pegawai, Sidang &amp; Statistik</h2>
                <p class="mt-1 text-xs leading-relaxed text-ink-muted">Kelola data pimpinan/hakim/pegawai, jadwal persidangan harian, dan rekap statistik perkara bulanan.</p>
            </div>
            <span class="mt-3 text-xs font-semibold text-primary">Kelola Data Peradilan &rarr;</span>
        </a>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Items Requiring Attention -->
        <section class="rounded-lg border border-hairline bg-white p-5 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">Kriteria yang Memerlukan Tindak Lanjut</h2>
                <a href="{{ route('admin.sinova-links') }}" class="text-xs font-semibold text-primary hover:underline">Kelola Semua</a>
            </div>

            @if ($attentionItems->isEmpty())
                <div class="py-8 text-center text-sm text-ink-body">
                    Seluruh kriteria yang berlaku untuk tingkat satuan kerja ini berstatus siap dan lengkap.
                </div>
            @else
                <ul class="divide-y divide-hairline">
                    @foreach ($attentionItems as $item)
                        <li wire:key="attn-{{ $item->id }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-ink">
                                    <span class="font-mono text-xs text-primary">{{ $item->code }}</span>
                                    {{ $item->name }}
                                </p>
                                <p class="text-xs text-ink-muted">{{ $item->health_message ?: 'Perlu pengisian dokumen atau verifikasi tautan.' }}</p>
                            </div>
                            <span @class([
                                'inline-flex shrink-0 items-center rounded border px-2.5 py-1 text-xs font-semibold',
                                'border-amber-200 bg-amber-50 text-amber-900' => $item->health_status === 'warning',
                                'border-red-200 bg-red-50 text-red-800' => in_array($item->health_status, ['broken', 'empty'], true),
                            ])>
                                {{ $item->health_status === 'warning' ? 'Perlu Perbaikan (5)' : 'Belum Siap (0)' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <!-- Runtime & Activity Column -->
        <aside class="space-y-6">
            <div class="rounded-lg border border-hairline bg-white p-5">
                <h2 class="font-serif text-lg font-bold text-ink">Status Infrastruktur</h2>
                <dl class="mt-3 space-y-2 text-xs">
                    <div class="flex justify-between border-b border-hairline pb-2">
                        <dt class="text-ink-muted">Versi PHP</dt>
                        <dd class="font-mono font-semibold text-ink">{{ PHP_VERSION }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-hairline pb-2">
                        <dt class="text-ink-muted">Laravel &amp; CMS</dt>
                        <dd class="font-mono font-semibold text-ink">{{ app()->version() }} / {{ config('satker.cms_version') }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-hairline pb-2">
                        <dt class="text-ink-muted">Total Kunjungan</dt>
                        <dd class="font-mono font-semibold text-ink">{{ number_format($totalVisits) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">Tautan Bermasalah</dt>
                        <dd class="font-mono font-semibold {{ $brokenLinksCount > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                            {{ $brokenLinksCount }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5">
                <h2 class="font-serif text-lg font-bold text-ink">Aktivitas Terbaru</h2>
                @if ($recentActivities->isEmpty())
                    <p class="mt-3 text-sm text-ink-muted">Belum ada catatan aktivitas yang direkam.</p>
                @else
                    <ul class="mt-3 space-y-2.5 text-xs">
                        @foreach ($recentActivities as $act)
                            <li wire:key="act-{{ $act->id }}" class="border-b border-hairline pb-2 last:border-b-0 last:pb-0">
                                <p class="font-medium text-ink">{{ $act->description }}</p>
                                <p class="mt-0.5 text-ink-muted">
                                    {{ $act->causer?->name ?? 'Sistem' }} · {{ $act->created_at?->diffForHumans() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </aside>
    </div>
</div>
