<div class="mx-auto w-full max-w-3xl rounded-xl border border-hairline bg-white p-6 sm:p-8">
    <div class="mb-6">
        <h2 class="font-serif text-2xl font-bold text-ink">Wizard Pengaturan Awal Satuan Kerja</h2>
        <p class="mt-1 text-sm text-ink-body">
            Satu instalasi untuk satu satuan kerja. Pengaturan ini mengisi identitas resmi, menu wajib SINOVA, serta tautan SIPP/SIMTEPA.
        </p>
    </div>

    <!-- Stepper -->
    <nav aria-label="Tahapan Pengaturan Awal" class="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach ([1 => '1. Identitas & Tingkat', 2 => '2. Alamat & Kontak', 3 => '3. SIPP & SIMTEPA', 4 => '4. Super Admin'] as $num => $label)
            <div @class([
                'rounded-md border px-3 py-2.5 text-xs font-semibold',
                'border-primary bg-primary-soft text-primary' => $step === $num,
                'border-emerald-200 bg-emerald-50 text-emerald-800' => $step > $num,
                'border-hairline bg-mist text-ink-muted' => $step < $num,
            ])>
                {{ $label }}
            </div>
        @endforeach
    </nav>

    <form wire:submit="{{ $step === 4 ? 'finishSetup' : 'nextStep' }}" class="space-y-5">
        @if ($step === 1)
            <flux:field>
                <flux:label for="nama_satker">Nama Satuan Kerja</flux:label>
                <flux:input id="nama_satker" wire:model="nama_satker" placeholder="Contoh: Pengadilan Agama Bandung / PTA Jawa Barat" required />
                <flux:error name="nama_satker" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:field>
                    <flux:label for="jenis_badan">Jenis Badan Peradilan</flux:label>
                    <flux:select id="jenis_badan" wire:model.live="jenis_badan">
                        <option value="PA">Pengadilan Agama (PA)</option>
                        <option value="PTA">Pengadilan Tinggi Agama (PTA)</option>
                        <option value="MS">Mahkamah Syar'iyah (MS)</option>
                        <option value="MS_ACEH">Mahkamah Syar'iyah Aceh</option>
                    </flux:select>
                    <flux:error name="jenis_badan" />
                </flux:field>

                <flux:field>
                    <flux:label for="kode_satker">Kode Satker</flux:label>
                    <flux:input id="kode_satker" wire:model="kode_satker" placeholder="Contoh: 401234" />
                    <flux:error name="kode_satker" />
                </flux:field>

                <flux:field>
                    <flux:label for="tahun_berdiri">Tahun Berdiri</flux:label>
                    <flux:input id="tahun_berdiri" type="number" wire:model="tahun_berdiri" placeholder="Contoh: 1982" />
                    <flux:error name="tahun_berdiri" />
                </flux:field>
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-ink">Pilih Tingkat Peradilan</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label @class([
                        'flex flex-col gap-1 rounded-lg border p-4',
                        'border-primary bg-primary-soft' => $tingkat === 'pertama',
                        'border-hairline bg-white' => $tingkat !== 'pertama',
                    ])>
                        <span class="flex items-center gap-2 text-sm font-bold text-ink">
                            <input type="radio" wire:model.live="tingkat" value="pertama" class="size-4 text-primary">
                            Tingkat Pertama (PA / MS)
                        </span>
                        <span class="text-xs text-ink-body">
                            Yurisdiksi kecamatan, laporan pengembalian sisa panjar, panggilan ghaib, serta SIPP satker lokal.
                        </span>
                    </label>

                    <label @class([
                        'flex flex-col gap-1 rounded-lg border p-4',
                        'border-primary bg-primary-soft' => $tingkat === 'banding',
                        'border-hairline bg-white' => $tingkat !== 'banding',
                    ])>
                        <span class="flex items-center gap-2 text-sm font-bold text-ink">
                            <input type="radio" wire:model.live="tingkat" value="banding" class="size-4 text-primary">
                            Tingkat Banding (PTA / MS Aceh)
                        </span>
                        <span class="text-xs text-ink-body">
                            Yurisdiksi kab/kota, SIPP Banding Mahkamah Agung, serta penjelasan panggilan ghaib tingkat pertama.
                        </span>
                    </label>
                </div>
                <flux:error name="tingkat" />
            </fieldset>

            <div class="rounded-md border border-amber-400 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                Pilihan tingkat akan dikunci setelah wizard selesai. Perubahan di kemudian hari hanya dapat dilakukan oleh Super Admin melalui konfirmasi ganda.
            </div>
        @endif

        @if ($step === 2)
            <flux:field>
                <flux:label for="alamat_jalan">Alamat Jalan</flux:label>
                <flux:input id="alamat_jalan" wire:model="alamat_jalan" placeholder="Jl. KH. Ahmad Dahlan No. 1" required />
                <flux:error name="alamat_jalan" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label for="desa_kelurahan">Desa / Kelurahan</flux:label>
                    <flux:input id="desa_kelurahan" wire:model="desa_kelurahan" />
                    <flux:error name="desa_kelurahan" />
                </flux:field>
                <flux:field>
                    <flux:label for="kecamatan">Kecamatan</flux:label>
                    <flux:input id="kecamatan" wire:model="kecamatan" />
                    <flux:error name="kecamatan" />
                </flux:field>
                <flux:field>
                    <flux:label for="kabupaten_kota">Kabupaten / Kota</flux:label>
                    <flux:input id="kabupaten_kota" wire:model="kabupaten_kota" />
                    <flux:error name="kabupaten_kota" />
                </flux:field>
                <flux:field>
                    <flux:label for="provinsi">Provinsi</flux:label>
                    <flux:input id="provinsi" wire:model="provinsi" required />
                    <flux:error name="provinsi" />
                </flux:field>
                <flux:field>
                    <flux:label for="kode_pos">Kode Pos</flux:label>
                    <flux:input id="kode_pos" wire:model="kode_pos" />
                    <flux:error name="kode_pos" />
                </flux:field>
                <flux:field>
                    <flux:label for="telepon">Nomor Telepon / Kontak</flux:label>
                    <flux:input id="telepon" wire:model="telepon" required />
                    <flux:error name="telepon" />
                </flux:field>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label for="email">Surat Elektronik Resmi</flux:label>
                    <flux:input id="email" type="email" wire:model="email" placeholder="info@pa-satker.go.id" required />
                    <flux:error name="email" />
                </flux:field>
                <flux:field>
                    <flux:label for="website_url">URL Situs Resmi (.go.id)</flux:label>
                    <flux:input id="website_url" type="url" wire:model="website_url" placeholder="https://pa-satker.go.id" />
                    <flux:error name="website_url" />
                </flux:field>
            </div>
        @endif

        @if ($step === 3)
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label for="sipp_url">URL SIPP Satker / Banding</flux:label>
                    <flux:input id="sipp_url" type="url" wire:model="sipp_url" placeholder="https://sipp.pa-satker.go.id" />
                    <flux:error name="sipp_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="ecourt_url">URL e-Court</flux:label>
                    <flux:input id="ecourt_url" type="url" wire:model="ecourt_url" />
                    <flux:error name="ecourt_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="direktori_putusan_url">URL Direktori Putusan Satker</flux:label>
                    <flux:input id="direktori_putusan_url" type="url" wire:model="direktori_putusan_url" />
                    <flux:error name="direktori_putusan_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="siwas_url">URL SIWAS MARI</flux:label>
                    <flux:input id="siwas_url" type="url" wire:model="siwas_url" />
                    <flux:error name="siwas_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="simtepa_pegawai_url">URL Embed SIMTEPA Pegawai</flux:label>
                    <flux:input id="simtepa_pegawai_url" type="url" wire:model="simtepa_pegawai_url" placeholder="Opsional, tersedia cadangan lokal" />
                    <flux:error name="simtepa_pegawai_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="simtepa_struktur_url">URL Embed SIMTEPA Struktur Organisasi</flux:label>
                    <flux:input id="simtepa_struktur_url" type="url" wire:model="simtepa_struktur_url" placeholder="Opsional, tersedia cadangan gambar/diagram" />
                    <flux:error name="simtepa_struktur_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="skm_url">URL Survei SKM</flux:label>
                    <flux:input id="skm_url" type="url" wire:model="skm_url" />
                    <flux:error name="skm_url" />
                </flux:field>
                <flux:field>
                    <flux:label for="spak_url">URL Survei SPAK</flux:label>
                    <flux:input id="spak_url" type="url" wire:model="spak_url" />
                    <flux:error name="spak_url" />
                </flux:field>
            </div>
        @endif

        @if ($step === 4)
            <flux:field>
                <flux:label for="admin_name">Nama Lengkap Super Admin</flux:label>
                <flux:input id="admin_name" wire:model="admin_name" placeholder="Contoh: Tim TI Pengadilan" required />
                <flux:error name="admin_name" />
            </flux:field>

            <flux:field>
                <flux:label for="admin_email">Email Login Super Admin</flux:label>
                <flux:input id="admin_email" type="email" wire:model="admin_email" placeholder="admin@pa-satker.go.id" required />
                <flux:error name="admin_email" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label for="admin_password">Kata Sandi</flux:label>
                    <flux:input id="admin_password" type="password" wire:model="admin_password" required />
                    <flux:error name="admin_password" />
                </flux:field>

                <flux:field>
                    <flux:label for="admin_password_confirmation">Konfirmasi Kata Sandi</flux:label>
                    <flux:input id="admin_password_confirmation" type="password" wire:model="admin_password_confirmation" required />
                </flux:field>
            </div>
        @endif

        <div class="flex items-center justify-between border-t border-hairline pt-4">
            <div>
                @if ($step > 1)
                    <flux:button type="button" wire:click="previousStep" variant="outline" class="min-h-11">
                        Kembali
                    </flux:button>
                @else
                    <span class="font-mono text-xs text-ink-muted">Langkah 1 dari 4</span>
                @endif
            </div>

            <flux:button type="submit" variant="primary" class="min-h-11 bg-primary font-semibold text-white hover:bg-primary-hover">
                {{ $step === 4 ? 'Selesaikan Instalasi & Aktifkan 2FA' : 'Simpan & Lanjut' }}
            </flux:button>
        </div>
    </form>
</div>
