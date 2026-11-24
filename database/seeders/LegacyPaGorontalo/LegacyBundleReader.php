<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use Generator;
use RuntimeException;

class LegacyBundleReader
{
    private static ?string $overrideBasePath = null;

    public static function setBasePath(string $path): void
    {
        self::$overrideBasePath = rtrim($path, '\\/');
    }

    public static function resetBasePath(): void
    {
        self::$overrideBasePath = null;
    }

    public static function basePath(): string
    {
        if (self::$overrideBasePath !== null && self::$overrideBasePath !== '') {
            return self::$overrideBasePath;
        }

        $envPath = (string) env('SATKER_LEGACY_BUNDLE_PATH', '');
        if ($envPath !== '') {
            return rtrim($envPath, '\\/');
        }

        return database_path('data/legacy-pa-gorontalo');
    }

    public static function filePath(string $filename): string
    {
        return self::basePath().DIRECTORY_SEPARATOR.ltrim($filename, '\\/');
    }

    public static function exists(string $filename): bool
    {
        return is_file(self::filePath($filename));
    }

    /**
     * @return array<mixed>
     */
    public static function readJson(string $filename): array
    {
        $path = self::filePath($filename);
        if (! is_file($path)) {
            throw new RuntimeException("Berkas bundle migrasi tidak ditemukan: {$path}. Jalankan `php artisan migration:prepare-joomla` terlebih dahulu.");
        }

        $raw = file_get_contents($path);
        if (! is_string($raw)) {
            throw new RuntimeException("Gagal membaca berkas bundle migrasi: {$path}");
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Stream an NDJSON file line-by-line to avoid loading thousands of articles into memory at once.
     *
     * @return Generator<int, array<string, mixed>>
     */
    public static function streamNdjson(string $filename): Generator
    {
        $path = self::filePath($filename);
        if (! is_file($path)) {
            throw new RuntimeException("Berkas NDJSON tidak ditemukan: {$path}. Jalankan `php artisan migration:prepare-joomla` terlebih dahulu.");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Gagal membuka berkas NDJSON: {$path}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    yield $decoded;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
