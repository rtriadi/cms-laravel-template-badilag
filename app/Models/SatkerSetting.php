<?php

namespace App\Models;

use Database\Factories\SatkerSettingFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'nama_satker',
    'jenis_badan',
    'tingkat',
    'is_tingkat_locked',
    'kode_satker',
    'tahun_berdiri',
    'alamat_jalan',
    'desa_kelurahan',
    'kecamatan',
    'kabupaten_kota',
    'provinsi',
    'kode_pos',
    'telepon',
    'email',
    'website_url',
    'logo_path',
    'favicon_path',
    'foto_gedung_path',
    'sipp_url',
    'ecourt_url',
    'direktori_putusan_url',
    'siwas_url',
    'simtepa_pegawai_url',
    'simtepa_struktur_url',
    'skm_url',
    'spak_url',
    'is_setup_completed',
])]
class SatkerSetting extends Model
{
    /** @use HasFactory<SatkerSettingFactory> */
    use HasFactory, LogsActivity;

    public const string CACHE_KEY = 'satker_setting.current';

    protected bool $allowTingkatChange = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_tingkat_locked' => 'boolean',
            'is_setup_completed' => 'boolean',
            'tahun_berdiri' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $setting): void {
            if (
                $setting->isDirty('tingkat')
                && (bool) $setting->getOriginal('is_tingkat_locked')
                && ! $setting->allowTingkatChange
            ) {
                throw new AuthorizationException('Tingkat peradilan sudah dikunci dan hanya dapat diubah oleh Super Admin melalui aksi resmi.');
            }
        });

        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('satker')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public static function current(): ?self
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return (new static)->newFromBuilder($cached);
        }

        if ($cached !== null) {
            Cache::forget(self::CACHE_KEY);
        }

        $setting = static::query()->first();

        if ($setting !== null) {
            Cache::forever(self::CACHE_KEY, $setting->getAttributes());
        }

        return $setting;
    }

    public function completeSetup(): void
    {
        $this->is_setup_completed = true;
        $this->is_tingkat_locked = true;
        $this->save();
    }

    /**
     * Change satker tier (`pertama` or `banding`) after setup lock, restricted to Super Admin.
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function changeTingkat(string $newTingkat, User $actor): void
    {
        if (! array_key_exists($newTingkat, config('satker.tingkat', ['pertama' => 'Tingkat Pertama', 'banding' => 'Tingkat Banding']))) {
            throw new InvalidArgumentException("Tingkat satker tidak valid: {$newTingkat}");
        }

        if (! $actor->hasRole('super_admin')) {
            throw new AuthorizationException('Hanya Super Admin yang berwenang mengubah tingkat peradilan.');
        }

        $previousTingkat = $this->tingkat;

        $this->allowTingkatChange = true;

        try {
            $this->tingkat = $newTingkat;
            $this->is_tingkat_locked = true;
            $this->save();
        } finally {
            $this->allowTingkatChange = false;
        }

        activity('satker')
            ->causedBy($actor)
            ->performedOn($this)
            ->withProperties([
                'previous_tingkat' => $previousTingkat,
                'new_tingkat' => $newTingkat,
            ])
            ->log('Mengubah tingkat peradilan satker');
    }

    public function formattedAddress(): string
    {
        $jalan = trim((string) $this->alamat_jalan);
        $kel = trim((string) $this->desa_kelurahan);
        $kec = trim((string) $this->kecamatan);

        if ($kel !== '' && ! preg_match('/^(kel\.?|kelurahan|desa)\b/iu', $kel)) {
            $kel = 'Kel. '.$kel;
        }

        if ($kec !== '' && ! preg_match('/^(kec\.?|kecamatan)\b/iu', $kec)) {
            $kec = 'Kec. '.$kec;
        }

        $parts = array_filter([
            $jalan !== '' ? $jalan : null,
            ($kel !== '' && ($jalan === '' || stripos($jalan, (string) $this->desa_kelurahan) === false)) ? $kel : null,
            ($kec !== '' && ($jalan === '' || stripos($jalan, (string) $this->kecamatan) === false)) ? $kec : null,
            $this->kabupaten_kota,
            $this->provinsi,
            $this->kode_pos,
        ]);

        return implode(', ', $parts);
    }

    public function replacePlaceholders(string $text): string
    {
        $tingkatLabel = config("satker.tingkat.{$this->tingkat}", $this->tingkat);
        $jenisLabel = config("satker.jenis_badan.{$this->jenis_badan}", $this->jenis_badan);

        $replacements = [
            '{{satker.nama}}' => (string) $this->nama_satker,
            '{{satker.jenis}}' => (string) $jenisLabel,
            '{{satker.tingkat}}' => (string) $tingkatLabel,
            '{{satker.kode}}' => (string) ($this->kode_satker ?? ''),
            '{{satker.kota}}' => (string) ($this->kabupaten_kota ?? ''),
            '{{satker.provinsi}}' => (string) ($this->provinsi ?? ''),
            '{{satker.alamat}}' => $this->formattedAddress(),
            '{{satker.telepon}}' => (string) ($this->telepon ?? ''),
            '{{satker.email}}' => (string) ($this->email ?? ''),
            '{{satker.website}}' => (string) ($this->website_url ?? ''),
        ];

        return strtr($text, $replacements);
    }
}
