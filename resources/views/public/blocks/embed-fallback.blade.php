@php
    $embedUrl = $block['resolved_embed_url'] ?? null;
    $embedTabs = is_array($block['resolved_embed_tabs'] ?? null) ? $block['resolved_embed_tabs'] : [];
    $activeTabKey = (string) ($block['active_simtepa_tab'] ?? ($embedTabs[0]['key'] ?? ''));
    $fallbackMode = $block['fallback_mode'] ?? 'officials_table';
    $isCrossSiteSipp = (bool) ($block['is_cross_site_sipp'] ?? false);
    $showFallback = $forceFallback || empty($embedUrl);
    $embedViewMode = (string) ($block['embed_view_mode'] ?? 'native');
    $simtepaProfiles = is_array($block['simtepa_native_profiles'] ?? null) ? $block['simtepa_native_profiles'] : null;
    $activeJsonUrl = $block['resolved_json_url'] ?? null;

    if (! $activeJsonUrl) {
        foreach ($embedTabs as $t) {
            if ($t['key'] === $activeTabKey) {
                $activeJsonUrl = $t['json_url'] ?? null;
                break;
            }
        }
    }

    $showNativeSimtepa = ! $showFallback && $simtepaProfiles !== null && $embedViewMode !== 'iframe';
@endphp

@if (! empty($block['note']))
    <p class="text-xs text-ink-muted">{{ $block['note'] }}</p>
@endif

