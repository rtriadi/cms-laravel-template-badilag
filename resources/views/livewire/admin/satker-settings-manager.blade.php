<div class="space-y-6" x-data="{ activeSection: 'identitas' }">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-[#edf7f3] px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <!-- Calm Page Header -->
    <div>
        <h2 class="font-serif text-2xl font-bold tracking-tight text-[#0f172a]">Pengaturan Satuan Kerja</h2>
        <p class="mt-0.5 text-xs text-[#64748b] sm:text-[13px]">
            Logo yang diunggah di sini otomatis menggantikan ikon brand di sidebar CMS, favicon tab browser, dan header portal publik.
        </p>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Left Vertical Sub-Navigation (Stripe/Linear Settings Pattern) -->
        <nav aria-label="Bagian Pengaturan Satker" class="space-y-1 lg:sticky lg:top-20 lg:col-span-3">
            @foreach ([
                ['id' => 'identitas', 'href' => '#sec-identitas', 'label' => 'Identitas & Logo'],
                ['id' => 'alamat', 'href' => '#sec-alamat', 'label' => 'Alamat & Kontak'],
                ['id' => 'integrasi', 'href' => '#sec-integrasi', 'label' => 'Integrasi SIPP & SIMTEPA'],
                ['id' => 'pengawasan', 'href' => '#sec-pengawasan', 'label' => 'Pengawasan & Beranda'],
                ['id' => 'tautan-medsos', 'href' => '#sec-tautan-medsos', 'label' => 'Tautan, Medsos & CCTV'],
                ['id' => 'tingkat', 'href' => '#sec-tingkat', 'label' => 'Tingkat Peradilan'],
            ] as $navItem)
                <a
                    href="{{ $navItem['href'] }}"
                    x-on:click="activeSection = '{{ $navItem['id'] }}'"
                    x-bind:class="activeSection === '{{ $navItem['id'] }}'
                        ? 'border-[#c7e6da] bg-[#edf7f3] font-bold text-primary'
                        : 'border-transparent text-[#334155] font-medium hover:bg-white hover:text-[#0f172a]'"
                    class="flex h-9 items-center rounded-lg border px-3.5 text-[13px] transition-colors"
                >
                    {{ $navItem['label'] }}
                </a>
            @endforeach
        </nav>

        <!-- Right Clean Stripe-Style Form Cards -->
        <form wire:submit="save" class="space-y-6 lg:col-span-9">
            <!-- Card 1: Identitas & Logo Satuan Kerja -->
            <section id="sec-identitas" class="studio-card scroll-mt-20 space-y-5 p-5 sm:p-6">
                <div class="border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Identitas &amp; Logo Satuan Kerja</h3>
                    <p class="mt-0.5 text-xs text-[#64748b]">Informasi resmi yang tampil di seluruh halaman publik, metadata SEO, dan identitas panel admin.</p>
                </div>

                <!-- Dynamic Logo Satker Live Row -->
                <div class="grid gap-4 rounded-xl border border-[#e2e8e5] bg-[#f9fbfa] p-4 sm:grid-cols-2">
                    <div class="flex items-start gap-3.5">
                        <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border-[1.5px] border-gold bg-white p-1.5 shadow-2xs">
                            @if ($existingLogoPath)
                                <img src="{{ asset('storage/' . $existingLogoPath) }}" alt="Logo {{ $nama_satker }}" width="56" height="56" class="size-full object-contain" />
                            @else
                                <span class="font-serif text-sm font-bold text-primary">PA</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:label for="st_logo">Logo Satuan Kerja (Maks. 2 MB)</flux:label>
                            <input id="st_logo" type="file" wire:model="logo" accept="image/*" class="mt-1 block w-full cursor-pointer text-xs text-[#0f172a] file:mr-2.5 file:cursor-pointer file:rounded-md file:border file:border-[#cbd5e1] file:bg-white file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-primary" />
                            <div wire:loading wire:target="logo" class="mt-1 text-[11px] font-semibold text-primary">Mengunggah logo...</div>
                            <flux:error name="logo" />
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <div class="flex h-14 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[#e2e8e5] bg-white shadow-2xs">
                            @if ($existingFotoGedungPath)
                                <img src="{{ asset('storage/' . $existingFotoGedungPath) }}" alt="Gedung {{ $nama_satker }}" width="80" height="56" class="size-full object-cover" />
                            @else
                                <span class="font-mono text-[10px] font-bold text-[#64748b]">GEDUNG</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:label for="st_foto">Foto Gedung Kantor (Maks. 4 MB)</flux:label>
                            <input id="st_foto" type="file" wire:model="foto_gedung" accept="image/*" class="mt-1 block w-full cursor-pointer text-xs text-[#0f172a] file:mr-2.5 file:cursor-pointer file:rounded-md file:border file:border-[#cbd5e1] file:bg-white file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-primary" />
                            <div wire:loading wire:target="foto_gedung" class="mt-1 text-[11px] font-semibold text-primary">Mengunggah foto gedung...</div>
                            <flux:error name="foto_gedung" />
                        </div>
                    </div>
                </div>

                <flux:field>
                    <flux:label for="st_nama">Nama Satuan Kerja *</flux:label>
                    <flux:input id="st_nama" wire:model="nama_satker" required placeholder="Contoh: Pengadilan Agama Gorontalo" />
                    <flux:error name="nama_satker" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-3">
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
            </section>

            <!-- Card 2: Alamat & Kontak Resmi -->
            <section id="sec-alamat" class="studio-card scroll-mt-20 space-y-4 p-5 sm:p-6">
                <div class="border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Alamat &amp; Kontak Resmi</h3>
                </div>

                <flux:field>
                    <flux:label for="st_jalan">Alamat Jalan</flux:label>
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
                        <flux:label for="st_pos">Kode Pos</flux:label>
                        <flux:input id="st_pos" wire:model="kode_pos" />
                    </flux:field>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="st_telp">Nomor Telepon</flux:label>
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
            </section>

            <!-- Card 3: Integrasi SIPP, SIMTEPA & Aplikasi Eksternal -->
            <section id="sec-integrasi" class="studio-card scroll-mt-20 space-y-5 p-5 sm:p-6">
                <div class="border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Integrasi SIPP, SIMTEPA &amp; Aplikasi Eksternal</h3>
                    <p class="mt-0.5 text-xs text-[#64748b]">Cukup tempelkan tautan dasar SIPP atau token 32 karakter SIMTEPA untuk pemetaan otomatis.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="ext_sipp">URL SIPP Satker</flux:label>
                        <flux:input id="ext_sipp" type="url" wire:model="sipp_url" placeholder="https://sipp.pa-...." />
                        <p class="mt-1 text-[11px] text-[#64748b]">Otomatis membentuk endpoint Daftar Perkara, Jadwal Sidang, &amp; Statistik.</p>
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_ecourt">URL e-Court MARI</flux:label>
                        <flux:input id="ext_ecourt" type="url" wire:model="ecourt_url" placeholder="https://ecourt.mahkamahagung.go.id" />
                        <p class="mt-1 text-[11px] text-[#64748b]">Tautan resmi layanan pendaftaran perkara daring.</p>
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
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <flux:label for="ext_sim_peg" class="mb-0!">Token / URL SIMTEPA Pegawai</flux:label>
                            <span class="font-mono text-[10.5px] text-[#64748b]">7 Tab Auto</span>
                        </div>
                        <flux:input id="ext_sim_peg" wire:model="simtepa_pegawai_url" placeholder="https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/..." />
                    </flux:field>
                    <flux:field>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <flux:label for="ext_sim_str" class="mb-0!">Token / URL SIMTEPA Struktur</flux:label>
                            <span class="font-mono text-[10.5px] text-[#64748b]">Bagan / Link Struktur</span>
                        </div>
                        <flux:input id="ext_sim_str" wire:model="simtepa_struktur_url" placeholder="https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/..." />
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

                @if ($satker)
                    <div class="space-y-3 rounded-xl border border-[#c7e6da] bg-[#edf7f3]/70 p-4 text-xs">
                        <div>
                            <p class="font-bold text-primary-deep">Pemetaan Otomatis Endpoint SIPP Aktif:</p>
                            @if ($satker->sipp_url)
                                <ul class="mt-1 space-y-1 font-mono text-[11px] text-[#334155]">
                                    <li><strong>Daftar Perkara:</strong> {{ $satker->resolvedSippEmbedUrl('sipp_perkara') }}</li>
                                    <li><strong>Jadwal Sidang:</strong> {{ $satker->resolvedSippEmbedUrl('sipp_jadwal') }}</li>
                                    <li><strong>Statistik Perkara:</strong> {{ $satker->resolvedSippEmbedUrl('sipp_statistik') }}</li>
                                </ul>
                            @else
                                <p class="mt-1 text-[11px] text-[#64748b]">URL SIPP belum diisi.</p>
                            @endif
                        </div>

                        @php
                            $simtepaTabs = $satker->simtepaCategoryTabs(false);
                            $simtepaStrukturResolved = $satker->resolveEmbedSource('simtepa_struktur');
                        @endphp
                        <div class="border-t border-[#c7e6da] pt-2.5">
                            <p class="font-bold text-primary-deep">URL Bagan Struktur Organisasi SIMTEPA Aktif:</p>
                            @if (! empty($simtepaStrukturResolved['embed_url']) && empty($simtepaStrukturResolved['tabs']))
                                <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 rounded-md border border-[#e2e8e5] bg-white px-2.5 py-1.5 text-[11px]">
                                    <span class="break-all font-mono text-[#0f172a]">{{ $simtepaStrukturResolved['embed_url'] }}</span>
                                    <a href="{{ $simtepaStrukturResolved['embed_url'] }}" target="_blank" rel="noopener noreferrer" class="shrink-0 font-mono font-semibold text-primary hover:underline">Buka &nearr;</a>
                                </div>
                            @elseif (! empty($simtepaStrukturResolved['tabs']))
                                <p class="mt-1 text-[11px] text-[#334155]">Menggunakan mode 6 Tab Pejabat SIMTEPA (isi URL <code>/share/struktur_organisasi/...</code> di atas untuk menanamkan bagan langsung).</p>
                            @else
                                <p class="mt-1 text-[11px] text-[#64748b]">URL struktur organisasi belum diisi (halaman Struktur Organisasi akan memakai data cadangan lokal).</p>
                            @endif
                        </div>

                        <div class="border-t border-[#c7e6da] pt-2.5">
                            <p class="font-bold text-primary-deep">
                                Pemetaan Otomatis 7 Kategori Share Data SIMTEPA
                                @if ($satker->extractSimtepaToken())
                                    (Token: <code class="font-mono">{{ $satker->extractSimtepaToken() }}</code>)
                                @endif
                            </p>
                            @if ($simtepaTabs !== [])
                                <ul class="mt-1.5 grid gap-1.5 sm:grid-cols-2">
                                    @foreach ($simtepaTabs as $sTab)
                                        <li class="flex items-center justify-between gap-2 rounded-md border border-[#e2e8e5] bg-white px-2.5 py-1.5 text-[11px]">
                                            <span class="truncate font-semibold text-[#0f172a]">{{ $loop->iteration }}. {{ $sTab['label'] }}</span>
                                            <span class="flex shrink-0 items-center gap-1.5 font-mono">
                                                <a href="{{ $sTab['html_url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary hover:underline">HTML</a>
                                                <span class="text-[#cbd5e1]">|</span>
                                                <a href="{{ $sTab['json_url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#64748b] hover:underline">JSON</a>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-1 text-[11px] text-[#64748b]">Tempelkan salah satu URL Share SIMTEPA di atas lalu klik Simpan untuk menghasilkan 7 kategori otomatis.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            <!-- Card 3B: Kelengkapan Halaman Depan (Pedoman Pengawasan A-6) -->
            <section id="sec-pengawasan" class="studio-card scroll-mt-20 space-y-5 p-5 sm:p-6">
                <div class="border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Pengawasan &amp; Kelengkapan Halaman Depan</h3>
                    <p class="mt-0.5 text-xs text-[#64748b]">
                        Tautan Pengadilan Tingkat Banding, angka Indeks Survei Pelayanan (SKM, SPAK, SPKP), serta tautan ringan Video Profil, Video PTSP, dan Infografis Kebijakan MA sesuai Pedoman Pengawasan.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="ext_pta_nama">Nama Pengadilan Tingkat Banding (PTA / MS Aceh)</flux:label>
                        <flux:input id="ext_pta_nama" wire:model="pta_nama" placeholder="Pengadilan Tinggi Agama Gorontalo" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="ext_pta_url">URL Website Pengadilan Tingkat Banding</flux:label>
                        <flux:input id="ext_pta_url" type="url" wire:model="pta_url" placeholder="https://pta-gorontalo.go.id" />
                    </flux:field>
                </div>

                <div class="border-t border-[#e2e8e5] pt-4">
                    <p class="mb-3 text-xs font-bold text-[#0f172a]">Indeks Survei Pelayanan di Beranda (SKM / SPAK / SPKP)</p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:field>
                            <flux:label for="idx_periode">Periode Survei</flux:label>
                            <flux:input id="idx_periode" wire:model="survei_periode" placeholder="Triwulan III 2026" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="idx_skm">Nilai IKM / SKM</flux:label>
                            <flux:input id="idx_skm" wire:model="nilai_skm" placeholder="95,40" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="idx_spak">Nilai IPAK / SPAK</flux:label>
                            <flux:input id="idx_spak" wire:model="nilai_spak" placeholder="96,10" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="idx_spkp">Nilai IPKL / SPKP</flux:label>
                            <flux:input id="idx_spkp" wire:model="nilai_spkp" placeholder="95,80" />
                        </flux:field>
                    </div>
                </div>

                <div class="border-t border-[#e2e8e5] pt-4">
                    <p class="mb-3 text-xs font-bold text-[#0f172a]">Tautan Video &amp; Infografis Kebijakan MA (Tanpa Embed Berat)</p>
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

            <!-- Card 3C: Tautan Instansi, Sosial Media Resmi & Access CCTV Online (ACO) -->
            <section id="sec-tautan-medsos" class="studio-card scroll-mt-20 space-y-5 p-5 sm:p-6">
                <div class="border-b border-[#e2e8e5] pb-3.5">
                    <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Tautan Instansi, Sosial Media &amp; CCTV (ACO)</h3>
                    <p class="mt-0.5 text-xs text-[#64748b]">
                        Kelola tautan website instansi terkait, kanal media sosial resmi satuan kerja, serta tautan Access CCTV Online (ACO) yang tampil di beranda dan footer.
                    </p>
                </div>

                <!-- Sub-block 1: Access CCTV Online (ACO) -->
                <div class="rounded-xl border border-[#c7e6da] bg-[#edf7f3]/60 p-4 space-y-3">
                    <div>
                        <p class="text-xs font-bold text-primary-deep">Access CCTV Online (ACO) Badilag</p>
                        <p class="mt-0.5 text-[11px] text-[#475569]">
                            Tampil sebagai kartu tautan ringan menuju portal pantauan CCTV daring (tanpa live streaming berat di beranda agar waktu muat tetap di bawah 5 detik sesuai SK Dirjen Badilag Bab III.A.5).
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="ext_cctv_url">URL Access CCTV Online (ACO)</flux:label>
                            <flux:input id="ext_cctv_url" type="url" wire:model="cctv_aco_url" placeholder="https://cctv.badilag.net/display/satker/..." />
                            <flux:error name="cctv_aco_url" />
                        </flux:field>
                        <flux:field>
                            <flux:label for="ext_cctv_label">Label Tampilan CCTV</flux:label>
                            <flux:input id="ext_cctv_label" wire:model="cctv_aco_label" placeholder="Access CCTV Online (ACO)" />
                            <flux:error name="cctv_aco_label" />
                        </flux:field>
                    </div>
                </div>

                <!-- Sub-block 2: Kanal Sosial Media Resmi -->
                <div class="border-t border-[#e2e8e5] pt-4">
                    <p class="mb-3 text-xs font-bold text-[#0f172a]">Kanal Sosial Media Resmi Satuan Kerja</p>
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
                            <flux:label for="soc_x">URL X / Twitter (Opsional)</flux:label>
                            <flux:input id="soc_x" type="url" wire:model="social_x_url" placeholder="https://x.com/..." />
                            <flux:error name="social_x_url" />
                        </flux:field>
                    </div>
                </div>

                <!-- Sub-block 3: Tautan Instansi Terkait (Dinamis) -->
                <div class="border-t border-[#e2e8e5] pt-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-[#0f172a]">Daftar Tautan Instansi Terkait</p>
                            <p class="mt-0.5 text-[11px] text-[#64748b]">Misalnya Mahkamah Agung RI, Ditjen Badilag, PTA/MS Aceh, PA sewilayah, dan Pemerintah Daerah.</p>
                        </div>
                        <button
                            type="button"
                            wire:click="addRelatedLink"
                            class="inline-flex min-h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-primary bg-[#edf7f3] px-3 text-xs font-bold text-primary hover:bg-primary hover:text-white"
                        >
                            + Tambah Tautan Instansi
                        </button>
                    </div>

                    @if ($related_links === [])
                        <div class="rounded-lg border border-dashed border-[#cbd5e1] bg-[#f8faf9] px-4 py-5 text-center text-xs text-[#64748b]">
                            Belum ada tautan instansi terkait. Klik tombol <strong>+ Tambah Tautan Instansi</strong> untuk menambahkan.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($related_links as $rIdx => $rItem)
                                <div wire:key="rel-link-{{ $rIdx }}" class="rounded-xl border border-[#e2e8e5] bg-[#f9fbfa] p-3.5 space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#e2e8e5] pb-2">
                                        <span class="font-mono text-[11px] font-bold text-primary">Tautan #{{ $rIdx + 1 }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                wire:click="moveRelatedLinkUp({{ $rIdx }})"
                                                @disabled($rIdx === 0)
                                                aria-label="Geser tautan #{{ $rIdx + 1 }} ke atas"
                                                class="inline-flex min-h-8 min-w-8 cursor-pointer items-center justify-center rounded border border-[#cbd5e1] bg-white px-2 text-xs font-bold text-[#334155] hover:bg-[#edf7f3] disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                &uarr;
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="moveRelatedLinkDown({{ $rIdx }})"
                                                @disabled($rIdx === count($related_links) - 1)
                                                aria-label="Geser tautan #{{ $rIdx + 1 }} ke bawah"
                                                class="inline-flex min-h-8 min-w-8 cursor-pointer items-center justify-center rounded border border-[#cbd5e1] bg-white px-2 text-xs font-bold text-[#334155] hover:bg-[#edf7f3] disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                &darr;
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeRelatedLink({{ $rIdx }})"
                                                class="inline-flex min-h-8 cursor-pointer items-center rounded border border-red-200 bg-red-50 px-2.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-12">
                                        <div class="sm:col-span-3">
                                            <flux:field>
                                                <flux:label for="rl_code_{{ $rIdx }}">Kode Singkat</flux:label>
                                                <flux:input id="rl_code_{{ $rIdx }}" wire:model="related_links.{{ $rIdx }}.code" placeholder="MARI / PTA" />
                                            </flux:field>
                                        </div>
                                        <div class="sm:col-span-9">
                                            <flux:field>
                                                <flux:label for="rl_title_{{ $rIdx }}">Nama Instansi</flux:label>
                                                <flux:input id="rl_title_{{ $rIdx }}" wire:model="related_links.{{ $rIdx }}.title" placeholder="Mahkamah Agung RI" />
                                            </flux:field>
                                        </div>
                                        <div class="sm:col-span-6">
                                            <flux:field>
                                                <flux:label for="rl_sub_{{ $rIdx }}">Keterangan Singkat</flux:label>
                                                <flux:input id="rl_sub_{{ $rIdx }}" wire:model="related_links.{{ $rIdx }}.subtitle" placeholder="Website Mahkamah Agung Republik Indonesia" />
                                            </flux:field>
                                        </div>
                                        <div class="sm:col-span-6">
                                            <flux:field>
                                                <flux:label for="rl_url_{{ $rIdx }}">URL Website</flux:label>
                                                <flux:input id="rl_url_{{ $rIdx }}" type="url" wire:model="related_links.{{ $rIdx }}.url" placeholder="https://www.mahkamahagung.go.id" />
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

            <!-- Card 4: Tingkat Peradilan -->
            <section id="sec-tingkat" class="studio-card scroll-mt-20 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-serif text-[17px] font-bold text-[#0f172a]">Tingkat Peradilan Aktif</h3>
                        <p class="mt-0.5 text-xs text-[#64748b]">
                            Status terkunci setelah setup awal:
                            <strong class="font-mono text-primary">{{ $tingkatOptions[$tingkat] ?? $tingkat }} ({{ strtoupper($tingkat) }})</strong>
                        </p>
                    </div>
                    @if ($isSuperAdmin)
                        <button type="button" wire:click="openTingkatModal" class="h-9 cursor-pointer rounded-lg border border-amber-400 bg-gold px-3.5 text-xs font-bold text-[#0f172a] hover:opacity-90">
                            Ubah Tingkat Peradilan
                        </button>
                    @endif
                </div>
                <p class="mt-2 text-xs text-[#64748b]">
                    Perubahan tingkat peradilan bersifat non-destruktif: halaman yang sudah diisi tetap tersimpan dan struktur menu disesuaikan otomatis oleh Super Admin.
                </p>
            </section>

            <!-- Sticky Bottom Save Bar -->
            <div class="studio-card sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 shadow-md">
                <p class="text-xs text-[#64748b]">
                    Semua perubahan identitas &amp; logo akan langsung memperbarui tampilan CMS dan website publik.
                </p>
                <div class="flex items-center gap-2">
                    <flux:button type="submit" variant="primary" class="h-9 cursor-pointer bg-primary px-5 text-xs font-semibold text-white hover:bg-primary-hover">
                        Simpan Pengaturan
                    </flux:button>
                </div>
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
            <div class="w-full max-w-md rounded-xl border border-[#e2e8e5] bg-white p-6 shadow-xl">
                <h3 id="tingkat-modal-title" class="font-serif text-lg font-bold text-[#0f172a]">
                    Konfirmasi Perubahan Tingkat Peradilan
                </h3>
                <p class="mt-2 text-xs leading-relaxed text-[#64748b]">
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
                        <button type="button" wire:click="closeTingkatModal" class="h-9 cursor-pointer rounded-lg border border-[#e2e8e5] bg-white px-4 text-xs font-semibold text-[#334155] hover:bg-[#f5f7f6]">
                            Batal
                        </button>
                        <button type="submit" class="h-9 cursor-pointer rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover">
                            Konfirmasi &amp; Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
