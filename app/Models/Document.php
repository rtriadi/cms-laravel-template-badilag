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
            ->logOnly(['collection_type', 'title', 'year', 'month', 'is_published'])
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
            'calk' => 'Catatan Atas Laporan Keuangan (CALK)',
            'pbj' => 'Rencana Umum Pengadaan (PBJ)',
            'lhkpn' => 'LHKPN Pejabat',
            'biaya_perkara' => 'Laporan Penggunaan Biaya Perkara',
            'sisa_panjar' => 'Pengembalian Sisa Panjar Perkara',
            'panggilan_ghaib' => 'Panggilan Ghaib (+ Relaas)',
            'prodeo_posbakum' => 'Layanan Prodeo, Posbakum & Sidang Keliling (SK / Brosur / Formulir)',
            'biaya_radius' => 'SK Panjar Biaya Perkara & Radius Panggilan',
            'prosedur_sop' => 'SOP & Panduan Prosedur Berperkara',
            'standar_pelayanan' => 'SK Standar Pelayanan, Maklumat & Layanan Disabilitas',
            'akses_informasi' => 'Laporan Akses Informasi (PPID)',
            'rekap_pengaduan' => 'Laporan Rekapitulasi Pengaduan',
            'hukuman_disiplin' => 'Hukuman Disiplin Hasil Pengawasan',
            'survei' => 'Laporan Survei SKM / SPAK / SPKP',
        ];
    }
}
