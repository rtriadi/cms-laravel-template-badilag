<?php

use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\Dashboard;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\CmsUpdateCheckService;
use App\Services\CmsUpdateQueueService;
use App\Services\CmsZeroDowntimeUpdater;
use App\Support\SafeReleaseArchiveExtractor;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function removeUpdateTestDirectory(string $dir): void
{
    if (! file_exists($dir) && ! is_link($dir) && @readlink($dir) === false) {
        return;
    }

    if (is_link($dir) || @readlink($dir) !== false) {
        if (PHP_OS_FAMILY === 'Windows' && is_dir($dir)) {
            @rmdir($dir);
        } else {
            @unlink($dir);
        }

        return;
    }

    if (! is_dir($dir)) {
        @unlink($dir);

        return;
    }

    $entries = @scandir($dir) ?: [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        removeUpdateTestDirectory($dir.DIRECTORY_SEPARATOR.$entry);
    }

    @rmdir($dir);
}

function createDirectoryLinkForTest(string $target, string $link): void
{
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = sprintf('cmd /c mklink /J %s %s >nul 2>&1', escapeshellarg($link), escapeshellarg($target));
        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new RuntimeException("Failed creating Windows junction {$link} -> {$target}");
        }

        return;
    }

    symlink($target, $link);
}

/**
 * @param  array<string, string>  $files
 * @return array{zip_bytes: string, sha256: string}
 */
function buildSyntheticReleaseZip(string $tempDir, string $name, array $files): array
{
    $zipPath = $tempDir.DIRECTORY_SEPARATOR.$name;
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $relativePath => $contents) {
        $zip->addFromString($relativePath, $contents);
    }

    $zip->close();

    return [
        'zip_bytes' => (string) file_get_contents($zipPath),
        'sha256' => (string) hash_file('sha256', $zipPath),
    ];
}

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'satker_update_feat_'.bin2hex(random_bytes(6));
    mkdir($this->tempDir, 0777, true);

    $this->stateFile = $this->tempDir.DIRECTORY_SEPARATOR.'cms-update-state.json';
    $this->versionFile = $this->tempDir.DIRECTORY_SEPARATOR.'version.json';
    $this->triggerFile = $this->tempDir.DIRECTORY_SEPARATOR.'cms-update-trigger.json';
    $this->jobStatusFile = $this->tempDir.DIRECTORY_SEPARATOR.'cms-update-job.json';

    file_put_contents($this->versionFile, json_encode([
        'version' => '1.0.0',
        'commit' => '1111111111111111111111111111111111111111',
        'released_at' => '2026-11-01T00:00:00Z',
        'min_php' => '8.4.1',
    ], JSON_THROW_ON_ERROR));

    config()->set('satker.update.enabled', true);
    config()->set('satker.update.repository', 'rtriadi/cms-laravel-template-badilag');
    config()->set('satker.update.channel', 'stable');
    config()->set('satker.update.branch', 'main');
    config()->set('satker.update.state_path', $this->stateFile);
    config()->set('satker.update.trigger_path', $this->triggerFile);
    config()->set('satker.update.job_status_path', $this->jobStatusFile);
    config()->set('satker.update.web_trigger_enabled', true);
    config()->set('satker.update.auto_update_enabled', false);
    config()->set('satker.update.check_ttl_seconds', 43200);
});

afterEach(function (): void {
    if (! isset($this->tempDir)) {
        return;
    }

    removeUpdateTestDirectory($this->tempDir);
});

it('detects a newer stable GitHub release, caches state to disk, and respects TTL', function (): void {
    Http::fake([
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/latest' => Http::response([
            'tag_name' => 'v1.1.0',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-26T06:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.1.0',
            'body' => "## Perubahan Penting\n- Perbaikan keamanan sanitasi URL\n- Peningkatan performa beranda",
            'assets' => [
                [
                    'name' => 'satkercms-v1.1.0.zip',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip',
                ],
                [
                    'name' => 'satkercms-v1.1.0.zip.sha256',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip.sha256',
                ],
            ],
        ], 200),
    ]);

    $service = new CmsUpdateCheckService($this->versionFile);
    $state = $service->check(force: true);

    expect($state['update_available'])->toBeTrue()
        ->and($state['installed_version'])->toBe('1.0.0')
        ->and($state['latest_version'])->toBe('1.1.0')
        ->and($state['zip_url'])->toBe('https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip')
        ->and($state['sha256_url'])->toBe('https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip.sha256')
        ->and(is_file($this->stateFile))->toBeTrue();

    // Second call within TTL must reuse persistent state without hitting GitHub again
    $cachedState = $service->check(force: false);
    expect($cachedState['latest_version'])->toBe('1.1.0');
    Http::assertSentCount(1);
});

