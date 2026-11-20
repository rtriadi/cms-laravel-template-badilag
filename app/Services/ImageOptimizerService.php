<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizerService
{
    public const int MAX_SOURCE_DIMENSION = 8000;

    public const int MAX_SOURCE_PIXELS = 25_000_000;

    /**
     * Store an uploaded image resized (by max width) and encoded as WebP when supported.
     */
    public function storeOptimized(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1280,
        int $quality = 82,
        string $disk = 'public',
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

        return $targetPath;
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
