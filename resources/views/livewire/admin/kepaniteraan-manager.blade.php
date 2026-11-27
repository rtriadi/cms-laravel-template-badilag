<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Data Kepaniteraan &amp; Aparatur</h1>
        <p class="mt-1 text-sm text-ink-body">
            Kelola data cadangan jadwal sidang, keadaan perkara bulanan, dan daftar aparatur saat integrasi SIPP atau SIMTEPA tidak dapat diakses.
        </p>
    </div>

    <div class="studio-card overflow-hidden">
        <!-- Clean Underline Tabs -->
        <div role="tablist" aria-label="Kategori Data Kepaniteraan dan Aparatur" class="studio-tabs px-4 pt-2 sm:px-6">
            @foreach ([
                'jadwal' => ['Jadwal Sidang Cadangan', $schedules->count()],
                'statistik' => ['Keadaan Perkara Bulanan', $statistics->count()],
                'pegawai' => ['Aparatur & Struktur', $officials->count()],
            ] as $tKey => [$tLabel, $tCount])
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $tab === $tKey ? 'true' : 'false' }}"
                    wire:click="$set('tab', '{{ $tKey }}')"
                    @class([
                        'inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 border-b-2 px-3 text-sm transition-colors',
                        'border-primary font-bold text-primary' => $tab === $tKey,
                        'border-transparent font-medium text-ink-body hover:text-ink' => $tab !== $tKey,
                    ])
                >
                    <span>{{ $tLabel }}</span>
                    <span @class([
                        'rounded-md px-2 py-0.5 font-mono text-xs font-semibold',
                        'bg-primary-soft text-primary' => $tab === $tKey,
                        'bg-mist text-ink-muted' => $tab !== $tKey,
                    ])>{{ $tCount }}</span>
                </button>
            @endforeach
        </div>

        <div class="space-y-5 p-4 sm:p-6">
            <div class="rounded-xl border border-primary/25 bg-primary-soft px-4 py-3 text-xs leading-relaxed text-ink-body">
                <strong class="text-primary-deep">Mode cadangan &amp; perlindungan data pribadi:</strong>
                Data ini otomatis tampil di website publik bila peladen SIPP/SIMTEPA sedang pemeliharaan. Sistem tidak menyimpan NIP, tanggal lahir, atau kontak pribadi aparatur pada data publik.
            </div>

            @if ($tab === 'jadwal')
                <div class="grid items-start gap-6 lg:grid-cols-12">
                    <div class="rounded-xl border border-hairline bg-mist p-5 lg:col-span-5">
                        <h2 class="text-base font-bold text-ink">Tambah Jadwal Sidang</h2>
                        <form wire:submit="saveSchedule" class="mt-4 space-y-4">
                            <flux:field>
                                <flux:label for="sch_date">Tanggal sidang <span class="text-red-700">*</span></flux:label>
                                <flux:input id="sch_date" type="date" wire:model="tanggal_sidang" required />
                                <flux:error name="tanggal_sidang" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="sch_num">Nomor perkara <span class="text-red-700">*</span></flux:label>
                                <flux:input id="sch_num" wire:model="nomor_perkara" required placeholder="Contoh: 123/Pdt.G/2026/PA.Gtlo" class="font-mono" />
                                <flux:error name="nomor_perkara" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="sch_parties">Para pihak <span class="text-red-700">*</span></flux:label>
                                <flux:input id="sch_parties" wire:model="para_pihak" required placeholder="Samarkan inisial bila perkara tertutup" />
                                <flux:error name="para_pihak" />
                            </flux:field>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="sch_room">Ruang sidang</flux:label>
                                    <flux:input id="sch_room" wire:model="ruang_sidang" placeholder="Ruang Sidang 1" />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="sch_agenda">Agenda sidang</flux:label>
                                    <flux:input id="sch_agenda" wire:model="agenda" placeholder="Sidang Pertama" />
                                </flux:field>
                            </div>
                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                Simpan Jadwal Sidang
                            </flux:button>
                        </form>
                    </div>

                    <div class="lg:col-span-7">
                        <h2 class="mb-3 text-base font-bold text-ink">Daftar Jadwal Sidang Cadangan ({{ $schedules->count() }})</h2>
                        @if ($schedules->isEmpty())
                            <div class="rounded-xl border border-hairline bg-mist/60 px-6 py-10 text-center">
                                <p class="text-sm font-semibold text-ink">Belum ada jadwal sidang cadangan</p>
                                <p class="mt-1 text-xs text-ink-muted">Isi formulir di samping untuk menambahkan jadwal cadangan.</p>
                            </div>
                        @else
                            <!-- Mobile Cards -->
                            <ul class="space-y-3 md:hidden">
                                @foreach ($schedules as $sch)
                                    <li wire:key="sch-mob-{{ $sch->id }}" class="rounded-xl border border-hairline bg-white p-4 space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-xs font-bold text-primary">{{ $sch->tanggal_sidang?->format('Y-m-d') }}</span>
                                            <button type="button" wire:click="deleteSchedule({{ $sch->id }})" wire:confirm="Hapus jadwal ini?" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                        </div>
                                        <p class="font-mono text-sm font-bold text-ink">{{ $sch->nomor_perkara }}</p>
                                        <p class="text-xs text-ink-body">{{ $sch->para_pihak }}</p>
                                        <p class="text-xs text-ink-muted">{{ $sch->ruang_sidang ?: '-' }} &middot; {{ $sch->agenda ?: '-' }}</p>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Desktop Table -->
                            <div class="hidden overflow-x-auto rounded-xl border border-hairline md:block">
                                <table class="w-full border-collapse text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-hairline bg-mist text-ink-muted">
                                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                                            <th class="px-3 py-3 font-semibold">Nomor perkara</th>
                                            <th class="px-3 py-3 font-semibold">Para pihak &amp; agenda</th>
                                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-hairline/70">
                                        @foreach ($schedules as $sch)
                                            <tr wire:key="sch-{{ $sch->id }}" class="hover:bg-mist/60">
                                                <td class="whitespace-nowrap px-4 py-3 font-mono text-ink-body">{{ $sch->tanggal_sidang?->format('Y-m-d') }}</td>
                                                <td class="whitespace-nowrap px-3 py-3 font-mono font-semibold text-ink">{{ $sch->nomor_perkara }}</td>
                                                <td class="px-3 py-3">
                                                    <p class="font-semibold text-ink">{{ $sch->para_pihak }}</p>
                                                    <p class="text-ink-muted">{{ $sch->ruang_sidang ?: '-' }} &middot; {{ $sch->agenda ?: '-' }}</p>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" wire:click="deleteSchedule({{ $sch->id }})" wire:confirm="Hapus jadwal ini?" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-red-50">
                                                        Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($tab === 'statistik')
                <div class="grid items-start gap-6 lg:grid-cols-12">
                    <div class="rounded-xl border border-hairline bg-mist p-5 lg:col-span-5">
                        <h2 class="text-base font-bold text-ink">Input Keadaan Perkara Bulanan</h2>
                        <p class="mt-0.5 text-xs text-ink-muted">Sisa akhir bulan dihitung otomatis dari Sisa Lalu + Masuk - Putus.</p>
                        <form wire:submit="saveStatistic" class="mt-4 space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="st_year">Tahun</flux:label>
                                    <flux:input id="st_year" type="number" wire:model="stat_year" min="2000" max="2100" required />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="st_month">Bulan (1-12)</flux:label>
                                    <flux:input id="st_month" type="number" wire:model="stat_month" min="1" max="12" required />
                                </flux:field>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <flux:field>
                                    <flux:label for="st_prev">Sisa Lalu</flux:label>
                                    <flux:input id="st_prev" type="number" wire:model="sisa_bulan_lalu" min="0" required />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="st_in">Masuk</flux:label>
                                    <flux:input id="st_in" type="number" wire:model="perkara_masuk" min="0" required />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="st_out">Putus</flux:label>
                                    <flux:input id="st_out" type="number" wire:model="perkara_putus" min="0" required />
                                </flux:field>
                            </div>
                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                Simpan Statistik Bulanan
                            </flux:button>
                        </form>
                    </div>

                    <div class="lg:col-span-7">
                        <h2 class="mb-3 text-base font-bold text-ink">Rekap Keadaan Perkara ({{ $statistics->count() }} Periode)</h2>
                        @if ($statistics->isEmpty())
                            <div class="rounded-xl border border-hairline bg-mist/60 px-6 py-10 text-center">
                                <p class="text-sm font-semibold text-ink">Belum ada data keadaan perkara</p>
                                <p class="mt-1 text-xs text-ink-muted">Tambahkan rekap perkara bulanan melalui formulir di samping.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto rounded-xl border border-hairline">
                                <table class="w-full border-collapse text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-hairline bg-mist text-ink-muted">
                                            <th class="px-4 py-3 font-semibold">Periode</th>
                                            <th class="px-3 py-3 text-right font-semibold">Sisa Lalu</th>
                                            <th class="px-3 py-3 text-right font-semibold">Masuk</th>
                                            <th class="px-3 py-3 text-right font-semibold">Putus</th>
                                            <th class="px-3 py-3 text-right font-semibold">Sisa Kini</th>
                                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-hairline/70">
                                        @foreach ($statistics as $st)
                                            <tr wire:key="stat-{{ $st->id }}" class="hover:bg-mist/60">
                                                <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-ink">{{ $st->year }}-{{ str_pad((string) $st->month, 2, '0', STR_PAD_LEFT) }}</td>
                                                <td class="px-3 py-3 text-right font-mono">{{ $st->sisa_bulan_lalu }}</td>
                                                <td class="px-3 py-3 text-right font-mono">{{ $st->perkara_masuk }}</td>
                                                <td class="px-3 py-3 text-right font-mono">{{ $st->perkara_putus }}</td>
                                                <td class="px-3 py-3 text-right font-mono font-bold text-primary">{{ $st->sisa_saat_ini }}</td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" wire:click="deleteStatistic({{ $st->id }})" wire:confirm="Hapus statistik periode ini?" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">
                                                        Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="grid items-start gap-6 xl:grid-cols-12">
                    <div class="rounded-xl border border-hairline bg-mist p-5 xl:col-span-6">
                        <h2 class="text-base font-bold text-ink">Tambah Aparatur / Struktur Organisasi</h2>
                        <form wire:submit="saveOfficial" class="mt-4 space-y-4">
                            <flux:field>
                                <flux:label for="off_name">Nama &amp; Gelar <span class="text-red-700">*</span></flux:label>
                                <flux:input id="off_name" wire:model="nama_gelar" required placeholder="Nama lengkap disertai gelar" />
                                <flux:error name="nama_gelar" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="off_pos">Jabatan <span class="text-red-700">*</span></flux:label>
                                <flux:input id="off_pos" wire:model="jabatan" required placeholder="Contoh: Ketua / Panitera / Sekretaris" />
                                <flux:error name="jabatan" />
                            </flux:field>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="off_cat">Kategori</flux:label>
                                    <x-admin.select id="off_cat" wire:model="kategori">
                                        <option value="pimpinan">Pimpinan</option>
                                        <option value="hakim">Hakim</option>
                                        <option value="pejabat">Pejabat Struktural/Fungsional</option>
                                        <option value="pegawai">Pegawai</option>
                                        <option value="honorer">PPNPN / Honorer</option>
                                    </x-admin.select>
                                </flux:field>
                                <flux:field>
                                    <flux:label for="off_parent">Atasan langsung (opsional)</flux:label>
                                    <x-admin.select id="off_parent" wire:model="parent_id" search-placeholder="Cari nama atau jabatan...">
                                        <option value="">Puncak Struktur</option>
                                        @foreach ($officials as $parentCandidate)
                                            <option value="{{ $parentCandidate->id }}">{{ $parentCandidate->jabatan }} ({{ $parentCandidate->nama_gelar }})</option>
                                        @endforeach
                                    </x-admin.select>
                                </flux:field>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label for="off_rank">Pangkat / Golongan</flux:label>
                                    <flux:input id="off_rank" wire:model="pangkat_golongan" placeholder="Contoh: Pembina Utama Muda (IV/c)" />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="off_tmt">TMT Jabatan</flux:label>
                                    <flux:input id="off_tmt" wire:model="tmt_jabatan" placeholder="Contoh: 01-03-2024" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label for="off_edu">Riwayat Pendidikan</flux:label>
                                <x-rich-editor
                                    id="off_edu"
                                    model="riwayat_pendidikan"
                                    label="Riwayat pendidikan aparatur"
                                    placeholder="S1 Hukum Islam, S2 Ilmu Hukum"
                                    :min-height="150"
                                    :compact="true"
                                />
                            </flux:field>
                            <flux:field>
                                <flux:label for="off_hist">Riwayat Jabatan</flux:label>
                                <x-rich-editor
                                    id="off_hist"
                                    model="riwayat_jabatan"
                                    label="Riwayat jabatan aparatur"
                                    placeholder="Ringkasan riwayat penempatan jabatan"
                                    :min-height="150"
                                    :compact="true"
                                />
                            </flux:field>
                            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                                Simpan Data Aparatur
                            </flux:button>
                        </form>
                    </div>

                    <div class="xl:col-span-6">
                        <h2 class="mb-3 text-base font-bold text-ink">Daftar Aparatur Cadangan ({{ $officials->count() }})</h2>
                        @if ($officials->isEmpty())
                            <div class="rounded-xl border border-hairline bg-mist/60 px-6 py-10 text-center">
                                <p class="text-sm font-semibold text-ink">Belum ada data aparatur cadangan</p>
                                <p class="mt-1 text-xs text-ink-muted">Data aparatur ini digunakan bila embed SIMTEPA tidak dapat dimuat.</p>
                            </div>
                        @else
                            <ul class="divide-y divide-hairline rounded-xl border border-hairline bg-white">
                                @foreach ($officials as $off)
                                    <li wire:key="off-{{ $off->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded bg-primary-soft px-2 py-0.5 text-[11px] font-semibold uppercase text-primary">{{ $off->kategori }}</span>
                                                @if ($off->pangkat_golongan)
                                                    <span class="text-xs text-ink-muted">{{ $off->pangkat_golongan }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-sm font-semibold text-ink">{{ $off->nama_gelar }}</p>
                                            <p class="text-xs text-ink-muted">
                                                {{ $off->jabatan }}
                                                @if ($off->parent)
                                                    (Atasan: {{ $off->parent->jabatan }})
                                                @endif
                                            </p>
                                        </div>
                                        <button type="button" wire:click="deleteOfficial({{ $off->id }})" wire:confirm="Hapus data aparatur ini?" class="inline-flex min-h-10 w-fit cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">
                                            Hapus
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
