<?php

declare(strict_types=1);

use App\Services\ImageOptimizerService;
use App\Services\LegacyMigration\LegacyMediaSanitizer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

function makeTempJoomlaRoot(): string
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'joomla_media_test_'.bin2hex(random_bytes(6));
    mkdir($dir.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita', 0775, true);
    mkdir($dir.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'banners', 0775, true);
    mkdir($dir.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'file', 0775, true);

    return $dir;
}

function createSamplePng(string $path, int $width = 900, int $height = 500): void
{
    $img = imagecreatetruecolor($width, $height);
    $green = imagecolorallocate($img, 5, 110, 79);
    imagefilledrectangle($img, 0, 0, $width, $height, $green);
    imagepng($img, $path);
    unset($img);
}

function removeDirRecursive(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

test('converts valid image to content-addressed webp with responsive variants and deduplicates', function (): void {
    Storage::fake('public');
    $root = makeTempJoomlaRoot();

    try {
        $srcImg = $root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'kegiatan.png';
        createSamplePng($srcImg, 900, 500);

        $sanitizer = new LegacyMediaSanitizer($root, new ImageOptimizerService, 'public');

        $first = $sanitizer->processImage('images/berita/kegiatan.png', 'post_featured');
        $second = $sanitizer->processImage('https://pa-gorontalo.go.id/images/berita/kegiatan.png?v=1', 'post_featured');

        expect($first)->not->toBeNull()
            ->and($first)->toStartWith('posts/')
            ->and($first)->toEndWith('.webp')
            ->and($second)->toBe($first);

        Storage::disk('public')->assertExists((string) $first);
        Storage::disk('public')->assertExists(ImageOptimizerService::variantPath((string) $first, 400));
        Storage::disk('public')->assertExists(ImageOptimizerService::variantPath((string) $first, 720));

        expect($sanitizer->stats()['dedup_hits'])->toBe(1);
    } finally {
        removeDirRecursive($root);
    }
});

test('blocks fake bypass.pdf malware artifact, php scripts, and path traversal', function (): void {
    Storage::fake('public');
    $root = makeTempJoomlaRoot();

    try {
        file_put_contents(
            $root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'banners'.DIRECTORY_SEPARATOR.'bypass.pdf',
            "/**\n* Note: This file may contain artifacts of previous malicious infection.\n*/",
        );
        file_put_contents(
            $root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'.berita.php',
            '<?php echo "evil";',
        );
        file_put_contents(
            $root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'file'.DIRECTORY_SEPARATOR.'resmi.pdf',
            '%PDF-1.4'."\n".str_repeat('0', 800)."\n%%EOF",
        );

        $sanitizer = new LegacyMediaSanitizer($root, new ImageOptimizerService, 'public');

        expect($sanitizer->processLocalPdf('images/banners/bypass.pdf', 'regulasi_sk'))->toBeNull()
            ->and($sanitizer->processImage('images/berita/.berita.php', 'editor'))->toBeNull()
            ->and($sanitizer->processImage('../../etc/passwd', 'editor'))->toBeNull();

        $validPdf = $sanitizer->processLocalPdf('images/file/resmi.pdf', 'dip_ppid');
        expect($validPdf)->not->toBeNull()
            ->and($validPdf)->toStartWith('documents/dip_ppid/resmi-')
            ->and($validPdf)->toEndWith('.pdf');

        Storage::disk('public')->assertExists((string) $validPdf);
        expect($sanitizer->stats()['rejected_count'])->toBe(3);
    } finally {
        removeDirRecursive($root);
    }
});
