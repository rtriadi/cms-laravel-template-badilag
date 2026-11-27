@php
    $wizardSteps = [
        1 => ['title' => 'Identitas Satuan Kerja', 'desc' => 'Nama, kode satker, dan tingkat peradilan'],
        2 => ['title' => 'Alamat & Kontak Resmi', 'desc' => 'Alamat kantor, telepon, email, dan website'],
        3 => ['title' => 'Integrasi Aplikasi MA', 'desc' => 'Tautan SIPP, e-Court, SIMTEPA, dan SIWAS'],
        4 => ['title' => 'Akun Super Admin', 'desc' => 'Buat akun pengelola pertama dan aktifkan 2FA'],
    ];
@endphp
<div class="grid items-start gap-6 lg:grid-cols-12">
    <!-- Left Stepper (Desktop Sidebar + Compact Mobile Bar) -->
    <aside class="studio-card p-5 lg:sticky lg:top-6 lg:col-span-4">
        <h2 class="text-sm font-bold text-ink">Tahapan pengaturan awal</h2>

        <nav aria-label="Tahapan Pengaturan Awal" class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-1 lg:gap-1.5">
            @foreach ($wizardSteps as $num => $meta)
                <div @class([
                    'flex items-start gap-3 rounded-xl border p-3 transition-colors',
                    'border-primary/40 bg-primary-soft' => $step === $num,
                    'border-emerald-200 bg-emerald-50/60' => $step > $num,
                    'border-hairline bg-mist/60' => $step < $num,
                ])>
                    <span @class([
                        'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        'bg-primary text-white' => $step === $num,
                        'bg-emerald-700 text-white' => $step > $num,
                        'bg-hairline text-ink-muted' => $step < $num,
                    ])>
                        @if ($step > $num)
                            &#10003;
                        @else
                            {{ $num }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p @class([
                            'text-xs font-bold sm:text-sm',
                            'text-primary' => $step === $num,
                            'text-ink' => $step !== $num,
                        ])>{{ $meta['title'] }}</p>
                        <p class="mt-0.5 hidden text-xs text-ink-muted lg:block">{{ $meta['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="mt-4 hidden rounded-xl border border-primary/25 bg-primary-soft p-3.5 text-xs leading-relaxed text-ink-body lg:block">
            <p class="font-bold text-primary">Data awal disiapkan otomatis</p>
            <p class="mt-1">Setelah selesai, 6 menu utama dan halaman standar SINOVA akan terpasang otomatis sesuai tingkat peradilan.</p>
        </div>
    </aside>

    <!-- Right Calm Single-Column Form -->
    <div class="studio-card p-5 sm:p-8 lg:col-span-8">
        <div class="border-b border-hairline pb-5">
            <span class="inline-flex rounded-md bg-primary-soft px-2.5 py-1 font-mono text-xs font-semibold text-primary">
                Langkah {{ $step }} dari 4
            </span>
            <h1 class="mt-2 font-serif text-2xl font-bold text-ink sm:text-[26px]">
                {{ $wizardSteps[$step]['title'] }}
            </h1>
            <p class="mt-1 text-sm text-ink-muted">
                Wizard Pengaturan Awal Satuan Kerja. Pengaturan ini dapat diubah kembali nanti di menu Pengaturan Satker.
            </p>
        </div>

        <form wire:submit="{{ $step === 4 ? 'finishSetup' : 'nextStep' }}" class="mt-6 space-y-5">
            @if ($step === 1)
                <flux:field>
                    <flux:label for="nama_satker">Nama Satuan Kerja <span class="text-red-700">*</span></flux:label>
                    <flux:input id="nama_satker" wire:model="nama_satker" placeholder="Contoh: Pengadilan Agama Gorontalo" required />
                    <flux:error name="nama_satker" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="jenis_badan">Jenis Badan Peradilan</flux:label>
                        <x-admin.select id="jenis_badan" wire:model.live="jenis_badan">
                            <option value="PA">Pengadilan Agama (PA)</option>
                            <option value="PTA">Pengadilan Tinggi Agama (PTA)</option>
                            <option value="MS">Mahkamah Syar'iyah (MS)</option>
                            <option value="MS_ACEH">Mahkamah Syar'iyah Aceh</option>
                        </x-admin.select>
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
                            'flex cursor-pointer flex-col gap-1.5 rounded-xl border p-4 transition-colors',
                            'border-primary bg-primary-soft' => $tingkat === 'pertama',
                            'border-hairline bg-white hover:bg-mist' => $tingkat !== 'pertama',
                        ])>
                            <span class="flex items-center gap-2.5 text-sm font-bold text-ink">
                                <input type="radio" wire:model.live="tingkat" value="pertama" class="size-4.5 text-primary">
                                Tingkat Pertama (PA / MS)
                            </span>
                            <span class="text-xs leading-relaxed text-ink-body">
                                Yurisdiksi kecamatan, laporan pengembalian sisa panjar, panggilan ghaib, serta SIPP satker lokal.
                            </span>
                        </label>

                        <label @class([
                            'flex cursor-pointer flex-col gap-1.5 rounded-xl border p-4 transition-colors',
                            'border-primary bg-primary-soft' => $tingkat === 'banding',
                            'border-hairline bg-white hover:bg-mist' => $tingkat !== 'banding',
                        ])>
                            <span class="flex items-center gap-2.5 text-sm font-bold text-ink">
                                <input type="radio" wire:model.live="tingkat" value="banding" class="size-4.5 text-primary">
                                Tingkat Banding (PTA / MS Aceh)
                            </span>
                            <span class="text-xs leading-relaxed text-ink-body">
                                Yurisdiksi kab/kota, SIPP Banding Mahkamah Agung, serta penjelasan panggilan ghaib tingkat pertama.
                            </span>
                        </label>
                    </div>
                    <flux:error name="tingkat" />
                </fieldset>

                <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-950">
                    Pilihan tingkat akan dikunci setelah wizard selesai. Perubahan di kemudian hari hanya dapat dilakukan oleh Super Admin melalui konfirmasi ganda.
                </div>
            @endif

            @if ($step === 2)
                <flux:field>
                    <flux:label for="alamat_jalan">Alamat Lengkap Kantor <span class="text-red-700">*</span></flux:label>
                    <flux:input id="alamat_jalan" wire:model="alamat_jalan" placeholder="Jl. Nama Jalan No. XX" required />
                    <flux:error name="alamat_jalan" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="desa_kelurahan">Desa / Kelurahan</flux:label>
                        <flux:input id="desa_kelurahan" wire:model="desa_kelurahan" placeholder="Contoh: Limba U II" />
                        <flux:error name="desa_kelurahan" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="kecamatan">Kecamatan</flux:label>
                        <flux:input id="kecamatan" wire:model="kecamatan" placeholder="Contoh: Kota Selatan" />
                        <flux:error name="kecamatan" />
                    </flux:field>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="kabupaten_kota">Kabupaten / Kota</flux:label>
                        <flux:input id="kabupaten_kota" wire:model="kabupaten_kota" placeholder="Contoh: Kota Gorontalo" />
                        <flux:error name="kabupaten_kota" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="provinsi">Provinsi <span class="text-red-700">*</span></flux:label>
                        <flux:input id="provinsi" wire:model="provinsi" placeholder="Contoh: Gorontalo" required />
                        <flux:error name="provinsi" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="kode_pos">Kode Pos</flux:label>
                        <flux:input id="kode_pos" wire:model="kode_pos" placeholder="96115" />
                        <flux:error name="kode_pos" />
                    </flux:field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="telepon">Nomor Telepon Kantor <span class="text-red-700">*</span></flux:label>
                        <flux:input id="telepon" wire:model="telepon" placeholder="(0435) 821xxx" required />
                        <flux:error name="telepon" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="email">Email Resmi Satker <span class="text-red-700">*</span></flux:label>
                        <flux:input id="email" type="email" wire:model="email" placeholder="info@pa-satker.go.id" required />
                        <flux:error name="email" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="website_url">Alamat Website Resmi (.go.id)</flux:label>
                    <flux:input id="website_url" type="url" wire:model="website_url" placeholder="https://pa-satker.go.id" />
                    <p class="mt-1 text-xs text-ink-muted">Digunakan untuk membuat tautan otomatis pada 40 butir kriteria SINOVA.</p>
                    <flux:error name="website_url" />
                </flux:field>
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
                        <flux:input id="simtepa_struktur_url" type="url" wire:model="simtepa_struktur_url" placeholder="Opsional, tersedia cadangan lokal" />
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
                    <flux:label for="admin_name">Nama Lengkap Super Admin <span class="text-red-700">*</span></flux:label>
                    <flux:input id="admin_name" wire:model="admin_name" placeholder="Contoh: Tim TI Pengadilan" required />
                    <flux:error name="admin_name" />
                </flux:field>

                <flux:field>
                    <flux:label for="admin_email">Email Login Super Admin <span class="text-red-700">*</span></flux:label>
                    <flux:input id="admin_email" type="email" wire:model="admin_email" placeholder="admin@pa-satker.go.id" required />
                    <flux:error name="admin_email" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="admin_password">Kata Sandi <span class="text-red-700">*</span></flux:label>
                        <flux:input id="admin_password" type="password" wire:model="admin_password" required />
                        <flux:error name="admin_password" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="admin_password_confirmation">Konfirmasi Kata Sandi <span class="text-red-700">*</span></flux:label>
                        <flux:input id="admin_password_confirmation" type="password" wire:model="admin_password_confirmation" required />
                    </flux:field>
                </div>
            @endif

            <div class="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @if ($step > 1)
                        <flux:button type="button" wire:click="previousStep" variant="outline" class="min-h-11 w-full cursor-pointer sm:w-auto">
                            &larr; Kembali
                        </flux:button>
                    @else
                        <span class="font-mono text-xs text-ink-muted">Langkah 1 dari 4</span>
                    @endif
                </div>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary px-6 font-semibold text-white hover:bg-primary-hover sm:w-auto">
                    {{ $step === 4 ? 'Selesaikan Instalasi & Aktifkan 2FA' : 'Simpan & Lanjut' }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
