<?php

use App\Actions\Posts\RecordPostViewAction;
use App\Actions\Security\InvalidateUserSessions;
use App\Http\Middleware\EnsureTwoFactorConfirmedMiddleware;
use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\CriteriaManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DocumentManager;
use App\Livewire\Admin\KepaniteraanManager;
use App\Livewire\Admin\SinovaLinks;
use App\Livewire\Admin\TwoFactorSetup;
use App\Livewire\Admin\UserRoleManager;
use App\Models\CriteriaItem;
use App\Models\Document;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\ImageOptimizerService;
use App\Services\LocalCaptchaService;
use App\Services\SinovaHealthCheckService;
use App\Support\SafeHtml;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Livewire;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;

function makeHardeningUser(string $role, bool $with2fa = true): User
{
    app(RoleAndPermissionSeeder::class)->run();

    $user = User::factory()->create([
        'two_factor_secret' => $with2fa ? encrypt('JBSWY3DPEHPK3PXP') : null,
        'two_factor_confirmed_at' => $with2fa ? now() : null,
    ]);
    $user->assignRole($role);

    return $user;
}

it('blocks DOM XSS bypass variants in SafeHtml::clean, cleanUrl, and CSV formula injection', function (): void {
    $payloads = [
        '<img/src="/storage/a.jpg"/onerror=alert(1)>',
        '<img src="/storage/a.jpg"onerror="alert(1)">',
        '<a href="&#106;avascript:alert(1)" target="_blank">Klik</a>',
        '<a href="&#x01;javascript:alert(1)">Control</a>',
        '<div style="position:fixed" id="mobile-nav-drawer" name="clobber">Teks</div>',
        '<img src="/storage/ok.jpg" srcset="javascript:alert(1)" alt="Aman">',
        '<svg><onload=alert(1)></svg>',
    ];

    foreach ($payloads as $input) {
        $clean = SafeHtml::clean($input);
        expect($clean)->not->toContain('onerror')
            ->and($clean)->not->toContain('javascript:')
            ->and($clean)->not->toContain('style=')
            ->and($clean)->not->toContain('srcset=')
            ->and($clean)->not->toContain('id=')
            ->and($clean)->not->toContain('name=')
            ->and($clean)->not->toContain('<svg');
    }

    $blankLink = SafeHtml::clean('<a href="https://mahkamahagung.go.id" target="_blank">MARI</a>');
    expect($blankLink)->toContain('rel="noopener noreferrer"');

    expect(SafeHtml::cleanUrl('/\\evil.test/steal'))->toBeNull()
        ->and(SafeHtml::cleanUrl('javascript://%0aalert(1)'))->toBeNull()
        ->and(SafeHtml::cleanUrl('data:text/html,test'))->toBeNull()
        ->and(SafeHtml::cleanUrl('/halaman/profil'))->toBe('/halaman/profil');

    expect(SafeHtml::sanitizeCsvCell('=CMD("calc")'))->toBe("'=CMD(\"calc\")")
        ->and(SafeHtml::sanitizeCsvCell('+1+1'))->toBe("'+1+1")
        ->and(SafeHtml::sanitizeCsvCell('-2+3'))->toBe("'-2+3")
        ->and(SafeHtml::sanitizeCsvCell('@SUM(A1:A2)'))->toBe("'@SUM(A1:A2)")
        ->and(SafeHtml::sanitizeCsvCell('Aman 2026'))->toBe('Aman 2026');
});

it('rejects unsafe manual URLs in SinovaLinks even when override is off and neutralizes CSV exports', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    app(RoleAndPermissionSeeder::class)->run();
    app(SinovaCriteriaSeeder::class)->run();

    $operator = makeHardeningUser('operator_ppid_humas');
    $item = CriteriaItem::query()->firstOrFail();

    $this->actingAs($operator);

    Livewire::test(SinovaLinks::class)
        ->call('startEdit', $item->id)
        ->set('editingUseManual', false)
        ->set('editingManualUrl', 'javascript://%0aalert(1)')
        ->call('saveEdit')
        ->assertHasErrors(['editingManualUrl']);

    Livewire::test(SinovaLinks::class)
        ->call('startEdit', $item->id)
        ->set('editingUseManual', true)
        ->set('editingManualUrl', '//evil.example/redirect')
        ->call('saveEdit')
        ->assertHasErrors(['editingManualUrl']);

    $item->update(['name' => '=HYPERLINK("https://evil.example","Klik")']);

    $response = Livewire::test(SinovaLinks::class)->call('exportCsv');
    $response->assertFileDownloaded();
    $csvContent = $response->effects['download']['content'] ?? '';
    expect(base64_decode((string) $csvContent))->toContain("'=HYPERLINK");
});

