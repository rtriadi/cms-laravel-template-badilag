<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-12">
            <!-- Identitas Satuan Kerja -->
            <div class="space-y-4 rounded-lg border border-hairline bg-white p-5 lg:col-span-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Identitas & Alamat Satuan Kerja</h2>

                <flux:field>
                    <flux:label for="st_nama">Nama Satuan Kerja</flux:label>
                    <flux:input id="st_nama" wire:model="nama_satker" required placeholder="Contoh: Pengadilan Agama Surabaya" />
                    <flux:error name="nama_satker" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_jenis">Jenis Badan</flux:label>
                        <flux:select id="st_jenis" wire:model="jenis_badan">
                            @foreach ($jenisBadanOptions as $jKey => $jLabel)
                                <option value="{{ $jKey }}">{{ $jLabel }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="st_kode">Kode Satker</flux:label>
                        <flux:input id="st_kode" wire:model="kode_satker" placeholder="401..." />
                    </flux:field>

                    <flux:field>
                        <flux:label for="st_tahun">Tahun Berdiri</flux:label>
                        <flux:input id="st_tahun" type="number" wire:model="tahun_berdiri" min="1900" max="2100" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="st_jalan">Alamat Jalan</flux:label>
                    <flux:input id="st_jalan" wire:model="alamat_jalan" placeholder="Jl. Ketintang Madya..." />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="st_kel">Desa / Kelurahan</flux:label>
                        <flux:input id="st_kel" wire:model="desa_kelurahan" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_kec">Kecamatan</flux:label>
                        <flux:input id="st_kec" wire:model="kecamatan" />
                    </flux:field>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_kota">Kabupaten / Kota</flux:label>
                        <flux:input id="st_kota" wire:model="kabupaten_kota" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_prov">Provinsi</flux:label>
                        <flux:input id="st_prov" wire:model="provinsi" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_pos">Kode Pos</flux:label>
                        <flux:input id="st_pos" wire:model="kode_pos" />
                    </flux:field>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_telp">Telepon</flux:label>
                        <flux:input id="st_telp" wire:model="telepon" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_email">Surel Resmi</flux:label>
                        <flux:input id="st_email" type="email" wire:model="email" />
                        <flux:error name="email" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_web">URL Website</flux:label>
                        <flux:input id="st_web" type="url" wire:model="website_url" />
                    </flux:field>
                </div>

                <div class="grid gap-4 rounded-lg border border-hairline bg-mist p-3.5 sm:grid-cols-2">
                    <div>
                        <flux:label for="st_logo">Logo Satker (Maks 2 MB)</flux:label>
                        <input id="st_logo" type="file" wire:model="logo" accept="image/*" class="mt-1 block w-full cursor-pointer text-xs text-ink file:mr-2 file:cursor-pointer file:rounded file:border file:border-hairline file:bg-white file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-primary" />
                        @if ($existingLogoPath)
                            <img src="{{ asset('storage/' . $existingLogoPath) }}" alt="Logo {{ $nama_satker }}" width="48" height="48" class="mt-2 h-12 w-12 rounded border border-hairline bg-white object-contain p-1" />
                        @endif
                    </div>
                    <div>
                        <flux:label for="st_foto">Foto Gedung Kantor (Maks 4 MB)</flux:label>
                        <input id="st_foto" type="file" wire:model="foto_gedung" accept="image/*" class="mt-1 block w-full cursor-pointer text-xs text-ink file:mr-2 file:cursor-pointer file:rounded file:border file:border-hairline file:bg-white file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-primary" />
                        @if ($existingFotoGedungPath)
                            <img src="{{ asset('storage/' . $existingFotoGedungPath) }}" alt="Gedung {{ $nama_satker }}" width="96" height="48" class="mt-2 h-12 w-24 rounded border border-hairline object-cover" />
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tautan Aplikasi Eksternal -->
            <div class="space-y-4 rounded-lg border border-hairline bg-white p-5 lg:col-span-6">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Integrasi Aplikasi & Tautan Eksternal</h2>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="ext_sipp">URL SIPP / Banding</flux:label>
                        <flux:input id="ext_sipp" type="url" wire:model="sipp_url" placeholder="https://sipp.pa-...." />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_ecourt">URL e-Court MARI</flux:label>
                        <flux:input id="ext_ecourt" type="url" wire:model="ecourt_url" placeholder="https://ecourt.mahkamahagung.go.id" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_dir">URL Direktori Putusan</flux:label>
                        <flux:input id="ext_dir" type="url" wire:model="direktori_putusan_url" placeholder="https://putusan3.mahkamahagung.go.id" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_siwas">URL SIWAS MARI</flux:label>
                        <flux:input id="ext_siwas" type="url" wire:model="siwas_url" placeholder="https://siwas.mahkamahagung.go.id" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_sim_peg">URL Embed SIMTEPA Pegawai</flux:label>
                        <flux:input id="ext_sim_peg" type="url" wire:model="simtepa_pegawai_url" placeholder="https://simtepa.badilag.net/..." />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_sim_str">URL Embed SIMTEPA Struktur</flux:label>
                        <flux:input id="ext_sim_str" type="url" wire:model="simtepa_struktur_url" placeholder="https://simtepa.badilag.net/..." />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_skm">URL Survei SKM / IKPM</flux:label>
                        <flux:input id="ext_skm" type="url" wire:model="skm_url" placeholder="https://..." />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_spak">URL Survei SPAK / IPAK</flux:label>
                        <flux:input id="ext_spak" type="url" wire:model="spak_url" placeholder="https://..." />
                    </flux:field>
                </div>

                <div class="rounded-lg border border-hairline bg-mist p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-ink">Tingkat Peradilan Aktif (Terkunci Setelah Setup)</p>
                            <p class="mt-0.5 font-mono text-xs font-semibold text-primary">
                                {{ $tingkatOptions[$tingkat] ?? $tingkat }} ({{ strtoupper($tingkat) }})
                            </p>
                        </div>
                        @if ($isSuperAdmin)
                            <button type="button" wire:click="openTingkatModal" class="min-h-11 cursor-pointer rounded-md border border-amber-500 bg-gold px-3.5 py-2 text-xs font-bold text-ink hover:opacity-90">
                                Ubah Tingkat Peradilan
                            </button>
                        @endif
                    </div>
                    <p class="mt-2 text-[11px] text-ink-muted">
                        Perubahan tingkat peradilan bersifat non-destruktif: halaman yang sudah diisi tetap tersimpan dan struktur menu disesuaikan otomatis oleh Super Admin.
                    </p>
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    Simpan Pengaturan Satker
                </flux:button>
            </div>
        </div>
    </form>

    <!-- Modal Konfirmasi Ganda Super Admin -->
    @if ($showTingkatModal && $isSuperAdmin)
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="tingkat-modal-title"
            @keydown.escape.window="$wire.closeTingkatModal()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-lg border border-hairline bg-white p-6 shadow-xl">
                <h3 id="tingkat-modal-title" class="font-serif text-lg font-bold text-ink">
                    Konfirmasi Perubahan Tingkat Peradilan
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-ink-muted">
                    Anda akan mengubah tingkat peradilan dari <strong>{{ strtoupper($tingkat) }}</strong> ke <strong>{{ strtoupper($pendingTingkat) }}</strong>. Operasi ini menambahkan halaman dan menyesuaikan visibilitas menu tingkat terkait secara non-destruktif.
                </p>

                <form wire:submit="confirmChangeTingkat" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label for="pending_tingkat">Target Tingkat Baru</flux:label>
                        <flux:select id="pending_tingkat" wire:model="pendingTingkat">
                            <option value="pertama">Tingkat Pertama (PERTAMA)</option>
                            <option value="banding">Tingkat Banding (BANDING)</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="confirm_phrase">Ketik "{{ strtoupper($pendingTingkat) }}" untuk Konfirmasi</flux:label>
                        <flux:input id="confirm_phrase" wire:model="confirmationPhrase" required autocomplete="off" />
                        <flux:error name="confirmationPhrase" />
                    </flux:field>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeTingkatModal" class="min-h-11 cursor-pointer rounded-md border border-hairline bg-white px-4 py-2 text-xs font-semibold text-ink hover:bg-mist">
                            Batal
                        </button>
                        <button type="submit" class="min-h-11 cursor-pointer rounded-md bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary-hover">
                            Konfirmasi & Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
