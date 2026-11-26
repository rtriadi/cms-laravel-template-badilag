<div class="space-y-6">
    @if ($phpNearEol)
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Versi PHP saat ini ({{ PHP_VERSION }}) berada di bawah PHP 8.4. Segera perbarui versi PHP ke cabang stabil terbaru (PHP 8.5) sesuai syarat non-EOL SK Dirjen Badilag.
        </div>
    @endif

    @if ($auditMessage)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $auditMessage }}
        </div>
    @endif

    @if ($updateNotice)
        <div role="status" class="rounded-xl border border-[#b6d7c9] bg-[#f0f7f4] px-4 py-3 text-sm font-medium text-[#056e4f]">
            {{ $updateNotice }}
        </div>
    @endif

    @if ($updateError)
        <div role="alert" class="rounded-xl border border-amber-300 bg-[#fffbeb] px-4 py-3 text-sm font-medium text-[#92400e]">
            {{ $updateError }}
        </div>
    @endif

    @if (($canManageUpdates ?? false) && (($updateState['update_available'] ?? false) === true))
        @php
            $updateCliCommand = ($updateState['channel'] ?? 'stable') === 'dev'
                ? 'git pull && php artisan cms:update --local-only'
                : 'php artisan cms:update';
        @endphp
        <section
            aria-label="Informasi Pembaruan Aplikasi"
            class="rounded-xl border border-[#b6d7c9] bg-[#f0f7f4] p-4 sm:p-5"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-serif text-base font-bold text-[#111827]">
                            Pembaruan SatkerCMS Tersedia
                        </h3>
                        <span class="rounded-md border border-[#056e4f]/30 bg-white px-2 py-0.5 font-mono text-xs font-semibold text-[#056e4f]">
                            {{ $updateState['installed_version'] ?? $cmsVersion }} -> {{ $updateState['latest_version'] }}
                        </span>
                    </div>
                    <p class="text-xs leading-relaxed text-[#374151] sm:text-[13px]">
                        Rilis baru terdeteksi dari repositori resmi GitHub. Jalankan perintah pembaruan melalui terminal peladen agar aplikasi diperbarui secara zero-downtime dengan backup basis data otomatis.
                    </p>
                    @if (! empty($updateState['release_notes']))
                        <p class="whitespace-pre-line rounded-lg border border-[#d8e2dd] bg-white px-3 py-2 text-xs leading-relaxed text-[#374151]">{{ $updateState['release_notes'] }}</p>
                    @endif
                    @if (! empty($updateState['release_url']))
                        <div class="pt-0.5">
                            <a
                                href="{{ $updateState['release_url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-11 items-center text-xs font-semibold text-[#056e4f] underline underline-offset-4 hover:text-[#04543c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#056e4f] sm:min-h-0"
                            >
                                Lihat Catatan Rilis di GitHub
                            </a>
                        </div>
                    @endif
                </div>

                <div
                    x-data="{ copied: false, cmd: @js($updateCliCommand) }"
                    class="w-full shrink-0 rounded-lg border border-[#022c20] bg-[#022c20] p-3 text-white lg:w-auto lg:min-w-[320px]"
                >
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono text-[11px] font-medium text-[#d8e2dd]">Perintah Terminal Peladen</span>
                        <button
                            type="button"
                            x-on:click="navigator.clipboard?.writeText(cmd); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-white/30 bg-[#033d2c] px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:bg-[#056e4f] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#ff9900] sm:min-h-8"
                        >
                            <span x-show="!copied">Salin Perintah</span>
                            <span x-cloak x-show="copied">Tersalin</span>
                        </button>
                    </div>
                    <code class="mt-2 block overflow-x-auto font-mono text-xs text-white">{{ $updateCliCommand }}</code>
                </div>
            </div>
        </section>
    @endif

    <!-- Calm Executive Greeting Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-serif text-2xl font-bold tracking-tight text-[#0f172a]">
                Selamat datang, {{ auth()->user()?->name ?? 'Pengelola' }}
            </h2>
            <p class="mt-0.5 text-xs text-[#64748b] sm:text-[13px]">
                Ringkasan kesiapan portal satuan kerja terhadap standar SK Dirjen Badilag 2722/2026 (SINOVA).
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($canManageUpdates ?? false)
                <button
                    type="button"
                    wire:click="checkForUpdates"
                    wire:loading.attr="disabled"
                    wire:target="checkForUpdates"
                    class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-[#d8e2dd] bg-white px-3.5 text-xs font-semibold text-[#334155] transition-colors hover:border-primary/40 hover:bg-primary-soft hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-9"
                >
                    <span wire:loading.remove wire:target="checkForUpdates">Cek Pembaruan</span>
                    <span wire:loading wire:target="checkForUpdates">Memeriksa GitHub...</span>
                </button>
            @endif
            <a
                href="{{ route('admin.sinova-links') }}"
                class="inline-flex min-h-11 items-center rounded-lg border border-[#e2e8e5] bg-white px-3.5 text-xs font-semibold text-[#334155] transition-colors hover:border-primary/40 hover:bg-primary-soft hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:min-h-9"
            >
                Link SINOVA
            </a>
            @if (auth()->user()?->can('manage-sinova-links') || auth()->user()?->hasRole('super_admin'))
                <button
                    type="button"
                    wire:click="runQuickAudit"
                    class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-primary bg-white px-4 text-xs font-semibold text-primary transition-colors hover:bg-primary hover:text-white"
                >
                    <span wire:loading.remove wire:target="runQuickAudit">Cek Kesehatan SINOVA</span>
                    <span wire:loading wire:target="runQuickAudit">Memeriksa tautan...</span>
                </button>
            @endif
        </div>
    </div>

    <!-- 4 Crisp Borderless-Stripe KPI Cards -->
    <section aria-label="Ringkasan Metrik Utama" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="studio-card p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-[10px] font-bold tracking-wider text-[#64748b] uppercase">Skor Kepatuhan SINOVA</span>
                <span class="rounded-md bg-[#edf7f3] px-2 py-0.5 font-mono text-[10px] font-bold text-primary">
                    {{ $summary['filled'] }}/{{ $summary['total'] }} Siap
                </span>
            </div>
            <p class="mt-2.5 font-serif text-3xl font-bold text-primary">{{ $summary['weighted_score'] }}</p>
            <p class="mt-1 text-xs text-[#64748b]">
                dari 100 poin &middot; {{ $summary['empty'] }} tautan belum diisi
            </p>
        </div>

        <a href="{{ route('admin.posts') }}" class="studio-card block p-5 transition-colors hover:border-primary/40">
            <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-[10px] font-bold tracking-wider text-[#64748b] uppercase">Publikasi Tayang</span>
                <span class="rounded-md bg-[#f1f5f9] px-2 py-0.5 text-[10px] font-semibold text-[#334155]">Editorial</span>
            </div>
            <p class="mt-2.5 font-serif text-3xl font-bold text-[#0f172a]">{{ number_format($publishedPostsCount) }}</p>
            <p class="mt-1 text-xs text-[#64748b]">Berita, pengumuman &amp; artikel aktif di portal</p>
        </a>

        <a href="{{ route('admin.comments') }}" class="studio-card block p-5 transition-colors hover:border-primary/40">
            <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-[10px] font-bold tracking-wider text-[#64748b] uppercase">Antrean Komentar</span>
                @if (($canModerateComments ?? false) && $pendingCommentsCount > 0)
                    <span class="rounded-md bg-[#fff7e6] px-2 py-0.5 text-[10px] font-semibold text-[#92400e]">Perlu Tinjau</span>
                @else
                    <span class="rounded-md bg-[#edf7f3] px-2 py-0.5 text-[10px] font-semibold text-primary">Terkendali</span>
                @endif
            </div>
            <p @class([
                'mt-2.5 font-serif text-3xl font-bold',
                'text-[#b45309]' => ($canModerateComments ?? false) && $pendingCommentsCount > 0,
                'text-[#0f172a]' => ! (($canModerateComments ?? false) && $pendingCommentsCount > 0),
            ])>
                {{ ($canModerateComments ?? false) ? $pendingCommentsCount : 0 }}
            </p>
            <p class="mt-1 text-xs text-[#64748b]">Menunggu moderasi humas satker</p>
        </a>

        <div class="studio-card p-5">
            <div class="flex items-center justify-between gap-2">
                <span class="font-mono text-[10px] font-bold tracking-wider text-[#64748b] uppercase">Kunjungan &amp; Sistem</span>
                <span class="rounded-md bg-[#edf7f3] px-2 py-0.5 font-mono text-[10px] font-semibold text-primary">PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</span>
            </div>
            <p class="mt-2.5 font-serif text-3xl font-bold text-[#0f172a]">{{ number_format($totalVisits) }}</p>
            <p class="mt-1 text-xs text-[#64748b]">
                Laravel {{ app()->version() }} &middot;
                <span class="{{ $brokenLinksCount > 0 ? 'font-semibold text-red-700' : 'text-emerald-700' }}">{{ $brokenLinksCount }} tautan rusak</span>
            </p>
        </div>
    </section>

    <!-- Main 2-Column Balanced Workspace (7 / 5) -->
    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Left 7 Columns: Quick Draft Composer + Recent Posts + Attention Queue -->
        <div class="space-y-6 lg:col-span-7">
            @if ($canWritePosts)
                <section class="studio-card p-5 sm:p-6">
                    <div class="border-b border-[#e2e8e5] pb-3.5">
                        <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Draf Cepat Publikasi</h3>
                        <p class="mt-0.5 text-xs text-[#64748b]">Catat ide pengumuman atau berita singkat langsung dari dashboard, lalu lengkapi kapan saja.</p>
                    </div>

                    @if ($quickDraftNotice)
                        <div role="status" class="mt-4 rounded-lg border border-emerald-200 bg-[#edf7f3] px-3.5 py-2.5 text-xs font-medium text-primary">
                            {{ $quickDraftNotice }}
                        </div>
                    @endif

                    <form wire:submit="saveQuickDraft" class="mt-4 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-12">
                            <div class="sm:col-span-8">
                                <flux:field>
                                    <flux:label for="qd_title">Judul Draf *</flux:label>
                                    <flux:input id="qd_title" wire:model="quickDraftTitle" required placeholder="Contoh: Sidang Keliling Triwulan IV Kec. Kota Barat" />
                                    <flux:error name="quickDraftTitle" />
                                </flux:field>
                            </div>
                            <div class="sm:col-span-4">
                                <flux:field>
                                    <flux:label for="qd_category">Kategori</flux:label>
                                    <flux:select id="qd_category" wire:model="quickDraftCategory">
                                        @foreach ($categories as $catKey => $catLabel)
                                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            </div>
                        </div>

                        <flux:field>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <flux:label for="qd_body" class="mb-0!">Catatan Ringkas *</flux:label>
                                <span class="font-mono text-[11px] text-[#64748b]">Disimpan sebagai draf</span>
                            </div>
                            <flux:textarea id="qd_body" wire:model="quickDraftBody" rows="3" required placeholder="Tulis poin utama pengumuman atau agenda kegiatan satuan kerja..." />
                            <flux:error name="quickDraftBody" />
                        </flux:field>

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" class="h-9 cursor-pointer bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover">
                                Simpan sebagai Draf
                            </flux:button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="studio-card p-5 sm:p-6">
                <div class="flex items-center justify-between border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Publikasi Terbaru</h3>
                    <a href="{{ route('admin.posts') }}" class="text-xs font-semibold text-primary hover:underline">Kelola Semua &rarr;</a>
                </div>

                @if ($recentPosts->isEmpty())
                    <p class="py-10 text-center text-sm text-[#64748b]">Belum ada berita, pengumuman, atau artikel yang dibuat.</p>
                @else
                    <ul class="divide-y divide-[#eef2f0]">
                        @foreach ($recentPosts as $rPost)
                            <li wire:key="rpost-{{ $rPost->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-semibold text-[#0f172a]">{{ $rPost->title }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 font-mono text-[11px] text-[#64748b]">
                                        <span>{{ $categories[$rPost->category] ?? $rPost->category }}</span>
                                        <span>&middot;</span>
                                        <span>{{ number_format((int) $rPost->views_count) }} dilihat</span>
                                        <span>&middot;</span>
                                        <span>{{ number_format((int) $rPost->approved_comments_count) }} komentar</span>
                                        @if (($canModerateComments ?? false) && (int) ($rPost->pending_comments_count ?? 0) > 0)
                                            <span class="font-bold text-[#b45309]">({{ $rPost->pending_comments_count }} pending)</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-[#edf7f3] text-primary' => $rPost->is_published,
                                        'bg-[#f1f5f9] text-[#64748b]' => ! $rPost->is_published,
                                    ])>
                                        {{ $rPost->is_published ? 'Tayang' : 'Draf' }}
                                    </span>
                                    @if ($canWritePosts ?? false)
                                        <a href="{{ route('admin.posts', ['edit' => $rPost->id]) }}" class="inline-flex h-8 items-center rounded-md border border-[#e2e8e5] bg-white px-2.5 text-xs font-semibold text-[#334155] hover:border-primary hover:text-primary">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($attentionItems->isNotEmpty())
                <section class="studio-card p-5 sm:p-6">
                    <div class="flex items-center justify-between border-b border-[#e2e8e5] pb-3.5">
                        <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Kriteria Perlu Tindak Lanjut</h3>
                        <a href="{{ route('admin.sinova-links') }}" class="text-xs font-semibold text-primary hover:underline">Lihat Matriks &rarr;</a>
                    </div>
                    <ul class="divide-y divide-[#eef2f0]">
                        @foreach ($attentionItems as $item)
                            <li wire:key="attn-{{ $item->id }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-semibold text-[#0f172a]">
                                        <span class="font-mono text-xs text-primary">{{ $item->code }}</span>
                                        {{ $item->name }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-[#64748b]">{{ $item->health_message ?: 'Perlu pengisian dokumen atau verifikasi tautan.' }}</p>
                                </div>
                                <span @class([
                                    'inline-flex shrink-0 items-center rounded-md px-2.5 py-1 text-[11px] font-semibold',
                                    'bg-[#fff7e6] text-[#92400e]' => $item->health_status === 'warning',
                                    'bg-[#fef2f2] text-red-700' => in_array($item->health_status, ['broken', 'empty'], true),
                                ])>
                                    {{ $item->health_status === 'warning' ? 'Revisi (5)' : 'Belum Siap (0)' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <!-- Right 5 Columns: Kesiapan 3 Aspek SINOVA + Pintasan Cepat + Log Aktivitas -->
        <aside class="space-y-6 lg:col-span-5">
            <section class="studio-card p-5 sm:p-6">
                <div class="border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Kesiapan 3 Aspek SINOVA</h3>
                    <p class="mt-0.5 text-xs text-[#64748b]">Distribusi bobot penilaian otomatis dari {{ $summary['total'] }} butir kriteria.</p>
                </div>

                <div class="mt-4 space-y-4">
                    @foreach ($summary['aspects'] as $key => $aspect)
                        <div wire:key="aspect-{{ $key }}">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="font-semibold text-[#0f172a]">{{ $aspect['label'] }} ({{ $aspect['weight'] }}%)</span>
                                <span class="font-mono font-bold text-[#0f172a]">{{ $aspect['score_pct'] }}%</span>
                            </div>
                            <div
                                class="mt-2 h-2 w-full overflow-hidden rounded-full bg-[#e6ece9]"
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ (int) $aspect['score_pct'] }}"
                                aria-label="Skor {{ $aspect['label'] }}"
                            >
                                <div
                                    class="h-full rounded-full {{ (int) $aspect['score_pct'] >= 90 ? 'bg-primary' : 'bg-gold' }}"
                                    style="width: {{ min(100, (int) $aspect['score_pct']) }}%"
                                ></div>
                            </div>
                            <p class="mt-1 font-mono text-[11px] text-[#64748b]">
                                {{ $aspect['healthy'] }} siap &middot; {{ $aspect['warning'] }} revisi &middot; {{ $aspect['broken'] + $aspect['empty'] }} nol
                            </p>
                        </div>
                    @endforeach
                </div>

                <a
                    href="{{ route('admin.sinova-links') }}"
                    class="mt-5 flex h-9 w-full items-center justify-center rounded-lg bg-[#edf7f3] text-xs font-semibold text-primary transition-colors hover:bg-primary hover:text-white"
                >
                    Buka Matriks {{ $summary['total'] }} Link Kriteria SINOVA &rarr;
                </a>
            </section>

            <!-- Compact Quick Shortcuts -->
            <section class="studio-card p-5">
                <h3 class="font-serif text-[15px] font-bold text-[#0f172a]">Akses Cepat Editorial</h3>
                <div class="mt-3 grid grid-cols-2 gap-2.5">
                    <a href="{{ route('admin.pages') }}" class="flex items-center justify-between rounded-lg border border-[#e2e8e5] bg-[#f9fbfa] px-3 py-2.5 text-xs font-semibold text-[#0f172a] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary">
                        <span>Page Builder</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="{{ route('admin.posts') }}" class="flex items-center justify-between rounded-lg border border-[#e2e8e5] bg-[#f9fbfa] px-3 py-2.5 text-xs font-semibold text-[#0f172a] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary">
                        <span>Publikasi</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="{{ route('admin.documents') }}" class="flex items-center justify-between rounded-lg border border-[#e2e8e5] bg-[#f9fbfa] px-3 py-2.5 text-xs font-semibold text-[#0f172a] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary">
                        <span>Dokumen PDF</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="{{ route('admin.settings') }}" class="flex items-center justify-between rounded-lg border border-[#e2e8e5] bg-[#f9fbfa] px-3 py-2.5 text-xs font-semibold text-[#0f172a] transition-colors hover:border-primary/40 hover:bg-[#edf7f3] hover:text-primary">
                        <span>Pengaturan</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </section>

            @if ($canViewAudit ?? false)
                <section class="studio-card p-5 sm:p-6">
                    <div class="border-b border-[#e2e8e5] pb-3.5">
                        <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Log Aktivitas Terbaru</h3>
                    </div>
                    @if ($recentActivities->isEmpty())
                        <p class="mt-4 text-xs text-[#64748b]">Belum ada catatan aktivitas yang direkam.</p>
                    @else
                        <ul class="mt-4 space-y-3.5">
                            @foreach ($recentActivities as $act)
                                <li wire:key="act-{{ $act->id }}" class="flex items-start gap-3">
                                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[12.5px] font-medium text-[#0f172a]">{{ $act->description }}</p>
                                        <p class="mt-0.5 font-mono text-[10.5px] text-[#64748b]">
                                            {{ $act->causer?->name ?? 'Sistem' }} &middot; {{ $act->created_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
        </aside>
    </div>
</div>
