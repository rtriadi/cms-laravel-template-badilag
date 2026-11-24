<?php

declare(strict_types=1);

use App\Services\ImageOptimizerService;
use App\Services\LegacyMigration\LegacyHtmlTransformer;
use App\Services\LegacyMigration\LegacyMediaSanitizer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('unwraps joomla sourcerer blocks and extracts contextual table documents with drive deduplication', function (): void {
    Storage::fake('public');
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'joomla_html_test_'.bin2hex(random_bytes(5));
    mkdir($root, 0775, true);

    try {
        $media = new LegacyMediaSanitizer($root, new ImageOptimizerService, 'public');
        $transformer = new LegacyHtmlTransformer;

        $html = <<<'HTML'
<p>{source}</p>
<p>&lt;style&gt;table { width: 100%; }&lt;/style&gt;</p>
<p>&lt;table&gt;&lt;tbody&gt;
&lt;tr&gt;&lt;td&gt;Bulan&lt;/td&gt;&lt;td&gt;Tahun 2026&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Januari&lt;/td&gt;&lt;td&gt;&lt;a href="https://drive.google.com/file/d/1x2sVVGOtIdyCBrml6i97E5767OCZs9O5/view?usp=sharing"&gt;Download File&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Januari Duplikat&lt;/td&gt;&lt;td&gt;&lt;a href="https://drive.google.com/open?id=1x2sVVGOtIdyCBrml6i97E5767OCZs9O5"&gt;Preview&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Februari&lt;/td&gt;&lt;td&gt;&lt;a href="https://drive.google.com/file/d/1CdbA8Mtc3q7yEGozr8b6ScNzWRPrJ6HI/preview"&gt;Download File&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;/tbody&gt;&lt;/table&gt;</p>
<p>{/source}</p>
HTML;

        $docs = $transformer->extractDocumentsFromHtml(
            $html,
            'lra',
            'Laporan Realisasi Anggaran',
            2025,
            $media,
            '2026-02-01 00:00:00',
        );

        expect($docs)->toHaveCount(2)
            ->and($docs[0]['collection_type'])->toBe('lra')
            ->and($docs[0]['year'])->toBe(2026)
            ->and($docs[0]['month'])->toBe(1)
            ->and($docs[0]['title'])->toContain('Januari')
            ->and($docs[0]['external_url'])->toBe('https://drive.google.com/file/d/1x2sVVGOtIdyCBrml6i97E5767OCZs9O5/view')
            ->and($docs[1]['month'])->toBe(2)
            ->and($docs[1]['external_url'])->toBe('https://drive.google.com/file/d/1CdbA8Mtc3q7yEGozr8b6ScNzWRPrJ6HI/view');
    } finally {
        @rmdir($root);
    }
});

test('rewrites inline local images to webp, strips duplicate opening hero image, and sanitizes xss', function (): void {
    Storage::fake('public');
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'joomla_rich_test_'.bin2hex(random_bytes(5));
    mkdir($root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita', 0775, true);

    try {
        foreach (['hero.png', 'galeri.png'] as $idx => $name) {
            $img = imagecreatetruecolor(640, 360);
            $color = imagecolorallocate($img, 10 + ($idx * 50), 110, 79);
            imagefilledrectangle($img, 0, 0, 640, 360, $color);
            imagepng($img, $root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.$name);
            unset($img);
        }

        $media = new LegacyMediaSanitizer($root, new ImageOptimizerService, 'public');
        $transformer = new LegacyHtmlTransformer;

        $rawHtml = <<<'HTML'
<!--[if gte mso 9]><xml><w:WordDocument></w:WordDocument></xml><![endif]-->
<p><img src="images/berita/hero.png" alt="IMG 20260101" /></p>
<p>Berita resmi <strong>PA Gorontalo</strong> {loadmodule mod_custom} <script>alert(1)</script></p>
<p><img src="images/berita/galeri.png" alt="" onerror="alert(2)" /></p>
HTML;

        $res = $transformer->cleanRichHtml(
            $rawHtml,
            $media,
            'Apel Pagi PA Gorontalo',
            'images/berita/hero.png',
        );

        expect($res['first_local_image'])->toBe('images/berita/hero.png')
            ->and($res['html'])->not->toContain('hero.png')
            ->and($res['html'])->not->toContain('<script')
            ->and($res['html'])->not->toContain('onerror')
            ->and($res['html'])->not->toContain('{loadmodule')
            ->and($res['html'])->toContain('/storage/editor-media/')
            ->and($res['html'])->toContain('alt="Apel Pagi PA Gorontalo"')
            ->and($res['html'])->toContain('loading="lazy"');
    } finally {
        @unlink($root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'hero.png');
        @unlink($root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'galeri.png');
        @rmdir($root.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita');
        @rmdir($root.DIRECTORY_SEPARATOR.'images');
        @rmdir($root);
    }
});
