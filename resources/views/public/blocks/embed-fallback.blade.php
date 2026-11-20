@php
    $embedUrl = $block['resolved_embed_url'] ?? null;
    $embedTabs = is_array($block['resolved_embed_tabs'] ?? null) ? $block['resolved_embed_tabs'] : [];
    $activeTabKey = (string) ($block['active_simtepa_tab'] ?? ($embedTabs[0]['key'] ?? ''));
    $fallbackMode = $block['fallback_mode'] ?? 'officials_table';
    $showFallback = $forceFallback || empty($embedUrl);
    $activeJsonUrl = null;
    foreach ($embedTabs as $t) {
        if ($t['key'] === $activeTabKey) {
            $activeJsonUrl = $t['json_url'] ?? null;
            break;
        }
    }
@endphp

@if (! empty($block['note']))
    <p class="text-xs text-ink-muted">{{ $block['note'] }}</p>
@endif

@if (! $showFallback)
    <div
        data-simtepa-embed-block
        class="space-y-3 rounded-lg border border-hairline bg-mist p-4"
    >
        @if ($embedTabs !== [])
            <div class="border-b border-hairline pb-3">
                <p class="mb-2 text-xs font-bold text-ink">Pilih Kategori Data Pegawai SIMTEPA:</p>
                <div role="tablist" aria-label="Kategori Profil Pegawai SIMTEPA" class="flex flex-wrap gap-1.5">
                    @foreach ($embedTabs as $tabIndex => $tab)
                        @php $isActiveTab = $tab['key'] === $activeTabKey || ($activeTabKey === '' && $tabIndex === 0); @endphp
                        <a
                            href="{{ request()->fullUrlWithQuery(['simtepa_tab' => $tab['key'], 'fallback' => null]) }}"
                            role="tab"
                            aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                            data-simtepa-tab-btn
                            data-html-url="{{ $tab['html_url'] }}"
                            data-json-url="{{ $tab['json_url'] }}"
                            @class([
                                'pub-btn inline-flex min-h-10 items-center rounded-md px-3 py-1.5 text-xs font-semibold',
                                'bg-primary text-white' => $isActiveTab,
                                'border border-hairline bg-white text-ink hover:border-primary hover:bg-primary-soft hover:text-primary' => ! $isActiveTab,
                            ])
                        >
                            {{ $loop->iteration }}. {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <span class="font-semibold text-primary">Tampilan Langsung Aplikasi Eksternal Aktif</span>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ request()->fullUrlWithQuery(['fallback' => 1]) }}"
                    class="pub-btn inline-flex min-h-10 items-center rounded border border-primary bg-white px-3 font-semibold text-primary hover:bg-primary-soft"
                >
                    Tampilkan Data Cadangan Lokal
                </a>
                @if ($activeJsonUrl)
                    <a
                        href="{{ $activeJsonUrl }}"
                        data-simtepa-json-link
                        target="_blank"
                        rel="noopener noreferrer"
                        class="pub-btn inline-flex min-h-10 items-center rounded border border-hairline bg-white px-3 font-mono font-semibold text-ink hover:bg-mist"
                    >
                        Endpoint JSON &nearr;
                    </a>
                @endif
                <a
                    href="{{ $embedUrl }}"
                    data-simtepa-html-link
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pub-btn inline-flex min-h-10 items-center rounded bg-primary px-3 font-semibold text-white hover:bg-primary-hover"
                >
                    Buka Halaman Sumber &nearr;
                </a>
            </div>
        </div>

        <iframe
            src="{{ $embedUrl }}"
            data-simtepa-iframe
            title="{{ $block['heading'] ?? 'Integrasi Data Eksternal' }}"
            loading="lazy"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
            referrerpolicy="no-referrer-when-downgrade"
            width="100%"
            height="580"
            class="h-[580px] w-full rounded border border-hairline bg-white"
        ></iframe>
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