it('reports no update when installed version equals latest or release is draft/prerelease', function (): void {
    Http::fakeSequence()
        ->push([
            'tag_name' => 'v1.0.0',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-01T00:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.0.0',
            'body' => 'Initial stable release',
            'assets' => [],
        ], 200)
        ->push([
            'tag_name' => 'v2.0.0-beta.1',
            'draft' => false,
            'prerelease' => true,
            'published_at' => '2026-11-26T00:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v2.0.0-beta.1',
            'body' => 'Beta preview',
            'assets' => [],
        ], 200);

    $service = new CmsUpdateCheckService($this->versionFile);

    $sameVersion = $service->check(force: true);
    expect($sameVersion['update_available'])->toBeFalse();

    $prerelease = $service->check(force: true);
    expect($prerelease['update_available'])->toBeFalse();
});

it('degrades gracefully with 1-hour backoff when GitHub returns rate-limit 403', function (): void {
    Http::fake([
        'https://api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403),
    ]);

    $service = new CmsUpdateCheckService($this->versionFile);
    $state = $service->check(force: true);

    expect($state['update_available'])->toBeFalse()
        ->and($state['last_error'])->not->toBeNull()
        ->and($state['retry_after'])->toBeGreaterThan(now()->timestamp + 3000);

    // Lazy check during backoff must NOT send another HTTP request
    $service->checkIfStale();
    Http::assertSentCount(1);
});

