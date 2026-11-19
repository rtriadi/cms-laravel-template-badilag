<?php

namespace App\Models;

use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'from_path',
    'to_path',
    'status_code',
    'hits',
])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    public const string CACHE_KEY = 'redirects.active_map';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * @return array<string, array{id: int, to_path: string, status_code: int}>
     */
    public static function cachedMap(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $map = [];
        foreach (static::query()->get(['id', 'from_path', 'to_path', 'status_code']) as $row) {
            $map[(string) $row->from_path] = [
                'id' => (int) $row->id,
                'to_path' => (string) $row->to_path,
                'status_code' => (int) $row->status_code,
            ];
        }

        Cache::forever(self::CACHE_KEY, $map);

        return $map;
    }
}
