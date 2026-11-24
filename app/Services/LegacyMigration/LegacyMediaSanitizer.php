<?php

declare(strict_types=1);

namespace App\Services\LegacyMigration;

use App\Services\ImageOptimizerService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LegacyMediaSanitizer
{
    /** @var array<string, array{dir: string, prefix: string, max_width: int, quality: int, variants: list<int>}> */
    private const array IMAGE_PROFILES = [
        'satker_logo' => [
            'dir' => 'satker',
            'prefix' => 'logo-',
            'max_width' => 512,
            'quality' => 88,
            'variants' => [128],
        ],
        'satker_building' => [
            'dir' => 'satker',
            'prefix' => 'gedung-',
            'max_width' => 1280,
            'quality' => 82,
            'variants' => [480, 800],
        ],
        'slider' => [
            'dir' => 'sliders',
            'prefix' => '',
            'max_width' => 1280,
            'quality' => 82,
            'variants' => [480, 800],
        ],
        'post_featured' => [
            'dir' => 'posts',
            'prefix' => '',
            'max_width' => 1000,
            'quality' => 82,
            'variants' => [400, 720],
        ],
        'editor' => [
            'dir' => 'editor-media',
            'prefix' => '',
            'max_width' => 1280,
            'quality' => 82,
            'variants' => [],
        ],
    ];

    /** @var list<string> */
    private const array ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const int MIN_PDF_BYTES = 512;

    private const int MAX_PDF_BYTES = 15_728_640; // 15 MB

    private readonly ?string $resolvedRoot;

    /** @var array<string, string> */
    private array $memoizedTargets = [];

    /** @var array<string, array{source: string, target: string, profile: string, sha256: string, before_bytes: int, after_bytes: int, variants: list<string>}> */
    private array $manifest = [];

    /** @var list<array{source: string, reason: string}> */
    private array $rejected = [];

    /** @var list<string> */
    private array $missing = [];

    private int $dedupHits = 0;

    private int $skippedExisting = 0;

    private int $imagesProcessed = 0;

    private int $pdfsProcessed = 0;

    private int $totalBeforeBytes = 0;

    private int $totalAfterBytes = 0;

    public function __construct(
        private readonly string $joomlaRoot,
        private readonly ImageOptimizerService $optimizer,
        private readonly string $disk = 'public',
        private readonly bool $dryRun = false,
    ) {
        $real = realpath($this->joomlaRoot);
        $this->resolvedRoot = is_string($real) ? rtrim($real, '\\/') : null;
    }

    /**
     * Validate, deduplicate (SHA-256), re-encode to WebP (+ responsive variants), and store a local Joomla image.
     */
    public function processImage(string $rawReference, string $profile = 'editor'): ?string
    {
        $cfg = self::IMAGE_PROFILES[$profile] ?? self::IMAGE_PROFILES['editor'];
        $realSource = $this->resolveSafeLocalPath($rawReference);
        if ($realSource === null) {
            return null;
        }

        $memoKey = $profile.'|'.$realSource;
        if (isset($this->memoizedTargets[$memoKey])) {
            $this->dedupHits++;

            return $this->memoizedTargets[$memoKey];
        }

        $ext = strtolower((string) pathinfo($realSource, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            $this->recordRejection($rawReference, "Ekstensi gambar tidak diizinkan (.{$ext}).");

            return null;
        }

        $imageInfo = @getimagesize($realSource);
        if (! is_array($imageInfo)) {
            $this->recordRejection($rawReference, 'Gagal memverifikasi magic bytes gambar (file korup atau bukan gambar valid).');

            return null;
        }

        $srcWidth = (int) ($imageInfo[0] ?? 0);
        $srcHeight = (int) ($imageInfo[1] ?? 0);
        $imageType = (int) ($imageInfo[2] ?? 0);

        if (! in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            $this->recordRejection($rawReference, "Tipe gambar internal ({$imageType}) tidak didukung.");

            return null;
        }

        if (
            $srcWidth <= 0
            || $srcHeight <= 0
            || $srcWidth > ImageOptimizerService::MAX_SOURCE_DIMENSION
            || $srcHeight > ImageOptimizerService::MAX_SOURCE_DIMENSION
            || ($srcWidth * $srcHeight) > ImageOptimizerService::MAX_SOURCE_PIXELS
        ) {
            $this->recordRejection($rawReference, "Dimensi gambar ({$srcWidth}x{$srcHeight}) melebihi batas aman 25 MP.");

            return null;
        }

        $sha256 = (string) @hash_file('sha256', $realSource);
        if ($sha256 === '') {
            $this->recordRejection($rawReference, 'Gagal menghitung hash SHA-256 berkas sumber.');

            return null;
        }

        $shortHash = substr($sha256, 0, 16);
        $isGif = $imageType === IMAGETYPE_GIF;
        $canEncodeWebp = ! $isGif && extension_loaded('gd') && function_exists('imagewebp');
        $targetExt = $canEncodeWebp ? 'webp' : $ext;
        $targetPath = "{$cfg['dir']}/{$cfg['prefix']}{$shortHash}.{$targetExt}";

        $beforeBytes = (int) (@filesize($realSource) ?: 0);
        $storage = Storage::disk($this->disk);
        $relativeSource = $this->relativeSourceLabel($realSource);

        if (! $this->dryRun && $storage->exists($targetPath)) {
            $afterBytes = (int) $storage->size($targetPath);
            $variants = [];
            if ($canEncodeWebp && $cfg['variants'] !== []) {
                $variants = $this->optimizer->generateVariants($targetPath, $cfg['variants'], max(76, $cfg['quality'] - 2), $this->disk);
            }
            $this->skippedExisting++;
            $this->recordManifest($relativeSource, $targetPath, $profile, $sha256, $beforeBytes, $afterBytes, $variants);
            $this->memoizedTargets[$memoKey] = $targetPath;

            return $targetPath;
        }

        if ($this->dryRun) {
            $this->imagesProcessed++;
            $this->totalBeforeBytes += $beforeBytes;
            $this->totalAfterBytes += $beforeBytes;
            $this->recordManifest($relativeSource, $targetPath, $profile, $sha256, $beforeBytes, $beforeBytes, []);
            $this->memoizedTargets[$memoKey] = $targetPath;

            return $targetPath;
        }

        $variants = [];
        if ($canEncodeWebp) {
            $webpBytes = $this->encodeWebp($realSource, $imageType, $srcWidth, $srcHeight, $cfg['max_width'], $cfg['quality']);
            if ($webpBytes === null) {
                $this->recordRejection($rawReference, 'GD gagal melakukan re-encode gambar menjadi WebP.');

                return null;
            }

            $storage->put($targetPath, $webpBytes);
            $afterBytes = strlen($webpBytes);

            if ($cfg['variants'] !== []) {
                $variants = $this->optimizer->generateVariants($targetPath, $cfg['variants'], max(76, $cfg['quality'] - 2), $this->disk);
            }
        } else {
            $rawBytes = @file_get_contents($realSource);
            if (! is_string($rawBytes) || $rawBytes === '') {
                $this->recordRejection($rawReference, 'Gagal membaca berkas gambar sumber.');

                return null;
            }
            $storage->put($targetPath, $rawBytes);
            $afterBytes = strlen($rawBytes);
        }

        $this->imagesProcessed++;
        $this->totalBeforeBytes += $beforeBytes;
        $this->totalAfterBytes += $afterBytes;
        $this->recordManifest($relativeSource, $targetPath, $profile, $sha256, $beforeBytes, $afterBytes, $variants);
        $this->memoizedTargets[$memoKey] = $targetPath;

        return $targetPath;
    }

    /**
     * Validate a local PDF (strict `%PDF-` magic bytes + size guardrails) and copy it cleanly to public storage.
     */
    public function processLocalPdf(string $rawReference, string $collectionType): ?string
    {
        $realSource = $this->resolveSafeLocalPath($rawReference);
        if ($realSource === null) {
            return null;
        }

        $cleanCollection = preg_replace('/[^a-z0-9_-]/', '', strtolower($collectionType)) ?: 'regulasi_sk';
        $memoKey = "pdf|{$cleanCollection}|{$realSource}";
        if (isset($this->memoizedTargets[$memoKey])) {
            $this->dedupHits++;

            return $this->memoizedTargets[$memoKey];
        }

        $ext = strtolower((string) pathinfo($realSource, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $this->recordRejection($rawReference, "Ekstensi dokumen bukan PDF (.{$ext}).");

            return null;
        }

        $size = (int) (@filesize($realSource) ?: 0);
        if ($size < self::MIN_PDF_BYTES || $size > self::MAX_PDF_BYTES) {
            $this->recordRejection($rawReference, "Ukuran PDF ({$size} byte) di luar batas aman (512 B - 15 MB).");

            return null;
        }

        $fp = @fopen($realSource, 'rb');
        $header = $fp !== false ? (string) fread($fp, 5) : '';
        if ($fp !== false) {
            fclose($fp);
        }

        if ($header !== '%PDF-') {
            $this->recordRejection($rawReference, 'Magic bytes bukan %PDF- (terdeteksi berkas palsu / artefak infeksi).');

            return null;
        }

        $sha256 = (string) @hash_file('sha256', $realSource);
        $shortHash = substr($sha256, 0, 8);
        $baseSlug = Str::slug((string) pathinfo($realSource, PATHINFO_FILENAME));
        $baseSlug = $baseSlug !== '' ? substr($baseSlug, 0, 64) : 'dokumen';
        $targetPath = "documents/{$cleanCollection}/{$baseSlug}-{$shortHash}.pdf";
        $relativeSource = $this->relativeSourceLabel($realSource);

        $storage = Storage::disk($this->disk);
        if (! $this->dryRun) {
            if ($storage->exists($targetPath)) {
                $this->skippedExisting++;
            } else {
                $bytes = @file_get_contents($realSource);
                if (! is_string($bytes) || $bytes === '') {
                    $this->recordRejection($rawReference, 'Gagal membaca isi berkas PDF.');

                    return null;
                }
                $storage->put($targetPath, $bytes);
                $this->pdfsProcessed++;
            }
        } else {
            $this->pdfsProcessed++;
        }

        $this->recordManifest($relativeSource, $targetPath, 'pdf:'.$cleanCollection, $sha256, $size, $size, []);
        $this->memoizedTargets[$memoKey] = $targetPath;

        return $targetPath;
    }

    /**
     * Resolve a Joomla relative path or pa-gorontalo.go.id URL safely inside the Joomla root directory.
     */
    public function resolveSafeLocalPath(string $rawReference): ?string
    {
        if ($this->resolvedRoot === null) {
            return null;
        }

        $cleaned = trim($rawReference);
        if ($cleaned === '' || str_starts_with(strtolower($cleaned), 'data:')) {
            return null;
        }

        if (preg_match('#^https?://#i', $cleaned) === 1) {
            $host = strtolower((string) parse_url($cleaned, PHP_URL_HOST));
            if ($host !== 'pa-gorontalo.go.id' && $host !== 'www.pa-gorontalo.go.id') {
                return null;
            }

            $cleaned = (string) parse_url($cleaned, PHP_URL_PATH);
        }

        $cleaned = (string) strtok($cleaned, '?#');
        $cleaned = rawurldecode($cleaned);
        $cleaned = ltrim(str_replace('\\', '/', $cleaned), '/');

        if ($cleaned === '' || str_contains($cleaned, "\0")) {
            return null;
        }

        if (preg_match('#(^|/)\.\.(/|$)#', $cleaned) === 1) {
            $this->recordRejection($rawReference, 'Percobaan path traversal (..) diblokir.');

            return null;
        }

        $candidate = $this->resolvedRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $cleaned);
        $realCandidate = realpath($candidate);

        if ($realCandidate === false || ! is_file($realCandidate)) {
            $this->missing[] = $rawReference;

            return null;
        }

        $normalizedRoot = strtolower($this->resolvedRoot.DIRECTORY_SEPARATOR);
        $normalizedCandidate = strtolower($realCandidate);
        if (! str_starts_with($normalizedCandidate, $normalizedRoot)) {
            $this->recordRejection($rawReference, 'Berkas berada di luar direktori proyek Joomla (realpath jail).');

            return null;
        }

        return $realCandidate;
    }

    /**
     * @return array{
     *     images_processed: int,
     *     pdfs_processed: int,
     *     dedup_hits: int,
     *     skipped_existing: int,
     *     rejected_count: int,
     *     missing_count: int,
     *     before_bytes: int,
     *     after_bytes: int,
     *     saved_bytes: int,
     *     saved_percent: float,
     *     manifest_count: int
     * }
     */
    public function stats(): array
    {
        $savedBytes = max(0, $this->totalBeforeBytes - $this->totalAfterBytes);
        $savedPercent = $this->totalBeforeBytes > 0
            ? round(($savedBytes / $this->totalBeforeBytes) * 100, 2)
            : 0.0;

        return [
            'images_processed' => $this->imagesProcessed,
            'pdfs_processed' => $this->pdfsProcessed,
            'dedup_hits' => $this->dedupHits,
            'skipped_existing' => $this->skippedExisting,
            'rejected_count' => count($this->rejected),
            'missing_count' => count(array_unique($this->missing)),
            'before_bytes' => $this->totalBeforeBytes,
            'after_bytes' => $this->totalAfterBytes,
            'saved_bytes' => $savedBytes,
            'saved_percent' => $savedPercent,
            'manifest_count' => count($this->manifest),
        ];
    }

    /**
     * @return list<array{source: string, target: string, profile: string, sha256: string, before_bytes: int, after_bytes: int, variants: list<string>}>
     */
    public function manifest(): array
    {
        return array_values($this->manifest);
    }

    /**
     * @return list<array{source: string, reason: string}>
     */
    public function rejected(): array
    {
        return $this->rejected;
    }

    /**
     * @return list<string>
     */
    public function missing(): array
    {
        return array_values(array_unique($this->missing));
    }

    private function encodeWebp(
        string $sourcePath,
        int $imageType,
        int $srcWidth,
        int $srcHeight,
        int $maxWidth,
        int $quality,
    ): ?string {
        $src = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($src === false) {
            return null;
        }

        $targetCanvas = $src;

        if ($srcWidth > $maxWidth) {
            $targetWidth = $maxWidth;
            $targetHeight = max(1, (int) round(($srcHeight * $maxWidth) / $srcWidth));
            $dst = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($dst === false) {
                unset($src);

                return null;
            }

            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);
            unset($src);
            $targetCanvas = $dst;
        } else {
            if (! imageistruecolor($targetCanvas)) {
                imagepalettetotruecolor($targetCanvas);
            }
            imagealphablending($targetCanvas, false);
            imagesavealpha($targetCanvas, true);
        }

        ob_start();
        $ok = @imagewebp($targetCanvas, null, max(1, min(100, $quality)));
        $webpBytes = ob_get_clean();
        unset($targetCanvas);

        if (! $ok || ! is_string($webpBytes) || $webpBytes === '') {
            return null;
        }

        return $webpBytes;
    }

    /**
     * @param  list<string>  $variants
     */
    private function recordManifest(
        string $source,
        string $target,
        string $profile,
        string $sha256,
        int $beforeBytes,
        int $afterBytes,
        array $variants,
    ): void {
        if (! isset($this->manifest[$target])) {
            $this->manifest[$target] = [
                'source' => $source,
                'target' => $target,
                'profile' => $profile,
                'sha256' => $sha256,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $afterBytes,
                'variants' => $variants,
            ];
        }
    }

    private function recordRejection(string $source, string $reason): void
    {
        $this->rejected[] = [
            'source' => $source,
            'reason' => $reason,
        ];
    }

    private function relativeSourceLabel(string $realSource): string
    {
        if ($this->resolvedRoot === null) {
            return $realSource;
        }

        $prefix = $this->resolvedRoot.DIRECTORY_SEPARATOR;
        if (stripos($realSource, $prefix) === 0) {
            return str_replace('\\', '/', substr($realSource, strlen($prefix)));
        }

        return str_replace('\\', '/', $realSource);
    }
}
