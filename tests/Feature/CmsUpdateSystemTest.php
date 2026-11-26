<?php

use App\Livewire\Admin\AuditAndBackupManager;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\CmsUpdateCheckService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'satker_update_feat_'.bin2hex(random_bytes(6));
    mkdir($this->tempDir, 0777, true);

    $this->stateFile = $this->tempDir.DIRECTORY_SEPARATOR.'cms-update-state.json';
    $this->versionFile = $this->tempDir.DIRECTORY_SEPARATOR.'version.json';

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
    config()->set('satker.update.check_ttl_seconds', 43200);
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
