@php
    $settingSections = [
        ['id' => 'identitas', 'href' => '#sec-identitas', 'label' => 'Identitas & Logo'],
        ['id' => 'alamat', 'href' => '#sec-alamat', 'label' => 'Alamat & Kontak'],
        ['id' => 'integrasi', 'href' => '#sec-integrasi', 'label' => 'Integrasi SIPP & SIMTEPA'],
        ['id' => 'pengawasan', 'href' => '#sec-pengawasan', 'label' => 'Pengawasan & Survei'],
        ['id' => 'tautan-medsos', 'href' => '#sec-tautan-medsos', 'label' => 'Tautan, Medsos & ACO'],
        ['id' => 'tingkat', 'href' => '#sec-tingkat', 'label' => 'Tingkat Peradilan'],
    ];
@endphp
<div class="space-y-6" x-data="{ activeSection: 'identitas' }">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Pengaturan Satuan Kerja</h1>
            <p class="mt-1 text-sm text-ink-body">
                Atur identitas, alamat, tautan aplikasi Mahkamah Agung, serta kanal resmi satker. Logo otomatis memperbarui sidebar admin dan header website publik.
            </p>
        </div>
    </div>

    <!-- Mobile Horizontal Section Jump Pills (< lg) -->
    <nav aria-label="Lompat ke bagian pengaturan" class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:hidden">
        @foreach ($settingSections as $navItem)
            <a
                href="{{ $navItem['href'] }}"
                x-on:click="activeSection = '{{ $navItem['id'] }}'"
                x-bind:class="activeSection === '{{ $navItem['id'] }}'
                    ? 'border-primary bg-primary text-white font-semibold'
                    : 'border-hairline bg-white text-ink font-medium'"
                class="inline-flex min-h-11 shrink-0 items-center rounded-lg border px-3.5 text-xs transition-colors"
            >
                {{ $navItem['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Desktop Sticky Sub-Navigation (>= lg) -->
        <nav aria-label="Bagian Pengaturan Satker" class="studio-card hidden space-y-1 p-2.5 lg:sticky lg:top-20 lg:col-span-3 lg:block">
            @foreach ($settingSections as $navItem)
                <a
                    href="{{ $navItem['href'] }}"
                    x-on:click="activeSection = '{{ $navItem['id'] }}'"
                    x-bind:class="activeSection === '{{ $navItem['id'] }}'
                        ? 'bg-primary-soft font-bold text-primary'
                        : 'text-ink-body font-medium hover:bg-mist hover:text-ink'"
                    class="flex min-h-10 items-center rounded-lg px-3.5 text-sm transition-colors"
                >
                    {{ $navItem['label'] }}
                </a>
            @endforeach
        </nav>

        <!-- Right Clean Calm Form Cards -->
        <form wire:submit="save" class="space-y-6 lg:col-span-9">
            <!-- Card 1: Identitas & Logo Satuan Kerja -->
            <section id="sec-identitas" class="studio-card scroll-mt-24 space-y-5 p-5 sm:p-7">
                <div class="border-b border-hairline pb-4">
                    <h2 class="text-base font-bold text-ink">Identitas &amp; Logo Satuan Kerja</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Nama dan logo tampil di header website, panel admin, dan metadata dokumen.</p>
                </div>

                <div class="grid items-stretch gap-4 sm:grid-cols-2">
                    <div class="flex items-start gap-3.5 rounded-xl border border-hairline bg-mist p-4">
                        <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border-[1.5px] border-gold bg-white p-1.5">
                            @if ($existingLogoPath)
                                <img src="{{ asset('storage/' . $existingLogoPath) }}" alt="Logo {{ $nama_satker }}" width="56" height="56" class="size-full object-contain" />
                            @else
                                <span class="font-mono text-xs font-bold text-primary">LOGO</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:label for="st_logo" class="mb-0!">Logo Satuan Kerja</flux:label>
                            <p class="mt-0.5 text-[11px] text-ink-muted">Maks. 2 MB (PNG, JPG, WebP)</p>
                            <input id="st_logo" type="file" wire:model="logo" accept="image/*" class="mt-2 block w-full cursor-pointer text-xs text-ink file:mr-2.5 file:min-h-9 file:cursor-pointer file:rounded-lg file:border file:border-hairline file:bg-white file:px-3 file:text-xs file:font-semibold file:text-primary" />
                            <div wire:loading wire:target="logo" class="mt-1 text-xs font-semibold text-primary">Mengunggah logo...</div>
                            <flux:error name="logo" />
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 rounded-xl border border-hairline bg-mist p-4">
                        <div class="flex h-14 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-hairline bg-white">
                            @if ($existingFotoGedungPath)
                                <img src="{{ asset('storage/' . $existingFotoGedungPath) }}" alt="Gedung {{ $nama_satker }}" width="80" height="56" class="size-full object-cover" />
                            @else
                                <span class="font-mono text-[10px] font-bold text-ink-muted">GEDUNG</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:label for="st_foto" class="mb-0!">Foto Gedung Kantor</flux:label>
                            <p class="mt-0.5 text-[11px] text-ink-muted">Maks. 4 MB (JPG, PNG, WebP)</p>
                            <input id="st_foto" type="file" wire:model="foto_gedung" accept="image/*" class="mt-2 block w-full cursor-pointer text-xs text-ink file:mr-2.5 file:min-h-9 file:cursor-pointer file:rounded-lg file:border file:border-hairline file:bg-white file:px-3 file:text-xs file:font-semibold file:text-primary" />
                            <div wire:loading wire:target="foto_gedung" class="mt-1 text-xs font-semibold text-primary">Mengunggah foto gedung...</div>
                            <flux:error name="foto_gedung" />
                        </div>
                    </div>
                </div>

                <flux:field>
                    <flux:label for="st_nama">Nama resmi satuan kerja <span class="text-red-700">*</span></flux:label>
                    <flux:input id="st_nama" wire:model="nama_satker" required placeholder="Contoh: Pengadilan Agama Gorontalo" />
                    <flux:error name="nama_satker" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_jenis">Jenis badan peradilan</flux:label>
                        <x-admin.select id="st_jenis" wire:model="jenis_badan">
                            @foreach ($jenisBadanOptions as $jKey => $jLabel)
                                <option value="{{ $jKey }}">{{ $jLabel }}</option>
                            @endforeach
                        </x-admin.select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="st_kode">Kode satker</flux:label>
                        <flux:input id="st_kode" wire:model="kode_satker" placeholder="401..." class="font-mono" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="st_tahun">Tahun berdiri</flux:label>
                        <flux:input id="st_tahun" type="number" wire:model="tahun_berdiri" min="1900" max="2100" class="font-mono" />
                    </flux:field>
                </div>
            </section>

            <!-- Card 2: Alamat & Kontak Resmi -->
            <section id="sec-alamat" class="studio-card scroll-mt-24 space-y-4 p-5 sm:p-7">
                <div class="border-b border-hairline pb-4">
                    <h2 class="text-base font-bold text-ink">Alamat &amp; Kontak Resmi</h2>
                </div>

                <flux:field>
                    <flux:label for="st_jalan">Alamat jalan</flux:label>
                    <flux:input id="st_jalan" wire:model="alamat_jalan" placeholder="Jl. ..." />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="st_kel">Desa / Kelurahan</flux:label>
                        <flux:input id="st_kel" wire:model="desa_kelurahan" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_kec">Kecamatan</flux:label>
                        <flux:input id="st_kec" wire:model="kecamatan" />
                    </flux:field>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_kota">Kabupaten / Kota</flux:label>
                        <flux:input id="st_kota" wire:model="kabupaten_kota" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_prov">Provinsi</flux:label>
                        <flux:input id="st_prov" wire:model="provinsi" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_pos">Kode pos</flux:label>
                        <flux:input id="st_pos" wire:model="kode_pos" class="font-mono" />
                    </flux:field>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_telp">Nomor telepon</flux:label>
                        <flux:input id="st_telp" wire:model="telepon" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_email">Email resmi</flux:label>
                        <flux:input id="st_email" type="email" wire:model="email" />
                        <flux:error name="email" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="st_web">URL website</flux:label>
                        <flux:input id="st_web" type="url" wire:model="website_url" class="font-mono" />
                    </flux:field>
                </div>
            </section>

            <!-- Card 3: Integrasi SIPP, SIMTEPA & Aplikasi Eksternal -->
            <section id="sec-integrasi" class="studio-card scroll-mt-24 space-y-5 p-5 sm:p-7">
                <div class="border-b border-hairline pb-4">
                    <h2 class="text-base font-bold text-ink">Integrasi SIPP, SIMTEPA &amp; Aplikasi Eksternal</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Cukup tempelkan tautan dasar SIPP atau token SIMTEPA untuk pemetaan otomatis tombol layanan di portal publik.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="ext_sipp">URL SIPP Satker</flux:label>
                        <flux:input id="ext_sipp" type="url" wire:model="sipp_url" placeholder="https://sipp.pa-...." class="font-mono" />
                        <p class="mt-1 text-xs text-ink-muted">Otomatis membentuk endpoint Daftar Perkara, Jadwal Sidang, &amp; Statistik.</p>
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_ecourt">URL e-Court MARI</flux:label>
                        <flux:input id="ext_ecourt" type="url" wire:model="ecourt_url" placeholder="https://ecourt.mahkamahagung.go.id" class="font-mono" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_dir">URL Direktori Putusan</flux:label>
                        <flux:input id="ext_dir" type="url" wire:model="direktori_putusan_url" placeholder="https://putusan3.mahkamahagung.go.id" class="font-mono" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_siwas">URL SIWAS MARI</flux:label>
                        <flux:input id="ext_siwas" type="url" wire:model="siwas_url" placeholder="https://siwas.mahkamahagung.go.id" class="font-mono" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_sim_peg">Token / URL SIMTEPA Pegawai</flux:label>
                        <flux:input id="ext_sim_peg" wire:model="simtepa_pegawai_url" placeholder="https://simtepa.mahkamahagung.go.id/share/..." class="font-mono" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_sim_str">Token / URL SIMTEPA Struktur</flux:label>
                        <flux:input id="ext_sim_str" wire:model="simtepa_struktur_url" placeholder="https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/..." class="font-mono" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_skm">URL Survei SKM / IKPM</flux:label>
                        <flux:input id="ext_skm" type="url" wire:model="skm_url" placeholder="https://..." class="font-mono" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_spak">URL Survei SPAK / IPAK</flux:label>
                        <flux:input id="ext_spak" type="url" wire:model="spak_url" placeholder="https://..." class="font-mono" />
                    </flux:field>
                </div>

                @if ($satker)
                    <div class="space-y-3 rounded-xl border border-emerald-200 bg-primary-soft p-4 text-xs">
                        <div>
                            <p class="font-bold text-primary-deep">Pemetaan Otomatis Endpoint SIPP Aktif:</p>
                            @if ($satker->sipp_url)
                                <ul class="mt-1 space-y-1 break-all font-mono text-[11px] text-ink-body">
                                    <li><strong>Daftar Perkara:</strong> {{ $satker->resolvedSippEmbedUrl('sipp_perkara') }}</li>
                                    <li><strong>Jadwal Sidang:</strong> {{ $satker->resolvedSippEmbedUrl('sipp_jadwal') }}</li>
                                    <li><strong>Statistik Perkara:</strong> {{ $satker->resolvedSippEmbedUrl('sipp_statistik') }}</li>
                                </ul>
                            @else
                                <p class="mt-1 text-ink-muted">URL SIPP belum diisi.</p>
                            @endif
                        </div>

                        @php
                            $simtepaTabs = $satker->simtepaCategoryTabs(false);
                            $simtepaStrukturResolved = $satker->resolveEmbedSource('simtepa_struktur');
                        @endphp
                        <div class="border-t border-emerald-200 pt-2.5">
                            <p class="font-bold text-primary-deep">URL Bagan Struktur Organisasi SIMTEPA Aktif:</p>
                            @if (! empty($simtepaStrukturResolved['embed_url']) && empty($simtepaStrukturResolved['tabs']))
                                <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-hairline bg-white px-3 py-2 text-[11px]">
                                    <span class="break-all font-mono text-ink">{{ $simtepaStrukturResolved['embed_url'] }}</span>
                                    <a href="{{ $simtepaStrukturResolved['embed_url'] }}" target="_blank" rel="noopener noreferrer" class="shrink-0 font-mono font-semibold text-primary hover:underline">Buka</a>
                                </div>
                            @elseif (! empty($simtepaStrukturResolved['tabs']))
                                <p class="mt-1 text-ink-body">Menggunakan mode 6 Tab Pejabat SIMTEPA.</p>
                            @else
                                <p class="mt-1 text-ink-muted">URL struktur organisasi belum diisi (memakai data cadangan lokal).</p>
                            @endif
                        </div>

                        @if ($simtepaTabs !== [])
                            <div class="border-t border-emerald-200 pt-2.5">
                                <p class="font-bold text-primary-deep">
                                    7 Kategori Share Data SIMTEPA
                                    @if ($satker->extractSimtepaToken())
                                        (Token: <code class="font-mono">{{ $satker->extractSimtepaToken() }}</code>)
                                    @endif
                                </p>
                                <ul class="mt-2 grid gap-1.5 sm:grid-cols-2">
                                    @foreach ($simtepaTabs as $sTab)
                                        <li class="flex items-center justify-between gap-2 rounded-lg border border-hairline bg-white px-3 py-1.5 text-[11px]">
                                            <span class="truncate font-semibold text-ink">{{ $loop->iteration }}. {{ $sTab['label'] }}</span>
                                            <span class="flex shrink-0 items-center gap-1.5 font-mono">
                                                <a href="{{ $sTab['html_url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary hover:underline">HTML</a>
                                                <span class="text-hairline">|</span>
                                                <a href="{{ $sTab['json_url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-ink-muted hover:underline">JSON</a>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            <!-- Card 4: Pengawasan & Indeks Survei -->
            <section id="sec-pengawasan" class="studio-card scroll-mt-24 space-y-5 p-5 sm:p-7">
                <div class="border-b border-hairline pb-4">
                    <h2 class="text-base font-bold text-ink">Pengawasan &amp; Kelengkapan Halaman Depan</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Tautan Pengadilan Tingkat Banding, angka Indeks Survei Pelayanan (SKM, SPAK, SPKP), serta tautan ringan Video Profil &amp; Kebijakan MA.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="ext_pta_nama">Nama Pengadilan Tingkat Banding</flux:label>
                        <flux:input id="ext_pta_nama" wire:model="pta_nama" placeholder="Pengadilan Tinggi Agama Gorontalo" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_pta_url">URL Website Pengadilan Tingkat Banding</flux:label>
                        <flux:input id="ext_pta_url" type="url" wire:model="pta_url" placeholder="https://pta-gorontalo.go.id" class="font-mono" />
                    </flux:field>
                </div>

                <div class="border-t border-hairline pt-4">
                    <p class="mb-3 text-sm font-bold text-ink">Indeks Survei Pelayanan di Beranda (SKM / SPAK / SPKP)</p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:field>
                            <flux:label for="idx_periode">Periode Survei</flux:label>
                            <flux:input id="idx_periode" wire:model="survei_periode" placeholder="Triwulan III 2026" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="idx_skm">Nilai IKM / SKM</flux:label>
                            <flux:input id="idx_skm" wire:model="nilai_skm" placeholder="95,40" class="font-mono" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="idx_spak">Nilai IPAK / SPAK</flux:label>
                            <flux:input id="idx_spak" wire:model="nilai_spak" placeholder="96,10" class="font-mono" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="idx_spkp">Nilai IPKL / SPKP</flux:label>
                            <flux:input id="idx_spkp" wire:model="nilai_spkp" placeholder="95,80" class="font-mono" />
                        </flux:field>
                    </div>
                </div>

                <div class="border-t border-hairline pt-4">
                    <p class="mb-3 text-sm font-bold text-ink">Tautan Video &amp; Infografis Kebijakan MA (Tanpa Embed Berat)</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:field>
                            <flux:label for="vid_profil">URL Video Profil Pengadilan</flux:label>
                            <flux:input id="vid_profil" type="url" wire:model="video_profil_url" placeholder="https://youtube.com/watch?v=..." />
                        </flux:field>
                        <flux:field>
                            <flux:label for="vid_ptsp">URL Video PTSP Pengadilan</flux:label>
                            <flux:input id="vid_ptsp" type="url" wire:model="video_ptsp_url" placeholder="https://youtube.com/watch?v=..." />
                        </flux:field>
                        <flux:field>
                            <flux:label for="vid_kebijakan">URL Video / Infografis Kebijakan MA</flux:label>
                            <flux:input id="vid_kebijakan" type="url" wire:model="video_kebijakan_ma_url" placeholder="https://youtube.com/watch?v=..." />
                        </flux:field>
                    </div>
                </div>
            </section>

            <!-- Card 5: Tautan Instansi, Sosial Media & CCTV (ACO) -->
            <section id="sec-tautan-medsos" class="studio-card scroll-mt-24 space-y-5 p-5 sm:p-7">
                <div class="border-b border-hairline pb-4">
                    <h2 class="text-base font-bold text-ink">Tautan Instansi, Sosial Media &amp; CCTV (ACO)</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Kelola tautan instansi terkait, kanal media sosial resmi, serta tautan Access CCTV Online (ACO) Badilag.</p>
                </div>

                <div class="space-y-3 rounded-xl border border-emerald-200 bg-primary-soft p-4">
                    <div>
                        <p class="text-sm font-bold text-primary-deep">Access CCTV Online (ACO) Badilag</p>
                        <p class="mt-0.5 text-xs text-ink-body">Tampil sebagai kartu tautan ringan tanpa live streaming berat di beranda agar waktu muat tetap di bawah 5 detik.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="ext_cctv_url">URL Access CCTV Online (ACO)</flux:label>
                            <flux:input id="ext_cctv_url" type="url" wire:model="cctv_aco_url" placeholder="https://cctv.badilag.net/display/satker/..." class="font-mono" />
                            <flux:error name="cctv_aco_url" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="ext_cctv_label">Label tampilan CCTV</flux:label>
                            <flux:input id="ext_cctv_label" wire:model="cctv_aco_label" placeholder="Access CCTV Online (ACO)" />
                            <flux:error name="cctv_aco_label" />
                        </flux:field>
                    </div>
                </div>

                <div class="border-t border-hairline pt-4">
                    <p class="mb-3 text-sm font-bold text-ink">Kanal Sosial Media Resmi Satuan Kerja</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="soc_fb">URL Facebook</flux:label>
                            <flux:input id="soc_fb" type="url" wire:model="social_facebook_url" placeholder="https://www.facebook.com/..." />
                            <flux:error name="social_facebook_url" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="soc_ig">URL Instagram</flux:label>
                            <flux:input id="soc_ig" type="url" wire:model="social_instagram_url" placeholder="https://www.instagram.com/..." />
                            <flux:error name="social_instagram_url" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="soc_tt">URL TikTok</flux:label>
                            <flux:input id="soc_tt" type="url" wire:model="social_tiktok_url" placeholder="https://www.tiktok.com/@..." />
                            <flux:error name="social_tiktok_url" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="soc_yt">URL YouTube</flux:label>
                            <flux:input id="soc_yt" type="url" wire:model="social_youtube_url" placeholder="https://www.youtube.com/@..." />
                            <flux:error name="social_youtube_url" />
                        </flux:field>
                        <flux:field class="sm:col-span-2">
                            <flux:label for="soc_x">URL X / Twitter (opsional)</flux:label>
                            <flux:input id="soc_x" type="url" wire:model="social_x_url" placeholder="https://x.com/..." />
                            <flux:error name="social_x_url" />
                        </flux:field>
                    </div>
                </div>

                <div class="space-y-3 border-t border-hairline pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-ink">Daftar Tautan Instansi Terkait</p>
                            <p class="mt-0.5 text-xs text-ink-muted">Mahkamah Agung RI, Ditjen Badilag, PTA/MS Aceh, PA sewilayah, dan Pemerintah Daerah.</p>
                        </div>
                        <button type="button" wire:click="addRelatedLink" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-primary bg-primary-soft px-3.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white sm:min-h-9">
                            + Tambah Tautan Instansi
                        </button>
                    </div>

                    @if ($related_links === [])
                        <div class="rounded-xl border border-dashed border-[#768780] bg-mist px-4 py-6 text-center text-xs text-ink-muted">
                            Belum ada tautan instansi terkait. Klik tombol <strong>+ Tambah Tautan Instansi</strong> untuk menambahkan.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($related_links as $rIdx => $rItem)
                                <div wire:key="rel-link-{{ $rIdx }}" class="space-y-3 rounded-xl border border-hairline bg-mist p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-2.5">
                                        <span class="font-mono text-xs font-bold text-primary">Tautan #{{ $rIdx + 1 }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" wire:click="moveRelatedLinkUp({{ $rIdx }})" @disabled($rIdx === 0) aria-label="Geser tautan #{{ $rIdx + 1 }} ke atas" class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white px-2 text-xs font-bold text-ink hover:bg-primary-soft disabled:opacity-40 sm:min-h-8 sm:min-w-8">&uarr;</button>
                                            <button type="button" wire:click="moveRelatedLinkDown({{ $rIdx }})" @disabled($rIdx === count($related_links) - 1) aria-label="Geser tautan #{{ $rIdx + 1 }} ke bawah" class="inline-flex min-h-10 min-w-10 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white px-2 text-xs font-bold text-ink hover:bg-primary-soft disabled:opacity-40 sm:min-h-8 sm:min-w-8">&darr;</button>
                                            <button type="button" wire:click="removeRelatedLink({{ $rIdx }})" class="inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">Hapus</button>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-12">
                                        <div class="sm:col-span-3">
                                            <flux:field>
                                                <flux:label for="rl_code_{{ $rIdx }}">Kode singkat</flux:label>
                                                <flux:input id="rl_code_{{ $rIdx }}" wire:model="related_links.{{ $rIdx }}.code" placeholder="MARI" class="font-mono" />
                                            </flux:field>
                                        </div>
                                        <div class="sm:col-span-9">
                                            <flux:field>
                                                <flux:label for="rl_title_{{ $rIdx }}">Nama instansi</flux:label>
                                                <flux:input id="rl_title_{{ $rIdx }}" wire:model="related_links.{{ $rIdx }}.title" placeholder="Mahkamah Agung RI" />
                                            </flux:field>
                                        </div>
                                        <div class="sm:col-span-6">
                                            <flux:field>
                                                <flux:label for="rl_sub_{{ $rIdx }}">Keterangan singkat</flux:label>
                                                <flux:input id="rl_sub_{{ $rIdx }}" wire:model="related_links.{{ $rIdx }}.subtitle" placeholder="Website Mahkamah Agung RI" />
                                            </flux:field>
                                        </div>
                                        <div class="sm:col-span-6">
                                            <flux:field>
                                                <flux:label for="rl_url_{{ $rIdx }}">URL website</flux:label>
                                                <flux:input id="rl_url_{{ $rIdx }}" type="url" wire:model="related_links.{{ $rIdx }}.url" placeholder="https://www.mahkamahagung.go.id" class="font-mono" />
                                                <flux:error name="related_links.{{ $rIdx }}.url" />
                                            </flux:field>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <!-- Card 6: Tingkat Peradilan -->
            <section id="sec-tingkat" class="studio-card scroll-mt-24 p-5 sm:p-7">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-ink">Tingkat Peradilan Aktif</h2>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            Status terkunci setelah setup awal:
                            <strong class="font-mono text-primary">{{ $tingkatOptions[$tingkat] ?? $tingkat }} ({{ strtoupper($tingkat) }})</strong>
                        </p>
                    </div>
                    @if ($isSuperAdmin)
                        <button type="button" wire:click="openTingkatModal" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg bg-gold px-4 text-xs font-bold text-ink hover:opacity-90 sm:min-h-10">
                            Ubah Tingkat Peradilan
                        </button>
                    @endif
                </div>
                <p class="mt-2 text-xs text-ink-muted">
                    Perubahan tingkat peradilan bersifat non-destruktif: halaman yang sudah diisi tetap tersimpan dan struktur menu disesuaikan otomatis oleh Super Admin.
                </p>
            </section>

            <!-- Sticky Bottom Save Bar -->
            <div class="studio-card sticky bottom-4 z-10 flex flex-col gap-3 px-5 py-3.5 shadow-md sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-ink-muted">
                    Semua perubahan identitas &amp; logo langsung memperbarui tampilan CMS dan website publik.
                </p>
                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary px-6 text-sm font-semibold text-white hover:bg-primary-hover sm:min-h-10 sm:w-auto">
                    Simpan Pengaturan
                </flux:button>
            </div>
        </form>
    </div>

    <!-- Modal Konfirmasi Ganda Super Admin -->
    @if ($showTingkatModal && $isSuperAdmin)
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="tingkat-modal-title"
            x-data
            x-init="window.createFocusTrap($el)"
            x-on:click.self="$wire.closeTingkatModal()"
            @keydown.escape.window="$wire.closeTingkatModal()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-xl border border-hairline bg-white p-6 shadow-xl">
                <h3 id="tingkat-modal-title" class="font-serif text-lg font-bold text-ink">
                    Konfirmasi Perubahan Tingkat Peradilan
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-ink-body">
                    Anda akan mengubah tingkat peradilan dari <strong>{{ strtoupper($tingkat) }}</strong> ke <strong>{{ strtoupper($pendingTingkat) }}</strong>. Operasi ini bersifat non-destruktif.
                </p>

                <form wire:submit="confirmChangeTingkat" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label for="pending_tingkat">Target tingkat baru</flux:label>
                        <x-admin.select id="pending_tingkat" wire:model="pendingTingkat">
                            <option value="pertama">Tingkat Pertama (PERTAMA)</option>
                            <option value="banding">Tingkat Banding (BANDING)</option>
                        </x-admin.select>
                    </flux:field>

                    <flux:field>
                        <flux:label for="confirm_phrase">Ketik "{{ strtoupper($pendingTingkat) }}" untuk konfirmasi</flux:label>
                        <flux:input id="confirm_phrase" wire:model="confirmationPhrase" required autocomplete="off" class="font-mono" />
                        <flux:error name="confirmationPhrase" />
                    </flux:field>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeTingkatModal" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink hover:bg-mist sm:min-h-9">
                            Batal (Esc)
                        </button>
                        <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-9">
                            Konfirmasi &amp; Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