it('prevents admin_konten from hijacking reserved admin/login routes or creating external redirects and 3-hop loops', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $adminKonten = makeHardeningUser('admin_konten');
    $this->actingAs($adminKonten);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/panel-admin/login')
        ->set('to_path', '/halaman/profil')
        ->call('saveRedirect')
        ->assertHasErrors(['from_path']);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/setup')
        ->set('to_path', '/halaman/profil')
        ->call('saveRedirect')
        ->assertHasErrors(['from_path']);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/halaman/lama')
        ->set('to_path', 'https://untrusted.example/phish')
        ->call('saveRedirect')
        ->assertHasErrors(['to_path']);

    Redirect::factory()->create(['from_path' => '/halaman/a', 'to_path' => '/halaman/b']);
    Redirect::factory()->create(['from_path' => '/halaman/b', 'to_path' => '/halaman/c']);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/halaman/c')
        ->set('to_path', '/halaman/a')
        ->call('saveRedirect')
        ->assertHasErrors(['to_path']);

    // Even if a reserved path row exists in DB, HandleRedirectsMiddleware must not intercept it
    Redirect::factory()->create(['from_path' => '/panel-admin/login', 'to_path' => 'https://untrusted.example/']);
    auth()->logout();
    $this->get('/panel-admin/login')->assertOk();
});

it('enforces mandatory 2FA on persistent Livewire middleware, Fortify user endpoints, and recovery-code step-up', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();

    $persistent = app(PersistentMiddleware::class)->getPersistentMiddleware();
    expect($persistent)->toContain(EnsureTwoFactorConfirmedMiddleware::class);

    $unconfirmedUser = makeHardeningUser('admin_konten', with2fa: false);
    $this->actingAs($unconfirmedUser);

    $middleware = new EnsureTwoFactorConfirmedMiddleware;
    $profileReq = Request::create('/panel-admin/user/profile-information', 'PUT');
    $profileReq->setUserResolver(fn () => $unconfirmedUser);
    $profileResp = $middleware->handle($profileReq, fn () => response('ok'));
    expect($profileResp->isRedirection())->toBeTrue();

    $delete2faReq = Request::create('/panel-admin/user/two-factor-authentication', 'DELETE');
    $delete2faReq->setUserResolver(fn () => $unconfirmedUser);
    $delete2faResp = $middleware->handle($delete2faReq, fn () => response('ok'));
    expect($delete2faResp->isRedirection())->toBeTrue();

    $confirmedUser = makeHardeningUser('super_admin', with2fa: true);
    $this->actingAs($confirmedUser);

    // Without recent password confirmation, regenerateRecoveryCodes redirects to password.confirm
    Livewire::test(TwoFactorSetup::class)
        ->call('regenerateRecoveryCodes')
        ->assertRedirect(route('password.confirm'));

    // With fresh password confirmation, recovery codes regenerate cleanly
    $this->withSession(['auth.password_confirmed_at' => time()]);
    Livewire::test(TwoFactorSetup::class)
        ->call('regenerateRecoveryCodes')
        ->assertSet('showRecoveryCodes', true);
});

