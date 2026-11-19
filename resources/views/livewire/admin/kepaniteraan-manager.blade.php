<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-ink">
        <strong>Catatan Data Cadangan & Privasi (SINOVA & SIMTEPA/SIPP):</strong>
        Data ini otomatis tampil sebagai mode cadangan (fallback) pada website publik apabila tautan SIPP atau SIMTEPA sedang tidak dapat diakses. Demi perlindungan data pribadi ASN, sistem tidak menyimpan NIP, tanggal lahir, atau kontak pribadi pada data publik aparatur.
    </div>

    <!-- Tab Selector -->
    <div role="tablist" aria-label="Kategori Data Kepaniteraan dan Aparatur" class="flex flex-wrap gap-2 border-b border-hairline pb-3">
        @foreach (['jadwal' => 'Jadwal Sidang (SIPP Cadangan)', 'statistik' => 'Statistik Perkara', 'pegawai' => 'Struktur & Aparatur (SIMTEPA Cadangan)'] as $tKey => $tLabel)
            <button
                type="button"
                role="tab"
                aria-selected="{{ $tab === $tKey ? 'true' : 'false' }}"
                wire:click="$set('tab', '{{ $tKey }}')"
                @class([
                    'min-h-11 cursor-pointer rounded-md px-4 py-2 text-xs font-semibold transition-colors',
                    'bg-primary text-white' => $tab === $tKey,
                    'border border-hairline bg-white text-ink hover:bg-mist' => $tab !== $tKey,
                ])>
                {{ $tLabel }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'jadwal')
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Tambah Jadwal Sidang</h2>
                <form wire:submit="saveSchedule" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label for="sch_date">Tanggal Sidang</flux:label>
                        <flux:input id="sch_date" type="date" wire:model="tanggal_sidang" required />
                        <flux:error name="tanggal_sidang" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="sch_num">Nomor Perkara</flux:label>
                        <flux:input id="sch_num" wire:model="nomor_perkara" required placeholder="Contoh: 123/Pdt.G/2026/PA.Sby" />
                        <flux:error name="nomor_perkara" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="sch_parties">Para Pihak (Disamarkan jika perkara tertutup)</flux:label>
                        <flux:input id="sch_parties" wire:model="para_pihak" required placeholder="Pihak P vs Pihak T" />
                        <flux:error name="para_pihak" />
                    </flux:field>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="sch_room">Ruang Sidang</flux:label>
                            <flux:input id="sch_room" wire:model="ruang_sidang" placeholder="Ruang Sidang 1" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="sch_agenda">Agenda Sidang</flux:label>
                            <flux:input id="sch_agenda" wire:model="agenda" placeholder="Sidang Pertama / Pembuktian" />
                        </flux:field>
                    </div>
                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                        Simpan Jadwal Sidang
                    </flux:button>
                </form>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Daftar Jadwal Sidang Cadangan ({{ $schedules->count() }})</h2>
                @if ($schedules->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada jadwal sidang cadangan.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="border-b border-hairline text-ink-muted">
                                    <th class="py-2 pr-3 font-semibold">Tanggal</th>
                                    <th class="py-2 pr-3 font-semibold">No. Perkara</th>
                                    <th class="py-2 pr-3 font-semibold">Para Pihak</th>
                                    <th class="py-2 pr-3 font-semibold">Ruang & Agenda</th>
                                    <th class="py-2 text-right font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                @foreach ($schedules as $sch)
                                    <tr wire:key="sch-{{ $sch->id }}">
                                        <td class="py-2.5 pr-3 font-mono">{{ $sch->tanggal_sidang?->format('Y-m-d') }}</td>
                                        <td class="py-2.5 pr-3 font-semibold text-ink">{{ $sch->nomor_perkara }}</td>
                                        <td class="py-2.5 pr-3 text-ink">{{ $sch->para_pihak }}</td>
                                        <td class="py-2.5 pr-3 text-ink-muted">{{ $sch->ruang_sidang ?: '-' }} / {{ $sch->agenda ?: '-' }}</td>
                                        <td class="py-2.5 text-right">
                                            <button type="button" wire:click="deleteSchedule({{ $sch->id }})" wire:confirm="Hapus jadwal ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-2.5 py-1 text-red-700 hover:bg-red-50">
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
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Input Keadaan Perkara Bulanan</h2>
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
                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                        Simpan Statistik Bulanan
                    </flux:button>
                </form>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Rekap Keadaan Perkara ({{ $statistics->count() }} Periode)</h2>
                @if ($statistics->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada data keadaan perkara cadangan.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="border-b border-hairline text-ink-muted">
                                    <th class="py-2 pr-3 font-semibold">Periode</th>
                                    <th class="py-2 pr-3 font-semibold">Sisa Lalu</th>
                                    <th class="py-2 pr-3 font-semibold">Masuk</th>
                                    <th class="py-2 pr-3 font-semibold">Putus</th>
                                    <th class="py-2 pr-3 font-semibold">Sisa Kini</th>
                                    <th class="py-2 text-right font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                @foreach ($statistics as $st)
                                    <tr wire:key="stat-{{ $st->id }}">
                                        <td class="py-2.5 pr-3 font-mono font-semibold">{{ $st->year }}-{{ str_pad((string) $st->month, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="py-2.5 pr-3 font-mono">{{ $st->sisa_bulan_lalu }}</td>
                                        <td class="py-2.5 pr-3 font-mono">{{ $st->perkara_masuk }}</td>
                                        <td class="py-2.5 pr-3 font-mono">{{ $st->perkara_putus }}</td>
                                        <td class="py-2.5 pr-3 font-mono font-bold text-primary">{{ $st->sisa_saat_ini }}</td>
                                        <td class="py-2.5 text-right">
                                            <button type="button" wire:click="deleteStatistic({{ $st->id }})" wire:confirm="Hapus statistik periode ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-2.5 py-1 text-red-700 hover:bg-red-50">
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
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Tambah Aparatur / Struktur Organisasi</h2>
                <form wire:submit="saveOfficial" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label for="off_name">Nama & Gelar</flux:label>
                        <flux:input id="off_name" wire:model="nama_gelar" required placeholder="Nama lengkap disertai gelar" />
                        <flux:error name="nama_gelar" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="off_pos">Jabatan</flux:label>
                        <flux:input id="off_pos" wire:model="jabatan" required placeholder="Contoh: Ketua / Panitera / Sekretaris" />
                        <flux:error name="jabatan" />
                    </flux:field>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="off_cat">Kategori</flux:label>
                            <flux:select id="off_cat" wire:model="kategori">
                                <option value="pimpinan">Pimpinan</option>
                                <option value="hakim">Hakim</option>
                                <option value="pejabat">Pejabat Struktural/Fungsional</option>
                                <option value="pegawai">Pegawai</option>
                                <option value="honorer">PPNPN / Honorer</option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label for="off_parent">Atasan Langsung (Opsional)</flux:label>
                            <flux:select id="off_parent" wire:model="parent_id">
                                <option value="">-- Puncak Struktur --</option>
                                @foreach ($officials as $parentCandidate)
                                    <option value="{{ $parentCandidate->id }}">{{ $parentCandidate->jabatan }} ({{ $parentCandidate->nama_gelar }})</option>
                                @endforeach
                            </flux:select>
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
                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                        Simpan Data Aparatur
                    </flux:button>
                </form>
            </div>

            <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Daftar Aparatur Cadangan ({{ $officials->count() }})</h2>
                @if ($officials->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-muted">Belum ada data aparatur cadangan yang ditambahkan.</p>
                @else
                    <ul class="mt-3 divide-y divide-hairline">
                        @foreach ($officials as $off)
                            <li wire:key="off-{{ $off->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
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
                                <button type="button" wire:click="deleteOfficial({{ $off->id }})" wire:confirm="Hapus data aparatur ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">
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
