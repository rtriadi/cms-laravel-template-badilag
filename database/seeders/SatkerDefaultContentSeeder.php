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
                'criteria_code' => '1.b.1',
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
                'criteria_code' => '1.b.p.a',
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
                'criteria_code' => '1.b.p.b',
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
                'criteria_code' => '1.b.p.c',
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
                'criteria_code' => '1.b.p.d',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'satker_contact', 'heading' => 'Identitas, Alamat Lengkap, dan Foto Gedung {{satker.nama}}'],
                ],
            ],
            [
                'slug' => 'profil/pejabat-pegawai',
                'title' => 'Profil Pejabat dan Pegawai',
                'criteria_code' => '1.b.3.a',
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
                'criteria_code' => '1.a.2.a',
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
                'criteria_code' => '1.a.2.b',
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
                'criteria_code' => '1.a.2.c',
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
                'criteria_code' => '1.a.2.d',
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
                'criteria_code' => '1.a.2.e',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'document_list', 'collection_type' => 'biaya_perkara', 'heading' => 'Rincian Penggunaan Biaya Perkara Per Bulan'],
                ],
            ],
            [
                'slug' => 'layanan-hukum/sisa-panjar',
                'title' => 'Laporan Pengembalian Sisa Panjar Perkara',
                'criteria_code' => '1.a.2.f',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'pertama',
                'blocks' => [
                    ['type' => 'document_list', 'collection_type' => 'sisa_panjar', 'heading' => 'Rincian Pengembalian Sisa Panjar Perkara Per Bulan'],
                ],
            ],
            [
                'slug' => 'layanan-hukum/ecourt',
                'title' => 'Layanan e-Court Mahkamah Agung RI',
                'criteria_code' => '1.b.2.a',
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
                'criteria_code' => '1.b.2.b',
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
                'criteria_code' => '1.b.2.c',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => 'Alur Layanan Berperkara di {{satker.nama}}',
                        'content' => '<h3>1. Tingkat Pertama</h3><p>Pendaftaran gugatan atau permohonan melalui meja Pelayanan Terpadu Satu Pintu (PTSP) atau secara elektronik melalui aplikasi e-Court Mahkamah Agung RI dengan melengkapi surat gugatan/permohonan, identitas para pihak, serta bukti pembayaran panjar biaya perkara (atau permohonan Prodeo).</p><h3>2. Tingkat Banding</h3><p>Permohonan banding diajukan paling lambat 14 hari kalender sejak putusan diucapkan atau diberitahukan secara sah kepada pihak yang tidak hadir melalui kepaniteraan pengadilan tingkat pertama atau melalui e-Court.</p><h3>3. Kasasi</h3><p>Permohonan kasasi diajukan dalam tenggang waktu 14 hari kalender setelah putusan banding diberitahukan, diikuti penyerahan memori kasasi paling lambat 14 hari kalender sejak pernyataan kasasi diterima.</p><h3>4. Peninjauan Kembali (PK)</h3><p>Diajukan terhadap putusan yang telah berkekuatan hukum tetap sesuai alasan dan tenggang waktu undang-undang melalui kepaniteraan pengadilan tingkat pertama yang memeriksa perkara asal.</p><h3>5. Pengambilan Produk Pengadilan</h3><p>Salinan putusan/penetapan dan Akta Cerai dapat diambil langsung oleh pihak berperkara atau kuasanya yang sah di loket Produk Pengadilan PTSP atau diunduh secara elektronik bagi perkara e-Court.</p><h3>6. Gugatan Sederhana (Small Claim Court)</h3><p>Penyelesaian perkara ekonomi syariah dengan nilai gugatan materiil maksimal sesuai Peraturan Mahkamah Agung, diperiksa oleh hakim tunggal dan diselesaikan paling lama 25 hari kerja sejak hari sidang pertama.</p>',
                    ],
                    [
                        'type' => 'document_list',
                        'collection_type' => 'prosedur_sop',
                        'heading' => 'Dokumen SOP & Panduan Prosedur Berperkara',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-hukum/biaya-berperkara',
                'title' => 'Biaya Proses Berperkara',
                'criteria_code' => '1.b.2.d',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => 'Komponen Panjar Biaya Perkara & Radius Panggilan',
                        'content' => '<p>Panjar biaya perkara pada {{satker.nama}} ditetapkan berdasarkan Surat Keputusan Ketua {{satker.nama}} tentang Panjar Biaya Perkara dan Radius Panggilan. Komponen biaya perkara meliputi:</p><ul><li><strong>Biaya Pendaftaran (PNBP):</strong> Biaya pendaftaran gugatan/permohonan, pencatatan, serta hak-hak kepaniteraan sesuai Peraturan Pemerintah tentang Jenis dan Tarif PNBP yang berlaku pada Mahkamah Agung.</li><li><strong>Biaya Proses (ATK):</strong> Biaya administrasi pemberkasan dan pengiriman dokumen persidangan.</li><li><strong>Biaya Panggilan / Pemberitahuan:</strong> Dihitung berdasarkan zona radius wilayah kelurahan/desa tempat tinggal para pihak, atau Rp0 untuk panggilan elektronik (e-Summons) melalui e-Court.</li><li><strong>Meterai &amp; Redaksi:</strong> Biaya meterai dan redaksi putusan/penetapan sesuai ketentuan perundang-undangan.</li></ul><p>Apabila terdapat kelebihan panjar biaya perkara setelah perkara putus, sisa panjar wajib dikembalikan kepada pihak berperkara melalui kasir PTSP atau transfer rekening.</p>',
                    ],
                    [
                        'type' => 'document_list',
                        'collection_type' => 'biaya_radius',
                        'heading' => 'SK Panjar Biaya Perkara dan Tabel Radius Panggilan Resmi',
                    ],
                ],
            ],
            // Kesekretariatan & Informasi Umum
            ['slug' => 'transparansi/rencana-aksi-kinerja', 'title' => 'Rencana Aksi Kinerja (5 Tahun Terakhir)', 'criteria_code' => '1.a.1.a', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'renaksi', 'heading' => 'Daftar Dokumen Rencana Aksi Kinerja']]],
            ['slug' => 'transparansi/laporan-tahunan', 'title' => 'Laporan Pelaksanaan Kegiatan / Laporan Tahunan', 'criteria_code' => '1.a.1.b', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'laporan_tahunan', 'heading' => 'Laporan Tahunan & Pelaksanaan Kegiatan']]],
            ['slug' => 'transparansi/lhkpn', 'title' => 'Laporan Harta Kekayaan Penyelenggara Negara (LHKPN)', 'criteria_code' => '1.a.3.a', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'lhkpn', 'heading' => 'Daftar Pejabat & Ikhtisar Kekayaan LHKPN (Min. 2 Tahun Terakhir)']]],
            ['slug' => 'transparansi/dipa', 'title' => 'Daftar Isian Pelaksanaan Anggaran (DIPA)', 'criteria_code' => '1.a.3.b', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'dipa', 'heading' => 'DIPA, Rencana Penggunaan Anggaran (RPD), dan Realisasi DIPA']]],
            ['slug' => 'transparansi/lra', 'title' => 'Laporan Realisasi Anggaran (LRA)', 'criteria_code' => '1.a.3.c', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'lra', 'heading' => 'Dokumen Laporan Realisasi Anggaran']]],
            [
                'slug' => 'transparansi/calk',
                'title' => 'Catatan Atas Laporan Keuangan (CALK) & Daftar Aset BMN',
                'criteria_code' => '1.a.3.d',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'document_list', 'collection_type' => 'calk', 'heading' => 'Dokumen Catatan Atas Laporan Keuangan (CALK) & Neraca'],
                    ['type' => 'document_list', 'collection_type' => 'aset_bmn', 'heading' => 'Ringkasan Daftar Aset dan Inventaris (BMN)'],
                ],
            ],
            ['slug' => 'transparansi/pengadaan-barang-jasa', 'title' => 'Rencana Umum Pengadaan Barang dan Jasa (PBJ)', 'criteria_code' => '1.a.3.e', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'pbj', 'heading' => 'Rencana Umum Pengadaan Barang dan Jasa']]],
            ['slug' => 'transparansi/renstra', 'title' => 'Rencana Strategis (Renstra)', 'criteria_code' => '1.a.3.f', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'renstra', 'heading' => 'Rancangan, Dokumen Utama, dan Reviu Renstra']]],
            ['slug' => 'transparansi/rkt', 'title' => 'Rencana Kinerja Tahunan (RKT)', 'criteria_code' => '1.a.3.g', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'rkt', 'heading' => 'Dokumen Rencana Kinerja Tahunan']]],
            ['slug' => 'transparansi/renaksi-berjalan', 'title' => 'Rencana Aksi Kinerja Tahun Berjalan', 'criteria_code' => '1.a.3.h', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'renaksi', 'heading' => 'Dokumen Rencana Aksi Kinerja Tahun Saat Ini']]],
            ['slug' => 'transparansi/perjanjian-kinerja', 'title' => 'Perjanjian Kinerja (PK)', 'criteria_code' => '1.a.3.i', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'pk', 'heading' => 'Dokumen Perjanjian Kinerja']]],
            ['slug' => 'transparansi/iku', 'title' => 'Indikator Kinerja Utama (IKU) dan Reviu IKU', 'criteria_code' => '1.a.3.j', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'iku', 'heading' => 'Dokumen IKU dan Reviu IKU']]],
            ['slug' => 'transparansi/lkjip', 'title' => 'Laporan Kinerja Instansi Pemerintah (LKjIP)', 'criteria_code' => '1.a.3.k', 'category_tag' => 'berkala', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'lkjip', 'heading' => 'Dokumen LKjIP']]],
            // Layanan Publik & Pengaduan
            [
                'slug' => 'layanan-publik/laporan-akses-informasi',
                'title' => 'Layanan Informasi Publik (PPID), DIP, SOP & Laporan Akses',
                'criteria_code' => '1.a.4.1.a',
                'category_tag' => 'setiap_saat',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => '1. Tata Cara Memperoleh Informasi, Pengajuan Keberatan & Hak Pemohon',
                        'content' => '<p>Layanan Informasi Publik pada {{satker.nama}} diselenggarakan oleh Pejabat Pengelola Informasi dan Dokumentasi (PPID) melalui Meja Informasi PTSP maupun secara elektronik sesuai SK KMA Nomor 2-144/KMA/SK/VIII/2022.</p><h3>Hak Pemohon Informasi</h3><ul><li>Setiap orang berhak memperoleh Informasi Publik yang terbuka dan akurat dari Pengadilan.</li><li>Pemohon berhak mengajukan keberatan secara tertulis kepada Atasan PPID apabila permohonan informasi ditolak, tidak ditanggapi, atau dikenakan biaya yang tidak wajar.</li></ul><h3>Standar Biaya Perolehan Salinan Informasi</h3><p>Pelayanan permintaan informasi publik pada {{satker.nama}} tidak dipungut biaya (Rp0). Apabila Pemohon meminta penggandaan/salinan fisik dokumen, biaya penggandaan ditetapkan berdasarkan SK Ketua {{satker.nama}} tentang Standar Biaya Perolehan Salinan Informasi.</p>',
                    ],
                    [
                        'type' => 'richtext',
                        'heading' => '2. Daftar Informasi Publik (DIP), Informasi Dikecualikan & 7 SOP PPID',
                        'content' => '<p>PPID {{satker.nama}} memutakhirkan Daftar Informasi Publik (DIP) paling kurang 2 (dua) kali dalam 1 (satu) tahun, melakukan pengujian konsekuensi terhadap Informasi yang Dikecualikan sesuai Pasal 19 UU Nomor 14 Tahun 2008, serta menerapkan 7 (tujuh) SOP Layanan Informasi Publik:</p><ol><li>SOP Pengumuman Informasi</li><li>SOP Pengelolaan Permohonan Informasi</li><li>SOP Pengelolaan Keberatan atas Informasi</li><li>SOP Penanganan Sengketa Informasi Publik oleh Atasan PPID</li><li>SOP Penetapan dan Pemutakhiran DIP</li><li>SOP Pengujian tentang Konsekuensi</li><li>SOP Pendokumentasian Informasi Publik</li></ol>',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'akses_informasi', 'heading' => 'Laporan Tahunan & Berkala Layanan Akses Informasi Publik (PPID)'],
                    ['type' => 'document_list', 'collection_type' => 'dip_ppid', 'heading' => 'SK PPID, Daftar Informasi Publik (DIP), Informasi Dikecualikan & 7 SOP PPID'],
                    ['type' => 'document_list', 'collection_type' => 'biaya_informasi', 'heading' => 'SK Standar Biaya Perolehan Salinan Informasi & Formulir Permohonan/Keberatan'],
                ],
            ],
            ['slug' => 'layanan-publik/rekapitulasi-pengaduan', 'title' => 'Laporan Rekapitulasi Pengaduan Masyarakat', 'criteria_code' => '1.a.4.2.a', 'category_tag' => 'setiap_saat', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'rekap_pengaduan', 'heading' => 'Rekapitulasi Pengaduan dan Tindak Lanjut (Inisial Pelapor)']]],
            ['slug' => 'layanan-publik/hukuman-disiplin', 'title' => 'Hukuman Disiplin Hasil Pengawasan', 'criteria_code' => '1.a.4.3.a', 'category_tag' => 'setiap_saat', 'tingkat_scope' => 'all', 'blocks' => [['type' => 'document_list', 'collection_type' => 'hukuman_disiplin', 'heading' => 'Daftar Hukuman Disiplin Hasil Pengawasan']]],
            [
                'slug' => 'layanan-publik/survei-pelayanan',
                'title' => 'Survei Pelayanan Masyarakat (SKM, SPAK, SPKP)',
                'criteria_code' => null,
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
                'criteria_code' => '1.b.4.a',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    ['type' => 'richtext', 'heading' => 'Maklumat Pelayanan {{satker.nama}}', 'content' => '<blockquote>Dengan ini, kami pimpinan dan seluruh aparatur {{satker.nama}} menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan dan apabila tidak menepati janji, kami siap menerima sanksi sesuai peraturan perundang-undangan yang berlaku.</blockquote>'],
                    ['type' => 'richtext', 'heading' => 'Standar Pelayanan Terpadu Satu Pintu (PTSP)', 'content' => '<p>{{satker.nama}} menyelenggarakan pelayanan administrasi peradilan secara terintegrasi melalui meja PTSP dengan kepastian persyaratan, prosedur, jangka waktu penyelesaian, dan biaya yang transparan.</p>'],
                    ['type' => 'richtext', 'heading' => 'Layanan Kelompok Rentan & Disabilitas', 'content' => '<p>{{satker.nama}} menyediakan fasilitas kursi roda, jalur pemandu (guiding block), ruang tunggu ramah anak dan ibu menyusui, toilet khusus disabilitas, antrean prioritas, serta pendampingan petugas bagi pencari keadilan berkebutuhan khusus.</p>'],
                    ['type' => 'richtext', 'heading' => 'Prosedur Peringatan Dini & Evakuasi Keadaan Darurat', 'content' => '<p>Apabila terjadi keadaan darurat (kebakaran, gempa bumi, atau gangguan keamanan) di lingkungan kantor {{satker.nama}}, pengunjung dan aparatur wajib mengikuti arahan petugas keamanan menuju jalur evakuasi dan Titik Kumpul darurat yang tersedia di halaman kantor pengadilan.</p>'],
                    ['type' => 'document_list', 'collection_type' => 'standar_pelayanan', 'heading' => 'Dokumen SK Standar Pelayanan, Maklumat & SOP Layanan Disabilitas'],
                    ['type' => 'document_list', 'collection_type' => 'sop_evakuasi', 'heading' => 'Dokumen SOP Peringatan Dini & Prosedur Evakuasi Keadaan Darurat'],
                ],
            ],
            // Pendukung
            [
                'slug' => 'layanan-hukum/hak-pencari-keadilan',
                'title' => 'Hak Pencari Keadilan (Prodeo & Posbakum)',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'richtext',
                        'heading' => '1. Layanan Pembebasan Biaya Perkara (Prodeo)',
                        'content' => '<p>Sesuai Peraturan Mahkamah Agung RI Nomor 1 Tahun 2014 tentang Pedoman Pemberian Layanan Hukum bagi Masyarakat Tidak Mampu di Pengadilan, setiap orang atau sekelompok orang yang tidak mampu secara ekonomi berhak mengajukan gugatan/permohonan secara cuma-cuma (Prodeo) pada {{satker.nama}} dengan biaya perkara Rp0 (dibebankan pada anggaran DIPA satuan kerja selama kuota tersedia atau Prodeo Murni).</p><h3>Persyaratan Pengajuan Prodeo</h3><ul><li>Surat Keterangan Tidak Mampu (SKTM) yang dikeluarkan oleh Kepala Desa/Lurah/Kepala Wilayah setempat yang menyatakan bahwa benar yang bersangkutan tidak mampu membayar biaya perkara; <strong>atau</strong></li><li>Surat Keterangan Tunjangan Sosial lainnya seperti Kartu Keluarga Sejahtera (KKS), Kartu Program Keluarga Harapan (PKH), Kartu Indonesia Sehat (KIS/BPJS PBI), atau dokumen bantuan sosial pemerintah yang masih berlaku.</li><li>Surat permohonan berperkara secara cuma-cuma (Prodeo) yang diajukan bersamaan dengan surat gugatan atau permohonan.</li></ul>',
                    ],
                    [
                        'type' => 'richtext',
                        'heading' => '2. Layanan Pos Bantuan Hukum (Posbakum)',
                        'content' => '<p>Pos Bantuan Hukum (Posbakum) {{satker.nama}} menyediakan layanan bantuan hukum gratis di gedung pengadilan bagi masyarakat pencari keadilan yang tidak mampu membayar jasa advokat.</p><h3>Jenis Layanan Gratis di Posbakum</h3><ul><li>Pemberian informasi, konsultasi, dan advis hukum terkait perkara yang akan atau sedang diajukan.</li><li>Bantuan pembuatan dokumen hukum yang dibutuhkan, seperti surat gugatan, surat permohonan, jawaban, replik, duplik, maupun permohonan pembebasan biaya perkara (Prodeo).</li><li>Penyediaan informasi mengenai daftar Organisasi Bantuan Hukum (OBH) terakreditasi yang dapat memberikan pendampingan secara cuma-cuma.</li></ul>',
                    ],
                    [
                        'type' => 'richtext',
                        'heading' => '3. Sidang di Luar Gedung Pengadilan (Sidang Keliling)',
                        'content' => '<p>{{satker.nama}} menyelenggarakan persidangan di luar gedung pengadilan (Sidang Keliling) pada kecamatan atau lokasi yang telah dijadwalkan untuk membantu masyarakat pencari keadilan yang mengalami hambatan biaya, jarak tempuh, atau kondisi fisik menuju kantor pengadilan.</p>',
                    ],
                    [
                        'type' => 'richtext',
                        'heading' => '4. Hak-Hak Pokok Para Pihak dalam Proses Persidangan',
                        'content' => '<ul><li>Hak diperlakukan sama di hadapan hukum tanpa diskriminasi.</li><li>Hak memperoleh bantuan hukum atau pendampingan advokat/Posbakum.</li><li>Hak mengajukan jawab-jinawab, alat bukti surat, saksi, maupun ahli di persidangan.</li><li>Hak memperoleh salinan putusan/penetapan dan Akta Cerai secara sah, serta hak mengajukan upaya hukum (Banding, Kasasi, Peninjauan Kembali) sesuai tenggang waktu undang-undang.</li></ul>',
                    ],
                    [
                        'type' => 'document_list',
                        'collection_type' => 'prodeo_posbakum',
                        'heading' => 'Dokumen, SK, SPK, Jadwal Piket, dan Laporan Layanan Prodeo & Posbakum',
                    ],
                    [
                        'type' => 'article_list',
                        'post_category' => 'layanan_hukum',
                        'heading' => 'Artikel & Informasi Terkait Hak Pencari Keadilan',
                    ],
                ],
            ],
            [
                'slug' => 'layanan-publik/pengaduan-siwas',
                'title' => 'Prosedur Pengaduan, Hak Pelapor & SIWAS MARI',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Sistem Informasi Pengawasan Mahkamah Agung (SIWAS)',
                        'url_source' => 'siwas_url',
                        'default_url' => 'https://siwas.mahkamahagung.go.id',
                        'description' => 'Sampaikan pengaduan dugaan pelanggaran perilaku hakim atau aparatur pengadilan secara daring melalui aplikasi resmi SIWAS Mahkamah Agung RI.',
                    ],
                    [
                        'type' => 'richtext',
                        'heading' => 'Tata Cara Pengaduan (Manual & Elektronik) serta Hak-Hak Pelapor',
                        'content' => '<h3>1. Saluran Pengaduan</h3><ul><li><strong>Secara Elektronik:</strong> Melalui aplikasi <a href="https://siwas.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer">SIWAS Mahkamah Agung RI</a> atau kanal pengaduan resmi {{satker.nama}}.</li><li><strong>Secara Manual / Langsung:</strong> Disampaikan secara tertulis maupun lisan melalui Meja Pengaduan pada PTSP {{satker.nama}} atau dimasukkan ke dalam Kotak Pengaduan resmi.</li></ul><h3>2. Hak-Hak Pelapor</h3><ul><li>Mendapatkan perlindungan kerahasiaan identitas (nama pelapor disamarkan dengan inisial pada rekapitulasi publik).</li><li>Mendapatkan nomor register pengaduan dan informasi perkembangan/status penanganan pengaduan secara transparan.</li><li>Bebas dari segala bentuk intimidasi atau perlakuan diskriminatif atas laporan yang disampaikan dengan iktikad baik.</li></ul>',
                    ],
                    [
                        'type' => 'document_list',
                        'collection_type' => 'regulasi_sk',
                        'heading' => 'SK Petugas Meja Pengaduan, SOP Pengaduan, SK Tim Pengelola Website/Medsos & SK Meja e-Court',
                    ],
                ],
            ],
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

            if ($item->linked_page_id === null || ! Page::query()->whereKey($item->linked_page_id)->exists()) {
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
                    ['title' => 'Visi & Misi', 'slug' => 'profil/visi-misi', 'criteria_code' => '1.b.1'],
                    ['title' => 'Tugas Pokok & Fungsi', 'slug' => 'profil/tupoksi', 'criteria_code' => '1.b.p.a'],
                    ['title' => 'Wilayah Yurisdiksi', 'slug' => 'profil/wilayah-yurisdiksi', 'criteria_code' => '1.b.p.b'],
                    ['title' => 'Struktur Organisasi', 'slug' => 'profil/struktur-organisasi', 'criteria_code' => '1.b.p.c'],
                    ['title' => 'Profil Pejabat & Pegawai', 'slug' => 'profil/pejabat-pegawai', 'criteria_code' => '1.b.3.a'],
                    ['title' => 'Alamat & Kontak Pengadilan', 'slug' => 'profil/alamat-kontak', 'criteria_code' => '1.b.p.d'],
                ],
            ],
            [
                'title' => 'Layanan Hukum',
                'type' => 'group',
                'sort_order' => 3,
                'children' => [
                    ['title' => 'SIPP Penelusuran Perkara', 'slug' => 'layanan-hukum/sipp', 'criteria_code' => '1.a.2.a'],
                    ['title' => 'Jadwal Persidangan', 'slug' => 'layanan-hukum/jadwal-sidang', 'criteria_code' => '1.a.2.b'],
                    ['title' => 'Statistik Perkara', 'slug' => 'layanan-hukum/statistik-perkara', 'criteria_code' => '1.a.2.c'],
                    ['title' => 'e-Court Mahkamah Agung', 'slug' => 'layanan-hukum/ecourt', 'criteria_code' => '1.b.2.a'],
                    ['title' => 'Direktori Putusan', 'slug' => 'layanan-hukum/direktori-putusan', 'criteria_code' => '1.b.2.b'],
                    ['title' => 'Prosedur Berperkara', 'slug' => 'layanan-hukum/prosedur-berperkara', 'criteria_code' => '1.b.2.c'],
                    ['title' => 'Biaya Proses Berperkara', 'slug' => 'layanan-hukum/biaya-berperkara', 'criteria_code' => '1.b.2.d'],
                    ['title' => 'Panggilan Ghaib', 'slug' => 'layanan-hukum/panggilan-ghaib', 'criteria_code' => '1.a.2.d'],
                    ['title' => 'Laporan Biaya Perkara', 'slug' => 'layanan-hukum/laporan-biaya-perkara', 'criteria_code' => '1.a.2.e'],
                    ['title' => 'Pengembalian Sisa Panjar', 'slug' => 'layanan-hukum/sisa-panjar', 'criteria_code' => '1.a.2.f', 'tingkat_scope' => 'pertama'],
                    ['title' => 'Hak Pencari Keadilan (Prodeo & Posbakum)', 'slug' => 'layanan-hukum/hak-pencari-keadilan'],
                ],
            ],
            [
                'title' => 'Transparansi',
                'type' => 'group',
                'sort_order' => 4,
                'children' => [
                    ['title' => 'Rencana Strategis (Renstra)', 'slug' => 'transparansi/renstra', 'criteria_code' => '1.a.3.f'],
                    ['title' => 'Rencana Kinerja Tahunan (RKT)', 'slug' => 'transparansi/rkt', 'criteria_code' => '1.a.3.g'],
                    ['title' => 'Perjanjian Kinerja (PK)', 'slug' => 'transparansi/perjanjian-kinerja', 'criteria_code' => '1.a.3.i'],
                    ['title' => 'IKU & Reviu IKU', 'slug' => 'transparansi/iku', 'criteria_code' => '1.a.3.j'],
                    ['title' => 'Laporan Kinerja (LKjIP)', 'slug' => 'transparansi/lkjip', 'criteria_code' => '1.a.3.k'],
                    ['title' => 'Rencana Aksi Kinerja (5 Tahun)', 'slug' => 'transparansi/rencana-aksi-kinerja', 'criteria_code' => '1.a.1.a'],
                    ['title' => 'Rencana Aksi Tahun Berjalan', 'slug' => 'transparansi/renaksi-berjalan', 'criteria_code' => '1.a.3.h'],
                    ['title' => 'Laporan Pelaksanaan Kegiatan', 'slug' => 'transparansi/laporan-tahunan', 'criteria_code' => '1.a.1.b'],
                    ['title' => 'DIPA, RPD & Realisasi', 'slug' => 'transparansi/dipa', 'criteria_code' => '1.a.3.b'],
                    ['title' => 'Laporan Realisasi Anggaran (LRA)', 'slug' => 'transparansi/lra', 'criteria_code' => '1.a.3.c'],
                    ['title' => 'Catatan Atas Laporan Keuangan (CALK)', 'slug' => 'transparansi/calk', 'criteria_code' => '1.a.3.d'],
                    ['title' => 'Pengadaan Barang & Jasa (PBJ)', 'slug' => 'transparansi/pengadaan-barang-jasa', 'criteria_code' => '1.a.3.e'],
                    ['title' => 'LHKPN Pejabat', 'slug' => 'transparansi/lhkpn', 'criteria_code' => '1.a.3.a'],
                ],
            ],
            [
                'title' => 'Layanan Publik',
                'type' => 'group',
                'sort_order' => 5,
                'children' => [
                    ['title' => 'Standar & Maklumat Pelayanan', 'slug' => 'layanan-publik/standar-maklumat-pelayanan', 'criteria_code' => '1.b.4.a'],
                    ['title' => 'Laporan Akses Informasi (PPID)', 'slug' => 'layanan-publik/laporan-akses-informasi', 'criteria_code' => '1.a.4.1.a'],
                    ['title' => 'Laporan Rekapitulasi Pengaduan', 'slug' => 'layanan-publik/rekapitulasi-pengaduan', 'criteria_code' => '1.a.4.2.a'],
                    ['title' => 'Hukuman Disiplin Pengawasan', 'slug' => 'layanan-publik/hukuman-disiplin', 'criteria_code' => '1.a.4.3.a'],
                    ['title' => 'Survei Pelayanan Masyarakat', 'slug' => 'layanan-publik/survei-pelayanan'],
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
                    ['title' => 'Artikel Layanan & Bantuan Hukum', 'type' => 'category', 'collection_slug' => 'layanan_hukum'],
                    ['title' => 'Inovasi Layanan', 'type' => 'category', 'collection_slug' => 'inovasi'],
                    ['title' => 'Opini & Gagasan Aparatur', 'type' => 'category', 'collection_slug' => 'opini'],
                    ['title' => 'Galeri Infografis', 'type' => 'category', 'collection_slug' => 'infografis'],
                    ['title' => 'Informasi Serta Merta', 'type' => 'category', 'collection_slug' => 'serta_merta'],
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
                } elseif (
                    $child->type === 'page'
                    && $page !== null
                    && ($child->page_id === null || ! Page::query()->whereKey($child->page_id)->exists())
                ) {
                    $child->page_id = $page->id;
                }

                $child->is_visible = $isVisible;
                $child->save();
            }
        }

        MenuItem::flushPublicCache();
    }
}