it('detects branch commit differences when channel is set to dev', function (): void {
    config()->set('satker.update.channel', 'dev');
    config()->set('satker.update.branch', 'main');

    Http::fake([
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/commits/main' => Http::response([
            'sha' => '2222222222222222222222222222222222222222',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/commit/2222222222222222222222222222222222222222',
            'commit' => [
                'message' => "feat: tambah modul audit cepat\n\nDetail tambahan commit",
                'committer' => [
                    'date' => '2026-11-26T07:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $service = new CmsUpdateCheckService($this->versionFile);
    $state = $service->check(force: true);

    expect($state['channel'])->toBe('dev')
        ->and($state['update_available'])->toBeTrue()
        ->and($state['latest_commit'])->toBe('2222222222222222222222222222222222222222')
        ->and($state['latest_version'])->toBe('main@2222222');
});

it('runs cms:check-update artisan command cleanly', function (): void {
    Http::fake([
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/latest' => Http::response([
            'tag_name' => 'v1.0.0',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-01T00:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.0.0',
            'body' => 'Rilis stabil',
            'assets' => [],
        ], 200),
    ]);

    $this->artisan('cms:check-update', ['--force' => true])
        ->assertExitCode(0);
});

it('validates signed URL and single-use nonce on /_system/opcache-reset even before setup', function (): void {
    expect(SatkerSetting::query()->count())->toBe(0);

    // Unsigned request must be forbidden, NOT 302 redirected to /setup
    $this->postJson('/_system/opcache-reset')
        ->assertForbidden();

    $nonce = 'test-opcache-nonce-'.bin2hex(random_bytes(8));
    Cache::put('cms_opcache_nonce:'.$nonce, true, 30);

    $signedUrl = URL::temporarySignedRoute(
        'system.opcache-reset',
        now()->addSeconds(30),
        ['nonce' => $nonce]
    );

    $this->postJson($signedUrl)
        ->assertOk()
        ->assertExactJson(['ok' => true]);

    expect(DB::table('page_visits')->where('path', 'like', '/_system%')->count())->toBe(0);

    // Replay with the exact same signed URL must be rejected immediately
    $this->postJson($signedUrl)
        ->assertForbidden();
});

it('blocks redirect rules targeting /_system paths and disallows /_system in robots.txt', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    SatkerSetting::query()->create([
        'nama_satker' => 'Pengadilan Agama Uji',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'is_setup_completed' => true,
        'is_tingkat_locked' => true,
    ]);

    $admin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/_system/opcache-reset')
        ->set('to_path', '/halaman/profil')
        ->set('status_code', 301)
        ->call('saveRedirect')
        ->assertHasErrors(['from_path']);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /_system', escape: false);
});

it('validates every redirect hop on download and strips Authorization before CDN redirect', function (): void {
    config()->set('satker.update.github_token', 'ghp_secret_token_123');

    Http::fake([
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/assets/1' => Http::response('', 302, [
            'Location' => 'https://release-assets.githubusercontent.com/signed-asset.zip',
        ]),
        'https://release-assets.githubusercontent.com/signed-asset.zip' => Http::response('zip-binary-ok', 200),
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/bad.zip' => Http::response('', 302, [
            'Location' => 'https://evil.example.com/payload.zip',
        ]),
    ]);

    $updater = new CmsZeroDowntimeUpdater(
        new SafeReleaseArchiveExtractor,
        new CmsUpdateCheckService($this->versionFile),
    );

    $dest = $this->tempDir.DIRECTORY_SEPARATOR.'downloaded.zip';
    $updater->downloadSecurely('https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/assets/1', $dest);

    expect(file_get_contents($dest))->toBe('zip-binary-ok');

    Http::assertSent(function (Request $request): bool {
        if (str_starts_with($request->url(), 'https://api.github.com/')) {
            return $request->hasHeader('Authorization', 'Bearer ghp_secret_token_123');
        }

        if (str_starts_with($request->url(), 'https://release-assets.githubusercontent.com/')) {
            return ! $request->hasHeader('Authorization');
        }

        return true;
    });

    $badDest = $this->tempDir.DIRECTORY_SEPARATOR.'blocked.zip';
    expect(fn () => $updater->downloadSecurely(
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/bad.zip',
        $badDest
    ))->toThrow(RuntimeException::class);
});