@if (! $showFallback)
    <div
        data-simtepa-embed-block
        data-embed-mode="{{ $showNativeSimtepa ? 'native' : 'iframe' }}"
        class="space-y-4 rounded-xl border border-hairline bg-mist p-3.5 sm:p-5"
    >
        @if ($embedTabs !== [])
            <div class="border-b border-hairline pb-3.5">
                <p class="mb-2 text-xs font-bold text-ink">Pilih Kategori Data Pegawai SIMTEPA:</p>
                <div role="tablist" aria-label="Kategori Profil Pegawai SIMTEPA" class="flex flex-wrap gap-1.5">
                    @foreach ($embedTabs as $tabIndex => $tab)
                        @php $isActiveTab = $tab['key'] === $activeTabKey || ($activeTabKey === '' && $tabIndex === 0); @endphp
                        <a
                            href="{{ request()->fullUrlWithQuery(['simtepa_tab' => $tab['key'], 'fallback' => null, 'embed_mode' => $embedViewMode === 'iframe' ? 'iframe' : null]) }}"
                            role="tab"
                            aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                            data-simtepa-tab-btn
                            data-html-url="{{ $tab['html_url'] }}"
                            data-json-url="{{ $tab['json_url'] }}"
                            @class([
                                'pub-btn inline-flex min-h-10 items-center rounded-lg px-3 py-1.5 text-xs font-semibold',
                                'bg-primary text-white shadow-2xs' => $isActiveTab,
                                'border border-hairline bg-white text-ink hover:border-primary hover:bg-primary-soft hover:text-primary' => ! $isActiveTab,
                            ])
                        >
                            {{ $loop->iteration }}. {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($showNativeSimtepa)
            <!-- Hybrid Mode B: Native SIMTEPA JSON Directory (Dynamic Height, Zero Scrollbar, Mobile-First) -->
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-primary-soft px-2.5 py-1 font-semibold text-primary">
                        <span class="size-1.5 rounded-full bg-primary" aria-hidden="true"></span>
                        Tampilan Ringkas SIMTEPA ({{ count($simtepaProfiles) }} aparatur)
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs md:justify-end">
                    <a
                        href="{{ request()->fullUrlWithQuery(['embed_mode' => 'iframe', 'fallback' => null]) }}"
                        class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                    >
                        Tampilan Embed Asli
                    </a>
                    <a
                        href="{{ request()->fullUrlWithQuery(['fallback' => 1]) }}"
                        class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg border border-primary bg-white px-3 font-semibold text-primary hover:bg-primary-soft"
                    >
                        Tampilkan Data Cadangan Lokal
                    </a>
                    @if ($activeJsonUrl)
                        <a
                            href="{{ $activeJsonUrl }}"
                            data-simtepa-json-link
                            target="_blank"
                            rel="noopener noreferrer"
                            class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3 font-mono font-semibold text-ink hover:bg-mist"
                        >
                            Endpoint JSON &nearr;
                        </a>
                    @endif
                    <a
                        href="{{ $embedUrl }}"
                        data-simtepa-html-link
                        target="_blank"
                        rel="noopener noreferrer"
                        class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg bg-primary px-3 font-semibold text-white hover:bg-primary-hover"
                    >
                        Buka Halaman Sumber &nearr;
                    </a>
                </div>
            </div>

            @if (count($simtepaProfiles) > 3)
                <div class="relative max-w-md">
                    <label for="simtepa-search-{{ $activeTabKey ?: 'all' }}" class="sr-only">Cari nama atau jabatan aparatur</label>
                    <input
                        id="simtepa-search-{{ $activeTabKey ?: 'all' }}"
                        type="search"
                        data-simtepa-search
                        placeholder="Cari nama atau jabatan aparatur..."
                        class="min-h-11 w-full rounded-lg border border-[#768780] bg-white px-3.5 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                    >
                </div>
            @endif

            @if ($simtepaProfiles === [])
                <div class="rounded-xl border border-hairline bg-white p-8 text-center">
                    <p class="text-sm font-semibold text-ink">Belum ada data aparatur pada kategori ini di SIMTEPA.</p>
                    <p class="mt-1 text-xs text-ink-muted">Gunakan tombol Tampilan Embed Asli atau Data Cadangan Lokal di atas bila diperlukan.</p>
                </div>
            @else
                <div class="grid items-stretch gap-4 sm:grid-cols-2 lg:grid-cols-3" data-simtepa-grid>
                    @foreach ($simtepaProfiles as $person)
                        @php
                            $initials = collect(preg_split('/\s+/', trim((string) preg_replace('/[^A-Za-z\s]/', '', $person['nama_gelar']))) ?: [])
                                ->filter()
                                ->take(2)
                                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('') ?: 'PA';
                            $searchBlob = mb_strtolower($person['nama_gelar'].' '.$person['jabatan'].' '.$person['pangkat_ruang']);
                        @endphp
                        <article
                            data-simtepa-card
                            data-search-text="{{ $searchBlob }}"
                            class="flex flex-col justify-between rounded-xl border border-hairline bg-white p-4 shadow-2xs transition-colors hover:border-primary/50"
                        >
                            <div class="flex items-start gap-3.5">
                                <div class="relative flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-hairline bg-primary-soft font-serif text-base font-bold text-primary">
                                    <span>{{ $initials }}</span>
                                    @if (! empty($person['photo_url']))
                                        <img
                                            src="{{ $person['photo_url'] }}"
                                            alt="Foto {{ $person['nama_gelar'] }}"
                                            width="64"
                                            height="64"
                                            loading="lazy"
                                            data-hide-on-error
                                            class="absolute inset-0 size-full object-cover object-top"
                                        >
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    @if ($person['jabatan'] !== '')
                                        <span class="inline-block rounded bg-primary-soft px-2 py-0.5 text-[11px] font-semibold text-primary">
                                            {{ $person['jabatan'] }}
                                        </span>
                                    @endif
                                    <h3 class="mt-1 font-serif text-base leading-snug font-bold text-ink">
                                        {{ $person['nama_gelar'] }}
                                    </h3>
                                    @if ($person['pangkat_ruang'] !== '')
                                        <p class="mt-1 text-xs text-ink-body">{{ $person['pangkat_ruang'] }}</p>
                                    @endif
                                    @if ($person['tmt_jabatan'] !== '')
                                        <p class="mt-0.5 font-mono text-[11px] text-ink-muted">TMT: {{ $person['tmt_jabatan'] }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($person['riwayat_pendidikan_html'] !== '' || $person['riwayat_pekerjaan_html'] !== '')
                                <details class="mt-3.5 border-t border-hairline/70 pt-2.5 text-xs">
                                    <summary class="flex min-h-9 cursor-pointer items-center justify-between font-semibold text-primary hover:underline">
                                        <span>Riwayat Pendidikan &amp; Jabatan</span>
                                        <span class="font-mono text-[11px] text-ink-muted">Lihat</span>
                                    </summary>
                                    <div class="mt-2.5 space-y-3 text-ink-body">
                                        @if ($person['riwayat_pendidikan_html'] !== '')
                                            <div>
                                                <p class="font-semibold text-ink">Riwayat Pendidikan</p>
                                                <div class="prose mt-1 max-w-none text-xs leading-relaxed text-ink-body">
                                                    {!! $person['riwayat_pendidikan_html'] !!}
                                                </div>
                                            </div>
                                        @endif
                                        @if ($person['riwayat_pekerjaan_html'] !== '')
                                            <div>
                                                <p class="font-semibold text-ink">Riwayat Pekerjaan</p>
                                                <div class="prose mt-1 max-w-none text-xs leading-relaxed text-ink-body">
                                                    {!! $person['riwayat_pekerjaan_html'] !!}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>
                <p data-simtepa-empty hidden class="rounded-xl border border-hairline bg-white py-8 text-center text-sm text-ink-muted">
                    Tidak ada aparatur yang cocok dengan kata pencarian tersebut.
                </p>
            @endif
        @else
            <!-- Hybrid Mode A: Full-Width App Stage + Adaptive Viewport Height + Fullscreen Focus Mode -->
            <div data-embed-stage class="space-y-3">
                <div class="flex flex-col gap-2.5 rounded-xl border border-hairline bg-white px-3.5 py-2.5 text-xs md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 font-semibold text-primary">
                            <span class="size-2 rounded-full bg-primary" aria-hidden="true"></span>
                            Tampilan Langsung Aplikasi Eksternal Aktif
                        </span>
                        <div class="hidden items-center gap-1 rounded-lg border border-hairline bg-mist p-0.5 sm:inline-flex" role="group" aria-label="Atur tinggi jendela embed">
                            <button
                                type="button"
                                data-embed-height-btn="auto"
                                aria-pressed="true"
                                class="cursor-pointer rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-primary shadow-2xs"
                            >
                                Adaptif
                            </button>
                            <button
                                type="button"
                                data-embed-height-btn="tall"
                                aria-pressed="false"
                                class="cursor-pointer rounded-md px-2 py-1 text-[11px] font-medium text-ink-muted hover:text-ink"
                            >
                                Lega
                            </button>
                            <button
                                type="button"
                                data-embed-height-btn="full"
                                aria-pressed="false"
                                class="cursor-pointer rounded-md px-2 py-1 text-[11px] font-medium text-ink-muted hover:text-ink"
                            >
                                Ekstra
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 md:justify-end">
                        @if ($activeJsonUrl && $embedViewMode === 'iframe')
                            <a
                                href="{{ request()->fullUrlWithQuery(['embed_mode' => null, 'fallback' => null]) }}"
                                class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg border border-primary bg-primary-soft px-3 font-semibold text-primary hover:bg-primary hover:text-white"
                            >
                                Tampilan Ringkas (Native)
                            </a>
                        @endif
                        <button
                            type="button"
                            data-embed-fullscreen-btn
                            aria-pressed="false"
                            class="pub-btn inline-flex min-h-10 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                        >
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 8V3.5H8M16.5 8V3.5H12M3.5 12v4.5H8M16.5 12v4.5H12" />
                            </svg>
                            <span data-embed-fullscreen-label>Layar Penuh</span>
                        </button>
                        <button
                            type="button"
                            data-simtepa-reload-btn
                            class="pub-btn inline-flex min-h-10 cursor-pointer items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary"
                        >
                            Muat Ulang Embed
                        </button>
                        <a
                            href="{{ request()->fullUrlWithQuery(['fallback' => 1]) }}"
                            class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg border border-primary bg-white px-3 font-semibold text-primary hover:bg-primary-soft"
                        >
                            Tampilkan Data Cadangan Lokal
                        </a>
                        @if ($activeJsonUrl)
                            <a
                                href="{{ $activeJsonUrl }}"
                                data-simtepa-json-link
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3 font-mono font-semibold text-ink hover:bg-mist"
                            >
                                Endpoint JSON &nearr;
                            </a>
                        @endif
                        <a
                            href="{{ $embedUrl }}"
                            data-simtepa-html-link
                            target="_blank"
                            rel="noopener noreferrer"
                            class="pub-btn inline-flex min-h-10 items-center whitespace-nowrap rounded-lg bg-primary px-3 font-semibold text-white hover:bg-primary-hover"
                        >
                            Buka Halaman Sumber &nearr;
                        </a>
                    </div>
                </div>

                @if ($isCrossSiteSipp)
                    <div role="note" class="rounded-lg border border-amber-300 bg-amber-50 px-3.5 py-2.5 text-xs leading-relaxed text-ink">
                        <strong>Catatan Tampilan Embed SIPP:</strong>
                        Situs sedang diakses melalui domain atau protokol berbeda dari server SIPP, sehingga browser memblokir cookie sesi lintas-domain saat jendela <strong>Detil</strong> dibuka (tampak layar gelap kosong). Gunakan <strong>Buka Halaman Sumber</strong> untuk membuka detil perkara penuh, atau klik <strong>Muat Ulang Embed</strong> untuk memulihkan tampilan. Pada server produksi HTTPS satu domain induk dengan SIPP, fitur detil di dalam embed bekerja normal.
                    </div>
                @endif

                <div class="rounded-lg border border-primary/25 bg-primary-soft/70 px-3.5 py-2.5 text-xs text-ink-body md:hidden">
                    Tampilan aplikasi ini optimal pada layar lebar. Gunakan tombol <strong>Layar Penuh</strong>, <strong>Buka Halaman Sumber</strong>, atau <strong>Tampilkan Data Cadangan Lokal</strong> untuk tampilan ramah HP.
                </div>

                @php
                    $defaultHeightClass = $fallbackMode === 'org_chart'
                        ? 'h-[clamp(720px,calc(100dvh-160px),1160px)]'
                        : 'h-[clamp(660px,calc(100dvh-170px),1080px)]';
                @endphp
                <div data-embed-frame-wrap class="relative overflow-hidden rounded-xl border border-hairline bg-white shadow-2xs">
                    <iframe
                        src="{{ $embedUrl }}"
                        data-simtepa-iframe
                        data-default-height-class="{{ $defaultHeightClass }}"
                        title="{{ $block['heading'] ?? 'Integrasi Data Eksternal' }}"
                        loading="lazy"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals allow-popups-to-escape-sandbox"
                        referrerpolicy="no-referrer-when-downgrade"
                        width="100%"
                        class="{{ $defaultHeightClass }} block w-full bg-white transition-[height] duration-200"
                    ></iframe>
                </div>
            </div>
        @endif
    </div>
@else
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-ink">
            <span>
                <strong>Mode Data Cadangan Lokal:</strong>
                @if (empty($embedUrl))
                    Tautan embed eksternal belum dikonfigurasi, menampilkan data resmi yang disimpan di CMS.
                @else
                    Menampilkan salinan data cadangan lokal agar layanan informasi tetap dapat diakses.
                @endif
            </span>
            @if (! empty($embedUrl))
                <a href="{{ request()->url() }}" class="font-semibold text-primary underline">Kembali ke Tampilan Embed</a>
            @endif
        </div>

        @if ($fallbackMode === 'org_chart')
            @if ($orgOfficials->isEmpty())
                <p class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
                    Data struktur organisasi cadangan belum diisi.
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($orgOfficials as $person)
                        <div class="rounded-lg border border-hairline bg-white p-4 shadow-2xs">
                            <span class="rounded bg-primary-soft px-2 py-0.5 text-2xs font-semibold uppercase text-primary">
                                {{ $person->kategori }}
                            </span>
                            <p class="mt-2 font-serif text-base font-bold text-ink">{{ $person->jabatan }}</p>
                            <p class="mt-1 text-sm font-semibold text-primary-deep">{{ $person->nama_gelar }}</p>
                            @if ($person->pangkat_golongan)
                                <p class="mt-1 text-xs text-ink-muted">{{ $person->pangkat_golongan }}</p>
                            @endif
                            @if ($person->parent)
                                <p class="mt-1 text-2xs text-ink-muted">Koordinasi: {{ $person->parent->jabatan }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @elseif ($fallbackMode === 'court_schedule')
            @if ($schedules->isEmpty())
                <p class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
                    Belum ada jadwal persidangan cadangan yang dicatat.
                </p>
            @else
                <div class="overflow-x-auto rounded-lg border border-hairline">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-3 py-2.5 font-semibold">Tanggal</th>
                                <th class="px-3 py-2.5 font-semibold">Nomor Perkara</th>
                                <th class="px-3 py-2.5 font-semibold">Para Pihak</th>
                                <th class="px-3 py-2.5 font-semibold">Ruang Sidang</th>
                                <th class="px-3 py-2.5 font-semibold">Agenda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline bg-white">
                            @foreach ($schedules as $sch)
                                <tr>
                                    <td class="px-3 py-2.5 font-mono">{{ $sch->tanggal_sidang?->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2.5 font-semibold text-ink">{{ $sch->nomor_perkara }}</td>
                                    <td class="px-3 py-2.5 text-ink">{{ $sch->para_pihak }}</td>
                                    <td class="px-3 py-2.5 text-ink-muted">{{ $sch->ruang_sidang ?: '-' }}</td>
                                    <td class="px-3 py-2.5 text-ink-muted">{{ $sch->agenda ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @elseif ($fallbackMode === 'case_statistics')
            @if ($statistics->isEmpty())
                <p class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
                    Belum ada data statistik perkara cadangan yang dicatat.
                </p>
            @else
                <div class="overflow-x-auto rounded-lg border border-hairline">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-3 py-2.5 font-semibold">Periode</th>
                                <th class="px-3 py-2.5 font-semibold">Sisa Bulan Lalu</th>
                                <th class="px-3 py-2.5 font-semibold">Perkara Masuk</th>
                                <th class="px-3 py-2.5 font-semibold">Perkara Putus</th>
                                <th class="px-3 py-2.5 font-semibold">Sisa Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline bg-white">
                            @foreach ($statistics as $st)
                                <tr>
                                    <td class="px-3 py-2.5 font-mono font-semibold text-ink">{{ $st->year }}-{{ str_pad((string) $st->month, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-3 py-2.5 font-mono">{{ $st->sisa_bulan_lalu }}</td>
                                    <td class="px-3 py-2.5 font-mono">{{ $st->perkara_masuk }}</td>
                                    <td class="px-3 py-2.5 font-mono">{{ $st->perkara_putus }}</td>
                                    <td class="px-3 py-2.5 font-mono font-bold text-primary">{{ $st->sisa_saat_ini }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            @if ($officials->isEmpty())
                <p class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
                    Belum ada data pejabat dan pegawai cadangan yang diisi.
                </p>
            @else
                <div class="overflow-x-auto rounded-lg border border-hairline">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-3 py-2.5 font-semibold">Nama &amp; Gelar</th>
                                <th class="px-3 py-2.5 font-semibold">Jabatan</th>
                                <th class="px-3 py-2.5 font-semibold">Kategori</th>
                                <th class="px-3 py-2.5 font-semibold">Pangkat / Gol.</th>
                                <th class="px-3 py-2.5 font-semibold">Pendidikan &amp; Riwayat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline bg-white">
                            @foreach ($officials as $person)
                                <tr>
                                    <td class="px-3 py-2.5 font-semibold text-ink">{{ $person->nama_gelar }}</td>
                                    <td class="px-3 py-2.5 text-ink">{{ $person->jabatan }}</td>
                                    <td class="px-3 py-2.5 uppercase text-primary">{{ $person->kategori }}</td>
                                    <td class="px-3 py-2.5 text-ink-muted">{{ $person->pangkat_golongan ?: '-' }}</td>
                                    <td class="px-3 py-2.5 text-ink-muted">
                                        <div class="prose max-w-none text-xs text-ink-muted">{!! $person->riwayat_pendidikan ? \App\Support\SafeHtml::cleanBody($person->riwayat_pendidikan) : '-' !!}</div>
                                        @if ($person->riwayat_jabatan)
                                            <div class="prose mt-0.5 max-w-none text-2xs text-ink-muted">Riwayat: {!! \App\Support\SafeHtml::cleanBody($person->riwayat_jabatan) !!}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
@endif
