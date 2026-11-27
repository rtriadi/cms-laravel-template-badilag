<?php

namespace App\Models;

use App\Http\Controllers\PublicComplianceController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Support\SafeHtml;
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
    'pta_nama',
    'pta_url',
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
    'survei_periode',
    'nilai_skm',
    'nilai_spak',
    'nilai_spkp',
    'video_profil_url',
    'video_ptsp_url',
    'video_kebijakan_ma_url',
    'cctv_aco_url',
    'cctv_aco_label',
    'social_facebook_url',
    'social_instagram_url',
    'social_tiktok_url',
    'social_youtube_url',
    'social_x_url',
    'related_links',
    'is_setup_completed',
])]
class SatkerSetting extends Model
{
    /** @use HasFactory<SatkerSettingFactory> */
    use HasFactory, LogsActivity;

    public const string CACHE_KEY = 'satker_setting.current';

    /**
     * Official SIMTEPA Share Data categories for Religious Court websites.
     *
     * @var array<string, string>
     */
    public const array SIMTEPA_CATEGORIES = [
        'profil_ketua' => 'Profil Ketua',
        'profil_wakil' => 'Profil Wakil Ketua',
        'profil_hakim' => 'Profil Hakim',
        'profil_kepaniteraan' => 'Profil Kepaniteraan',
        'profil_kesekretariatan' => 'Profil Kesekretariatan',
        'profil_fungsional' => 'Profil Fungsional',
        'profil_pelaksana' => 'Profil Pelaksana',
    ];

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
            'related_links' => 'array',
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
            self::forgetMemo();
            Cache::forget(self::CACHE_KEY);
            PublicHomeController::flushCache();
            PublicComplianceController::flushCache();
            SecurityHeadersMiddleware::flushFrameSrcCache();
        });

        static::deleted(function (): void {
            self::forgetMemo();
            Cache::forget(self::CACHE_KEY);
            PublicHomeController::flushCache();
            PublicComplianceController::flushCache();
            SecurityHeadersMiddleware::flushFrameSrcCache();
        });
    }

    public static function forgetMemo(): void
    {
        if (app()->bound(self::CACHE_KEY)) {
            app()->forgetInstance(self::CACHE_KEY);
        }
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
        if (app()->bound(self::CACHE_KEY)) {
            $memo = app()->make(self::CACHE_KEY);

            return $memo instanceof self ? $memo : null;
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            $hydrated = (new static)->newFromBuilder($cached);
            app()->instance(self::CACHE_KEY, $hydrated);

            return $hydrated;
        }

        if ($cached !== null) {
            Cache::forget(self::CACHE_KEY);
        }

        $setting = static::query()->first();

        if ($setting !== null) {
            Cache::forever(self::CACHE_KEY, $setting->getAttributes());
            app()->instance(self::CACHE_KEY, $setting);
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

        $bareKel = preg_replace('/^(kel\.?|kelurahan|desa)\s+/iu', '', trim((string) $this->desa_kelurahan)) ?? '';
        $bareKec = preg_replace('/^(kec\.?|kecamatan)\s+/iu', '', trim((string) $this->kecamatan)) ?? '';

        $jalanHasKel = $bareKel !== '' && preg_match('/\b(?:kel\.?|kelurahan|desa)\s*'.preg_quote($bareKel, '/').'\b/iu', $jalan) === 1;
        $jalanHasKec = $bareKec !== '' && preg_match('/\b(?:kec\.?|kecamatan)\s*'.preg_quote($bareKec, '/').'\b/iu', $jalan) === 1;

        $parts = array_filter([
            $jalan !== '' ? $jalan : null,
            ($kel !== '' && ($jalan === '' || ! $jalanHasKel)) ? $kel : null,
            ($kec !== '' && ($jalan === '' || ! $jalanHasKec)) ? $kec : null,
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

    /**
     * Extract the 32-character SIMTEPA satker share token from configured SIMTEPA URLs or raw token.
     */
    public function extractSimtepaToken(): ?string
    {
        foreach ([$this->simtepa_pegawai_url, $this->simtepa_struktur_url] as $candidate) {
            $raw = trim((string) $candidate);
            if ($raw !== '' && preg_match('/([a-f0-9]{32})/i', $raw, $matches) === 1) {
                return strtolower($matches[1]);
            }
        }

        return null;
    }

    /**
     * Build an official SIMTEPA share URL (html or json) for one of the 7 profile categories.
     */
    public function simtepaShareUrl(string $category, string $format = 'html'): ?string
    {
        if (! array_key_exists($category, self::SIMTEPA_CATEGORIES)) {
            return null;
        }

        $token = $this->extractSimtepaToken();
        if ($token === null) {
            return null;
        }

        $fmt = $format === 'json' ? 'json' : 'html';

        return "https://simtepa.mahkamahagung.go.id/share/{$category}/{$fmt}/{$token}";
    }

    /**
     * Return all 7 (or 6 for org chart without pelaksana) SIMTEPA tabs when a valid share token is configured.
     *
     * @return array<int, array{key: string, label: string, html_url: string, json_url: string}>
     */
    public function simtepaCategoryTabs(bool $excludePelaksana = false): array
    {
        $token = $this->extractSimtepaToken();
        if ($token === null) {
            return [];
        }

        $tabs = [];
        foreach (self::SIMTEPA_CATEGORIES as $key => $label) {
            if ($excludePelaksana && $key === 'profil_pelaksana') {
                continue;
            }

            $tabs[] = [
                'key' => $key,
                'label' => $label,
                'html_url' => "https://simtepa.mahkamahagung.go.id/share/{$key}/html/{$token}",
                'json_url' => "https://simtepa.mahkamahagung.go.id/share/{$key}/json/{$token}",
            ];
        }

        return $tabs;
    }

    /**
     * Resolve SIPP URL for Jadwal Sidang (/list_jadwal_sidang), Daftar Perkara (/), or Statistik (/statistik_perkara).
     */
    public function resolvedSippEmbedUrl(string $sourceKey): ?string
    {
        $raw = SafeHtml::cleanUrl($this->sipp_url);
        if ($raw === null) {
            return null;
        }

        $base = rtrim($raw, '/');
        $path = (string) parse_url($base, PHP_URL_PATH);
        $isRootUrl = $path === '' || $path === '/';

        return match ($sourceKey) {
            'sipp_jadwal' => str_ends_with($base, '/list_jadwal_sidang')
                ? $base
                : ($isRootUrl ? $base.'/list_jadwal_sidang' : $base),
            'sipp_statistik' => str_ends_with($base, '/statistik_perkara')
                ? $base
                : ($isRootUrl ? $base.'/statistik_perkara' : $base),
            default => $base.'/',
        };
    }

    /**
     * Resolve embed URL and optional multi-tab SIMTEPA categories for an embed_fallback block.
     *
     * @return array{embed_url: ?string, tabs: array<int, array{key: string, label: string, html_url: string, json_url: string}>}
     */
    public function resolveEmbedSource(string $sourceKey, ?string $customOverride = null): array
    {
        $cleanCustom = SafeHtml::cleanUrl($customOverride);
        if ($cleanCustom !== null) {
            return [
                'embed_url' => $cleanCustom,
                'tabs' => [],
            ];
        }

        $singleSimtepaMap = [
            'simtepa_ketua' => 'profil_ketua',
            'simtepa_wakil' => 'profil_wakil',
            'simtepa_hakim' => 'profil_hakim',
            'simtepa_kepaniteraan' => 'profil_kepaniteraan',
            'simtepa_kesekretariatan' => 'profil_kesekretariatan',
            'simtepa_fungsional' => 'profil_fungsional',
            'simtepa_pelaksana' => 'profil_pelaksana',
        ];

        if (isset($singleSimtepaMap[$sourceKey])) {
            $catKey = $singleSimtepaMap[$sourceKey];
            $htmlUrl = $this->simtepaShareUrl($catKey, 'html') ?? SafeHtml::cleanUrl($this->simtepa_pegawai_url);

            return [
                'embed_url' => $htmlUrl,
                'tabs' => [],
            ];
        }

        if ($sourceKey === 'simtepa_pegawai') {
            $tabs = $this->simtepaCategoryTabs(false);
            if ($tabs !== []) {
                return [
                    'embed_url' => $tabs[0]['html_url'],
                    'tabs' => $tabs,
                ];
            }

            return [
                'embed_url' => SafeHtml::cleanUrl($this->simtepa_pegawai_url),
                'tabs' => [],
            ];
        }

        if ($sourceKey === 'simtepa_struktur') {
            $directStruktur = SafeHtml::cleanUrl($this->simtepa_struktur_url);

            if ($directStruktur !== null) {
                $isProfilShareUrl = preg_match('#/share/profil_[a-z_]+/(?:html|json)/[a-f0-9]{32}#i', $directStruktur) === 1;

                if (! $isProfilShareUrl) {
                    return [
                        'embed_url' => $directStruktur,
                        'tabs' => [],
                    ];
                }

                $tabs = $this->simtepaCategoryTabs(true);
                if ($tabs !== []) {
                    return [
                        'embed_url' => $tabs[0]['html_url'],
                        'tabs' => $tabs,
                    ];
                }
            }

            return [
                'embed_url' => $directStruktur,
                'tabs' => [],
            ];
        }

        if ($sourceKey === 'simtepa_struktur_tabs') {
            $tabs = $this->simtepaCategoryTabs(true);
            if ($tabs !== []) {
                return [
                    'embed_url' => $tabs[0]['html_url'],
                    'tabs' => $tabs,
                ];
            }

            return [
                'embed_url' => SafeHtml::cleanUrl($this->simtepa_pegawai_url),
                'tabs' => [],
            ];
        }

        if (in_array($sourceKey, ['sipp_jadwal', 'sipp_statistik', 'sipp_perkara'], true)) {
            return [
                'embed_url' => $this->resolvedSippEmbedUrl($sourceKey),
                'tabs' => [],
            ];
        }

        return [
            'embed_url' => null,
            'tabs' => [],
        ];
    }

    /**
     * Resolve an official SIMTEPA Share Data JSON endpoint URL when available for a single-category source key.
     */
    public function resolveSimtepaJsonUrl(string $sourceKey, ?string $customOverride = null): ?string
    {
        if (SafeHtml::cleanUrl($customOverride) !== null) {
            return null;
        }

        $singleSimtepaMap = [
            'simtepa_ketua' => 'profil_ketua',
            'simtepa_wakil' => 'profil_wakil',
            'simtepa_hakim' => 'profil_hakim',
            'simtepa_kepaniteraan' => 'profil_kepaniteraan',
            'simtepa_kesekretariatan' => 'profil_kesekretariatan',
            'simtepa_fungsional' => 'profil_fungsional',
            'simtepa_pelaksana' => 'profil_pelaksana',
        ];

        if (isset($singleSimtepaMap[$sourceKey])) {
            return $this->simtepaShareUrl($singleSimtepaMap[$sourceKey], 'json');
        }

        return null;
    }

    /**
     * Return configured official social media channels with sanitized URLs.
     *
     * @return list<array{key: string, label: string, description: string, url: string}>
     */
    public function socialChannels(): array
    {
        $satkerLabel = $this->nama_satker !== '' ? $this->nama_satker : 'Satuan Kerja';
        $definitions = [
            ['key' => 'facebook', 'field' => 'social_facebook_url', 'label' => 'Facebook', 'description' => "Akun Facebook resmi {$satkerLabel}"],
            ['key' => 'instagram', 'field' => 'social_instagram_url', 'label' => 'Instagram', 'description' => "Akun Instagram resmi {$satkerLabel}"],
            ['key' => 'tiktok', 'field' => 'social_tiktok_url', 'label' => 'TikTok', 'description' => "Akun TikTok resmi {$satkerLabel}"],
            ['key' => 'youtube', 'field' => 'social_youtube_url', 'label' => 'YouTube', 'description' => "Kanal YouTube resmi {$satkerLabel}"],
            ['key' => 'x', 'field' => 'social_x_url', 'label' => 'X (Twitter)', 'description' => "Akun X resmi {$satkerLabel}"],
        ];

        $channels = [];
        foreach ($definitions as $def) {
            $clean = SafeHtml::cleanUrl($this->{$def['field']});
            if ($clean === null) {
                continue;
            }

            $channels[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'description' => $def['description'],
                'url' => $clean,
            ];
        }

        return $channels;
    }

    /**
     * Return sanitized institutional partner links configured in CMS settings.
     *
     * @return list<array{code: string, title: string, subtitle: string, url: string}>
     */
    public function resolvedRelatedLinks(): array
    {
        if (! is_array($this->related_links)) {
            return [];
        }

        $items = [];
        foreach ($this->related_links as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $url = SafeHtml::cleanUrl(isset($row['url']) ? (string) $row['url'] : null);

            if ($title === '' || $url === null) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '') {
                $code = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $title) ?: 'LINK', 0, 6));
            }

            $items[] = [
                'code' => mb_substr($code, 0, 12),
                'title' => $title,
                'subtitle' => trim((string) ($row['subtitle'] ?? '')),
                'url' => $url,
            ];
        }

        return $items;
    }
}