it('invalidates active database sessions on role change and enforces departmental RBAC on documents and kepaniteraan', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    Storage::fake('public');

    $superAdmin = makeHardeningUser('super_admin');
    $target = makeHardeningUser('admin_konten');
    $originalRemember = 'initial-remember-token-123';
    $target->forceFill(['remember_token' => $originalRemember])->save();

    config(['session.driver' => 'database']);
    DB::table('sessions')->insert([
        'id' => 'target-session-id-1',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => base64_encode('{}'),
        'last_activity' => time(),
    ]);

    $this->actingAs($superAdmin);
    Livewire::test(UserRoleManager::class)
        ->call('edit', $target->id)
        ->set('role', 'penilai_internal')
        ->call('save')
        ->assertHasNoErrors();

    expect(DB::table('sessions')->where('user_id', $target->id)->exists())->toBeFalse()
        ->and($target->fresh()->remember_token)->not->toBe($originalRemember);

    // DocumentManager rejects unallowlisted collection_type and cross-department edits, and deletes stored PDF on delete
    $ppidOperator = makeHardeningUser('operator_ppid_humas');
    $this->actingAs($ppidOperator);

    Livewire::test(DocumentManager::class)
        ->set('collection_type', '../editor-media')
        ->set('title', 'Path Traversal Attempt')
        ->set('year', 2026)
        ->call('save')
        ->assertHasErrors(['collection_type']);

    Livewire::test(DocumentManager::class)
        ->set('collection_type', 'lkjip')
        ->set('title', 'Cross Dept Attempt')
        ->set('year', 2026)
        ->call('save')
        ->assertForbidden();

    $pdf = UploadedFile::fake()->createWithContent('dip-2026.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    Livewire::test(DocumentManager::class)
        ->set('collection_type', 'dip_ppid')
        ->set('title', 'DIP PPID 2026')
        ->set('year', 2026)
        ->set('pdf_file', $pdf)
        ->call('save')
        ->assertHasNoErrors();

    $doc = Document::query()->where('title', 'DIP PPID 2026')->firstOrFail();
    expect($doc->file_path)->toStartWith('documents/dip_ppid/')
        ->and(Storage::disk('public')->exists($doc->file_path))->toBeTrue();

    Livewire::test(DocumentManager::class)->call('delete', $doc->id);
    expect(Storage::disk('public')->exists($doc->file_path))->toBeFalse();

    // KepaniteraanManager blocks operator_kesekretariatan from court schedules/statistics
    $sekreOperator = makeHardeningUser('operator_kesekretariatan');
    $this->actingAs($sekreOperator);

    Livewire::test(KepaniteraanManager::class)
        ->set('tanggal_sidang', '2026-11-20')
        ->set('nomor_perkara', '1/Pdt.G/2026')
        ->set('para_pihak', 'A vs B')
        ->call('saveSchedule')
        ->assertForbidden();
});

it('hardens SSRF probing against IPv6, custom ports, DNS failure, and redirects', function (): void {
    $satker = SatkerSetting::factory()->setupCompleted()->create();
    $service = app(SinovaHealthCheckService::class);

    foreach ([
        'http://[::1]/panel-admin',
        'http://[::ffff:127.0.0.1]/admin',
        'http://93.184.216.34:6379/',
        'http://nonexistent-host.invalid-tld.test/',
    ] as $blockedUrl) {
        $item = CriteriaItem::factory()->create([
            'manual_url' => $blockedUrl,
            'use_manual_url' => true,
        ]);

        $checked = $service->checkItem($item, $satker, probeExternal: true);
        expect($checked->health_status)->toBe('broken');
    }

    Http::fake([
        'https://93.184.216.34/start' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secret']),
    ]);

    $redirectItem = CriteriaItem::factory()->create([
        'manual_url' => 'https://93.184.216.34/start',
        'use_manual_url' => true,
    ]);

    $checkedRedirect = $service->checkItem($redirectItem, $satker, probeExternal: true);
    expect($checkedRedirect->health_status)->toBe('broken')
        ->and($checkedRedirect->health_message)->toContain('privat atau lokal');
});

it('enforces tingkat_scope, post expires_at, wildcard search guard, HMAC captcha, CSP, and safe backup config', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['tingkat' => 'pertama']);

    Page::factory()->create([
        'title' => 'Halaman Khusus Banding',
        'slug' => 'khusus/banding-only',
        'tingkat_scope' => 'banding',
        'is_published' => true,
    ]);

    Post::factory()->create([
        'title' => 'Pengumuman Kedaluwarsa',
        'slug' => 'pengumuman-kedaluwarsa',
        'category' => 'serta_merta',
        'is_published' => true,
        'published_at' => now()->subDays(5),
        'expires_at' => now()->subHour(),
    ]);

    $this->get('/halaman/khusus/banding-only')->assertNotFound();
    $this->get('/cari?q=Khusus+Banding')->assertOk()->assertDontSee('Halaman Khusus Banding');
    $this->get('/cari?q=%%%')->assertOk()->assertDontSee('Halaman Khusus Banding');

    $this->get('/publikasi/pengumuman-kedaluwarsa')->assertNotFound();
    $this->get('/publikasi')->assertOk()->assertDontSee('Pengumuman Kedaluwarsa');
    $this->get('/sitemap.xml')->assertOk()->assertDontSee('pengumuman-kedaluwarsa');

    // HMAC local captcha avoids bcrypt on generation and retains legacy bcrypt verify compatibility
    $captcha = app(LocalCaptchaService::class);
    $code = $captcha->generateCode();
    expect((string) session(LocalCaptchaService::SESSION_KEY))->toStartWith('hmac:')
        ->and($captcha->verify($code))->toBeTrue();

    session([LocalCaptchaService::SESSION_KEY => Hash::make('ABCDE')]);
    expect($captcha->verify('abcde'))->toBeTrue();

    // CSP header + backup & filesystem config safety
    $homeResponse = $this->get('/');
    $homeResponse->assertOk();
    expect((string) $homeResponse->headers->get('Content-Security-Policy'))
        ->toContain("frame-ancestors 'self'")
        ->toContain("object-src 'none'");

    expect(config('backup.backup.source.files.exclude'))
        ->toContain(base_path('.env'))
        ->toContain(base_path('.env.local'))
        ->toContain(base_path('.git'))
        ->toContain(storage_path('logs'))
        ->toContain(storage_path('app/private'))
        ->and(config('backup.backup.verify_backup'))->toBeTrue()
        ->and(config('backup.notifications.mail.to'))->not->toBe('your@example.com')
        ->and(config('filesystems.disks.local.serve'))->toBeFalse();

    // CriteriaManager sanitizes export filename
    $superAdmin = makeHardeningUser('super_admin');
    $this->actingAs($superAdmin);
    Livewire::test(CriteriaManager::class)
        ->set('activeVersion', '../2026')
        ->call('exportJson')
        ->assertFileDownloaded('juknis-sinova-..-2026.json');

    // ImageOptimizerService dimension cap constant is enforced
    expect(ImageOptimizerService::MAX_SOURCE_DIMENSION)->toBe(8000)
        ->and(ImageOptimizerService::MAX_SOURCE_PIXELS)->toBe(25_000_000);
});

