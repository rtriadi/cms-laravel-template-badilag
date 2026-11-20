<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizerService
{
    public const int MAX_SOURCE_DIMENSION = 8000;

    public const int MAX_SOURCE_PIXELS = 25_000_000;

    /**
     * Store an uploaded image resized (by max width) and encoded as WebP when supported,
     * optionally generating smaller responsive WebP variants alongside the primary image.
     *
     * @param  array<int, int>  $variantWidths
     */
    public function storeOptimized(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1280,
        int $quality = 82,
        string $disk = 'public',
        array $variantWidths = [],
    ): string {
        $directory = trim($directory, '/');
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower($file->getClientOriginalExtension());

        if (
            ! extension_loaded('gd')
            || ! function_exists('imagewebp')
            || $ext === 'gif'
            || $mime === 'image/gif'
            || str_contains($mime, 'svg')
            || $ext === 'svg'
        ) {
            return (string) $file->store($directory, $disk);
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            return (string) $file->store($directory, $disk);
        }

        $imageInfo = @getimagesize($realPath);
        if (! is_array($imageInfo)) {
            return (string) $file->store($directory, $disk);
        }

        [$srcWidth, $srcHeight, $imageType] = $imageInfo;

        if (! in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return (string) $file->store($directory, $disk);
        }

        $webpBytes = $this->convertToWebpBytes($realPath, $imageType, (int) $srcWidth, (int) $srcHeight, $maxWidth, $quality);
        if ($webpBytes === null) {
            return (string) $file->store($directory, $disk);
        }

        $targetPath = $directory.'/'.Str::random(40).'.webp';
        Storage::disk($disk)->put($targetPath, $webpBytes);

        if ($variantWidths !== []) {
            $primaryWidth = min((int) $srcWidth, $maxWidth);
            $this->writeVariantsFromSource($realPath, $imageType, (int) $srcWidth, (int) $srcHeight, $targetPath, $primaryWidth, $variantWidths, $quality, $disk);
        }

        return $targetPath;
    }

    /**
     * Generate missing responsive sibling WebP variants for an already stored image.
     *
     * @param  array<int, int>  $variantWidths
     * @return array<int, string>
     */
    public function generateVariants(
        string $relativePath,
        array $variantWidths,
        int $quality = 80,
        string $disk = 'public',
    ): array {
        $relativePath = ltrim($relativePath, '/');
        $storage = Storage::disk($disk);

        if (
            $relativePath === ''
            || $variantWidths === []
            || ! $storage->exists($relativePath)
            || ! extension_loaded('gd')
            || ! function_exists('imagewebp')
            || strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'webp'
        ) {
            return [];
        }

        $rawBytes = $storage->get($relativePath);
        if (! is_string($rawBytes) || $rawBytes === '') {
            return [];
        }

        $imageInfo = @getimagesizefromstring($rawBytes);
        if (! is_array($imageInfo)) {
            return [];
        }

        [$srcWidth, $srcHeight, $imageType] = $imageInfo;
        if (! in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'satker_var_');
        if ($tmpFile === false) {
            return [];
        }

        try {
            file_put_contents($tmpFile, $rawBytes);

            return $this->writeVariantsFromSource($tmpFile, $imageType, (int) $srcWidth, (int) $srcHeight, $relativePath, (int) $srcWidth, $variantWidths, $quality, $disk);
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Delete a stored image and any generated responsive sibling variants.
     */
    public function deleteWithVariants(string $relativePath, string $disk = 'public'): void
    {
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '') {
            return;
        }

        $storage = Storage::disk($disk);
        $storage->delete($relativePath);

        $dir = trim((string) pathinfo($relativePath, PATHINFO_DIRNAME), './');
        $stem = (string) pathinfo($relativePath, PATHINFO_FILENAME);

        if ($stem === '') {
            return;
        }

        $prefix = ($dir !== '' ? $dir.'/' : '').$stem.'-';
        foreach ($storage->files($dir) as $candidate) {
            if (str_starts_with($candidate, $prefix) && str_ends_with($candidate, 'w.webp')) {
                $storage->delete($candidate);
            }
        }
    }

    /**
     * Build the sibling filename for a given width variant (e.g. `sliders/foo.webp` -> `sliders/foo-480w.webp`).
     */
    public static function variantPath(string $relativePath, int $width): string
    {
        $clean = ltrim($relativePath, '/');
        $dir = trim((string) pathinfo($clean, PATHINFO_DIRNAME), './');
        $stem = (string) pathinfo($clean, PATHINFO_FILENAME);

        return ($dir !== '' ? $dir.'/' : '')."{$stem}-{$width}w.webp";
    }

    /**
     * Resolve `src`, `srcset`, and `sizes` attributes for a stored public image using available WebP variants.
     *
     * @param  array<int, int>  $candidateWidths
     * @return array{src: string, srcset: ?string, sizes: ?string}
     */
    public static function responsiveAttributes(
        string $relativePath,
        array $candidateWidths,
        string $sizes,
        ?int $preferredSrcWidth = null,
        ?int $fallbackPrimaryWidth = null,
    ): array {
        $clean = ltrim($relativePath, '/');
        $defaultSrc = asset('storage/'.$clean);

        if ($clean === '' || strtolower((string) pathinfo($clean, PATHINFO_EXTENSION)) !== 'webp') {
            return ['src' => $defaultSrc, 'srcset' => null, 'sizes' => null];
        }

        $publicRoot = storage_path('app/public');
        $entries = [];
        $chosenSrc = $defaultSrc;

        $sortedWidths = array_values(array_unique(array_filter(array_map('intval', $candidateWidths), static fn (int $w): bool => $w > 0)));
        sort($sortedWidths);

        foreach ($sortedWidths as $w) {
            $varPath = self::variantPath($clean, $w);
            if (is_file($publicRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $varPath))) {
                $varUrl = asset('storage/'.$varPath);
                $entries[] = "{$varUrl} {$w}w";
                if ($preferredSrcWidth !== null && $w === $preferredSrcWidth) {
                    $chosenSrc = $varUrl;
                }
            }
        }

        if ($entries === []) {
            return ['src' => $defaultSrc, 'srcset' => null, 'sizes' => null];
        }

        $dims = self::imageDimensions($clean);
        $primaryWidth = $dims['width'] ?? $fallbackPrimaryWidth;
        if ($primaryWidth !== null && $primaryWidth > end($sortedWidths)) {
            $entries[] = "{$defaultSrc} {$primaryWidth}w";
        } elseif ($primaryWidth === null) {
            $entries[] = "{$defaultSrc} 1280w";
        }

        return [
            'src' => $chosenSrc,
            'srcset' => implode(', ', $entries),
            'sizes' => $sizes,
        ];
    }

    /**
     * Read cached natural pixel dimensions of a stored image on the public disk.
     *
     * @return array{width: int, height: int}|null
     */
    public static function imageDimensions(?string $relativePath): ?array
    {
        $clean = ltrim((string) ($relativePath ?? ''), '/');
        if ($clean === '') {
            return null;
        }

        $fullPath = storage_path('app/public'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $clean));
        if (! is_file($fullPath)) {
            return null;
        }

        $cacheKey = 'img_dims.'.md5($clean.'|'.(string) @filemtime($fullPath));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['width'], $cached['height'])) {
            return [
                'width' => (int) $cached['width'],
                'height' => (int) $cached['height'],
            ];
        }

        $info = @getimagesize($fullPath);
        if (! is_array($info) || (int) $info[0] <= 0 || (int) $info[1] <= 0) {
            return null;
        }

        $dims = [
            'width' => (int) $info[0],
            'height' => (int) $info[1],
        ];

        Cache::put($cacheKey, $dims, 3600);

        return $dims;
    }

    /**
     * @param  array<int, int>  $variantWidths
     * @return array<int, string>
     */
    private function writeVariantsFromSource(
        string $sourcePath,
        int $imageType,
        int $srcWidth,
        int $srcHeight,
        string $primaryRelativePath,
        int $primaryWidth,
        array $variantWidths,
        int $quality,
        string $disk,
    ): array {
        $storage = Storage::disk($disk);
        $created = [];

        foreach (array_unique(array_map('intval', $variantWidths)) as $width) {
            if ($width <= 0 || $width >= $primaryWidth || $width >= $srcWidth) {
                continue;
            }

            $varPath = self::variantPath($primaryRelativePath, $width);
            if ($storage->exists($varPath)) {
                continue;
            }

            $bytes = $this->convertToWebpBytes($sourcePath, $imageType, $srcWidth, $srcHeight, $width, $quality);
            if ($bytes !== null) {
                $storage->put($varPath, $bytes);
                $created[] = $varPath;
            }
        }

        return $created;
    }

    /**
     * Optimize an existing image file stored on a disk, converting/resizing it to WebP.
     *
     * @return array{path: string, before_bytes: int, after_bytes: int, optimized: bool}
     */
    public function optimizeStoredFile(
        string $relativePath,
        int $maxWidth = 1280,
        int $quality = 82,
        string $disk = 'public',
    ): array {
        $relativePath = ltrim($relativePath, '/');
        $storage = Storage::disk($disk);

        if (
            $relativePath === ''
            || ! $storage->exists($relativePath)
            || ! extension_loaded('gd')
            || ! function_exists('imagewebp')
        ) {
            return [
                'path' => $relativePath,
                'before_bytes' => 0,
                'after_bytes' => 0,
                'optimized' => false,
            ];
        }

        $rawBytes = $storage->get($relativePath);
        if (! is_string($rawBytes) || $rawBytes === '') {
            return [
                'path' => $relativePath,
                'before_bytes' => 0,
                'after_bytes' => 0,
                'optimized' => false,
            ];
        }

        $beforeBytes = strlen($rawBytes);
        $imageInfo = @getimagesizefromstring($rawBytes);
        if (! is_array($imageInfo)) {
            return [
                'path' => $relativePath,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $beforeBytes,
                'optimized' => false,
            ];
        }

        [$srcWidth, $srcHeight, $imageType] = $imageInfo;
        $ext = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));

        if (! in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [
                'path' => $relativePath,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $beforeBytes,
                'optimized' => false,
            ];
        }

        if ($ext === 'webp' && $imageType === IMAGETYPE_WEBP && (int) $srcWidth <= $maxWidth) {
            return [
                'path' => $relativePath,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $beforeBytes,
                'optimized' => false,
            ];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'satker_img_');
        if ($tmpFile === false) {
            return [
                'path' => $relativePath,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $beforeBytes,
                'optimized' => false,
            ];
        }

        try {
            file_put_contents($tmpFile, $rawBytes);
            $webpBytes = $this->convertToWebpBytes($tmpFile, $imageType, (int) $srcWidth, (int) $srcHeight, $maxWidth, $quality);
        } finally {
            @unlink($tmpFile);
        }

        if ($webpBytes === null) {
            return [
                'path' => $relativePath,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $beforeBytes,
                'optimized' => false,
            ];
        }

        $afterBytes = strlen($webpBytes);
        $wasResized = (int) $srcWidth > $maxWidth;

        if (! $wasResized && $afterBytes >= $beforeBytes) {
            return [
                'path' => $relativePath,
                'before_bytes' => $beforeBytes,
                'after_bytes' => $beforeBytes,
                'optimized' => false,
            ];
        }

        $dir = trim((string) pathinfo($relativePath, PATHINFO_DIRNAME), './');
        $newPath = ($dir !== '' ? $dir.'/' : '').Str::random(40).'.webp';

        $storage->put($newPath, $webpBytes);

        if ($newPath !== $relativePath) {
            $storage->delete($relativePath);
        }

        return [
            'path' => $newPath,
            'before_bytes' => $beforeBytes,
            'after_bytes' => $afterBytes,
            'optimized' => true,
        ];
    }

    private function convertToWebpBytes(
        string $sourcePath,
        int $imageType,
        int $srcWidth,
        int $srcHeight,
        int $maxWidth,
        int $quality,
    ): ?string {
        if (
            $srcWidth <= 0
            || $srcHeight <= 0
            || $maxWidth <= 0
            || $srcWidth > self::MAX_SOURCE_DIMENSION
            || $srcHeight > self::MAX_SOURCE_DIMENSION
            || ($srcWidth * $srcHeight) > self::MAX_SOURCE_PIXELS
        ) {
            return null;
        }

        $src = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($src === false) {
            return null;
        }

        if (! imageistruecolor($src)) {
            imagepalettetotruecolor($src);
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
}
