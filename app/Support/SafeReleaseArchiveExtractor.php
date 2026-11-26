<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

class SafeReleaseArchiveExtractor
{
    /**
     * @var array<int, string>
     */
    public const array ALLOWED_DOWNLOAD_HOSTS = [
        'github.com',
        'api.github.com',
        'objects.githubusercontent.com',
        'release-assets.githubusercontent.com',
        'github-releases.githubusercontent.com',
    ];

    public static function isAllowedDownloadUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return false;
        }

        $parts = parse_url($trimmed);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'https') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        return in_array($host, self::ALLOWED_DOWNLOAD_HOSTS, true);
    }

    public static function verifySha256(string $archivePath, string $expectedContent): bool
    {
        if (! is_file($archivePath) || ! is_readable($archivePath)) {
            return false;
        }

        if (preg_match('/^([a-fA-F0-9]{64})(?:\s+|$)/', trim($expectedContent), $matches) !== 1) {
            return false;
        }

        $expectedHash = strtolower($matches[1]);
        $actualHash = hash_file('sha256', $archivePath);

        if (! is_string($actualHash)) {
            return false;
        }

        return hash_equals($expectedHash, strtolower($actualHash));
    }

    public function extract(string $zipPath, string $destinationDir): void
    {
        if (! is_file($zipPath) || ! is_readable($zipPath)) {
            throw new RuntimeException('Berkas arsip rilis tidak ditemukan atau tidak dapat dibaca.');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Gagal membuka berkas arsip ZIP rilis.');
        }

        try {
            $entriesToExtract = [];

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $rawName = $zip->getNameIndex($index);
                if (! is_string($rawName) || $rawName === '') {
                    throw new RuntimeException('Arsip rilis memiliki nama entri tidak valid.');
                }

                $normalized = $this->validateAndNormalizeEntryPath($rawName);

                $opsys = 0;
                $attr = 0;
                if ($zip->getExternalAttributesIndex($index, $opsys, $attr)) {
                    $unixMode = ($attr >> 16) & 0170000;
                    if ($opsys === ZipArchive::OPSYS_UNIX && $unixMode === 0120000) {
                        throw new RuntimeException("Arsip rilis ditolak karena memuat symlink tidak aman: {$rawName}");
                    }
                }

                if ($this->shouldSkipProtectedPath($normalized)) {
                    continue;
                }

                $entriesToExtract[] = [
                    'raw' => $rawName,
                    'normalized' => $normalized,
                    'is_dir' => str_ends_with($normalized, '/'),
                ];
            }

            if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0775, true) && ! is_dir($destinationDir)) {
                throw new RuntimeException('Gagal membuat direktori tujuan ekstraksi rilis.');
            }

            foreach ($entriesToExtract as $entry) {
                $relative = trim($entry['normalized'], '/');
                if ($relative === '') {
                    continue;
                }

                $targetPath = $destinationDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if ($entry['is_dir']) {
                    if (! is_dir($targetPath) && ! mkdir($targetPath, 0775, true) && ! is_dir($targetPath)) {
                        throw new RuntimeException("Gagal membuat direktori: {$relative}");
                    }

                    continue;
                }

                $parentDir = dirname($targetPath);
                if (! is_dir($parentDir) && ! mkdir($parentDir, 0775, true) && ! is_dir($parentDir)) {
                    throw new RuntimeException("Gagal membuat direktori induk untuk: {$relative}");
                }

                $sourceStream = $zip->getStream($entry['raw']);
                if (! is_resource($sourceStream)) {
                    throw new RuntimeException("Gagal membaca aliran berkas dari arsip: {$relative}");
                }

                $destStream = @fopen($targetPath, 'wb');
                if (! is_resource($destStream)) {
                    fclose($sourceStream);
                    throw new RuntimeException("Gagal menulis berkas hasil ekstraksi: {$relative}");
                }

                stream_copy_to_stream($sourceStream, $destStream);
                fclose($sourceStream);
                fclose($destStream);
            }
        } finally {
            $zip->close();
        }
    }

    private function validateAndNormalizeEntryPath(string $rawName): string
    {
        if (preg_match('/[\x00-\x1F]/', $rawName) === 1) {
            throw new RuntimeException('Arsip rilis memuat karakter kontrol pada nama berkas.');
        }

        if (str_starts_with($rawName, '/') || str_starts_with($rawName, '\\')) {
            throw new RuntimeException("Arsip rilis memuat path absolut terlarang: {$rawName}");
        }

        if (preg_match('/^[a-zA-Z]:/', $rawName) === 1) {
            throw new RuntimeException("Arsip rilis memuat path drive absolut terlarang: {$rawName}");
        }

        $normalized = str_replace('\\', '/', $rawName);
        $segments = explode('/', $normalized);

        foreach ($segments as $segment) {
            if ($segment === '..') {
                throw new RuntimeException("Arsip rilis ditolak karena upaya path traversal: {$rawName}");
            }
        }

        return $normalized;
    }

    private function shouldSkipProtectedPath(string $normalized): bool
    {
        $clean = trim($normalized, '/');
        if ($clean === '') {
            return true;
        }

        if ($clean === '.env') {
            return true;
        }

        if (str_starts_with($clean, '.env.') && ! in_array($clean, ['.env.example', '.env.production.example'], true)) {
            return true;
        }

        if (str_starts_with($clean, 'storage/app/') || str_starts_with($clean, 'storage/logs/')) {
            return true;
        }

        return false;
    }
}
