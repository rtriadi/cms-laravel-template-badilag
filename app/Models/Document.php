<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'collection_type',
    'title',
    'year',
    'month',
    'status_label',
    'file_path',
    'external_url',
    'metadata',
    'is_published',
    'created_by',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'metadata' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document')
            ->logOnly(['collection_type', 'title', 'year', 'month', 'status_label', 'file_path', 'external_url', 'is_published'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    public static function collectionTypes(): array
    {
        return [
            'renstra' => 'Rencana Strategis (Renstra)',
            'rkt' => 'Rencana Kinerja Tahunan (RKT)',
            'renaksi' => 'Rencana Aksi Kinerja',
            'pk' => 'Perjanjian Kinerja (PK)',
            'iku' => 'Indikator Kinerja Utama (IKU)',
            'lkjip' => 'Laporan Kinerja (LKjIP)',
            'laporan_tahunan' => 'Laporan Pelaksanaan Kegiatan / Tahunan',
            'dipa' => 'DIPA / RPD / Realisasi',
            'lra' => 'Laporan Realisasi Anggaran (LRA)',
            'calk' => 'Catatan Atas Laporan Keuangan (CALK) & Neraca',
            'aset_bmn' => 'Ringkasan Daftar Aset dan Inventaris (BMN)',
            'pbj' => 'Rencana Umum Pengadaan Barang & Jasa (PBJ)',
            'lhkpn' => 'LHKPN / LHKASN Pejabat & Aparatur',
            'biaya_perkara' => 'Laporan Penggunaan Biaya Perkara',
            'sisa_panjar' => 'Pengembalian Sisa Panjar Perkara',
            'panggilan_ghaib' => 'Panggilan Ghaib (+ Relaas)',
            'prodeo_posbakum' => 'Layanan Prodeo, Posbakum & Sidang Keliling (SK / SPK / Jadwal Piket / Laporan)',
            'biaya_radius' => 'SK Panjar Biaya Perkara & Radius Panggilan',
            'prosedur_sop' => 'SOP Kepaniteraan, PTSP & Panduan Prosedur Berperkara',
            'standar_pelayanan' => 'SK Standar Pelayanan PTSP, Maklumat & SOP Layanan Disabilitas',
            'akses_informasi' => 'Laporan Layanan Akses Informasi Publik (PPID)',
            'dip_ppid' => 'SK PPID, Daftar Informasi Publik (DIP), Informasi Dikecualikan & 7 SOP PPID',
            'biaya_informasi' => 'SK Standar Biaya Perolehan Informasi & Formulir Permohonan/Keberatan PPID',
            'rekap_pengaduan' => 'Laporan Rekapitulasi Pengaduan Masyarakat',
            'hukuman_disiplin' => 'Hukuman Disiplin Hasil Pengawasan',
            'survei' => 'Laporan Survei SKM / SPAK / SPKP (SI SURTI)',
            'regulasi_sk' => 'Peraturan, Kebijakan, SK Tim Pengelola Website/Medsos, SK Meja e-Court & Pengaduan',
            'sop_evakuasi' => 'SOP Peringatan Dini & Prosedur Evakuasi Keadaan Darurat',
        ];
    }
}
