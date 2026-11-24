<?php

declare(strict_types=1);

namespace App\Services\LegacyMigration;

use App\Models\HomeSlider;
use App\Services\ImageOptimizerService;
use App\Support\IndonesianTitleNormalizer;
use App\Support\SafeHtml;
use Closure;
use Illuminate\Support\Str;
use RuntimeException;

class PaGorontaloMigrationExtractor
{
    private const string DEFAULT_SIMTEPA_TOKEN = 'a87bd82962cda7f528247a68ee4ac2a5';

    /** @var array<int, string> */
    private const array POST_CATEGORY_MAP = [
        115 => 'berita',
        114 => 'pengumuman',
        104 => 'pengumuman',
        118 => 'layanan_hukum',
    ];

    /**
     * Map legacy article IDs to SatkerCMS document collection types for structured extraction.
     *
     * @var array<int, array{collection: string, label: string}>
     */
    private const array DOCUMENT_ARTICLE_MAP = [
        87 => ['collection' => 'renstra', 'label' => 'Rencana Strategis (Renstra)'],
        350 => ['collection' => 'rkt', 'label' => 'Rencana Kinerja Tahunan (RKT)'],
        361 => ['collection' => 'renaksi', 'label' => 'Rencana Aksi Kinerja'],
        351 => ['collection' => 'pk', 'label' => 'Perjanjian Kinerja (PK)'],
        352 => ['collection' => 'iku', 'label' => 'Indikator Kinerja Utama (IKU)'],
        102 => ['collection' => 'lkjip', 'label' => 'Laporan Kinerja (LKjIP / LAKIP)'],
        104 => ['collection' => 'laporan_tahunan', 'label' => 'Laporan Tahunan'],
        155 => ['collection' => 'laporan_tahunan', 'label' => 'Program Kerja'],
        240 => ['collection' => 'laporan_tahunan', 'label' => 'Agenda Kerja Satker'],
        157 => ['collection' => 'dipa', 'label' => 'DIPA PA Gorontalo'],
        88 => ['collection' => 'dipa', 'label' => 'Rencana Kerja dan Anggaran (RKA-KL)'],
        209 => ['collection' => 'dipa', 'label' => 'RKAK-L PA Gorontalo'],
        152 => ['collection' => 'lra', 'label' => 'Laporan Realisasi Anggaran (LRA)'],
        360 => ['collection' => 'lra', 'label' => 'Realisasi Penerimaan PNBP'],
        323 => ['collection' => 'lra', 'label' => 'Laporan Keuangan'],
        151 => ['collection' => 'calk', 'label' => 'Catatan Atas Laporan Keuangan (CALK)'],
        150 => ['collection' => 'calk', 'label' => 'Neraca Keuangan'],
        103 => ['collection' => 'aset_bmn', 'label' => 'Ringkasan Daftar Aset & Inventaris (BMN)'],
        149 => ['collection' => 'lhkpn', 'label' => 'LHKPN Pejabat'],
        349 => ['collection' => 'lhkpn', 'label' => 'LHKASN Aparatur'],
        359 => ['collection' => 'panggilan_ghaib', 'label' => 'Panggilan Ghaib'],
        315 => ['collection' => 'biaya_radius', 'label' => 'SK Panjar Biaya Perkara & Radius Panggilan'],
        238 => ['collection' => 'prosedur_sop', 'label' => 'SOP Kepaniteraan'],
        239 => ['collection' => 'prosedur_sop', 'label' => 'SOP Kesekretariatan'],
        810 => ['collection' => 'prosedur_sop', 'label' => 'Panduan e-Court'],
        1030 => ['collection' => 'prosedur_sop', 'label' => 'Gugatan Sederhana'],
        186 => ['collection' => 'prosedur_sop', 'label' => 'Prosedur Pengambilan Akta Cerai'],
        113 => ['collection' => 'prodeo_posbakum', 'label' => 'Peraturan & Kebijakan Prodeo'],
        342 => ['collection' => 'prodeo_posbakum', 'label' => 'Prosedur Layanan Prodeo'],
        344 => ['collection' => 'prodeo_posbakum', 'label' => 'Dasar Hukum Posbakum'],
        345 => ['collection' => 'prodeo_posbakum', 'label' => 'Pelaksana Posbakum'],
        246 => ['collection' => 'standar_pelayanan', 'label' => 'Standar Pelayanan Peradilan'],
        254 => ['collection' => 'standar_pelayanan', 'label' => 'Standar & Maklumat Pelayanan'],
        189 => ['collection' => 'standar_pelayanan', 'label' => 'Standar dan Maklumat Pelayanan'],
        105 => ['collection' => 'akses_informasi', 'label' => 'Laporan Layanan Informasi Publik'],
        1095 => ['collection' => 'akses_informasi', 'label' => 'Laporan Tahunan Informasi Publik'],
        96 => ['collection' => 'dip_ppid', 'label' => 'Prosedur Permohonan Informasi (PPID)'],
        249 => ['collection' => 'biaya_informasi', 'label' => 'Biaya Salinan Informasi'],
        250 => ['collection' => 'biaya_informasi', 'label' => 'Formulir Permohonan Informasi'],
        253 => ['collection' => 'rekap_pengaduan', 'label' => 'Laporan Pelayanan Pengaduan'],
        172 => ['collection' => 'rekap_pengaduan', 'label' => 'Prosedur Pengaduan'],
        173 => ['collection' => 'rekap_pengaduan', 'label' => 'Hak Pelapor dan Terlapor'],
        251 => ['collection' => 'rekap_pengaduan', 'label' => 'Pedoman Pengaduan'],
        252 => ['collection' => 'rekap_pengaduan', 'label' => 'Alur Penanganan Pengaduan'],
        177 => ['collection' => 'hukuman_disiplin', 'label' => 'Hukuman Disiplin'],
        179 => ['collection' => 'hukuman_disiplin', 'label' => 'Tingkat dan Jenis Hukuman Disiplin'],
        89 => ['collection' => 'hukuman_disiplin', 'label' => 'Pengawasan dan Kode Etik Hakim'],
        355 => ['collection' => 'hukuman_disiplin', 'label' => 'Hakim Pengawas Bidang'],
        158 => ['collection' => 'survei', 'label' => 'Survei Kepuasan Masyarakat (SKM)'],
        165 => ['collection' => 'regulasi_sk', 'label' => 'Kebijakan & Peraturan PA Gorontalo'],
        190 => ['collection' => 'regulasi_sk', 'label' => 'Pertimbangan & Nasehat Hukum MA-RI'],
        192 => ['collection' => 'regulasi_sk', 'label' => 'Pedoman Organisasi & Administrasi'],
        193 => ['collection' => 'regulasi_sk', 'label' => 'SK Dirjen Badilag MA-RI'],
        194 => ['collection' => 'regulasi_sk', 'label' => 'SE Dirjen Badilag MA-RI'],
        195 => ['collection' => 'regulasi_sk', 'label' => 'Nota Kesepahaman Ditjen Badilag'],
        196 => ['collection' => 'regulasi_sk', 'label' => 'Peraturan Penyelesaian Ekonomi Syariah'],
        237 => ['collection' => 'regulasi_sk', 'label' => 'Uraian Tugas Pegawai / Job Desk'],
        325 => ['collection' => 'regulasi_sk', 'label' => 'Daftar Urut Kepangkatan (DUK)'],
        326 => ['collection' => 'regulasi_sk', 'label' => 'Daftar Urut Senioritas (DUS)'],
        327 => ['collection' => 'regulasi_sk', 'label' => 'Bezzeting Pegawai'],
        357 => ['collection' => 'regulasi_sk', 'label' => 'MoU Pihak Ketiga'],
        384 => ['collection' => 'regulasi_sk', 'label' => 'Hak Perempuan dan Anak Pasca Perceraian'],
    ];

