<?php

use App\Http\Controllers\PublicSearchController;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Jobs\RunDatabaseBackupJob;
use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\Dashboard;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function waveTwoUser(string $role): User
{
    test()->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

it('enforces strict nonce-based CSP without unsafe-inline/unsafe-eval on public routes and builds dynamic frame-src allowlist', function (): void {
    Cache::flush();

    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'sipp_url' => 'https://sipp.pa-gorontalo.go.id',
        'simtepa_pegawai_url' => 'https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/abcdef1234567890abcdef1234567890',
    ]);

    Page::factory()->create([
        'slug' => 'layanan-publik/survei-kustom',
        'title' => 'Survei Kustom',
        'is_published' => true,
        'tingkat_scope' => 'all',
        'blocks' => [[
            'type' => 'embed_fallback',
            'custom_embed_url' => 'https://survei.pa-gorontalo.go.id/form/skm',
        ]],
    ]);

    $response = $this->get('/')->assertOk();
    $csp = (string) $response->headers->get('Content-Security-Policy');
    $html = $response->getContent();

    // Extract generated CSP nonce from script-src directive
    expect($csp)
        ->not->toContain("script-src 'self' 'unsafe-inline'")
        ->not->toContain("'unsafe-eval'")
        ->not->toContain("frame-src 'self' https:;")
        ->toContain('https://sipp.pa-gorontalo.go.id')
        ->toContain('https://simtepa.mahkamahagung.go.id')
        ->toContain('https://survei.pa-gorontalo.go.id')
        ->toContain('https://www.openstreetmap.org');

    preg_match("/script-src 'self' 'nonce-([^']+)'/", $csp, $nonceMatch);
    expect($nonceMatch)->toHaveCount(2);

    // Inline accessibility script on public layout must carry the matching CSP nonce
    expect($html)->toContain('nonce="'.$nonceMatch[1].'"');

    // Updating SatkerSetting flushes cached frame-src origins
    $satker->update(['sipp_url' => 'https://sipp-baru.pa-gorontalo.go.id']);
    expect(Cache::has(SecurityHeadersMiddleware::FRAME_SRC_CACHE_KEY))->toBeFalse();

    $refreshedCsp = (string) $this->get('/')->assertOk()->headers->get('Content-Security-Policy');
    expect($refreshedCsp)->toContain('https://sipp-baru.pa-gorontalo.go.id');

    // Login / admin routes retain Livewire/Flux script compatibility while still keeping strict frame-src
    $loginCsp = (string) $this->get(route('login'))->assertOk()->headers->get('Content-Security-Policy');
    expect($loginCsp)
        ->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'")
        ->not->toContain("frame-src 'self' https:;");
});

it('removes dead three-hero and motion-gsap imports from public bundle and removes inline onerror handler', function (): void {
    $publicJs = (string) file_get_contents(resource_path('js/public.js'));
    $viteConfig = (string) file_get_contents(base_path('vite.config.js'));
    $embedBlade = (string) file_get_contents(resource_path('views/public/blocks/embed-fallback.blade.php'));

    expect($publicJs)
        ->not->toContain('./hero-3d.js')
        ->not->toContain('./motion-gsap.js')
        ->toContain('initImageErrorFallback');

    expect($viteConfig)
        ->not->toContain('three-hero')
        ->not->toContain('motion-gsap');

    expect($embedBlade)
        ->not->toContain('onerror=')
        ->toContain('data-hide-on-error');
});

it('builds safe MySQL FULLTEXT boolean prefix queries and falls back cleanly on short inputs', function (): void {
    expect(PublicSearchController::buildFullTextBooleanQuery('jadwal sidang prodeo'))
        ->toBe('+jadwal* +sidang* +prodeo*')
        ->and(PublicSearchController::buildFullTextBooleanQuery('+prodeo -spam "posbakum" (2026) ~test*'))
        ->toBe('+prodeo* +spam* +posbakum* +2026* +test*')
        ->and(PublicSearchController::buildFullTextBooleanQuery('PA di ke'))
        ->toBeNull()
        ->and(PublicSearchController::buildFullTextBooleanQuery('satu dua tiga empat lima enam tujuh delapan sembilan sepuluh'))
        ->toBe('+satu* +dua* +tiga* +empat* +lima* +enam* +tujuh* +delapan*');
});

it('renders Dashboard without blocking GitHub HTTP checks and caches summary metrics with automatic invalidation', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    config()->set('satker.update.enabled', true);

    $tmpState = sys_get_temp_dir().'/satker-wave2-state-'.uniqid().'.json';
    config()->set('satker.update.state_path', $tmpState);
    @unlink($tmpState);

    Http::fake();
    Cache::flush();

    $superAdmin = waveTwoUser('super_admin');
    $this->actingAs($superAdmin);

    Livewire::test(Dashboard::class)->assertOk();

    // Dashboard render must never send synchronous GitHub HTTP requests even when state file is missing/stale
    Http::assertNothingSent();

    // Dashboard summary metrics are cached
    expect(Cache::has(Dashboard::METRICS_CACHE_KEY))->toBeTrue();

    // Saving a post or comment invalidates Dashboard metrics cache immediately
    $post = Post::factory()->create(['is_published' => true, 'published_at' => now()]);
    expect(Cache::has(Dashboard::METRICS_CACHE_KEY))->toBeFalse();

    Livewire::test(Dashboard::class)->assertOk();
    expect(Cache::has(Dashboard::METRICS_CACHE_KEY))->toBeTrue();

    PostComment::factory()->create([
        'post_id' => $post->id,
        'status' => PostComment::STATUS_PENDING,
    ]);
    expect(Cache::has(Dashboard::METRICS_CACHE_KEY))->toBeFalse();
});

it('supports queueing manual database backups in AuditAndBackupManager and executes RunDatabaseBackupJob safely', function (): void {
    Storage::fake('local');
    SatkerSetting::factory()->setupCompleted()->create();
    Cache::flush();

    $superAdmin = waveTwoUser('super_admin');
    $adminKonten = waveTwoUser('admin_konten');

    // Non-super_admin cannot queue backups
    Livewire::actingAs($adminKonten)
        ->test(AuditAndBackupManager::class)
        ->call('queueBackup')
        ->assertForbidden();

    // Explicit queueBackup dispatches RunDatabaseBackupJob and records queued status
    Queue::fake();
    Livewire::actingAs($superAdmin)
        ->test(AuditAndBackupManager::class)
        ->set('tab', 'backup')
        ->call('queueBackup')
        ->assertSee('antrean latar belakang');

    Queue::assertPushed(RunDatabaseBackupJob::class);
    expect(Cache::get(RunDatabaseBackupJob::STATUS_CACHE_KEY)['state'] ?? null)->toBe('queued');

    // Enabling async_manual with non-sync queue connection auto-queues runBackup()
    Queue::fake();
    config()->set('satker.backup.async_manual', true);
    config()->set('queue.default', 'database');

    Livewire::actingAs($superAdmin)
        ->test(AuditAndBackupManager::class)
        ->call('runBackup')
        ->assertSee('antrean latar belakang');

    Queue::assertPushed(RunDatabaseBackupJob::class);

    // Executing RunDatabaseBackupJob handles backup:run cleanly and updates cache status
    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run', ['--only-db' => true, '--disable-notifications' => true])
        ->andReturn(0);
    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Backup completed!');

    (new RunDatabaseBackupJob($superAdmin->id, $superAdmin->name))->handle();

    expect(Cache::get(RunDatabaseBackupJob::STATUS_CACHE_KEY)['state'] ?? null)->toBe('completed');
});