test('two-factor recovery codes require step-up confirmation, Fortify direct 2FA disable is blocked, and session version revocation works across drivers', function () {
    SatkerSetting::factory()->setupCompleted()->create();

    $admin = makeHardeningUser('super_admin');
    $admin->forceFill([
        'two_factor_recovery_codes' => encrypt(json_encode(['RECOV-CODE-1111', 'RECOV-CODE-2222'])),
    ])->save();

    $prop = new ReflectionProperty(TwoFactorSetup::class, 'showRecoveryCodes');
    expect($prop->getAttributes(Locked::class))->not->toBeEmpty();

    // Without recent password confirmation, recovery codes are never decrypted into the view
    $this->actingAs($admin);
    Livewire::test(TwoFactorSetup::class)
        ->assertViewHas('recoveryCodes', [])
        ->assertDontSee('RECOV-CODE-1111');

    // Direct Fortify 2FA disable & recovery code endpoints are blocked once 2FA is confirmed
    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete('/panel-admin/user/two-factor-authentication')
        ->assertForbidden();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/panel-admin/user/two-factor-recovery-codes')
        ->assertForbidden();

    // Session version revocation works regardless of session driver (file/array/database)
    $oldVersion = (int) ($admin->session_version ?? 1);
    app(InvalidateUserSessions::class)->execute($admin);
    $admin->refresh();

    expect((int) $admin->session_version)->toBe($oldVersion + 1);

    $this->actingAs($admin)
        ->withSession(['auth.session_version' => $oldVersion])
        ->get('/panel-admin')
        ->assertRedirect(route('login'));

    // UserRoleManager invalidates target user sessions when email is changed
    $target = makeHardeningUser('admin_konten');
    $targetOldVersion = (int) ($target->session_version ?? 1);

    $this->actingAs($admin)
        ->withSession([
            'auth.session_version' => (int) $admin->session_version,
            'auth.password_hash' => $admin->getAuthPassword(),
        ]);

    Livewire::test(UserRoleManager::class)
        ->call('edit', $target->id)
        ->set('email', 'updated-security@mahkamahagung.go.id')
        ->call('save')
        ->assertHasNoErrors();

    expect((int) $target->fresh()->session_version)->toBeGreaterThan($targetOldVersion);
});

