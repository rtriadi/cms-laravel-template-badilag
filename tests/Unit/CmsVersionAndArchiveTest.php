<?php

use App\Support\CmsVersion;
use App\Support\SafeReleaseArchiveExtractor;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'satker_unit_'.bin2hex(random_bytes(6));
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    if (! isset($this->tempDir) || ! is_dir($this->tempDir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isLink() || $item->isFile()) {
            @unlink($item->getPathname());
        } else {
            @rmdir($item->getPathname());
        }
    }

    @rmdir($this->tempDir);
});

it('reads and normalizes version metadata from version.json', function (): void {
    $versionFile = $this->tempDir.DIRECTORY_SEPARATOR.'version.json';
    file_put_contents($versionFile, json_encode([
        'version' => 'v1.2.3',
        'commit' => 'abcdef1234567890abcdef1234567890abcdef12',
        'released_at' => '2026-11-26T08:00:00Z',
        'min_php' => '8.4.0',
    ], JSON_THROW_ON_ERROR));

    $meta = CmsVersion::fromFile($versionFile);

    expect($meta)->toBe([
        'version' => '1.2.3',
        'commit' => 'abcdef1234567890abcdef1234567890abcdef12',
        'released_at' => '2026-11-26T08:00:00Z',
        'min_php' => '8.4.0',
    ])
        ->and(CmsVersion::current($versionFile))->toBe('1.2.3')
        ->and(CmsVersion::normalize('v2.0.0-rc.1'))->toBe('2.0.0-rc.1');
});

it('falls back to satker config version when version.json is missing or malformed', function (): void {
    config()->set('satker.cms_version', '1.0.5-fallback');

    $missing = CmsVersion::fromFile($this->tempDir.DIRECTORY_SEPARATOR.'missing.json');
    expect($missing['version'])->toBe('1.0.5-fallback')
        ->and($missing['min_php'])->toBe('8.4.1');

    $corruptFile = $this->tempDir.DIRECTORY_SEPARATOR.'corrupt.json';
    file_put_contents($corruptFile, '{not-valid-json');

    $corrupt = CmsVersion::fromFile($corruptFile);
    expect($corrupt['version'])->toBe('1.0.5-fallback');
});

it('enforces the HTTPS GitHub download host allowlist', function (): void {
    expect(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.0.1/satkercms-v1.0.1.zip'))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/assets/123'))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://objects.githubusercontent.com/github-production-release-asset/123'))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://release-assets.githubusercontent.com/github-production-release-asset/123'))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://github-releases.githubusercontent.com/123/asset.zip'))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('http://github.com/rtriadi/cms/releases/download/v1.0.1/a.zip'))->toBeFalse()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://evil.example.com/satkercms-v1.0.1.zip'))->toBeFalse()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://github.com.evil.com/a.zip'))->toBeFalse()
        ->and(SafeReleaseArchiveExtractor::isAllowedDownloadUrl('https://token@github.com/rtriadi/cms/a.zip'))->toBeFalse();
});

it('verifies sha256 checksums in both bare and sha256sum file formats', function (): void {
    $archivePath = $this->tempDir.DIRECTORY_SEPARATOR.'release.zip';
    file_put_contents($archivePath, 'satkercms-binary-payload');
    $expectedHash = hash_file('sha256', $archivePath);

    expect(SafeReleaseArchiveExtractor::verifySha256($archivePath, $expectedHash))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::verifySha256($archivePath, strtoupper($expectedHash)."  satkercms-v1.0.1.zip\n"))->toBeTrue()
        ->and(SafeReleaseArchiveExtractor::verifySha256($archivePath, str_repeat('a', 64)))->toBeFalse()
        ->and(SafeReleaseArchiveExtractor::verifySha256($archivePath, 'not-a-valid-sha256'))->toBeFalse();
});

it('extracts valid release archives while skipping .env and storage/app payloads', function (): void {
    $zipPath = $this->tempDir.DIRECTORY_SEPARATOR.'valid.zip';
    $extractDir = $this->tempDir.DIRECTORY_SEPARATOR.'extracted';
    mkdir($extractDir, 0777, true);
    file_put_contents($extractDir.DIRECTORY_SEPARATOR.'.env', 'APP_KEY=existing-secret');

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('version.json', '{"version":"1.1.0"}');
    $zip->addFromString('app/Support/notes..v2.txt', 'double-dot filename is safe');
    $zip->addFromString('.env', 'APP_KEY=malicious-overwrite');
    $zip->addFromString('storage/app/public/logo.webp', 'should-not-overwrite-user-uploads');
    $zip->close();

    $extractor = new SafeReleaseArchiveExtractor;
    $extractor->extract($zipPath, $extractDir);

    expect(file_get_contents($extractDir.DIRECTORY_SEPARATOR.'version.json'))->toBe('{"version":"1.1.0"}')
        ->and(file_get_contents($extractDir.DIRECTORY_SEPARATOR.'app/Support/notes..v2.txt'))->toBe('double-dot filename is safe')
        ->and(file_get_contents($extractDir.DIRECTORY_SEPARATOR.'.env'))->toBe('APP_KEY=existing-secret')
        ->and(file_exists($extractDir.DIRECTORY_SEPARATOR.'storage/app/public/logo.webp'))->toBeFalse();
});

it('rejects archives containing ZipSlip path traversal or absolute paths', function (string $maliciousEntry): void {
    $zipPath = $this->tempDir.DIRECTORY_SEPARATOR.'malicious.zip';
    $extractDir = $this->tempDir.DIRECTORY_SEPARATOR.'target';

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString($maliciousEntry, '<?php echo "pwned";');
    $zip->close();

    $extractor = new SafeReleaseArchiveExtractor;

    expect(fn () => $extractor->extract($zipPath, $extractDir))
        ->toThrow(RuntimeException::class);
})->with([
    'parent traversal' => '../evil.php',
    'nested backslash traversal' => 'app\\..\\..\\evil.php',
    'absolute unix path' => '/etc/passwd',
    'windows drive path' => 'C:/evil.php',
]);

it('rejects archives containing Unix symlink entries', function (): void {
    $zipPath = $this->tempDir.DIRECTORY_SEPARATOR.'symlink.zip';
    $extractDir = $this->tempDir.DIRECTORY_SEPARATOR.'symlink_target';

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('public/sneaky-link', '/etc/passwd');
    $zip->setExternalAttributesName('public/sneaky-link', ZipArchive::OPSYS_UNIX, (0120777 << 16));
    $zip->close();

    $extractor = new SafeReleaseArchiveExtractor;

    expect(fn () => $extractor->extract($zipPath, $extractDir))
        ->toThrow(RuntimeException::class);
});
