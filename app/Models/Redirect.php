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

    public const string PATH_CACHE_VERSION_KEY = 'redirects.path_cache_version';

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
            self::flushPathCaches();
        });

        static::deleted(function (): void {
            self::flushPathCaches();
        });
    }

    public static function flushPathCaches(): void
    {
        Cache::forget(self::CACHE_KEY);
        $nextVersion = ((int) Cache::get(self::PATH_CACHE_VERSION_KEY, 1)) + 1;
        Cache::forever(self::PATH_CACHE_VERSION_KEY, $nextVersion);
    }

    /**
     * Perform a fast unique-indexed point lookup cached per path (hits and misses)
     * without loading the entire redirect table into memory.
     *
     * @return array{id: int, to_path: string, status_code: int}|null
     */
    public static function findForPath(string $path): ?array
    {
        $version = (int) Cache::get(self::PATH_CACHE_VERSION_KEY, 1);
        $cacheKey = 'redirects.path.v'.$version.'.'.sha1($path);

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('hit', $cached)) {
            return $cached['hit'] ? $cached['data'] : null;
        }

        $row = static::query()
            ->where('from_path', $path)
            ->first(['id', 'to_path', 'status_code']);

        if ($row === null) {
            Cache::put($cacheKey, ['hit' => false, 'data' => null], 300);

            return null;
        }

        $payload = [
            'id' => (int) $row->id,
            'to_path' => (string) $row->to_path,
            'status_code' => (int) $row->status_code,
        ];

        Cache::put($cacheKey, ['hit' => true, 'data' => $payload], 300);

        return $payload;
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
