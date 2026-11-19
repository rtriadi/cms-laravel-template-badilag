<?php

namespace Database\Seeders;

use App\Models\CriteriaItem;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SatkerSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatkerDefaultContentSeeder extends Seeder
{
    public function run(?string $tingkat = null): void
    {
        $setting = SatkerSetting::current();
        $activeTingkat = $tingkat ?? $setting?->tingkat ?? 'pertama';

        DB::transaction(function () use ($activeTingkat, $setting): void {
            $this->seedSliders();
            $pagesBySlug = $this->seedPages($activeTingkat, $setting);
            $this->linkCriteriaPages($pagesBySlug);
            $this->seedMenus($activeTingkat, $pagesBySlug);
        });
    }

    private function seedSliders(): void
    {
        if (HomeSlider::query()->exists()) {
            return;
        }

        $slides = [
            [
                'title' => 'Portal Resmi {{satker.nama}}',
                'subtitle' => 'Pelayanan informasi peradilan yang terbuka, cepat, dan mudah diakses oleh seluruh pencari keadilan.',
                'image_alt' => 'Banner utama portal layanan {{satker.nama}}',
                'cta_label' => 'Cek Jadwal Sidang',
                'cta_url' => '/halaman/layanan-hukum/jadwal-sidang',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Keterbukaan Informasi & SAKIP',
                'subtitle' => 'Akses dokumen perencanaan kinerja, laporan keuangan, dan transparansi anggaran tahun berjalan.',
                'image_alt' => 'Banner transparansi informasi publik {{satker.nama}}',
                'cta_label' => 'Buka Transparansi',
                'cta_url' => '/halaman/transparansi/lkjip',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Layanan Ramah Kelompok Rentan',
                'subtitle' => 'Tersedia pembaca layar suara Bahasa Indonesia, mode kontras tinggi, dan perbesar teks hingga 200 persen.',
                'image_alt' => 'Banner layanan aksesibilitas disabilitas {{satker.nama}}',
                'cta_label' => 'Panduan Aksesibilitas',
                'cta_url' => '/aksesibilitas',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($slides as $slide) {
            HomeSlider::query()->create($slide);
        }
    }

    /**
     * @return array<string, Page>
     */
    private function seedPages(string $activeTingkat, ?SatkerSetting $setting): array
    {
        $isBanding = $activeTingkat === 'banding';
        $sippUrl = $isBanding
            ? 'https://banding.mahkamahagung.go.id/'
            : ($setting?->sipp_url ?: 'https://sipp.mahkamahagung.go.id');

        $pageDefinitions = [
            // Profil Pengadilan
            [
                'slug' => 'profil/visi-misi',
                'title' => 'Visi dan Misi {{satker.nama}}',
                'criteria_code' => '1.b.p.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'richtext', 'heading' => 'Visi', 'content' => '<p>Terwujudnya {{satker.nama}} yang agung, profesional, dan akuntabel.</p>'],
                    ['type' => 'richtext', 'heading' => 'Misi', 'content' => '<ul><li>Menjaga kemandirian peradilan.</li><li>Memberikan pelayanan hukum yang berkeadilan kepada pencari keadilan.</li><li>Meningkatkan kredibilitas dan transparansi badan peradilan.</li></ul>'],
                ],
            ],
            [
                'slug' => 'profil/tupoksi',
                'title' => 'Tugas Pokok dan Fungsi',
                'criteria_code' => '1.b.p.2',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => 'Tugas Pokok dan Fungsi {{satker.nama}}',
                        'content' => $isBanding
                            ? '<p>{{satker.nama}} bertugas dan berwenang mengadili perkara yang menjadi kewenangan Peradilan Agama pada tingkat banding serta mengawasi jalannya peradilan pada tingkat pertama di wilayah hukumnya.</p>'
                            : '<p>{{satker.nama}} bertugas dan berwenang memeriksa, memutus, dan menyelesaikan perkara pada tingkat pertama di bidang perkawinan, waris, wasiat, hibah, wakaf, zakat, infaq, shadaqah, dan ekonomi syariah.</p>',
                    ],
                ],
            ],
            [
                'slug' => 'profil/wilayah-yurisdiksi',
                'title' => 'Wilayah Yurisdiksi {{satker.nama}}',
                'criteria_code' => '1.b.p.3',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => $isBanding ? 'Daftar Kabupaten dan Kota Wilayah Hukum' : 'Daftar Kecamatan Wilayah Yurisdiksi',
                        'content' => $isBanding
                            ? '<p>Silakan lengkapi daftar seluruh Kabupaten dan Kota pada wilayah yurisdiksi {{satker.nama}} melalui Page Builder.</p>'
                            : '<p>Silakan lengkapi daftar seluruh Kecamatan pada wilayah yurisdiksi {{satker.nama}} melalui Page Builder.</p>',
                    ],
                    ['type' => 'map_yurisdiksi', 'heading' => 'Peta Wilayah Yurisdiksi', 'latitude' => '-6.9175', 'longitude' => '107.6191', 'zoom' => 11],
                ],
            ],
            [
                'slug' => 'profil/struktur-organisasi',
                'title' => 'Struktur Organisasi',
                'criteria_code' => '1.b.p.4',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'embed_fallback',
                        'source_key' => 'simtepa_struktur',
                        'fallback_mode' => 'org_chart',
                        'heading' => 'Struktur Organisasi {{satker.nama}}',
                        'note' => 'Susunan pimpinan dan pejabat struktural serta fungsional satuan kerja.',
                    ],
                ],
            ],
            [
                'slug' => 'profil/alamat-kontak',
                'title' => 'Alamat dan Kontak Pengadilan',
                'criteria_code' => '1.b.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'satker_contact', 'heading' => 'Identitas, Alamat Lengkap, dan Foto Gedung {{satker.nama}}'],
                ],
            ],
            [
                'slug' => 'profil/pejabat-pegawai',
                'title' => 'Profil Pejabat dan Pegawai',
                'criteria_code' => '1.b.3.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'embed_fallback',
                        'source_key' => 'simtepa_pegawai',
                        'fallback_mode' => 'officials_table',
                        'heading' => 'Daftar Profil Pejabat, Pegawai, dan Tenaga Honorer',
                        'note' => 'Daftar profil pimpinan, hakim, pejabat, dan aparatur satuan kerja.',
                    ],
                ],
            ],
            // Kepaniteraan
            [
                'slug' => 'layanan-hukum/sipp',
                'title' => 'Sistem Informasi Penelusuran Perkara (SIPP)',
                'criteria_code' => '1.a.2.1',
                'category_tag' => 'setiap_saat',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => $isBanding ? 'SIPP Tingkat Banding Mahkamah Agung RI' : 'SIPP {{satker.nama}}',
                        'url_source' => 'sipp_url',
                        'default_url' => $sippUrl,
                        'description' => 'Gunakan Sistem Informasi Penelusuran Perkara untuk memantau tahapan dan informasi perkara secara transparan.',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/jadwal-sidang',
                'title' => 'Jadwal Persidangan Harian',
                'criteria_code' => '1.a.2.2',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'embed_fallback',
                        'source_key' => 'sipp_jadwal',
                        'fallback_mode' => 'court_schedule',
                        'heading' => 'Agenda Persidangan Hari Ini',
                        'note' => 'Menampilkan jadwal sidang dari SIPP atau daftar jadwal cadangan lokal.',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/statistik-perkara',
                'title' => 'Statistik Perkara Bulanan',
                'criteria_code' => '1.a.2.3',
                'category_tag' => 'setiap_saat',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'embed_fallback',
                        'source_key' => 'sipp_statistik',
                        'fallback_mode' => 'case_statistics',
                        'heading' => 'Rekapitulasi Statistik Perkara Per Bulan',
                        'note' => 'Meliputi sisa perkara bulan lalu, perkara masuk, perkara putus, dan sisa perkara saat ini.',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/panggilan-ghaib',
                'title' => 'Panggilan Ghaib',
                'criteria_code' => '1.a.2.4',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $isBanding
                    ? [
                        ['type' => 'richtext', 'heading' => 'Penjelasan Panggilan Ghaib pada Pengadilan Tingkat Pertama', 'content' => '<p>Panggilan ghaib dilaksanakan pada Pengadilan Agama tingkat pertama terhadap pihak yang tidak diketahui alamat tempat tinggalnya di wilayah hukum Republik Indonesia sesuai ketentuan hukum acara yang berlaku.</p>'],
                    ]
                    : [
                        ['type' => 'document_list', 'collection_type' => 'panggilan_ghaib', 'heading' => 'Daftar Panggilan Ghaib dan Dokumen Relaas'],
                    ],
            ],
            [
                'slug' => 'layanan-hukum/laporan-biaya-perkara',
                'title' => 'Laporan Penggunaan Biaya Perkara',
                'criteria_code' => '1.a.2.5',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'document_list', 'collection_type' => 'biaya_perkara', 'heading' => 'Rincian Penggunaan Biaya Perkara Per Bulan'],
                ],
            ],
            [
                'slug' => 'layanan-hukum/sisa-panjar',
                'title' => 'Laporan Pengembalian Sisa Panjar Perkara',
                'criteria_code' => '1.a.2.6',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'pertama',
                'blocks' => [
                    ['type' => 'document_list', 'collection_type' => 'sisa_panjar', 'heading' => 'Rincian Pengembalian Sisa Panjar Perkara Per Bulan'],
                ],
            ],
            [
                'slug' => 'layanan-hukum/ecourt',
                'title' => 'Layanan e-Court Mahkamah Agung RI',
                'criteria_code' => '1.b.2.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Tentang e-Court Mahkamah Agung RI',
                        'url_source' => 'ecourt_url',
                        'default_url' => 'https://ecourt.mahkamahagung.go.id',
                        'description' => 'e-Court adalah layanan pendaftaran perkara (e-Filing), taksiran panjar dan pembayaran (e-Payment), pemanggilan (e-Summons), serta persidangan secara elektronik (e-Litigation).',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/direktori-putusan',
                'title' => 'Direktori Putusan Satuan Kerja',
                'criteria_code' => '1.b.2.2',
                'category_tag' => 'setiap_saat',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Direktori Putusan Mahkamah Agung RI ({{satker.nama}})',
                        'url_source' => 'direktori_putusan_url',
                        'default_url' => 'https://putusan3.mahkamahagung.go.id',
                        'description' => 'Akses langsung arsip putusan resmi {{satker.nama}} pada portal Direktori Putusan Mahkamah Agung RI.',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/prosedur-berperkara',
                'title' => 'Prosedur Berperkara',
                'criteria_code' => '1.b.2.3',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => 'Alur Layanan Berperkara di {{satker.nama}}',
                        'content' => '<h3>1. Tingkat Pertama</h3><p>Pendaftaran gugatan atau permohonan melalui meja PTSP atau e-Court.</p><h3>2. Tingkat Banding</h3><p>Permohonan banding diajukan paling lambat 14 hari kalender sejak putusan diucapkan atau diberitahukan.</p><h3>3. Kasasi</h3><p>Permohonan kasasi dan memori kasasi diajukan melalui kepaniteraan pengadilan tingkat pertama.</p><h3>4. Peninjauan Kembali (PK)</h3><p>Diajukan sesuai syarat dan tenggang waktu undang-undang melalui kepaniteraan pengadilan asal.</p><h3>5. Pengambilan Produk Pengadilan</h3><p>Akta cerai dan salinan putusan diambil di loket produk atau melalui layanan elektronik.</p><h3>6. Gugatan Sederhana</h3><p>Penyelesaian perkara wanprestasi atau perbuatan melawan hukum dengan nilai gugatan sesuai peraturan Mahkamah Agung.</p>',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/biaya-berperkara',
                'title' => 'Biaya Proses Berperkara',
                'criteria_code' => '1.b.2.4',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'richtext', 'heading' => 'Rincian Panjar Biaya Perkara dan Radius Panggilan', 'content' => '<p>Silakan perbarui rincian SK panjar biaya perkara serta radius panggilan resmi {{satker.nama}} di sini.</p>'],
                ],
            ],
            // Kesekretariatan & Informasi Umum
            ['slug' => 'transparansi/rencana-aksi-kinerja', 'title' => 'Rencana Aksi Kinerja (5 Tahun Terakhir)', 'criteria_code' => '1.a.1.1', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'renaksi', 'heading' => 'Daftar Dokumen Rencana Aksi Kinerja']]],
            ['slug' => 'transparansi/laporan-tahunan', 'title' => 'Laporan Pelaksanaan Kegiatan / Laporan Tahunan', 'criteria_code' => '1.a.1.2', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'laporan_tahunan', 'heading' => 'Laporan Tahunan & Pelaksanaan Kegiatan']]],
            ['slug' => 'transparansi/lhkpn', 'title' => 'Laporan Harta Kekayaan Penyelenggara Negara (LHKPN)', 'criteria_code' => '1.a.3.1', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'lhkpn', 'heading' => 'Daftar Pejabat & Ikhtisar Kekayaan LHKPN (Min. 2 Tahun Terakhir)']]],
            ['slug' => 'transparansi/dipa', 'title' => 'Daftar Isian Pelaksanaan Anggaran (DIPA)', 'criteria_code' => '1.a.3.2', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'dipa', 'heading' => 'DIPA, Rencana Penggunaan Anggaran (RPD), dan Realisasi DIPA']]],
            ['slug' => 'transparansi/lra', 'title' => 'Laporan Realisasi Anggaran (LRA)', 'criteria_code' => '1.a.3.3', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'lra', 'heading' => 'Dokumen Laporan Realisasi Anggaran']]],
            ['slug' => 'transparansi/calk', 'title' => 'Catatan Atas Laporan Keuangan (CALK)', 'criteria_code' => '1.a.3.4', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'calk', 'heading' => 'Dokumen Catatan Atas Laporan Keuangan']]],
            ['slug' => 'transparansi/pengadaan-barang-jasa', 'title' => 'Rencana Umum Pengadaan Barang dan Jasa (PBJ)', 'criteria_code' => '1.a.3.5', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'pbj', 'heading' => 'Rencana Umum Pengadaan Barang dan Jasa']]],
            ['slug' => 'transparansi/renstra', 'title' => 'Rencana Strategis (Renstra)', 'criteria_code' => '1.a.3.6', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'renstra', 'heading' => 'Rancangan, Dokumen Utama, dan Reviu Renstra']]],
            ['slug' => 'transparansi/rkt', 'title' => 'Rencana Kinerja Tahunan (RKT)', 'criteria_code' => '1.a.3.7', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'rkt', 'heading' => 'Dokumen Rencana Kinerja Tahunan']]],
            ['slug' => 'transparansi/renaksi-berjalan', 'title' => 'Rencana Aksi Kinerja Tahun Berjalan', 'criteria_code' => '1.a.3.8', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'renaksi', 'heading' => 'Dokumen Rencana Aksi Kinerja Tahun Saat Ini']]],
            ['slug' => 'transparansi/perjanjian-kinerja', 'title' => 'Perjanjian Kinerja (PK)', 'criteria_code' => '1.a.3.9', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'pk', 'heading' => 'Dokumen Perjanjian Kinerja']]],
            ['slug' => 'transparansi/iku', 'title' => 'Indikator Kinerja Utama (IKU) dan Reviu IKU', 'criteria_code' => '1.a.3.10', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'iku', 'heading' => 'Dokumen IKU dan Reviu IKU']]],
            ['slug' => 'transparansi/lkjip', 'title' => 'Laporan Kinerja Instansi Pemerintah (LKjIP)', 'criteria_code' => '1.a.3.11', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'lkjip', 'heading' => 'Dokumen LKjIP']]],
            // Layanan Publik & Pengaduan
            ['slug' => 'layanan-publik/laporan-akses-informasi', 'title' => 'Laporan Akses Informasi Publik (PPID)', 'criteria_code' => '1.a.4.1.1', 'category_tag' => 'setiap_saat', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'akses_informasi', 'heading' => 'Laporan Tahunan dan Berkala Layanan PPID']]],
            ['slug' => 'layanan-publik/rekapitulasi-pengaduan', 'title' => 'Laporan Rekapitulasi Pengaduan Masyarakat', 'criteria_code' => '1.a.4.2.1', 'category_tag' => 'setiap_saat', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'rekap_pengaduan', 'heading' => 'Rekapitulasi Pengaduan dan Tindak Lanjut (Inisial Pelapor)']]],
            ['slug' => 'layanan-publik/hukuman-disiplin', 'title' => 'Hukuman Disiplin Hasil Pengawasan', 'criteria_code' => '1.a.4.3.1', 'category_tag' => 'setiap_saat', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'hukuman_disiplin', 'heading' => 'Daftar Hukuman Disiplin Hasil Pengawasan']]],
            [
                'slug' => 'layanan-publik/survei-pelayanan',
                'title' => 'Survei Pelayanan Masyarakat (SKM, SPAK, SPKP)',
                'criteria_code' => '1.a.4.4.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'document_list', 'collection_type' => 'survei', 'heading' => 'Nilai dan Laporan SKM, SPAK, serta SPKP'],
                    ['type' => 'richtext', 'heading' => 'Statistik Pengunjung Website', 'content' => '<p>Rekapitulasi statistik kunjungan website {{satker.nama}} dapat dipantau langsung pada halaman <a href="/statistik-pengunjung">Statistik Pengunjung</a>.</p>'],
                ],
            ],
            [
                'slug' => 'layanan-publik/standar-maklumat-pelayanan',
                'title' => 'Standar dan Maklumat Pelayanan Pengadilan',
                'criteria_code' => '1.b.4.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'richtext', 'heading' => 'Maklumat Pelayanan {{satker.nama}}', 'content' => '<p>Dengan ini, kami pimpinan dan seluruh aparatur {{satker.nama}} menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan dan apabila tidak menepati janji, kami siap menerima sanksi sesuai peraturan perundang-undangan.</p>'],
                    ['type' => 'richtext', 'heading' => 'Layanan Kelompok Rentan & Disabilitas', 'content' => '<p>{{satker.nama}} menyediakan fasilitas kursi roda, jalur pemandu, toilet khusus disabilitas, layanan prioritas, serta dukungan pendampingan bagi pencari keadilan berkebutuhan khusus.</p>'],
                ],
            ],
            // Pendukung
            ['slug' => 'layanan-hukum/hak-pencari-keadilan', 'title' => 'Hak Pencari Keadilan (Prodeo & Posbakum)', 'criteria_code' => null, 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'richtext', 'heading' => 'Layanan Pembebasan Biaya Perkara (Prodeo) dan Posbakum', 'content' => '<p>Masyarakat tidak mampu berhak memperoleh layanan pembebasan biaya perkara (Prodeo) dan konsultasi hukum gratis di Pos Bantuan Hukum (Posbakum) {{satker.nama}}.</p>']]],
            ['slug' => 'layanan-publik/pengaduan-siwas', 'title' => 'Prosedur Pengaduan & SIWAS MARI', 'criteria_code' => null, 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'external_portal', 'heading' => 'Sistem Informasi Pengawasan Mahkamah Agung (SIWAS)', 'url_source' => 'siwas_url', 'default_url' => 'https://siwas.mahkamahagung.go.id', 'description' => 'Sampaikan pengaduan dugaan pelanggaran perilaku aparatur pengadilan secara rahasia melalui SIWAS MARI.']]],
        ];

        $pages = [];

        foreach ($pageDefinitions as $def) {
            $isApplicable = $def['tingkat_scope'] === 'all' || $def['tingkat_scope'] === $activeTingkat;

            $page = Page::query()->firstOrNew(['slug' => $def['slug']]);
            if (! $page->exists) {
                $page->title = $def['title'];
                $page->blocks = $def['blocks'];
                $page->category_tag = $def['category_tag'];
                $page->criteria_code = $def['criteria_code'];
                $page->tingkat_scope = $def['tingkat_scope'];
                $page->published_at = now();
            }

            $page->is_published = $isApplicable;
            $page->save();

            $pages[$def['slug']] = $page;
        }

        return $pages;
    }

    /**
     * @param  array<string, Page>  $pagesBySlug
     */
    private function linkCriteriaPages(array $pagesBySlug): void
    {
        foreach ($pagesBySlug as $page) {
            if (empty($page->criteria_code)) {
                continue;
            }

            $item = CriteriaItem::query()->where('code', $page->criteria_code)->first();
            if ($item === null) {
                continue;
            }

            if ($item->linked_page_id === null) {
                $item->linked_page_id = $page->id;
                $item->save();
            }
        }
    }

    /**
     * @param  array<string, Page>  $pagesBySlug
     */
    private function seedMenus(string $activeTingkat, array $pagesBySlug): void
    {
        $menuTree = [
            [
                'title' => 'Beranda',
                'type' => 'external',
                'url' => '/',
                'sort_order' => 1,
                'children' => [],
            ],
            [
                'title' => 'Profil Pengadilan',
                'type' => 'group',
                'sort_order' => 2,
                'children' => [
                    ['title' => 'Visi & Misi', 'slug' => 'profil/visi-misi', 'criteria_code' => '1.b.p.1'],
                    ['title' => 'Tugas Pokok & Fungsi', 'slug' => 'profil/tupoksi', 'criteria_code' => '1.b.p.2'],
                    ['title' => 'Wilayah Yurisdiksi', 'slug' => 'profil/wilayah-yurisdiksi', 'criteria_code' => '1.b.p.3'],
                    ['title' => 'Struktur Organisasi', 'slug' => 'profil/struktur-organisasi', 'criteria_code' => '1.b.p.4'],
                    ['title' => 'Profil Pejabat & Pegawai', 'slug' => 'profil/pejabat-pegawai', 'criteria_code' => '1.b.3.1'],
                    ['title' => 'Alamat & Kontak Pengadilan', 'slug' => 'profil/alamat-kontak', 'criteria_code' => '1.b.1'],
                ],
            ],
            [
                'title' => 'Layanan Hukum',
                'type' => 'group',
                'sort_order' => 3,
                'children' => [
                    ['title' => 'SIPP Penelusuran Perkara', 'slug' => 'layanan-hukum/sipp', 'criteria_code' => '1.a.2.1'],
                    ['title' => 'Jadwal Persidangan', 'slug' => 'layanan-hukum/jadwal-sidang', 'criteria_code' => '1.a.2.2'],
                    ['title' => 'Statistik Perkara', 'slug' => 'layanan-hukum/statistik-perkara', 'criteria_code' => '1.a.2.3'],
                    ['title' => 'e-Court Mahkamah Agung', 'slug' => 'layanan-hukum/ecourt', 'criteria_code' => '1.b.2.1'],
                    ['title' => 'Direktori Putusan', 'slug' => 'layanan-hukum/direktori-putusan', 'criteria_code' => '1.b.2.2'],
                    ['title' => 'Prosedur Berperkara', 'slug' => 'layanan-hukum/prosedur-berperkara', 'criteria_code' => '1.b.2.3'],
                    ['title' => 'Biaya Proses Berperkara', 'slug' => 'layanan-hukum/biaya-berperkara', 'criteria_code' => '1.b.2.4'],
                    ['title' => 'Panggilan Ghaib', 'slug' => 'layanan-hukum/panggilan-ghaib', 'criteria_code' => '1.a.2.4'],
                    ['title' => 'Laporan Biaya Perkara', 'slug' => 'layanan-hukum/laporan-biaya-perkara', 'criteria_code' => '1.a.2.5'],
                    ['title' => 'Pengembalian Sisa Panjar', 'slug' => 'layanan-hukum/sisa-panjar', 'criteria_code' => '1.a.2.6', 'tingkat_scope' => 'pertama'],
                    ['title' => 'Hak Pencari Keadilan (Prodeo & Posbakum)', 'slug' => 'layanan-hukum/hak-pencari-keadilan'],
                ],
            ],
            [
                'title' => 'Transparansi',
                'type' => 'group',
                'sort_order' => 4,
                'children' => [
                    ['title' => 'Rencana Strategis (Renstra)', 'slug' => 'transparansi/renstra', 'criteria_code' => '1.a.3.6'],
                    ['title' => 'Rencana Kinerja Tahunan (RKT)', 'slug' => 'transparansi/rkt', 'criteria_code' => '1.a.3.7'],
                    ['title' => 'Perjanjian Kinerja (PK)', 'slug' => 'transparansi/perjanjian-kinerja', 'criteria_code' => '1.a.3.9'],
                    ['title' => 'IKU & Reviu IKU', 'slug' => 'transparansi/iku', 'criteria_code' => '1.a.3.10'],
                    ['title' => 'Laporan Kinerja (LKjIP)', 'slug' => 'transparansi/lkjip', 'criteria_code' => '1.a.3.11'],
                    ['title' => 'Rencana Aksi Kinerja (5 Tahun)', 'slug' => 'transparansi/rencana-aksi-kinerja', 'criteria_code' => '1.a.1.1'],
                    ['title' => 'Rencana Aksi Tahun Berjalan', 'slug' => 'transparansi/renaksi-berjalan', 'criteria_code' => '1.a.3.8'],
                    ['title' => 'Laporan Pelaksanaan Kegiatan', 'slug' => 'transparansi/laporan-tahunan', 'criteria_code' => '1.a.1.2'],
                    ['title' => 'DIPA, RPD & Realisasi', 'slug' => 'transparansi/dipa', 'criteria_code' => '1.a.3.2'],
                    ['title' => 'Laporan Realisasi Anggaran (LRA)', 'slug' => 'transparansi/lra', 'criteria_code' => '1.a.3.3'],
                    ['title' => 'Catatan Atas Laporan Keuangan (CALK)', 'slug' => 'transparansi/calk', 'criteria_code' => '1.a.3.4'],
                    ['title' => 'Pengadaan Barang & Jasa (PBJ)', 'slug' => 'transparansi/pengadaan-barang-jasa', 'criteria_code' => '1.a.3.5'],
                    ['title' => 'LHKPN Pejabat', 'slug' => 'transparansi/lhkpn', 'criteria_code' => '1.a.3.1'],
                ],
            ],
            [
                'title' => 'Layanan Publik',
                'type' => 'group',
                'sort_order' => 5,
                'children' => [
                    ['title' => 'Standar & Maklumat Pelayanan', 'slug' => 'layanan-publik/standar-maklumat-pelayanan', 'criteria_code' => '1.b.4.1'],
                    ['title' => 'Laporan Akses Informasi (PPID)', 'slug' => 'layanan-publik/laporan-akses-informasi', 'criteria_code' => '1.a.4.1.1'],
                    ['title' => 'Laporan Rekapitulasi Pengaduan', 'slug' => 'layanan-publik/rekapitulasi-pengaduan', 'criteria_code' => '1.a.4.2.1'],
                    ['title' => 'Hukuman Disiplin Pengawasan', 'slug' => 'layanan-publik/hukuman-disiplin', 'criteria_code' => '1.a.4.3.1'],
                    ['title' => 'Survei Pelayanan Masyarakat', 'slug' => 'layanan-publik/survei-pelayanan', 'criteria_code' => '1.a.4.4.1'],
                    ['title' => 'Pengaduan SIWAS MARI', 'slug' => 'layanan-publik/pengaduan-siwas'],
                ],
            ],
            [
                'title' => 'Berita & Publikasi',
                'type' => 'external',
                'url' => '/publikasi',
                'sort_order' => 6,
                'children' => [
                    ['title' => 'Semua Berita & Artikel', 'type' => 'external', 'url' => '/publikasi'],
                    ['title' => 'Berita Pelayanan', 'type' => 'category', 'collection_slug' => 'berita'],
                    ['title' => 'Pengumuman Resmi', 'type' => 'category', 'collection_slug' => 'pengumuman'],
                    ['title' => 'Inovasi Layanan', 'type' => 'category', 'collection_slug' => 'inovasi'],
                    ['title' => 'Opini & Gagasan Aparatur', 'type' => 'category', 'collection_slug' => 'opini'],
                ],
            ],
        ];

        foreach ($menuTree as $topDef) {
            $parent = MenuItem::query()->firstOrCreate(
                [
                    'location' => 'header',
                    'parent_id' => null,
                    'title' => $topDef['title'],
                ],
                [
                    'type' => $topDef['type'],
                    'url' => $topDef['url'] ?? null,
                    'is_visible' => true,
                    'sort_order' => $topDef['sort_order'],
                    'tingkat_scope' => 'all',
                ],
            );

            foreach ($topDef['children'] as $childIndex => $childDef) {
                $scope = $childDef['tingkat_scope'] ?? 'all';
                $isVisible = $scope === 'all' || $scope === $activeTingkat;
                $page = isset($childDef['slug']) ? ($pagesBySlug[$childDef['slug']] ?? null) : null;

                $child = MenuItem::query()->firstOrNew([
                    'location' => 'header',
                    'parent_id' => $parent->id,
                    'title' => $childDef['title'],
                ]);

                if (! $child->exists) {
                    $child->type = $childDef['type'] ?? ($page ? 'page' : 'external');
                    $child->page_id = $page?->id;
                    $child->collection_slug = $childDef['collection_slug'] ?? null;
                    $child->url = $childDef['url'] ?? null;
                    $child->sort_order = $childIndex + 1;
                    $child->criteria_code = $childDef['criteria_code'] ?? null;
                    $child->tingkat_scope = $scope;
                }

                $child->is_visible = $isVisible;
                $child->save();
            }
        }
    }
}
