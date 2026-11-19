<?php

namespace App\Models;

use Database\Factories\CaseStatisticFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'year',
    'month',
    'sisa_bulan_lalu',
    'perkara_masuk',
    'perkara_putus',
    'sisa_saat_ini',
])]
class CaseStatistic extends Model
{
    /** @use HasFactory<CaseStatisticFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'sisa_bulan_lalu' => 'integer',
            'perkara_masuk' => 'integer',
            'perkara_putus' => 'integer',
            'sisa_saat_ini' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $statistic): void {
            $statistic->sisa_saat_ini = max(
                0,
                (int) $statistic->sisa_bulan_lalu + (int) $statistic->perkara_masuk - (int) $statistic->perkara_putus,
            );
        });
    }
}