test('unpublished documents use private disk, public menu hides draft pages, and dashboard scopes data by role permissions', function () {
    Storage::fake('public');
    Storage::fake('local');
    SatkerSetting::factory()->setupCompleted()->create();

    $admin = makeHardeningUser('super_admin');
    $pdf = UploadedFile::fake()->createWithContent('dipa-rahasia.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

    $this->actingAs($admin);
    Livewire::test(DocumentManager::class)
        ->set('collection_type', 'dip_ppid')
        ->set('title', 'DIPA Draf Non Publik')
        ->set('year', 2026)
        ->set('is_published', false)
        ->set('pdf_file', $pdf)
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('title', 'DIPA Draf Non Publik')->firstOrFail();
    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);

    // Publishing moves the PDF from private local disk to public disk
    Livewire::test(DocumentManager::class)
        ->call('edit', $document->id)
        ->set('is_published', true)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists($document->file_path);
    Storage::disk('local')->assertMissing($document->file_path);

    // MenuItem::cachedPublicMenus filters out links to unpublished pages
    $draftPage = Page::query()->create([
        'title' => 'Halaman Draf Internal',
        'slug' => 'halaman-draf-internal',
        'body' => '<p>Draf</p>',
        'is_published' => false,
        'tingkat_scope' => 'all',
    ]);

    MenuItem::query()->create([
        'title' => 'Tautan Draf Internal',
        'type' => 'page',
        'page_id' => $draftPage->id,
        'is_published' => true,
        'tingkat_scope' => 'all',
        'sort_order' => 99,
    ]);

    $publicMenus = MenuItem::cachedPublicMenus('first');
    expect(collect($publicMenus['header'] ?? [])->pluck('title')->all())->not->toContain('Tautan Draf Internal');

    // Dashboard hides draft posts, pending comment counts, and audit logs from penilai_internal
    Post::query()->create([
        'title' => 'Berita Draf Rahasia Pimpinan',
        'slug' => 'berita-draf-rahasia-pimpinan',
        'category' => 'berita',
        'excerpt' => 'Draf internal',
        'body' => '<p>Draf internal</p>',
        'is_published' => false,
        'tingkat_scope' => 'all',
    ]);

    $sekreOperator = makeHardeningUser('operator_kesekretariatan');
    $this->actingAs($sekreOperator);

    Livewire::test(Dashboard::class)
        ->assertDontSee('Berita Draf Rahasia Pimpinan')
        ->assertViewHas('recentActivities', fn ($activities) => $activities->isEmpty())
        ->assertViewHas('pendingCommentsCount', 0);
});

test('cookieless post view recording deduplicates by network fingerprint, pending comments preserve home cache, and sitemap rejects uncanonical host cache poisoning', function () {
    SatkerSetting::factory()->setupCompleted()->create();

    $post = Post::query()->create([
        'title' => 'Berita Uji Deduplikasi View',
        'slug' => 'berita-uji-deduplikasi-view',
        'category' => 'berita',
        'excerpt' => 'Ringkasan',
        'body' => '<p>Konten berita</p>',
        'is_published' => true,
        'published_at' => now()->subMinute(),
        'tingkat_scope' => 'all',
        'views_count' => 0,
    ]);

    $action = app(RecordPostViewAction::class);

    // Empty User-Agent is treated as automated bot
    $emptyUaRequest = Request::create('/publikasi/berita-uji-deduplikasi-view', 'GET', [], [], [], [
        'REMOTE_ADDR' => '198.51.100.10',
        'HTTP_USER_AGENT' => '',
    ]);
    expect($action->execute($post, $emptyUaRequest))->toBeFalse()
        ->and($post->fresh()->views_count)->toBe(0);

    // Repeated cookieless requests from same IP + User-Agent deduplicate via network fingerprint
    $cookielessReq1 = Request::create('/publikasi/berita-uji-deduplikasi-view', 'GET', [], [], [], [
        'REMOTE_ADDR' => '198.51.100.25',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);
    $cookielessReq2 = Request::create('/publikasi/berita-uji-deduplikasi-view', 'GET', [], [], [], [
        'REMOTE_ADDR' => '198.51.100.25',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);

    expect($action->execute($post, $cookielessReq1))->toBeTrue()
        ->and($action->execute($post->fresh(), $cookielessReq2))->toBeFalse()
        ->and($post->fresh()->views_count)->toBe(1);

    // Submitting a pending comment does not flush homepage cache
    Cache::put('public_home.first.v2', 'cached-home-marker', 300);
    PostComment::query()->create([
        'post_id' => $post->id,
        'author_name' => 'Pemohon Informasi',
        'body' => 'Komentar baru berstatus pending.',
        'status' => PostComment::STATUS_PENDING,
    ]);
    expect(Cache::get('public_home.first.v2'))->toBe('cached-home-marker');

    // Non-canonical Host header does not write to shared public_sitemap_xml cache
    Cache::forget('public_sitemap_xml.first');
    $this->call('GET', '/sitemap.xml', [], [], [], ['HTTP_HOST' => 'untrusted-host.example.org']);
    expect(Cache::get('public_sitemap_xml.first'))->toBeNull();
});