it('performs atomic_release symlink swap, preserves previous Vite chunks, and supports instant rollback', function (): void {
    $root = $this->tempDir.DIRECTORY_SEPARATOR.'vps_site';
    $v100 = $root.DIRECTORY_SEPARATOR.'releases'.DIRECTORY_SEPARATOR.'v1.0.0';
    $shared = $root.DIRECTORY_SEPARATOR.'shared';
    $current = $root.DIRECTORY_SEPARATOR.'current';

    mkdir($v100.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets', 0777, true);
    mkdir($shared.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public', 0777, true);

    file_put_contents($shared.DIRECTORY_SEPARATOR.'.env', "APP_KEY=base64:sharedsecret\n");
    file_put_contents($shared.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'sk.pdf', 'pdf-bytes');
    file_put_contents($v100.DIRECTORY_SEPARATOR.'version.json', json_encode(['version' => '1.0.0', 'min_php' => '8.4.1']));
    file_put_contents($v100.DIRECTORY_SEPARATOR.'artisan', "<?php\n");
    file_put_contents($v100.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json', '{"old":true}');
    file_put_contents($v100.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'hero-old.js', 'old-chunk');

    createDirectoryLinkForTest($v100, $current);

    $bundle = buildSyntheticReleaseZip($this->tempDir, 'satkercms-v1.1.0.zip', [
        'version.json' => json_encode(['version' => '1.1.0', 'commit' => str_repeat('b', 40), 'min_php' => '8.4.1']),
        'artisan' => "<?php\n",
        'app/NewFeature.php' => "<?php // v1.1.0\n",
        'storage/logs/.gitignore' => "*\n",
        'public/build/manifest.json' => '{"new":true}',
        'public/build/assets/hero-new.js' => 'new-chunk',
    ]);

    Http::fake([
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip' => Http::response($bundle['zip_bytes'], 200),
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip.sha256' => Http::response($bundle['sha256']."  satkercms-v1.1.0.zip\n", 200),
        '*' => Http::response(['ok' => true], 200),
    ]);

    $invokedPaths = [];
    $updater = new CmsZeroDowntimeUpdater(
        new SafeReleaseArchiveExtractor,
        new CmsUpdateCheckService($v100.DIRECTORY_SEPARATOR.'version.json'),
        function (string $releasePath) use (&$invokedPaths): bool {
            $invokedPaths[] = $releasePath;

            return true;
        }
    );

    $result = $updater->updateToRelease([
        'version' => '1.1.0',
        'tag' => 'v1.1.0',
        'zip_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip',
        'sha256_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.1.0/satkercms-v1.1.0.zip.sha256',
    ], $v100);

    $v110 = $root.DIRECTORY_SEPARATOR.'releases'.DIRECTORY_SEPARATOR.'v1.1.0';
    $currentVersionJson = json_decode((string) file_get_contents($current.DIRECTORY_SEPARATOR.'version.json'), true);

    expect($result['mode'])->toBe('atomic_release')
        ->and($result['version'])->toBe('1.1.0')
        ->and($currentVersionJson['version'])->toBe('1.1.0')
        ->and(file_exists($v110.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'hero-old.js'))->toBeTrue()
        ->and(file_exists($v110.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'hero-new.js'))->toBeTrue()
        ->and(file_exists($v110.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'sk.pdf'))->toBeTrue()
        ->and($invokedPaths)->toHaveCount(1);

    // Instant rollback must switch `current` back to v1.0.0
    $rolledBack = $updater->rollback(basePath: $v110);
    $afterRollbackJson = json_decode((string) file_get_contents($current.DIRECTORY_SEPARATOR.'version.json'), true);

    expect($rolledBack['version'])->toBe('1.0.0')
        ->and($afterRollbackJson['version'])->toBe('1.0.0');
});

it('performs staged_inplace swap with separate public_html, updates database/data, and supports rollback and auto-revert', function (): void {
    $hostingRoot = $this->tempDir.DIRECTORY_SEPARATOR.'cpanel_user';
    $core = $hostingRoot.DIRECTORY_SEPARATOR.'satkercms_core';
    $publicHtml = $hostingRoot.DIRECTORY_SEPARATOR.'public_html';

    mkdir($core.DIRECTORY_SEPARATOR.'app', 0777, true);
    mkdir($core.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache', 0777, true);
    mkdir($core.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'data', 0777, true);
    mkdir($core.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public', 0777, true);
    mkdir($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets', 0777, true);

    file_put_contents($core.DIRECTORY_SEPARATOR.'.env', "APP_KEY=cpanel-secret\n");
    file_put_contents($core.DIRECTORY_SEPARATOR.'version.json', json_encode(['version' => '1.0.0', 'min_php' => '8.4.1']));
    file_put_contents($core.DIRECTORY_SEPARATOR.'artisan', "<?php\n");
    file_put_contents($core.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Sample.php', "<?php // v1.0.0\n");
    file_put_contents($core.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'juknis-2026.json', '{"edition":1}');
    file_put_contents($core.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'packages.php', '<?php return ["stale" => true];');
    file_put_contents($core.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'keep.webp', 'keep-image');
    file_put_contents($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json', '{"v":1}');
    file_put_contents($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'app-v1.css', 'body{}');

    config()->set('satker.update.public_path_override', $publicHtml);

    $bundle = buildSyntheticReleaseZip($this->tempDir, 'satkercms-v1.2.0.zip', [
        'version.json' => json_encode(['version' => '1.2.0', 'min_php' => '8.4.1']),
        'artisan' => "<?php\n",
        'app/Sample.php' => "<?php // v1.2.0\n",
        'database/data/juknis-2026.json' => '{"edition":2}',
        'public/build/manifest.json' => '{"v":2}',
        'public/build/assets/app-v2.css' => 'main{}',
    ]);

    Http::fake([
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip' => Http::response($bundle['zip_bytes'], 200),
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip.sha256' => Http::response($bundle['sha256'], 200),
        '*' => Http::response(['ok' => true], 200),
    ]);

    $updater = new CmsZeroDowntimeUpdater(
        new SafeReleaseArchiveExtractor,
        new CmsUpdateCheckService($core.DIRECTORY_SEPARATOR.'version.json'),
        fn (string $path): bool => ! is_file($path.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'packages.php'),
    );

    $result = $updater->updateToRelease([
        'version' => '1.2.0',
        'tag' => 'v1.2.0',
        'zip_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip',
        'sha256_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip.sha256',
    ], $core);

    expect($result['mode'])->toBe('staged_inplace')
        ->and(json_decode((string) file_get_contents($core.DIRECTORY_SEPARATOR.'version.json'), true)['version'])->toBe('1.2.0')
        ->and(file_get_contents($core.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'juknis-2026.json'))->toBe('{"edition":2}')
        ->and(file_get_contents($core.DIRECTORY_SEPARATOR.'.env'))->toBe("APP_KEY=cpanel-secret\n")
        ->and(file_get_contents($core.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'keep.webp'))->toBe('keep-image')
        ->and(file_get_contents($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json'))->toBe('{"v":2}')
        ->and(file_exists($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'app-v1.css'))->toBeTrue()
        ->and(file_exists($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'app-v2.css'))->toBeTrue();

    // Rollback staged_inplace restores both core files and public_html manifest.json
    $rolled = $updater->rollback(basePath: $core);
    expect($rolled['version'])->toBe('1.0.0')
        ->and(file_get_contents($core.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'juknis-2026.json'))->toBe('{"edition":1}')
        ->and(file_get_contents($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json'))->toBe('{"v":1}');

    // Auto-revert when post-swap runner fails
    $failingUpdater = new CmsZeroDowntimeUpdater(
        new SafeReleaseArchiveExtractor,
        new CmsUpdateCheckService($core.DIRECTORY_SEPARATOR.'version.json'),
        fn (): bool => false,
    );

    expect(fn () => $failingUpdater->updateToRelease([
        'version' => '1.2.0',
        'tag' => 'v1.2.0',
        'zip_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip',
        'sha256_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip.sha256',
    ], $core))->toThrow(RuntimeException::class);

    expect(json_decode((string) file_get_contents($core.DIRECTORY_SEPARATOR.'version.json'), true)['version'])->toBe('1.0.0')
        ->and(file_get_contents($publicHtml.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json'))->toBe('{"v":1}');
});

it('aborts update immediately when sha256 checksum mismatches', function (): void {
    $core = $this->tempDir.DIRECTORY_SEPARATOR.'tampered_site';
    mkdir($core.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app', 0777, true);
    file_put_contents($core.DIRECTORY_SEPARATOR.'version.json', json_encode(['version' => '1.0.0', 'min_php' => '8.4.1']));

    $bundle = buildSyntheticReleaseZip($this->tempDir, 'tampered.zip', [
        'version.json' => json_encode(['version' => '1.9.0', 'min_php' => '8.4.1']),
    ]);

    Http::fake([
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.9.0/tampered.zip' => Http::response($bundle['zip_bytes'], 200),
        'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.9.0/tampered.zip.sha256' => Http::response(str_repeat('0', 64), 200),
    ]);

    $updater = new CmsZeroDowntimeUpdater(
        new SafeReleaseArchiveExtractor,
        new CmsUpdateCheckService($core.DIRECTORY_SEPARATOR.'version.json'),
    );

    expect(fn () => $updater->updateToRelease([
        'version' => '1.9.0',
        'tag' => 'v1.9.0',
        'zip_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.9.0/tampered.zip',
        'sha256_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.9.0/tampered.zip.sha256',
    ], $core))->toThrow(RuntimeException::class);

    expect(json_decode((string) file_get_contents($core.DIRECTORY_SEPARATOR.'version.json'), true)['version'])->toBe('1.0.0');
});

it('runs cms:update --local-only cleanly', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    SatkerSetting::query()->create([
        'nama_satker' => 'Pengadilan Agama Uji',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'is_setup_completed' => true,
        'is_tingkat_locked' => true,
    ]);

    $this->artisan('cms:update', ['--local-only' => true])
        ->assertExitCode(0);
});

it('displays update banner and allows manual check on Dashboard only for super_admin and admin_konten', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    SatkerSetting::query()->create([
        'nama_satker' => 'Pengadilan Agama Uji',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'is_setup_completed' => true,
        'is_tingkat_locked' => true,
    ]);

    file_put_contents($this->stateFile, json_encode([
        'channel' => 'stable',
        'installed_version' => '1.0.0-dev',
        'latest_version' => '1.3.0',
        'latest_tag' => 'v1.3.0',
        'update_available' => true,
        'published_at' => '2026-11-26T08:00:00Z',
        'release_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.3.0',
        'release_notes' => 'Penambahan modul audit dan perbaikan aksesibilitas',
        'checked_at' => now()->timestamp,
        'retry_after' => null,
        'last_error' => null,
    ], JSON_THROW_ON_ERROR));

    $superAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('super_admin');

    $penilai = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $penilai->assignRole('penilai_internal');

    $this->actingAs($superAdmin);
    Livewire::test(Dashboard::class)
        ->assertSee('Pembaruan SatkerCMS Tersedia')
        ->assertSee('1.3.0')
        ->assertSee('php artisan cms:update')
        ->assertSee('Cek Pembaruan');

    // Dev channel shows git pull + --local-only command hint
    config()->set('satker.update.channel', 'dev');
    file_put_contents($this->stateFile, json_encode([
        'channel' => 'dev',
        'installed_version' => '1.0.0-dev',
        'installed_commit' => str_repeat('1', 40),
        'latest_version' => 'main@abcdef1',
        'latest_commit' => 'abcdef1234567890abcdef1234567890abcdef12',
        'update_available' => true,
        'published_at' => '2026-11-26T09:00:00Z',
        'release_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/commit/abcdef1234567890abcdef1234567890abcdef12',
        'release_notes' => 'chore: sinkronisasi kriteria terbaru',
        'checked_at' => now()->timestamp,
        'retry_after' => null,
        'last_error' => null,
    ], JSON_THROW_ON_ERROR));

    Livewire::test(Dashboard::class)
        ->assertSee('main@abcdef1')
        ->assertSee('git pull && php artisan cms:update --local-only');

    // Non-privileged role (penilai_internal) must NOT see update banner or trigger checkForUpdates
    config()->set('satker.update.channel', 'stable');
    $this->actingAs($penilai);

    Livewire::test(Dashboard::class)
        ->assertDontSee('Pembaruan SatkerCMS Tersedia')
        ->assertDontSee('Cek Pembaruan')
        ->call('checkForUpdates')
        ->assertForbidden();

    // Manual trigger by super_admin hits GitHub and shows up-to-date feedback
    Http::fake([
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/latest' => Http::response([
            'tag_name' => 'v1.0.0-dev',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-26T09:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.0.0-dev',
            'body' => 'Versi saat ini',
            'assets' => [],
        ], 200),
    ]);

    $this->actingAs($superAdmin);
    Livewire::test(Dashboard::class)
        ->call('checkForUpdates')
        ->assertSee('versi terbaru');
});

it('signs, consumes once, rejects tampered or expired triggers, and cancels queued updates in CmsUpdateQueueService', function (): void {
    $queue = new CmsUpdateQueueService;

    $job = $queue->enqueueUpdate(
        targetTag: 'v1.2.0',
        skipBackup: true,
        userId: 1,
        userName: 'Ketua Admin',
        fromVersion: '1.0.0',
    );

    expect($job['status'])->toBe('queued')
        ->and($job['action'])->toBe('update')
        ->and($job['target_tag'])->toBe('v1.2.0')
        ->and($job['skip_backup'])->toBeTrue()
        ->and(is_file($this->triggerFile))->toBeTrue();

    // Valid trigger is consumed strictly once (deleted immediately on consume)
    $consumed = $queue->consumeValidTrigger();
    expect($consumed)->not->toBeNull()
        ->and($consumed['target_tag'])->toBe('v1.2.0')
        ->and($consumed['skip_backup'])->toBeTrue()
        ->and(is_file($this->triggerFile))->toBeFalse()
        ->and($queue->consumeValidTrigger())->toBeNull();

    // Tampered HMAC signature must be rejected and discarded
    $queue->clearJobStatus();
    $queue->enqueueUpdate('v1.2.0', false, 1, 'Ketua Admin', '1.0.0');
    $raw = json_decode((string) file_get_contents($this->triggerFile), true);
    $raw['target_tag'] = 'v9.9.9';
    file_put_contents($this->triggerFile, json_encode($raw, JSON_THROW_ON_ERROR));

    expect($queue->consumeValidTrigger())->toBeNull()
        ->and(is_file($this->triggerFile))->toBeFalse();

    // Expired trigger must be rejected and discarded
    $queue->clearJobStatus();
    $queue->enqueueUpdate('v1.2.0', false, 1, 'Ketua Admin', '1.0.0');
    $expired = json_decode((string) file_get_contents($this->triggerFile), true);
    $expired['expires_at'] = now()->subMinutes(5)->timestamp;
    file_put_contents($this->triggerFile, json_encode($expired, JSON_THROW_ON_ERROR));

    expect($queue->consumeValidTrigger())->toBeNull()
        ->and(is_file($this->triggerFile))->toBeFalse();

    // Cancel removes pending trigger and clears queued status
    $queue->clearJobStatus();
    $queue->enqueueUpdate('v1.2.0', false, 1, 'Ketua Admin', '1.0.0');
    expect($queue->cancelQueued())->toBeTrue()
        ->and(is_file($this->triggerFile))->toBeFalse()
        ->and($queue->getJobStatus())->toBeNull();
});

it('processes queued update triggers and respects CMS_UPDATE_AUTO flag in cms:process-update-queue', function (): void {
    Http::fake([
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/tags/v1.2.0' => Http::response([
            'tag_name' => 'v1.2.0',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-27T08:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.2.0',
            'body' => 'Rilis uji antrean',
            'assets' => [
                [
                    'name' => 'satkercms-v1.2.0.zip',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip',
                ],
                [
                    'name' => 'satkercms-v1.2.0.zip.sha256',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.2.0/satkercms-v1.2.0.zip.sha256',
                ],
            ],
        ], 200),
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/latest' => Http::response([
            'tag_name' => 'v1.4.0',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-27T08:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.4.0',
            'body' => 'Rilis otomatis dini hari',
            'assets' => [
                [
                    'name' => 'satkercms-v1.4.0.zip',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.4.0/satkercms-v1.4.0.zip',
                ],
                [
                    'name' => 'satkercms-v1.4.0.zip.sha256',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.4.0/satkercms-v1.4.0.zip.sha256',
                ],
            ],
        ], 200),
        'https://api.github.com/repos/rtriadi/cms-laravel-template-badilag/releases/tags/v1.4.0' => Http::response([
            'tag_name' => 'v1.4.0',
            'draft' => false,
            'prerelease' => false,
            'published_at' => '2026-11-27T08:00:00Z',
            'html_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.4.0',
            'body' => 'Rilis otomatis dini hari',
            'assets' => [
                [
                    'name' => 'satkercms-v1.4.0.zip',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.4.0/satkercms-v1.4.0.zip',
                ],
                [
                    'name' => 'satkercms-v1.4.0.zip.sha256',
                    'browser_download_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.4.0/satkercms-v1.4.0.zip.sha256',
                ],
            ],
        ], 200),
    ]);

    // Idle run when no trigger exists succeeds immediately with zero HTTP calls
    $this->artisan('cms:process-update-queue')
        ->assertExitCode(0);
    Http::assertNothingSent();

    // Auto mode does nothing when CMS_UPDATE_AUTO is false
    config()->set('satker.update.auto_update_enabled', false);
    $this->artisan('cms:process-update-queue', ['--auto' => true])
        ->assertExitCode(0);
    Http::assertNothingSent();

    // Queued trigger executes updater and writes succeeded job status
    $queue = app(CmsUpdateQueueService::class);
    $queue->enqueueUpdate('v1.2.0', skipBackup: true, userId: 1, userName: 'Super Admin', fromVersion: '1.0.0');

    $mockUpdater = Mockery::mock(CmsZeroDowntimeUpdater::class);
    $mockUpdater->shouldReceive('detectLayout')->andReturn(['mode' => 'staged_inplace']);
    $mockUpdater->shouldReceive('updateToRelease')
        ->once()
        ->andReturn(['mode' => 'staged_inplace', 'version' => '1.2.0', 'previous_version' => '1.0.0']);
    $this->app->instance(CmsZeroDowntimeUpdater::class, $mockUpdater);

    $this->artisan('cms:process-update-queue')
        ->assertExitCode(0);

    $status = $queue->getJobStatus();
    expect($status)->not->toBeNull()
        ->and($status['status'])->toBe('succeeded')
        ->and($status['to_version'])->toBe('1.2.0');

    // Auto mode with CMS_UPDATE_AUTO=true checks GitHub and triggers update automatically
    $queue->clearJobStatus();
    config()->set('satker.update.auto_update_enabled', true);

    $autoMockUpdater = Mockery::mock(CmsZeroDowntimeUpdater::class);
    $autoMockUpdater->shouldReceive('detectLayout')->andReturn(['mode' => 'staged_inplace']);
    $autoMockUpdater->shouldReceive('updateToRelease')
        ->once()
        ->andReturn(['mode' => 'staged_inplace', 'version' => '1.4.0', 'previous_version' => '1.0.0']);
    $this->app->instance(CmsZeroDowntimeUpdater::class, $autoMockUpdater);

    $this->artisan('cms:process-update-queue', ['--auto' => true, '--skip-backup' => true])
        ->assertExitCode(0);

    $autoStatus = $queue->getJobStatus();
    expect($autoStatus)->not->toBeNull()
        ->and($autoStatus['status'])->toBe('succeeded')
        ->and($autoStatus['requested_by_name'])->toBe('Auto-Updater Terjadwal');
});

it('allows only super_admin to queue or cancel 1-click web updates on Dashboard and respects CMS_UPDATE_WEB_TRIGGER', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    SatkerSetting::query()->create([
        'nama_satker' => 'Pengadilan Agama Uji',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'is_setup_completed' => true,
        'is_tingkat_locked' => true,
    ]);

    file_put_contents($this->stateFile, json_encode([
        'channel' => 'stable',
        'installed_version' => '1.0.0-dev',
        'latest_version' => '1.3.0',
        'latest_tag' => 'v1.3.0',
        'update_available' => true,
        'published_at' => '2026-11-27T08:00:00Z',
        'release_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/tag/v1.3.0',
        'release_notes' => 'Rilis 1.3.0',
        'zip_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.3.0/satkercms-v1.3.0.zip',
        'sha256_url' => 'https://github.com/rtriadi/cms-laravel-template-badilag/releases/download/v1.3.0/satkercms-v1.3.0.zip.sha256',
        'checked_at' => now()->timestamp,
        'retry_after' => null,
        'last_error' => null,
    ], JSON_THROW_ON_ERROR));

    $superAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('super_admin');

    $adminKonten = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $adminKonten->assignRole('admin_konten');

    // admin_konten sees banner & CLI, does NOT see 1-click update button, and gets 403 on queueWebUpdate
    $this->actingAs($adminKonten);
    Livewire::test(Dashboard::class)
        ->assertSee('Pembaruan SatkerCMS Tersedia')
        ->assertDontSee('Perbarui ke v1.3.0')
        ->call('queueWebUpdate')
        ->assertForbidden();

    // super_admin sees 1-click button, can queue update with skipBackup option, sees queued status, and can cancel
    $this->actingAs($superAdmin);
    Livewire::test(Dashboard::class)
        ->assertSee('Perbarui ke v1.3.0')
        ->set('skipUpdateBackup', true)
        ->call('queueWebUpdate')
        ->assertSee('Menunggu antrean')
        ->assertSee('Batalkan Antrean')
        ->call('cancelQueuedUpdate')
        ->assertDontSee('Batalkan Antrean');

    // When CMS_UPDATE_WEB_TRIGGER=false, even super_admin cannot see or call 1-click trigger
    config()->set('satker.update.web_trigger_enabled', false);
    Livewire::test(Dashboard::class)
        ->assertDontSee('Perbarui ke v1.3.0')
        ->call('queueWebUpdate')
        ->assertForbidden();
});
