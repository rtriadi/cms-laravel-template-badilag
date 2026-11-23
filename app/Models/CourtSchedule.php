<?php

namespace App\Models;

use App\Http\Controllers\PublicHomeController;
use Database\Factories\CourtScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tanggal_sidang',
    'nomor_perkara',
    'para_pihak',
    'ruang_sidang',
    'agenda',
    'file_pdf_path',
])]
class CourtSchedule extends Model
{
    /** @use HasFactory<CourtScheduleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_sidang' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            PublicHomeController::flushCache();
        });

        static::deleted(function (): void {
            PublicHomeController::flushCache();
        });
    }
}