    /**
     * Local static PDF files inside `D:\Project\#CMS JOOMLA\images\` guaranteed to be mapped cleanly.
     *
     * @var list<array{path: string, collection: string, title: string, year: int, month: ?int}>
     */
    private const array LOCAL_PDF_CATALOG = [
        ['path' => 'images/Reviu-IKU-Pengadilan-Agama-Gorontalo.pdf', 'collection' => 'iku', 'title' => 'Reviu Indikator Kinerja Utama (IKU) PA Gorontalo', 'year' => 2024, 'month' => null],
        ['path' => 'images/file/SK_KMA_1-144-2011-compressed.pdf', 'collection' => 'dip_ppid', 'title' => 'SK KMA Nomor 1-144/KMA/SK/I/2011 tentang Pedoman Pelayanan Informasi di Pengadilan', 'year' => 2011, 'month' => 1],
        ['path' => 'images/file/PP_NO_53_2008_ttg_biaya_salinan_informasi.pdf', 'collection' => 'biaya_informasi', 'title' => 'PP Nomor 53 Tahun 2008 tentang Jenis dan Tarif PNBP (Biaya Salinan Informasi)', 'year' => 2008, 'month' => null],
        ['path' => 'images/file/Standar_dan_Maklumat_01_2017_Pelayanan_Pengadilan.pdf', 'collection' => 'standar_pelayanan', 'title' => 'Standar dan Maklumat Pelayanan Pengadilan (01/2017)', 'year' => 2017, 'month' => 1],
        ['path' => 'images/file/Standar_Pelayanan_Pengadilan_26_KMA_SK_II_2012-compressed.pdf', 'collection' => 'standar_pelayanan', 'title' => 'SK KMA Nomor 026/KMA/SK/II/2012 tentang Standar Pelayanan Peradilan', 'year' => 2012, 'month' => 2],
        ['path' => 'images/file/Petunjuk_Pelaksanaan_Peraturan_Mahkamah_Agung_RI_Nomor_1_Tahun_2014.pdf', 'collection' => 'prodeo_posbakum', 'title' => 'Petunjuk Pelaksanaan PERMA RI Nomor 1 Tahun 2014 tentang Layanan Hukum Masyarakat Tidak Mampu', 'year' => 2014, 'month' => null],
        ['path' => 'images/file/SEMA No. 10-2010.pdf', 'collection' => 'prodeo_posbakum', 'title' => 'SEMA Nomor 10 Tahun 2010 tentang Pedoman Pemberian Bantuan Hukum', 'year' => 2010, 'month' => null],
        ['path' => 'images/file/Fikram_perma_No_9_2016_ttg_pengaduan_web.pdf', 'collection' => 'rekap_pengaduan', 'title' => 'PERMA Nomor 9 Tahun 2016 tentang Pedoman Penanganan Pengaduan (Whistleblowing System)', 'year' => 2016, 'month' => null],
        ['path' => 'images/file/KEPPH.pdf', 'collection' => 'hukuman_disiplin', 'title' => 'Kode Etik dan Pedoman Perilaku Hakim (KEPPH)', 'year' => 2009, 'month' => null],
        ['path' => 'images/file/pp53.pdf', 'collection' => 'hukuman_disiplin', 'title' => 'PP Nomor 53 Tahun 2010 tentang Disiplin Pegawai Negeri Sipil', 'year' => 2010, 'month' => null],
        ['path' => 'images/file/076_kma_sk_vi_2009.pdf', 'collection' => 'hukuman_disiplin', 'title' => 'SK KMA Nomor 076/KMA/SK/VI/2009 tentang Pedoman Pelaksanaan Penanganan Pengaduan', 'year' => 2009, 'month' => 6],
        ['path' => 'images/file/Fikram_216_kma_sk_xii_2011.pdf', 'collection' => 'hukuman_disiplin', 'title' => 'SK KMA Nomor 216/KMA/SK/XII/2011 tentang Pedoman Penanganan Pengaduan', 'year' => 2011, 'month' => 12],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/januari_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Januari 2018', 'year' => 2018, 'month' => 1],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/pebruari_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Februari 2018', 'year' => 2018, 'month' => 2],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/Maret_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Maret 2018', 'year' => 2018, 'month' => 3],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/April_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara April 2018', 'year' => 2018, 'month' => 4],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/Mei_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Mei 2018', 'year' => 2018, 'month' => 5],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/Juni_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Juni 2018', 'year' => 2018, 'month' => 6],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/Juli_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Juli 2018', 'year' => 2018, 'month' => 7],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/agustus_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Agustus 2018', 'year' => 2018, 'month' => 8],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/september_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara September 2018', 'year' => 2018, 'month' => 9],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/oktober_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Oktober 2018', 'year' => 2018, 'month' => 10],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/nopember_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara November 2018', 'year' => 2018, 'month' => 11],
        ['path' => 'images/file/LAPORAN_BIAYA_PERKARA_TAHUN_2018/Desember_2018.pdf', 'collection' => 'biaya_perkara', 'title' => 'Laporan Penggunaan Biaya Perkara Desember 2018', 'year' => 2018, 'month' => 12],
    ];

    /**
     * Legacy menu path to SatkerCMS destination route mapping (for 301 Redirects).
     *
     * @var array<string, string>
     */
    private const array MENU_REDIRECT_OVERRIDES = [
        '/tentang-pengadian/info-pengadilan/pengantar-dari-ketua-pengadilan' => '/halaman/profil/pengantar-ketua',
        '/tentang-pengadian/info-pengadilan/visi-dan-misi' => '/halaman/profil/visi-misi',
        '/tentang-pengadian/info-pengadilan/sejarah-pengadilan-2' => '/halaman/profil/tupoksi',
        '/tentang-pengadian/info-pengadilan/sejarah-pengadilan' => '/halaman/profil/sejarah-pengadilan',
        '/tentang-pengadian/info-pengadilan/so-struktur-organisasi' => '/halaman/profil/struktur-organisasi',
        '/tentang-pengadian/info-pengadilan/wilayah-yurisdiksi' => '/halaman/profil/wilayah-yurisdiksi',
        '/tentang-pengadian/info-pengadilan/alamat-pengadilan' => '/halaman/profil/alamat-kontak',
        '/tentang-pengadian/profile-pengadilan/p-ketua' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/p-wakil-ketua' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/p-hakim' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/p-kepaniteraan' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/p-kesekretariatan' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/p-pelaksana' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/upt-kesekretariatan' => '/halaman/profil/pejabat-pegawai',
        '/tentang-pengadian/profile-pengadilan/uraian-tugas-pegawai-job-desk' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/tentang-pengadian/profile-pengadilan/daftar-urut-kepangkatan' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/tentang-pengadian/profile-pengadilan/daftar-urut-senioritas' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/tentang-pengadian/profile-pengadilan/bezzeting' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/tentang-pengadian/info-program-kerja/rencana-strategis' => '/halaman/transparansi/renstra',
        '/tentang-pengadian/info-program-kerja/rencana-kerja-dan-anggaran' => '/halaman/transparansi/dipa',
        '/tentang-pengadian/info-program-kerja/program-kerja' => '/halaman/transparansi/laporan-tahunan',
        '/tentang-pengadian/info-program-kerja/agenda-kerja-satker' => '/halaman/transparansi/laporan-tahunan',
        '/tentang-pengadian/info-program-kerja/iku' => '/halaman/transparansi/iku',
        '/tentang-pengadian/info-program-kerja/rencana-kinerja-tahunan' => '/halaman/transparansi/rkt',
        '/tentang-pengadian/info-program-kerja/perjanjian-kinerja' => '/halaman/transparansi/perjanjian-kinerja',
        '/tentang-pengadian/info-program-kerja/rencana-aksi-kinerja' => '/halaman/transparansi/rencana-aksi-kinerja',
        '/tentang-pengadian/sop/s-o-p-kepaniteraan' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/tentang-pengadian/sop/s-o-p-kesekretariatan' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/tentang-pengadian/berita-kegiatan/arsip-berita' => '/publikasi',
        '/tentang-pengadian/skm' => '/halaman/layanan-publik/survei-pelayanan',
        '/layanan-publik/informasi-layanan-pengadilan/jam-kerja-kantor' => '/halaman/layanan-publik/fasilitas-dan-ptsp',
        '/layanan-publik/informasi-layanan-pengadilan/standar-pelayanan' => '/halaman/layanan-publik/standar-maklumat-pelayanan',
        '/layanan-publik/informasi-layanan-pengadilan/prosedur-persidangan' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-publik/informasi-layanan-pengadilan/tata-tertib-persidangan' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-publik/informasi-layanan-pengadilan/informasi-pelayanan-sms' => '/halaman/layanan-publik/fasilitas-dan-ptsp',
        '/layanan-publik/layanan-informasi-publik/hak-hak-pemohon-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/kategorisasi-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/prosedur-permohonan-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/tata-cara-keberatan-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/biaya-salinan-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/formulir-permohonan-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/laporan-pelayanan-informasi-publik' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/laporan-pelayanan-informasi-publik2' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/layanan-publik/layanan-informasi-publik/hak-perempuan-dan-anak-pasca-perceraian' => '/halaman/layanan-publik/hak-perempuan-dan-anak',
        '/layanan-publik/pengaduan/hak-hak-pelapor-dan-terlapor' => '/halaman/layanan-publik/pengaduan-siwas',
        '/layanan-publik/pengaduan/prosedur-pengaduan' => '/halaman/layanan-publik/pengaduan-siwas',
        '/layanan-publik/pengaduan/pedoman-pengaduan' => '/halaman/layanan-publik/pengaduan-siwas',
        '/layanan-publik/pengaduan/alur-penangangan-pengaduan' => '/halaman/layanan-publik/pengaduan-siwas',
        '/layanan-publik/pengaduan/laporan-pelayanan-pengaduan' => '/halaman/layanan-publik/rekapitulasi-pengaduan',
        '/layanan-publik/standar-maklumat-pelayanan' => '/halaman/layanan-publik/standar-maklumat-pelayanan',
        '/layanan-publik/prosedur-peringatan-dini-dan-prosedur-keadaan-darurat' => '/halaman/layanan-publik/standar-maklumat-pelayanan',
        '/layanan-publik/fasilitas-publik' => '/halaman/layanan-publik/fasilitas-dan-ptsp',
        '/layanan-publik/petugas-ptsp' => '/halaman/layanan-publik/fasilitas-dan-ptsp',
        '/layanan-publik/mou-pihak-ketiga' => '/halaman/layanan-publik/fasilitas-dan-ptsp',
        '/layanan-publik/arsip-artikel' => '/publikasi?kategori=layanan_hukum',
        '/layanan-publik/panggilan-ghaib' => '/halaman/layanan-hukum/panggilan-ghaib',
        '/layanan-publik/statistik-pegawai' => '/halaman/profil/pejabat-pegawai',
        '/layanan-hukum/hak-hak-pencari-keadilan' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/hak-hak-pokok-dalam-proses-persidangan' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/prosedur-pengajuan-dan-biaya-perkara/tingkat-pertama' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/prosedur-pengajuan-dan-biaya-perkara/tingkat-banding' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/prosedur-pengajuan-dan-biaya-perkara/tingkat-kasasi' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/prosedur-pengajuan-dan-biaya-perkara/tingkat-peninjauan-kembali' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/penyelesaian-perkara/tingkat-pertama' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/penyelesaian-perkara/tingkat-banding' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/penyelesaian-perkara/tingkat-kasasi' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/penyelesaian-perkara/peninjauan-kembali-pk' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/tahapan-proses-berperkara' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/prosedur-dan-tata-tertib-persidangan' => '/halaman/layanan-hukum/prosedur-berperkara',
        '/layanan-hukum/prosedur-pengambilan-akta-cerai' => '/halaman/layanan-hukum/akta-cerai',
        '/layanan-hukum/panduan-e-court' => '/halaman/layanan-hukum/ecourt',
        '/layanan-hukum/sk-radius-biaya-perkara' => '/halaman/layanan-hukum/biaya-berperkara',
        '/layanan-hukum/mediasi/prosedur-mediasi' => '/halaman/layanan-hukum/mediasi',
        '/layanan-hukum/mediasi/daftar-mediator' => '/halaman/layanan-hukum/mediasi',
        '/layanan-hukum/prodeo/prosedur-prodeo' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/prodeo/biaya-prodeo' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/prodeo/informasi-layanan-prodeo-elektronik' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/layanan-perkara-prodeo/prosedur' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/layanan-perkara-prodeo/biaya' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/layanan-perkara-prodeo/peraturan-dan-kebijakan' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/posbakum/pelaksana-posbakum' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/posbakum/penerima-jasa-bantuan-hukum' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/posbakum/jenis-jasa-hukum-yang-dilayani' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/posbakum/syarat-dan-mekanisme' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/posbakum/dasar-hukum-posbakum' => '/halaman/layanan-hukum/hak-pencari-keadilan',
        '/layanan-hukum/gugatan-sederhana' => '/halaman/layanan-hukum/gugatan-sederhana',
        '/layanan-hukum/pengawasan-dan-disiplin/pedoman-pengawasan' => '/halaman/layanan-publik/hukuman-disiplin',
        '/layanan-hukum/pengawasan-dan-disiplin/hukuman-disiplin' => '/halaman/layanan-publik/hukuman-disiplin',
        '/layanan-hukum/pengawasan-dan-disiplin/laporan-penyelesaian-pelanggaran' => '/halaman/layanan-publik/hukuman-disiplin',
        '/layanan-hukum/pengawasan-dan-disiplin/tingkat-dan-jenis-hukuman-disiplin' => '/halaman/layanan-publik/hukuman-disiplin',
        '/layanan-hukum/pengawasan-dan-disiplin/putusan-majelis-kehormatan-hakim-mkh' => '/halaman/layanan-publik/hukuman-disiplin',
        '/layanan-hukum/pengawasan-dan-disiplin/pengawasan-dan-kode-etik-hakim' => '/halaman/layanan-publik/hukuman-disiplin',
        '/layanan-hukum/pengawasan-dan-disiplin/hakim-pengawas-bidang' => '/halaman/layanan-publik/hukuman-disiplin',
        '/transparan/laporan/dipa-pa-gorontalo' => '/halaman/transparansi/dipa',
        '/transparan/laporan/dipa-01-dan-dipa-04' => '/halaman/transparansi/dipa',
        '/transparan/laporan/rkak-l1' => '/halaman/transparansi/dipa',
        '/transparan/laporan/lhkpn2' => '/halaman/transparansi/lhkpn',
        '/transparan/laporan/lhkasn' => '/halaman/transparansi/lhkpn',
        '/transparan/laporan/calk1' => '/halaman/transparansi/calk',
        '/transparan/laporan/neraca1' => '/halaman/transparansi/calk',
        '/transparan/laporan/laporan-tahunan' => '/halaman/transparansi/laporan-tahunan',
        '/transparan/laporan/lra1' => '/halaman/transparansi/lra',
        '/transparan/laporan/realisasi-pnbp' => '/halaman/transparansi/lra',
        '/transparan/laporan/daftar-aset-dan-inventaris' => '/halaman/transparansi/calk',
        '/transparan/laporan/laporan-pelayanan-informasi-publik1' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/transparan/laporan/laporan-tahunan-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/transparan/laporan-keuangan-perkara/laporan-biaya-perkara' => '/halaman/layanan-hukum/laporan-biaya-perkara',
        '/transparan/laporan-keuangan-perkara/laporan-keuangan1' => '/halaman/transparansi/lra',
        '/transparan/sakip/lkijp' => '/halaman/transparansi/lkjip',
        '/transparan/zona-integritas' => '/halaman/transparansi/zona-integritas',
        '/berita/kebijakan-dan-peraturan-pengadilan-agama-gorontalo' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/berita/standar-dan-maklumat-pelayanan' => '/halaman/layanan-publik/standar-maklumat-pelayanan',
        '/berita/pertimbangan-dan-nasehat-hukum-ma-ri' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/berita/pedoman-organisasi-dan-administrasi' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/berita/surat-keputusan-dirjen-badilag-ma-ri' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/berita/surat-edaran-dirjen-badilag-ma-ri' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/berita/nota-kesepahaman-ditjen-badilag' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/berita/peraturan-penyelesaian-ekonomi-syariah' => '/halaman/layanan-hukum/peraturan-kebijakan',
        '/informasi-pengadilan' => '/publikasi?kategori=pengumuman',
        '/seputar-peradilan' => '/publikasi?kategori=berita',
        '/berita-pengadilan' => '/publikasi',
        '/cara-memperoleh-informasi' => '/halaman/layanan-publik/laporan-akses-informasi',
        '/list-jadwal-sidang' => '/halaman/layanan-hukum/jadwal-sidang',
        '/penelusuran-perkara' => '/halaman/layanan-hukum/sipp',
    ];

    public function __construct(
        private readonly JoomlaSqlStreamParser $sqlParser,
        private readonly LegacyHtmlTransformer $htmlTransformer,
        private readonly ImageOptimizerService $imageOptimizer,
    ) {}

    /**
     * Extract, sanitize, optimize media, and emit the structured migration bundle into `$outputDir`.
     *
     * @param  Closure(string): void|null  $progress
     * @return array<string, mixed>
     */
    public function extract(
        string $joomlaRoot,
        string $sqlPath,
        string $outputDir,
        string $tablePrefix = 'ydfui_',
        string $disk = 'public',
        bool $dryRun = false,
        bool $skipVisits = false,
        ?Closure $progress = null,
    ): array {
        $notify = $progress ?? static function (string $msg): void {};

        if (! is_dir($joomlaRoot)) {
            throw new RuntimeException("Direktori Joomla tidak ditemukan: {$joomlaRoot}");
        }

        if (! is_file($sqlPath)) {
            throw new RuntimeException("Berkas SQL dump tidak ditemukan: {$sqlPath}");
        }

        $startedAt = microtime(true);
        $media = new LegacyMediaSanitizer($joomlaRoot, $this->imageOptimizer, $disk, $dryRun);

        $notify('1/7 Mem-parsing tabel inti Joomla secara streaming...');
        $tables = $this->sqlParser->extractTables($sqlPath, [
            $tablePrefix.'categories',
            $tablePrefix.'content',
            $tablePrefix.'content_frontpage',
            $tablePrefix.'menu',
            $tablePrefix.'modules',
        ]);

        $categoriesById = [];
        $sampleCategoryIds = [];
        foreach ($tables[$tablePrefix.'categories'] as $cat) {
            $cid = (int) ($cat['id'] ?? 0);
            $categoriesById[$cid] = $cat;
            if (str_contains(strtolower((string) ($cat['path'] ?? '')), 'sample-data')) {
                $sampleCategoryIds[$cid] = true;
            }
        }

        $articlesById = [];
        $publishedArticles = [];
        $sampleArticlesExcluded = 0;
        foreach ($tables[$tablePrefix.'content'] as $row) {
            $aid = (int) ($row['id'] ?? 0);
            $articlesById[$aid] = $row;
            if ((int) ($row['state'] ?? 0) === 1) {
                $cid = (int) ($row['catid'] ?? 0);
                if (isset($sampleCategoryIds[$cid])) {
                    $sampleArticlesExcluded++;

                    continue;
                }
                $publishedArticles[] = $row;
            }
        }

        $frontpageIds = [];
        foreach ($tables[$tablePrefix.'content_frontpage'] as $fp) {
            $frontpageIds[(int) ($fp['content_id'] ?? 0)] = true;
        }

        $modulesById = [];
        foreach ($tables[$tablePrefix.'modules'] as $mod) {
            $modulesById[(int) ($mod['id'] ?? 0)] = $mod;
        }

        $publishedMenus = array_values(array_filter(
            $tables[$tablePrefix.'menu'],
            static fn (array $m): bool => (int) ($m['published'] ?? 0) === 1 && (int) ($m['client_id'] ?? 0) === 0,
        ));

        /** @var list<string> $externalImagesSeen */
        $externalImagesSeen = [];

        $notify('2/7 Mengekstrak identitas satker & aset branding...');
        $satker = $this->buildSatkerPayload($publishedMenus, $media);

        $notify('3/7 Mengekstrak slider beranda (SP Smart Slider)...');
        $sliders = $this->buildSlidersPayload($modulesById, $articlesById, $media);

        $notify('4/7 Memproses 1.000+ publikasi & mengonversi gambar ke WebP...');
        [$posts, $postRedirects, $postExternalImages] = $this->buildPostsPayload(
            $publishedArticles,
            $frontpageIds,
            $media,
        );
        $externalImagesSeen = [...$externalImagesSeen, ...$postExternalImages];

        $notify('5/7 Menyusun konsolidasi 35 halaman SINOVA + 9 halaman ekstra & menu...');
        [$pages, $extraMenus, $pageExternalImages] = $this->buildPagesAndMenusPayload(
            $articlesById,
            $media,
        );
        $externalImagesSeen = [...$externalImagesSeen, ...$pageExternalImages];

        $notify('6/7 Mengekstrak dokumen lokal PDF + 685+ tautan Google Drive & redirect 301...');
        $documents = $this->buildDocumentsPayload(
            $articlesById,
            $publishedArticles,
            $modulesById,
            $publishedMenus,
            $media,
        );
        $redirects = $this->buildRedirectsPayload($publishedMenus, $postRedirects);

        $notify('7/7 Mengagregasi statistik kunjungan harian...');
        $dailyVisits = $skipVisits
            ? []
            : $this->sqlParser->aggregateVisitorCounterByDate($sqlPath, $tablePrefix.'zt_visitor_counter');

        $pageVisits = [];
        foreach ($dailyVisits as $date => $count) {
            $pageVisits[] = [
                'visit_date' => $date,
                'path' => '/',
                'visits_count' => $count,
            ];
        }

        // Security audit: inspect known suspicious legacy files so the report documents blocked threats
        $securityAuditBlocked = $this->auditKnownLegacyThreats($joomlaRoot, $media);

        $durationSec = round(microtime(true) - $startedAt, 2);
        $mediaStats = $media->stats();
        $externalImagesUnique = array_values(array_unique($externalImagesSeen));

        $documentsByCollection = [];
        foreach ($documents as $doc) {
            $col = (string) $doc['collection_type'];
            $documentsByCollection[$col] = ($documentsByCollection[$col] ?? 0) + 1;
        }
        ksort($documentsByCollection);

        $postsByCategory = [];
        foreach ($posts as $p) {
            $cat = (string) $p['category'];
            $postsByCategory[$cat] = ($postsByCategory[$cat] ?? 0) + 1;
        }
        ksort($postsByCategory);

        $report = [
            'generated_at' => now()->toIso8601String(),
            'dry_run' => $dryRun,
            'duration_seconds' => $durationSec,
            'source' => [
                'joomla_root' => $joomlaRoot,
                'sql_path' => $sqlPath,
                'table_prefix' => $tablePrefix,
            ],
            'counts' => [
                'satker_settings' => 1,
                'sliders_total' => count($sliders),
                'sliders_active' => count(array_filter($sliders, static fn (array $s): bool => (bool) $s['is_active'])),
                'pages_total' => count($pages),
                'extra_menus' => count($extraMenus),
                'documents_total' => count($documents),
                'documents_by_collection' => $documentsByCollection,
                'posts_total' => count($posts),
                'posts_by_category' => $postsByCategory,
                'redirects_total' => count($redirects),
                'page_visits_days' => count($pageVisits),
                'page_visits_sum' => array_sum($dailyVisits),
                'sample_articles_excluded' => $sampleArticlesExcluded,
            ],
            'media' => $mediaStats,
            'security' => [
                'rejected_files' => $media->rejected(),
                'legacy_threats_detected' => $securityAuditBlocked,
            ],
            'warnings' => [
                'missing_local_media' => $media->missing(),
                'external_images' => $externalImagesUnique,
            ],
        ];

        if (! $dryRun) {
            $this->writeBundleFiles(
                $outputDir,
                $satker,
                $sliders,
                $pages,
                $extraMenus,
                $documents,
                $posts,
                $redirects,
                $pageVisits,
                $media->manifest(),
                $report,
            );
        }

        return $report;
    }

    /**
     * @param  list<array<string, mixed>>  $publishedMenus
     * @return array<string, mixed>
     */
    private function buildSatkerPayload(array $publishedMenus, LegacyMediaSanitizer $media): array
    {
        $simtepaToken = self::DEFAULT_SIMTEPA_TOKEN;
        $simtepaStruktur = "https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/{$simtepaToken}";
        $simtepaPegawai = "https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/{$simtepaToken}";
        $sippUrl = 'https://sipp.pa-gorontalo.go.id';
        $direktoriUrl = 'https://putusan.mahkamahagung.go.id/pengadilan/pa-gorontalo/periode/register';

        foreach ($publishedMenus as $menu) {
            $paramsRaw = (string) ($menu['params'] ?? '');
            $link = (string) ($menu['link'] ?? '');
            $candidateUrl = $link;
            if (str_contains($link, 'com_wrapper') && $paramsRaw !== '') {
                $decoded = json_decode($paramsRaw, true);
                if (is_array($decoded) && is_string($decoded['url'] ?? null)) {
                    $candidateUrl = $decoded['url'];
                }
            }

            if (preg_match('/simtepa\.mahkamahagung\.go\.id\/share\/[^\/]+\/(?:html\/)?([a-f0-9]{32})/i', $candidateUrl, $m) === 1) {
                $simtepaToken = strtolower($m[1]);
                if (str_contains($candidateUrl, 'struktur_organisasi')) {
                    $simtepaStruktur = "https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/{$simtepaToken}";
                }
                if (str_contains($candidateUrl, 'profil_ketua')) {
                    $simtepaPegawai = "https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/{$simtepaToken}";
                }
            }

            if (preg_match('#^https?://sipp\.pa-gorontalo\.go\.id/?$#i', $candidateUrl) === 1) {
                $sippUrl = 'https://sipp.pa-gorontalo.go.id';
            }

            if (str_contains($candidateUrl, 'putusan.mahkamahagung.go.id/pengadilan/pa-gorontalo')) {
                $direktoriUrl = $candidateUrl;
            }
        }

        $logoPath = $media->processImage('images/front_image/mahkamah_agung.png', 'satker_logo')
            ?? $media->processImage('images/banners/logoweb.png', 'satker_logo');

        $gedungPath = $media->processImage('images/berita/gedung-kantor.jpg', 'satker_building')
            ?? $media->processImage('images/SELAMAT-DATANG-ZI.jpg', 'satker_building');

        return [
            'nama_satker' => 'Pengadilan Agama Gorontalo',
            'jenis_badan' => 'PA',
            'tingkat' => 'pertama',
            'is_tingkat_locked' => true,
            'kode_satker' => '401827',
            'tahun_berdiri' => 1957,
            'alamat_jalan' => 'Jl. Achmad Nadjamuddin No. 22',
            'desa_kelurahan' => 'Dulalowo Timur',
            'kecamatan' => 'Kota Tengah',
            'kabupaten_kota' => 'Kota Gorontalo',
            'provinsi' => 'Gorontalo',
            'kode_pos' => '96138',
            'telepon' => '(0435) 827982',
            'email' => 'surat@pa-gorontalo.go.id',
            'website_url' => 'https://pa-gorontalo.go.id',
            'logo_path' => $logoPath,
            'foto_gedung_path' => $gedungPath,
            'sipp_url' => $sippUrl,
            'ecourt_url' => 'https://ecourt.mahkamahagung.go.id',
            'direktori_putusan_url' => $direktoriUrl,
            'siwas_url' => 'https://siwas.mahkamahagung.go.id',
            'simtepa_pegawai_url' => $simtepaPegawai,
            'simtepa_struktur_url' => $simtepaStruktur,
            'skm_url' => null,
            'spak_url' => null,
            'is_setup_completed' => true,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $modulesById
     * @param  array<int, array<string, mixed>>  $articlesById
     * @return list<array<string, mixed>>
     */
    private function buildSlidersPayload(
        array $modulesById,
        array $articlesById,
        LegacyMediaSanitizer $media,
    ): array {
        $sliderMod = $modulesById[111] ?? null;
        if ($sliderMod === null) {
            foreach ($modulesById as $mod) {
                if (($mod['module'] ?? '') === 'mod_sp_smart_slider') {
                    $sliderMod = $mod;

                    break;
                }
            }
        }

        if ($sliderMod === null) {
            return [];
        }

        $params = json_decode((string) ($sliderMod['params'] ?? ''), true);
        $textData = is_array($params) ? ($params['sliders']['text'] ?? null) : null;
        if (! is_array($textData)) {
            return [];
        }

        $titles = is_array($textData['title'] ?? null) ? $textData['title'] : [];
        $pretitles = is_array($textData['pretitle'] ?? null) ? $textData['pretitle'] : [];
        $images = is_array($textData['image'] ?? null) ? $textData['image'] : [];
        $links = is_array($textData['link'] ?? null) ? $textData['link'] : [];
        $states = is_array($textData['state'] ?? null) ? $textData['state'] : [];

        $slides = [];
        $activeAssigned = 0;

        foreach ($titles as $idx => $rawTitle) {
            $title = trim((string) $rawTitle);
            $rawImg = trim((string) ($images[$idx] ?? ''));
            if ($title === '' && $rawImg === '') {
                continue;
            }

            $storedImg = $rawImg !== '' ? $media->processImage($rawImg, 'slider') : null;
            $subtitle = trim((string) ($pretitles[$idx] ?? ''));
            $rawLink = trim((string) ($links[$idx] ?? ''));
            $ctaUrl = $this->normalizeSliderCtaUrl($rawLink, $articlesById);

            $wasPublished = strtolower((string) ($states[$idx] ?? '')) === 'published';
            $isActive = $wasPublished && $activeAssigned < HomeSlider::MAX_ACTIVE_SLIDES;
            if ($isActive) {
                $activeAssigned++;
            }

            $slides[] = [
                'title' => Str::limit($title !== '' ? $title : 'Informasi Pengadilan Agama Gorontalo', 240, ''),
                'subtitle' => $subtitle !== '' ? Str::limit($subtitle, 240, '...') : null,
                'image_path' => $storedImg,
                'image_alt' => Str::limit('Banner '.$title.' - Pengadilan Agama Gorontalo', 240, ''),
                'cta_label' => $ctaUrl !== null ? 'Selengkapnya' : null,
                'cta_url' => $ctaUrl,
                'sort_order' => count($slides) + 1,
                'is_active' => $isActive,
            ];
        }

        return $slides;
    }

    /**
     * @param  list<array<string, mixed>>  $publishedArticles
     * @param  array<int, bool>  $frontpageIds
     * @return array{0: list<array<string, mixed>>, 1: array<string, string>, 2: list<string>}
     */
    private function buildPostsPayload(
        array $publishedArticles,
        array $frontpageIds,
        LegacyMediaSanitizer $media,
    ): array {
        $posts = [];
        $postRedirects = [];
        $externalImages = [];
        $usedSlugs = [];

        foreach ($publishedArticles as $row) {
            $catId = (int) ($row['catid'] ?? 0);
            if (! array_key_exists($catId, self::POST_CATEGORY_MAP)) {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            $title = trim(html_entity_decode((string) ($row['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($title === '') {
                continue;
            }
            $title = IndonesianTitleNormalizer::normalizeIfAllCaps($title);

            $rawHtml = trim((string) ($row['introtext'] ?? ''))."\n".trim((string) ($row['fulltext'] ?? ''));
            $category = self::POST_CATEGORY_MAP[$catId];

            $rawAlias = trim((string) ($row['alias'] ?? ''));
            $baseSlug = Str::slug($rawAlias !== '' ? $rawAlias : $title);
            $baseSlug = $baseSlug !== '' ? substr($baseSlug, 0, 210) : "publikasi-{$id}";
            $slug = $baseSlug;
            if (isset($usedSlugs[$slug])) {
                $slug = substr($baseSlug, 0, 200).'-'.$id;
            }
            $usedSlugs[$slug] = true;

            // Resolve featured image (used for card thumbnails & OpenGraph): JSON image_intro/fulltext -> first local <img> in body
            $featuredSrc = null;
            $imagesJson = json_decode((string) ($row['images'] ?? ''), true);
            if (is_array($imagesJson)) {
                $cand = trim((string) ($imagesJson['image_intro'] ?? $imagesJson['image_fulltext'] ?? ''));
                if ($cand !== '') {
                    $featuredSrc = $cand;
                }
            }

            if ($featuredSrc === null) {
                $firstImg = $this->htmlTransformer->extractFirstLocalImageSrc($rawHtml);
                if ($firstImg !== null) {
                    $featuredSrc = $firstImg;
                }
            }

            $featuredPath = $featuredSrc !== null
                ? $media->processImage($featuredSrc, 'post_featured')
                : null;

            $cleaned = $this->htmlTransformer->cleanRichHtml(
                $rawHtml,
                $media,
                $title,
                null,
                $category === 'pengumuman' ? 'panggilan_ghaib' : 'regulasi_sk',
            );

            $externalImages = [...$externalImages, ...$cleaned['external_images']];

            $bodyHtml = $cleaned['html'];
            if ($bodyHtml === '') {
                $bodyHtml = '<p>'.e($title).'</p>';
            }

            $createdAt = $this->normalizeTimestamp((string) ($row['created'] ?? ''));
            $publishUp = $this->normalizeTimestamp((string) ($row['publish_up'] ?? '')) ?? $createdAt;
            $modifiedAt = $this->normalizeTimestamp((string) ($row['modified'] ?? '')) ?? $createdAt;

            $publishTimestamp = $publishUp !== null ? strtotime($publishUp) : false;
            $isRecentFrontpage = isset($frontpageIds[$id])
                && ($publishTimestamp === false || $publishTimestamp >= now()->subYear()->timestamp);

            $plainBodyLength = mb_strlen(trim((string) preg_replace(
                '/[\s\xC2\xA0\p{Z}]+/u',
                ' ',
                html_entity_decode(strip_tags($cleaned['html']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            )));
            $isEmptyPlaceholder = $slug === 'kegiatan-pengadilan'
                || ($featuredPath === null && ! str_contains($cleaned['html'], '<img') && $plainBodyLength < 20);

            $posts[] = [
                'legacy_id' => $id,
                'title' => Str::limit($title, 250, '...'),
                'slug' => $slug,
                'category' => $category,
                'excerpt' => $this->htmlTransformer->extractExcerpt($bodyHtml, 220),
                'body' => $bodyHtml,
                'featured_image_path' => $featuredPath,
                'featured_image_alt' => $featuredPath !== null ? Str::limit($title, 240, '') : null,
                'is_pinned' => $isRecentFrontpage,
                'is_published' => ! $isEmptyPlaceholder,
                'views_count' => max(0, (int) ($row['hits'] ?? 0)),
                'comments_enabled' => true,
                'published_at' => $publishUp,
                'created_at' => $createdAt,
                'updated_at' => $modifiedAt,
            ];

            // Record legacy Joomla article URL -> new /publikasi/{slug} 301 redirect
            if ($rawAlias !== '') {
                $legacyPrefix = $catId === 114 ? '/informasi-pengadilan/' : '/tentang-pengadian/berita-kegiatan/arsip-berita/';
                $fromPath = $legacyPrefix.$id.'-'.$rawAlias;
                if (strlen($fromPath) <= 250) {
                    $postRedirects[$fromPath] = '/publikasi/'.$slug;
                }
            }
        }

        return [$posts, $postRedirects, $externalImages];
    }

    /**
     * Build consolidated SINOVA pages (injected with PA Gorontalo content) + 9 high-value extra PA Gorontalo pages.
     *
     * @param  array<int, array<string, mixed>>  $articlesById
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>, 2: list<string>}
     */
    private function buildPagesAndMenusPayload(array $articlesById, LegacyMediaSanitizer $media): array
    {
        $externalImages = [];

        $makeRichtextBlock = function (int $articleId, ?string $headingOverride = null, string $pdfCol = 'regulasi_sk') use ($articlesById, $media, &$externalImages): ?array {
            $art = $articlesById[$articleId] ?? null;
            if ($art === null) {
                return null;
            }

            $title = trim(html_entity_decode((string) ($art['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $heading = $headingOverride ?? ($title !== '' ? $title : 'Informasi');
            $raw = trim((string) ($art['introtext'] ?? ''))."\n".trim((string) ($art['fulltext'] ?? ''));
            $cleaned = $this->htmlTransformer->cleanRichHtml($raw, $media, $heading, null, $pdfCol);
            $externalImages = [...$externalImages, ...$cleaned['external_images']];

            if (trim($cleaned['html']) === '') {
                return null;
            }

            return [
                'type' => 'richtext',
                'heading' => $heading,
                'content' => $cleaned['html'],
            ];
        };

        $compactBlocks = static fn (array $blocks): array => array_values(array_filter($blocks, static fn (?array $b): bool => $b !== null));

        $pages = [
            // === SINOVA STANDARD PAGES (Content Injection, preserving criteria_code & SINOVA blocks) ===
            [
                'slug' => 'profil/visi-misi',
                'title' => 'Visi dan Misi {{satker.nama}}',
                'criteria_code' => '1.b.p.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(74, 'Visi dan Misi Pengadilan Agama Gorontalo'),
                ]),
            ],
            [
                'slug' => 'profil/tupoksi',
                'title' => 'Tugas Pokok dan Fungsi',
                'criteria_code' => '1.b.p.2',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(75, 'Tugas Pokok dan Fungsi Pengadilan Agama Gorontalo'),
                ]),
            ],
            [
                'slug' => 'profil/wilayah-yurisdiksi',
                'title' => 'Wilayah Yurisdiksi {{satker.nama}}',
                'criteria_code' => '1.b.p.3',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(80, 'Daftar Kecamatan dan Kelurahan Wilayah Yurisdiksi'),
                    [
                        'type' => 'map_yurisdiksi',
                        'heading' => 'Peta Lokasi & Wilayah Yurisdiksi PA Gorontalo',
                        'latitude' => '0.5586632',
                        'longitude' => '123.0569467',
                        'zoom' => 13,
                    ],
                ]),
            ],
            [
                'slug' => 'profil/alamat-kontak',
                'title' => 'Alamat dan Kontak Pengadilan',
                'criteria_code' => '1.b.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    ['type' => 'satker_contact', 'heading' => 'Identitas, Alamat Lengkap, dan Foto Gedung {{satker.nama}}'],
                    $makeRichtextBlock(79, 'Informasi Alamat & Lokasi Kantor'),
                ]),
            ],
            [
                'slug' => 'profil/pejabat-pegawai',
                'title' => 'Profil Pejabat dan Pegawai',
                'criteria_code' => '1.b.3.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    [
                        'type' => 'embed_fallback',
                        'source_key' => 'simtepa_pegawai',
                        'fallback_mode' => 'officials_table',
                        'heading' => 'Daftar Profil Pejabat, Hakim, dan Pegawai (SIMTEPA)',
                        'note' => 'Data kepegawaian terintegrasi langsung dengan SIMTEPA Mahkamah Agung RI.',
                    ],
                    $makeRichtextBlock(358, 'UPT Kesekretariatan'),
                ]),
            ],
            [
                'slug' => 'layanan-hukum/ecourt',
                'title' => 'Layanan e-Court Mahkamah Agung RI',
                'criteria_code' => '1.b.2.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal e-Court Mahkamah Agung RI',
                        'url_source' => 'ecourt_url',
                        'default_url' => 'https://ecourt.mahkamahagung.go.id',
                        'description' => 'Layanan pendaftaran perkara (e-Filing), taksiran panjar dan pembayaran (e-Payment), pemanggilan (e-Summons), serta persidangan secara elektronik (e-Litigation).',
                    ],
                    $makeRichtextBlock(810, 'Panduan Penggunaan e-Court PA Gorontalo', 'prosedur_sop'),
                ]),
            ],
            [
                'slug' => 'layanan-hukum/prosedur-berperkara',
                'title' => 'Prosedur Berperkara',
                'criteria_code' => '1.b.2.3',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(260, 'Tahapan Proses Berperkara', 'prosedur_sop'),
                    $makeRichtextBlock(1179, 'Prosedur Pengajuan Tingkat Pertama', 'prosedur_sop'),
                    $makeRichtextBlock(166, 'Prosedur Pengajuan Tingkat Banding', 'prosedur_sop'),
                    $makeRichtextBlock(168, 'Prosedur Pengajuan Tingkat Kasasi', 'prosedur_sop'),
                    $makeRichtextBlock(169, 'Prosedur Peninjauan Kembali (PK)', 'prosedur_sop'),
                    $makeRichtextBlock(182, 'Penyelesaian Perkara Tingkat Pertama', 'prosedur_sop'),
                    $makeRichtextBlock(242, 'Prosedur Persidangan', 'prosedur_sop'),
                    $makeRichtextBlock(92, 'Tata Tertib Persidangan', 'prosedur_sop'),
                    [
                        'type' => 'document_list',
                        'collection_type' => 'prosedur_sop',
                        'heading' => 'Dokumen SOP Kepaniteraan, Kesekretariatan & Panduan Berperkara',
                    ],
                ]),
            ],
            [
                'slug' => 'layanan-hukum/biaya-berperkara',
                'title' => 'Biaya Proses Berperkara',
                'criteria_code' => '1.b.2.4',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(315, 'SK Panjar Biaya Perkara & Radius Panggilan PA Gorontalo', 'biaya_radius'),
                    [
                        'type' => 'document_list',
                        'collection_type' => 'biaya_radius',
                        'heading' => 'Daftar SK Panjar Biaya Perkara dan Tabel Radius Panggilan Resmi',
                    ],
                ]),
            ],
            [
                'slug' => 'layanan-hukum/laporan-biaya-perkara',
                'title' => 'Laporan Penggunaan Biaya Perkara',
                'criteria_code' => '1.a.2.5',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - Laporan Biaya Perkara Terkini',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/12',
                        'description' => 'Akses data laporan keuangan biaya perkara terbaru melalui portal SIPETA Pengadilan Agama Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'biaya_perkara', 'heading' => 'Arsip Dokumen Penggunaan Biaya Perkara'],
                ],
            ],
            [
                'slug' => 'layanan-hukum/hak-pencari-keadilan',
                'title' => 'Hak Pencari Keadilan (Prodeo & Posbakum)',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(259, 'Hak-Hak Para Pihak Pencari Keadilan', 'prodeo_posbakum'),
                    $makeRichtextBlock(339, 'Hak-Hak Pokok dalam Proses Persidangan', 'prodeo_posbakum'),
                    $makeRichtextBlock(342, 'Prosedur Layanan Pembebasan Biaya Perkara (Prodeo)', 'prodeo_posbakum'),
                    $makeRichtextBlock(343, 'Biaya Layanan Prodeo', 'prodeo_posbakum'),
                    $makeRichtextBlock(783, 'Informasi & Layanan Prodeo Secara Elektronik', 'prodeo_posbakum'),
                    $makeRichtextBlock(345, 'Pelaksana Pos Bantuan Hukum (Posbakum)', 'prodeo_posbakum'),
                    $makeRichtextBlock(346, 'Penerima & Jenis Jasa Layanan Posbakum', 'prodeo_posbakum'),
                    $makeRichtextBlock(344, 'Dasar Hukum Posbakum', 'prodeo_posbakum'),
                    ['type' => 'document_list', 'collection_type' => 'prodeo_posbakum', 'heading' => 'Dokumen SK, Peraturan & Panduan Prodeo / Posbakum'],
                    ['type' => 'article_list', 'post_category' => 'layanan_hukum', 'heading' => 'Artikel & Informasi Layanan Hukum'],
                ]),
            ],
            [
                'slug' => 'layanan-publik/laporan-akses-informasi',
                'title' => 'Layanan Informasi Publik (PPID), DIP, SOP & Laporan Akses',
                'criteria_code' => '1.a.4.1.1',
                'category_tag' => 'setiap_saat',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - Laporan Layanan Informasi Publik',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/9',
                        'description' => 'Rekapitulasi laporan layanan informasi publik terkini pada portal SIPETA Pengadilan Agama Gorontalo.',
                    ],
                    $makeRichtextBlock(247, 'Hak-Hak Pemohon Informasi', 'dip_ppid'),
                    $makeRichtextBlock(95, 'Kategorisasi Informasi Publik', 'dip_ppid'),
                    $makeRichtextBlock(96, 'Prosedur Permohonan Informasi', 'dip_ppid'),
                    $makeRichtextBlock(174, 'Tata Cara Pengajuan Keberatan Informasi', 'dip_ppid'),
                    $makeRichtextBlock(249, 'Standar Biaya Salinan Informasi', 'biaya_informasi'),
                    ['type' => 'document_list', 'collection_type' => 'akses_informasi', 'heading' => 'Laporan Layanan Akses Informasi Publik (PPID)'],
                    ['type' => 'document_list', 'collection_type' => 'dip_ppid', 'heading' => 'SK PPID, Daftar Informasi Publik (DIP) & Regulasi PPID'],
                    ['type' => 'document_list', 'collection_type' => 'biaya_informasi', 'heading' => 'Biaya Salinan & Formulir Permohonan Informasi'],
                ]),
            ],
            [
                'slug' => 'layanan-publik/pengaduan-siwas',
                'title' => 'Prosedur Pengaduan, Hak Pelapor & SIWAS MARI',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    [
                        'type' => 'external_portal',
                        'heading' => 'Sistem Informasi Pengawasan Mahkamah Agung (SIWAS)',
                        'url_source' => 'siwas_url',
                        'default_url' => 'https://siwas.mahkamahagung.go.id',
                        'description' => 'Sampaikan pengaduan dugaan pelanggaran perilaku hakim atau aparatur pengadilan secara daring melalui aplikasi resmi SIWAS Mahkamah Agung RI.',
                    ],
                    $makeRichtextBlock(173, 'Hak-Hak Pelapor dan Terlapor', 'rekap_pengaduan'),
                    $makeRichtextBlock(172, 'Prosedur Pengaduan', 'rekap_pengaduan'),
                    $makeRichtextBlock(252, 'Alur Penanganan Pengaduan', 'rekap_pengaduan'),
                    ['type' => 'document_list', 'collection_type' => 'regulasi_sk', 'heading' => 'SK & Regulasi Pengaduan, Website, dan Tata Kelola'],
                ]),
            ],
            [
                'slug' => 'layanan-publik/hukuman-disiplin',
                'title' => 'Hukuman Disiplin Hasil Pengawasan',
                'criteria_code' => '1.a.4.3.1',
                'category_tag' => 'setiap_saat',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(164, 'Pedoman Pengawasan', 'hukuman_disiplin'),
                    $makeRichtextBlock(177, 'Hukuman Disiplin', 'hukuman_disiplin'),
                    $makeRichtextBlock(178, 'Laporan Penyelesaian Pelanggaran', 'hukuman_disiplin'),
                    $makeRichtextBlock(179, 'Tingkat dan Jenis Hukuman Disiplin', 'hukuman_disiplin'),
                    $makeRichtextBlock(180, 'Putusan Majelis Kehormatan Hakim (MKH)', 'hukuman_disiplin'),
                    ['type' => 'document_list', 'collection_type' => 'hukuman_disiplin', 'heading' => 'Dokumen Pengawasan, Kode Etik & Hukuman Disiplin'],
                ]),
            ],
            [
                'slug' => 'layanan-publik/standar-maklumat-pelayanan',
                'title' => 'Standar dan Maklumat Pelayanan Pengadilan',
                'criteria_code' => '1.b.4.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(254, 'Maklumat Pelayanan Pengadilan Agama Gorontalo', 'standar_pelayanan'),
                    $makeRichtextBlock(246, 'Standar Pelayanan Peradilan', 'standar_pelayanan'),
                    $makeRichtextBlock(324, 'Prosedur Peringatan Dini dan Evakuasi Keadaan Darurat', 'sop_evakuasi'),
                    ['type' => 'document_list', 'collection_type' => 'standar_pelayanan', 'heading' => 'Dokumen SK Standar Pelayanan & Maklumat'],
                    ['type' => 'document_list', 'collection_type' => 'sop_evakuasi', 'heading' => 'Dokumen SOP Evakuasi Keadaan Darurat'],
                ]),
            ],
            // === TRANSPARANCY PAGES WITH SIPETA EXTERNAL PORTALS + LOCAL DOCUMENT LIST ===
            [
                'slug' => 'transparansi/lhkpn',
                'title' => 'Laporan Harta Kekayaan Penyelenggara Negara (LHKPN)',
                'criteria_code' => '1.a.3.1',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - LHKPN & LHKASN Terkini',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/1',
                        'description' => 'Pantau publikasi LHKPN pejabat terbaru pada portal SIPETA PA Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'lhkpn', 'heading' => 'Arsip Dokumen LHKPN & LHKASN'],
                ],
            ],
            [
                'slug' => 'transparansi/calk',
                'title' => 'Catatan Atas Laporan Keuangan (CALK) & Daftar Aset BMN',
                'criteria_code' => '1.a.3.4',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - CALK, Neraca & Aset BMN',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/2',
                        'description' => 'Akses laporan CALK, Neraca, dan Daftar Aset BMN terkini melalui portal SIPETA PA Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'calk', 'heading' => 'Dokumen Catatan Atas Laporan Keuangan (CALK) & Neraca'],
                    ['type' => 'document_list', 'collection_type' => 'aset_bmn', 'heading' => 'Ringkasan Daftar Aset dan Inventaris (BMN)'],
                ],
            ],
            [
                'slug' => 'transparansi/laporan-tahunan',
                'title' => 'Laporan Pelaksanaan Kegiatan / Laporan Tahunan',
                'criteria_code' => '1.a.1.2',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - Laporan Tahunan',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/6',
                        'description' => 'Akses arsip Laporan Tahunan terbaru melalui portal SIPETA Pengadilan Agama Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'laporan_tahunan', 'heading' => 'Daftar Dokumen Laporan Tahunan, Program & Agenda Kerja'],
                ],
            ],
            [
                'slug' => 'transparansi/lkjip',
                'title' => 'Laporan Kinerja Instansi Pemerintah (LKjIP)',
                'criteria_code' => '1.a.3.11',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - LKjIP Terkini',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/14',
                        'description' => 'Akses Laporan Kinerja Instansi Pemerintah (LKjIP) terbaru pada portal SIPETA PA Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'lkjip', 'heading' => 'Arsip Dokumen LKjIP / LAKIP'],
                ],
            ],
            [
                'slug' => 'transparansi/dipa',
                'title' => 'Daftar Isian Pelaksanaan Anggaran (DIPA)',
                'criteria_code' => '1.a.3.2',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - RKAK-L & DIPA',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/5',
                        'description' => 'Akses RKAK-L dan dokumen anggaran terbaru pada portal SIPETA PA Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'dipa', 'heading' => 'DIPA 01, DIPA 04, dan RKA-KL PA Gorontalo'],
                ],
            ],
            [
                'slug' => 'transparansi/lra',
                'title' => 'Laporan Realisasi Anggaran (LRA)',
                'criteria_code' => '1.a.3.3',
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => [
                    [
                        'type' => 'external_portal',
                        'heading' => 'Portal SIPETA - Laporan Keuangan',
                        'url_source' => '',
                        'default_url' => 'https://sipeta.pa-gorontalo.go.id/informasi/13',
                        'description' => 'Akses rekapitulasi Laporan Keuangan terkini pada portal SIPETA PA Gorontalo.',
                    ],
                    ['type' => 'document_list', 'collection_type' => 'lra', 'heading' => 'Dokumen Laporan Realisasi Anggaran (LRA) & PNBP'],
                ],
            ],

            // === 9 EXTRA HIGH-VALUE PA GORONTALO PAGES ===
            [
                'slug' => 'profil/pengantar-ketua',
                'title' => 'Pengantar Ketua Pengadilan Agama Gorontalo',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(73, 'Sambutan & Pengantar Ketua Pengadilan Agama Gorontalo'),
                ]),
            ],
            [
                'slug' => 'profil/sejarah-pengadilan',
                'title' => 'Sejarah Pengadilan Agama Gorontalo',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(236, 'Sejarah Berdirinya Pengadilan Agama Gorontalo'),
                ]),
            ],
            [
                'slug' => 'layanan-hukum/mediasi',
                'title' => 'Layanan Mediasi & Daftar Mediator',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(340, 'Prosedur Mediasi di Pengadilan Agama Gorontalo'),
                    $makeRichtextBlock(341, 'Daftar Mediator PA Gorontalo'),
                ]),
            ],
            [
                'slug' => 'layanan-hukum/gugatan-sederhana',
                'title' => 'Gugatan Sederhana (Small Claim Court)',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(1030, 'Informasi & Dokumen Gugatan Sederhana', 'prosedur_sop'),
                ]),
            ],
            [
                'slug' => 'layanan-hukum/akta-cerai',
                'title' => 'Prosedur Pengambilan Akta Cerai',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(186, 'Persyaratan dan Prosedur Pengambilan Akta Cerai', 'prosedur_sop'),
                ]),
            ],
            [
                'slug' => 'layanan-hukum/peraturan-kebijakan',
                'title' => 'Peraturan, Kebijakan & Pedoman Peradilan',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(165, 'Kebijakan dan Peraturan Pengadilan Agama Gorontalo', 'regulasi_sk'),
                    ['type' => 'document_list', 'collection_type' => 'regulasi_sk', 'heading' => 'Daftar Peraturan, SK/SE Badilag, Ekonomi Syariah & Kepegawaian'],
                ]),
            ],
            [
                'slug' => 'transparansi/zona-integritas',
                'title' => 'Pembangunan Zona Integritas (WBK / WBBM)',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(373, 'Lembar Kerja Evaluasi (LKE) PMPZI Pengadilan Agama Gorontalo', 'regulasi_sk'),
                ]),
            ],
            [
                'slug' => 'layanan-publik/fasilitas-dan-ptsp',
                'title' => 'Jam Layanan, Fasilitas Publik & PTSP',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(91, 'Jam Kerja & Pelayanan Publik'),
                    $makeRichtextBlock(354, 'Petugas Pelayanan Terpadu Satu Pintu (PTSP)'),
                    $makeRichtextBlock(353, 'Fasilitas Publik Pengadilan Agama Gorontalo'),
                    $makeRichtextBlock(93, 'Informasi Pelayanan SMS & Daring'),
                    $makeRichtextBlock(357, 'Nota Kesepahaman (MoU) Pihak Ketiga', 'regulasi_sk'),
                ]),
            ],
            [
                'slug' => 'layanan-publik/hak-perempuan-dan-anak',
                'title' => 'Hak Perempuan dan Anak Pasca Perceraian',
                'criteria_code' => null,
                'category_tag' => 'berkala',
                'tingkat_scope' => 'all',
                'blocks' => $compactBlocks([
                    $makeRichtextBlock(384, 'Perlindungan Hak Perempuan dan Anak Pasca Perceraian', 'regulasi_sk'),
                ]),
            ],
        ];

        // Drop any page whose blocks ended up empty (e.g. when running against a tiny test SQL fixture)
        $pages = array_values(array_filter(
            $pages,
            static fn (array $page): bool => $page['blocks'] !== [],
        ));

        $extraMenus = [
            ['parent_title' => 'Profil Pengadilan', 'title' => 'Pengantar Ketua', 'slug' => 'profil/pengantar-ketua', 'sort_order' => 10],
            ['parent_title' => 'Profil Pengadilan', 'title' => 'Sejarah Pengadilan', 'slug' => 'profil/sejarah-pengadilan', 'sort_order' => 11],
            ['parent_title' => 'Layanan Hukum', 'title' => 'Mediasi & Daftar Mediator', 'slug' => 'layanan-hukum/mediasi', 'sort_order' => 15],
            ['parent_title' => 'Layanan Hukum', 'title' => 'Gugatan Sederhana', 'slug' => 'layanan-hukum/gugatan-sederhana', 'sort_order' => 16],
            ['parent_title' => 'Layanan Hukum', 'title' => 'Prosedur Akta Cerai', 'slug' => 'layanan-hukum/akta-cerai', 'sort_order' => 17],
            ['parent_title' => 'Layanan Hukum', 'title' => 'Peraturan & Kebijakan', 'slug' => 'layanan-hukum/peraturan-kebijakan', 'sort_order' => 18],
            ['parent_title' => 'Transparansi', 'title' => 'Zona Integritas (ZI)', 'slug' => 'transparansi/zona-integritas', 'sort_order' => 20],
            ['parent_title' => 'Layanan Publik', 'title' => 'Jam Layanan, Fasilitas & PTSP', 'slug' => 'layanan-publik/fasilitas-dan-ptsp', 'sort_order' => 15],
            ['parent_title' => 'Layanan Publik', 'title' => 'Hak Perempuan & Anak', 'slug' => 'layanan-publik/hak-perempuan-dan-anak', 'sort_order' => 16],
        ];

        return [$pages, $extraMenus, $externalImages];
    }

    /**
     * Extract and deduplicate all structured documents from articles, modules, menus, and verified local PDFs.
     *
     * @param  array<int, array<string, mixed>>  $articlesById
     * @param  list<array<string, mixed>>  $publishedArticles
     * @param  array<int, array<string, mixed>>  $modulesById
     * @param  list<array<string, mixed>>  $publishedMenus
     * @return list<array<string, mixed>>
     */
    private function buildDocumentsPayload(
        array $articlesById,
        array $publishedArticles,
        array $modulesById,
        array $publishedMenus,
        LegacyMediaSanitizer $media,
    ): array {
        $allDocs = [];
        $seen = [];

        $pushDocs = static function (array $items) use (&$allDocs, &$seen): void {
            foreach ($items as $item) {
                $key = $item['collection_type'].'|'.($item['file_path'] ?? $item['external_url'] ?? '');
                if ($key === $item['collection_type'].'|' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $allDocs[] = $item;
            }
        };

        // 1. Verified local PDFs from catalog
        foreach (self::LOCAL_PDF_CATALOG as $pdfDef) {
            $stored = $media->processLocalPdf($pdfDef['path'], $pdfDef['collection']);
            if ($stored === null) {
                continue;
            }

            $pushDocs([[
                'collection_type' => $pdfDef['collection'],
                'title' => $pdfDef['title'],
                'year' => $pdfDef['year'],
                'month' => $pdfDef['month'],
                'status_label' => 'PDF Lokal',
                'file_path' => $stored,
                'external_url' => null,
                'metadata' => ['sumber' => 'Arsip PDF Resmi PA Gorontalo'],
                'is_published' => true,
                'created_at' => sprintf('%04d-%02d-01 00:00:00', $pdfDef['year'], $pdfDef['month'] ?? 1),
            ]]);
        }

        // 2. Structured document articles
        foreach (self::DOCUMENT_ARTICLE_MAP as $aid => $meta) {
            $art = $articlesById[$aid] ?? null;
            if ($art === null) {
                continue;
            }

            $rawHtml = trim((string) ($art['introtext'] ?? ''))."\n".trim((string) ($art['fulltext'] ?? ''));
            $createdAt = $this->normalizeTimestamp((string) ($art['created'] ?? ''));
            $fallbackYear = $createdAt !== null ? (int) substr($createdAt, 0, 4) : (int) date('Y');

            $extracted = $this->htmlTransformer->extractDocumentsFromHtml(
                $rawHtml,
                $meta['collection'],
                $meta['label'],
                $fallbackYear,
                $media,
                $createdAt,
            );
            $pushDocs($extracted);
        }

        // 3. Module 153: Informasi Perkara Ghaib (Sourcerer accordion table with ~48-69 GDrive relaas links)
        $modGhaib = $modulesById[153] ?? null;
        if ($modGhaib !== null) {
            $extractedGhaib = $this->htmlTransformer->extractDocumentsFromHtml(
                (string) ($modGhaib['content'] ?? ''),
                'panggilan_ghaib',
                'Panggilan Ghaib',
                (int) date('Y'),
                $media,
                now()->format('Y-m-d H:i:s'),
            );
            $pushDocs($extractedGhaib);
        }

        // 4. Panggilan Ghaib announcements inside catid=114 (Informasi Pengadilan)
        foreach ($publishedArticles as $art) {
            if ((int) ($art['catid'] ?? 0) !== 114) {
                continue;
            }
            $title = trim((string) ($art['title'] ?? ''));
            if (! preg_match('/panggilan\s+(?:ghaib|ghoib|sidang)/i', $title)) {
                continue;
            }
            $rawHtml = trim((string) ($art['introtext'] ?? ''))."\n".trim((string) ($art['fulltext'] ?? ''));
            $createdAt = $this->normalizeTimestamp((string) ($art['created'] ?? ''));
            $fallbackYear = $createdAt !== null ? (int) substr($createdAt, 0, 4) : (int) date('Y');
            $extracted = $this->htmlTransformer->extractDocumentsFromHtml(
                $rawHtml,
                'panggilan_ghaib',
                Str::limit($title, 120, ''),
                $fallbackYear,
                $media,
                $createdAt,
            );
            $pushDocs($extracted);
        }

        // 5. Direct Google Drive links attached directly to Joomla menu items (SK PPID, PBJ, SK/SOP Website)
        foreach ($publishedMenus as $menu) {
            $link = trim((string) ($menu['link'] ?? ''));
            if (! str_contains(strtolower($link), 'drive.google.com')) {
                continue;
            }

            $canonical = $this->htmlTransformer->canonicalizeExternalDocUrl($link);
            if ($canonical === null) {
                continue;
            }

            $mTitle = trim((string) ($menu['title'] ?? 'Dokumen Resmi'));
            $mPath = strtolower((string) ($menu['path'] ?? ''));
            $collection = match (true) {
                str_contains($mPath, 'lelang') || str_contains(strtolower($mTitle), 'lelang') => 'pbj',
                str_contains($mPath, 'ppid') || str_contains(strtolower($mTitle), 'ppid') => 'dip_ppid',
                default => 'regulasi_sk',
            };

            $year = preg_match('/\b(20[12]\d)\b/', $mPath.' '.$mTitle, $ym) === 1
                ? (int) $ym[1]
                : (int) date('Y');

            $pushDocs([[
                'collection_type' => $collection,
                'title' => Str::limit($mTitle, 240, ''),
                'year' => $year,
                'month' => null,
                'status_label' => 'SK Resmi',
                'file_path' => null,
                'external_url' => $canonical,
                'metadata' => ['sumber' => 'Menu Resmi PA Gorontalo'],
                'is_published' => true,
                'created_at' => sprintf('%04d-01-01 00:00:00', $year),
            ]]);
        }

        return $allDocs;
    }

    /**
     * @param  list<array<string, mixed>>  $publishedMenus
     * @param  array<string, string>  $postRedirects
     * @return list<array{from_path: string, to_path: string, status_code: int}>
     */
    private function buildRedirectsPayload(array $publishedMenus, array $postRedirects): array
    {
        $map = self::MENU_REDIRECT_OVERRIDES;

        foreach ($publishedMenus as $menu) {
            $rawPath = '/'.ltrim(trim((string) ($menu['path'] ?? '')), '/');
            if ($rawPath === '/' || $rawPath === '/homepage' || strlen($rawPath) > 250) {
                continue;
            }

            if (isset($map[$rawPath])) {
                continue;
            }

            // Fallback mapping for any unmapped menu path by top-level legacy section
            if (str_starts_with($rawPath, '/tentang-pengadian/')) {
                $map[$rawPath] = '/halaman/profil/visi-misi';
            } elseif (str_starts_with($rawPath, '/transparan/')) {
                $map[$rawPath] = '/halaman/transparansi/lkjip';
            }
        }

        foreach ($postRedirects as $from => $to) {
            if (! isset($map[$from]) && strlen($from) <= 250 && strlen($to) <= 250) {
                $map[$from] = $to;
            }
        }

        $rows = [];
        foreach ($map as $from => $to) {
            $cleanFrom = '/'.ltrim(trim($from), '/');
            $cleanTo = trim($to);
            if ($cleanFrom === '/' || $cleanFrom === $cleanTo || $cleanTo === '') {
                continue;
            }
            $rows[] = [
                'from_path' => $cleanFrom,
                'to_path' => $cleanTo,
                'status_code' => 301,
            ];
        }

        return $rows;
    }

    /**
     * Inspect known suspicious files in legacy Joomla images directory so the audit report explicitly logs blocked threats.
     *
     * @return list<array{path: string, reason: string}>
     */
    private function auditKnownLegacyThreats(string $joomlaRoot, LegacyMediaSanitizer $media): array
    {
        $detected = [];
        $bypassPdf = $joomlaRoot.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'banners'.DIRECTORY_SEPARATOR.'bypass.pdf';
        if (is_file($bypassPdf)) {
            $media->processLocalPdf('images/banners/bypass.pdf', 'regulasi_sk');
            $detected[] = [
                'path' => 'images/banners/bypass.pdf',
                'reason' => 'PDF palsu bekas artefak infeksi malware (gagal validasi %PDF- & ukuran < 512 B) — otomatis diblokir.',
            ];
        }

        foreach (['images/berita/.berita.php', 'images/banners/.banners.php', 'images/banners/arena.php5', 'images/file/.file.php'] as $suspect) {
            $full = $joomlaRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $suspect);
            if (is_file($full)) {
                $detected[] = [
                    'path' => $suspect,
                    'reason' => 'Berkas skrip PHP di dalam direktori media — tidak pernah disentuh/disalin (allowlist ekstensi).',
                ];
            }
        }

        return $detected;
    }

    /**
     * @param  array<int, array<string, mixed>>  $articlesById
     */
    private function normalizeSliderCtaUrl(string $rawLink, array $articlesById): ?string
    {
        if ($rawLink === '') {
            return null;
        }

        $lower = strtolower($rawLink);
        foreach (['youtube.com', 'youtu.be', 'vimeo.com', 'twitch.tv', '.mp4', '.m3u8'] as $forbidden) {
            if (str_contains($lower, $forbidden)) {
                return null;
            }
        }

        if (preg_match('#pa-gorontalo\.go\.id/[^/]+/(\d+)-([a-z0-9-]+)#i', $rawLink, $m) === 1) {
            $aid = (int) $m[1];
            $art = $articlesById[$aid] ?? null;
            $slug = $art !== null
                ? Str::slug((string) ($art['alias'] ?? $art['title'] ?? $m[2]))
                : Str::slug($m[2]);

            return $slug !== '' ? '/publikasi/'.$slug : null;
        }

        return SafeHtml::cleanUrl($rawLink);
    }

    private function normalizeTimestamp(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || str_starts_with($trimmed, '0000-00-00')) {
            return null;
        }

        return $trimmed;
    }

    /**
     * @param  array<string, mixed>  $satker
     * @param  list<array<string, mixed>>  $sliders
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array<string, mixed>>  $extraMenus
     * @param  list<array<string, mixed>>  $documents
     * @param  list<array<string, mixed>>  $posts
     * @param  list<array<string, mixed>>  $redirects
     * @param  list<array<string, mixed>>  $pageVisits
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, mixed>  $report
     */
    private function writeBundleFiles(
        string $outputDir,
        array $satker,
        array $sliders,
        array $pages,
        array $extraMenus,
        array $documents,
        array $posts,
        array $redirects,
        array $pageVisits,
        array $manifest,
        array $report,
    ): void {
        if (! is_dir($outputDir) && ! mkdir($outputDir, 0775, true) && ! is_dir($outputDir)) {
            throw new RuntimeException("Gagal membuat direktori bundle migrasi: {$outputDir}");
        }

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'satker.json', (string) json_encode($satker, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'sliders.json', (string) json_encode($sliders, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'pages.json', (string) json_encode($pages, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'menus.json', (string) json_encode($extraMenus, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'documents.json', (string) json_encode($documents, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'redirects.json', (string) json_encode($redirects, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'page_visits.json', (string) json_encode($pageVisits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'media-manifest.json', (string) json_encode($manifest, $flags));
        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'migration-report.json', (string) json_encode($report, $flags));

        // Write posts as NDJSON for memory-efficient streaming during seeding
        $postsFp = fopen($outputDir.DIRECTORY_SEPARATOR.'posts.ndjson', 'wb');
        if ($postsFp === false) {
            throw new RuntimeException('Gagal menulis berkas posts.ndjson.');
        }

        try {
            foreach ($posts as $post) {
                fwrite($postsFp, (string) json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            }
        } finally {
            fclose($postsFp);
        }

        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'migration-report.md', $this->renderMarkdownReport($report));
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderMarkdownReport(array $report): string
    {
        $counts = $report['counts'];
        $media = $report['media'];
        $beforeMb = round(((int) $media['before_bytes']) / 1048576, 2);
        $afterMb = round(((int) $media['after_bytes']) / 1048576, 2);

        $lines = [
            '# Laporan Hasil Ekstraksi & Audit Migrasi PA Gorontalo',
            '',
            "- **Waktu Generate:** {$report['generated_at']}",
            "- **Durasi Ekstraksi:** {$report['duration_seconds']} detik",
            "- **Sumber SQL:** `{$report['source']['sql_path']}`",
            "- **Sumber Media:** `{$report['source']['joomla_root']}`",
            '',
            '## 1. Ringkasan Data Termigrasi',
            '',
            '| Modul Target | Jumlah |',
            '| :--- | ---: |',
            "| `satker_settings` | {$counts['satker_settings']} |",
            "| `home_sliders` | {$counts['sliders_total']} ({$counts['sliders_active']} aktif, maks 5 SINOVA) |",
            "| `pages` (SINOVA + Ekstra) | {$counts['pages_total']} |",
            "| `menu_items` (Submenu Ekstra) | {$counts['extra_menus']} |",
            "| `documents` (PDF Lokal + GDrive) | {$counts['documents_total']} |",
            "| `posts` (Publikasi Aktif) | {$counts['posts_total']} |",
            "| `redirects` (301 SEO) | {$counts['redirects_total']} |",
            "| `page_visits` (Agregat Harian) | {$counts['page_visits_days']} hari (total ".number_format((int) $counts['page_visits_sum']).' kunjungan) |',
            "| Artikel Sample-Data Joomla Dibuang | {$counts['sample_articles_excluded']} |",
            '',
            '## 2. Statistik Zero-Trust Media & Optimasi WebP',
            '',
            "- **Gambar Baru Dikonversi:** {$media['images_processed']}",
            "- **PDF Lokal Valid Disalin:** {$media['pdfs_processed']}",
            "- **Deduplikasi SHA-256 (Memo Hit):** {$media['dedup_hits']}",
            "- **Skip File Sudah Ada (Idempoten):** {$media['skipped_existing']}",
            "- **Ukuran Sebelum → Sesudah:** {$beforeMb} MB → {$afterMb} MB (**hemat {$media['saved_percent']}%**)",
            "- **Berkas Ditolak Keamanan:** {$media['rejected_count']}",
            "- **Berkas Lokal Tidak Ditemukan:** {$media['missing_count']}",
            '',
            '## 3. Distribusi Dokumen per Koleksi SINOVA',
            '',
            '| Koleksi (`collection_type`) | Jumlah |',
            '| :--- | ---: |',
        ];

        foreach ($counts['documents_by_collection'] as $col => $cnt) {
            $lines[] = "| `{$col}` | {$cnt} |";
        }

        $lines[] = '';
        $lines[] = '## 4. Ancaman Legacy yang Diblokir';
        $lines[] = '';
        foreach ($report['security']['legacy_threats_detected'] as $threat) {
            $lines[] = "- `{$threat['path']}` — {$threat['reason']}";
        }

        if ($report['security']['rejected_files'] !== []) {
            $lines[] = '';
            $lines[] = '### Daftar Penolakan Media Sanitizer';
            foreach ($report['security']['rejected_files'] as $rej) {
                $lines[] = "- `{$rej['source']}` — {$rej['reason']}";
            }
        }

        if ($report['warnings']['external_images'] !== []) {
            $lines[] = '';
            $lines[] = '## 5. Catatan Gambar Eksternal (Non-Lokal)';
            foreach ($report['warnings']['external_images'] as $ext) {
                $lines[] = "- {$ext}";
            }
        }

        return implode("\n", $lines)."\n";
    }
}
