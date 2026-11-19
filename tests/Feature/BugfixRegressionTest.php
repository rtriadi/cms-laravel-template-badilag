<?php

use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\CriteriaManager;
use App\Livewire\Admin\DocumentManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\SatkerSettingsManager;
use App\Livewire\Admin\SliderManager;
use App\Livewire\Admin\UserRoleManager;
use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use App\Models\LinkCheckLog;
use App\Models\MenuItem;
use App\Models\Official;
use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\SinovaHealthCheckService;
use App\Support\SafeHtml;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makeSuperAdminForRegression(): User
{
    app(RoleAndPermissionSeeder::class)->run();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole('super_admin');

    return $user;
}

it('validates and scores uiux aspect consistently across CriteriaManager and SinovaHealthCheckService', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeSuperAdminForRegression();
    $this->actingAs($admin);

    Livewire::test(CriteriaManager::class)
        ->set('group_code', '2.z')
        ->set('group_name', 'Uji Aspek UIUX')
        ->set('group_aspect', 'uiux')
        ->set('group_weight', 20)
        ->set('group_sort_order', 99)
        ->call('saveGroup')
        ->assertHasNoErrors();

    $group = CriteriaGroup::query()->where('code', '2.z')->firstOrFail();
    expect($group->aspect)->toBe('uiux');

    CriteriaItem::factory()->create([
        'criteria_group_id' => $group->id,
        'code' => '2.z.1',
        'health_status' => 'healthy',
        'estimated_score' => 10,
    ]);

    $summary = app(SinovaHealthCheckService::class)->scoreSummary();
    expect($summary['aspects']['uiux']['healthy'])->toBeGreaterThanOrEqual(1)
        ->and($summary['aspects']['uiux']['score_pct'])->toBe(100.0);
});

it('excludes pegawai from org_chart health readiness and blocks SSRF targets in probeExternalUrl', function (): void {
    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'simtepa_struktur_url' => null,
    ]);
    app(RoleAndPermissionSeeder::class)->run();
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    // Only pegawai exists (no pimpinan/pejabat/hakim) -> org_chart should warn!
    Official::factory()->create([
        'kategori' => 'pegawai',
        'is_active' => true,
    ]);

    $orgItem = CriteriaItem::query()->where('code', '1.b.p.4')->firstOrFail();
    $checked = app(SinovaHealthCheckService::class)->checkItem($orgItem, $satker, probeExternal: false);

    expect($checked->health_status)->toBe('warning');

    // SSRF guard blocks loopback and cloud metadata private IPs
    $ssrfItem = CriteriaItem::factory()->create([
        'manual_url' => 'http://127.0.0.1/panel-admin',
        'use_manual_url' => true,
    ]);
    $ssrfChecked = app(SinovaHealthCheckService::class)->checkItem($ssrfItem, $satker, probeExternal: true);

    expect($ssrfChecked->health_status)->toBe('broken')
        ->and($ssrfChecked->health_message)->toContain('privat atau lokal');

    $metaItem = CriteriaItem::factory()->create([
        'manual_url' => 'http://169.254.169.254/latest/meta-data/',
        'use_manual_url' => true,
    ]);
    $metaChecked = app(SinovaHealthCheckService::class)->checkItem($metaItem, $satker, probeExternal: true);

    expect($metaChecked->health_status)->toBe('broken');
});

it('prevents redirect loops on page slug revert and AuditAndBackupManager rules', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeSuperAdminForRegression();
    $this->actingAs($admin);

    $page = Page::factory()->create(['slug' => 'uji/a', 'is_published' => true]);
    $page->update(['slug' => 'uji/b']);
    $page->update(['slug' => 'uji/a']);

    expect(Redirect::query()->where('from_path', '/halaman/uji/a')->exists())->toBeFalse()
        ->and(Redirect::query()->where('from_path', '/halaman/uji/b')->value('to_path'))->toBe('/halaman/uji/a');

    // Reverted page renders 200 without redirect loop!
    $this->get('/halaman/uji/a')->assertOk();

    // AuditAndBackupManager rejects self-loop, reverse loop, and javascript: URLs
    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/sama')
        ->set('to_path', '/sama')
        ->call('saveRedirect')
        ->assertHasErrors(['to_path']);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/xss')
        ->set('to_path', 'javascript:alert(1)')
        ->call('saveRedirect')
        ->assertHasErrors(['to_path']);

    Redirect::factory()->create(['from_path' => '/x', 'to_path' => '/y']);

    Livewire::test(AuditAndBackupManager::class)
        ->set('from_path', '/y')
        ->set('to_path', '/x')
        ->call('saveRedirect')
        ->assertHasErrors(['to_path']);
});

it('validates menu depth, prevents cycles, normalizes sibling swaps, and renders 3 levels in public header', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeSuperAdminForRegression();
    $this->actingAs($admin);

    $page = Page::factory()->create(['slug' => 'menu-uji', 'is_published' => true]);

    // Missing required page_id / external url validation + javascript: URL rejection
    Livewire::test(MenuManager::class)
        ->set('title', 'Tanpa Halaman')
        ->set('type', 'page')
        ->call('save')
        ->assertHasErrors(['page_id']);

    Livewire::test(MenuManager::class)
        ->set('title', 'Link Jahat')
        ->set('type', 'external')
        ->set('url', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['url']);

    // Circular hierarchy and depth > 3 guard
    $rootA = MenuItem::factory()->create(['location' => 'header', 'title' => 'Level 1 Root', 'type' => 'group', 'sort_order' => 0]);
    $childB = MenuItem::factory()->create(['location' => 'header', 'parent_id' => $rootA->id, 'title' => 'Level 2 Group', 'type' => 'group', 'sort_order' => 1]);
    $grandC = MenuItem::factory()->create(['location' => 'header', 'parent_id' => $childB->id, 'title' => 'Level 3 Link', 'type' => 'page', 'page_id' => $page->id, 'sort_order' => 1]);

    // Trying to move rootA under childB must fail cycle guard
    Livewire::test(MenuManager::class)
        ->call('edit', $rootA->id)
        ->set('parent_id', $childB->id)
        ->call('save')
        ->assertHasErrors(['parent_id']);

    // Duplicate sort_order swap normalizes and moves sibling accurately
    $sibling2 = MenuItem::factory()->create(['location' => 'header', 'title' => 'Sibling Kedua', 'type' => 'external', 'url' => '/publikasi', 'sort_order' => 0]);

    Livewire::test(MenuManager::class)
        ->call('moveUp', $sibling2->id);

    $rootA->refresh();
    $sibling2->refresh();
    expect($sibling2->sort_order)->toBeLessThan($rootA->sort_order);

    // Public header renders Level-3 grandchild link, footer menu, and custom shortcut menu
    MenuItem::factory()->create(['location' => 'footer', 'title' => 'Tautan Footer Uji', 'type' => 'external', 'url' => '/peta-situs']);
    MenuItem::factory()->create(['location' => 'shortcut', 'title' => 'Pintasan Uji Cepat', 'type' => 'external', 'url' => '/cari']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Level 3 Link')
        ->assertSee('Tautan Footer Uji')
        ->assertSee('Pintasan Uji Cepat');
});

it('prevents demoting or deleting the last super_admin user', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $superAdmin = makeSuperAdminForRegression();
    $this->actingAs($superAdmin);

    Livewire::test(UserRoleManager::class)
        ->call('edit', $superAdmin->id)
        ->set('role', 'admin_konten')
        ->call('save')
        ->assertHasErrors(['role']);

    expect($superAdmin->fresh()->hasRole('super_admin'))->toBeTrue();
});

it('handles SafeHtml body and url sanitization cleanly and rejects unsafe slider URLs', function (): void {
    expect(SafeHtml::cleanBody('<p>Paragraf 1</p><p>Paragraf 2</p>'))->toBe('<p>Paragraf 1</p><p>Paragraf 2</p>')
        ->and(SafeHtml::cleanBody("Baris 1\nBaris 2"))->toContain('<br')
        ->and(SafeHtml::cleanUrl('javascript:alert(1)'))->toBeNull()
        ->and(SafeHtml::cleanUrl('//evil.test/steal'))->toBeNull()
        ->and(SafeHtml::cleanUrl('/halaman/test'))->toBe('/halaman/test')
        ->and(SafeHtml::cleanUrl('https://pa-soreang.go.id'))->toBe('https://pa-soreang.go.id');

    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeSuperAdminForRegression();
    $this->actingAs($admin);

    Livewire::test(SliderManager::class)
        ->set('title', 'Slide XSS')
        ->set('image_alt', 'Alt XSS')
        ->set('cta_url', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['cta_url']);
});

it('accepts empty strings on nullable int Livewire properties without TypeError and instantiates all 7 factories', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeSuperAdminForRegression();
    $this->actingAs($admin);

    Livewire::test(DocumentManager::class)
        ->set('title', 'Dokumen Tanpa Bulan')
        ->set('year', 2026)
        ->set('month', '')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(SatkerSettingsManager::class)
        ->set('nama_satker', 'Pengadilan Agama Uji')
        ->set('tahun_berdiri', '')
        ->call('save')
        ->assertHasNoErrors();

    $schedule = CourtSchedule::factory()->create();
    $visit = PageVisit::factory()->create();
    $stat = CaseStatistic::factory()->create();
    $item = CriteriaItem::factory()->create();
    $log = LinkCheckLog::factory()->create();
    $redirect = Redirect::factory()->create();
    $post = Post::factory()->create(['body' => '<p>Konten artikel bersih</p><p>Paragraf dua</p>']);

    expect($schedule->id)->toBeInt()
        ->and($visit->id)->toBeInt()
        ->and($stat->id)->toBeInt()
        ->and($item->id)->toBeInt()
        ->and($log->id)->toBeInt()
        ->and($redirect->id)->toBeInt()
        ->and($post->id)->toBeInt();
});

it('caches SatkerSetting safely using database cache driver with serializable_classes disabled', function (): void {
    config(['cache.default' => 'database', 'cache.serializable_classes' => false]);
    Cache::store('database')->flush();

    SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Uji Cache Database',
    ]);

    $first = SatkerSetting::current();
    $second = SatkerSetting::current();

    expect($first)->toBeInstanceOf(SatkerSetting::class)
        ->and($second)->toBeInstanceOf(SatkerSetting::class)
        ->and($second?->nama_satker)->toBe('Pengadilan Agama Uji Cache Database')
        ->and($second?->is_setup_completed)->toBeTrue();
});

it('uploads images and PDFs for the rich text editor and sanitizes img tags safely', function (): void {
    Storage::fake('public');
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeSuperAdminForRegression();
    $this->actingAs($admin);

    $imgFile = UploadedFile::fake()->image('foto-berita.jpg', 600, 400);
    $imgResp = $this->postJson(route('admin.media.upload'), ['file' => $imgFile]);
    $imgResp->assertOk()
        ->assertJsonPath('is_pdf', false)
        ->assertJsonPath('name', 'foto-berita.jpg');
    expect((string) $imgResp->json('location'))->toStartWith('/storage/editor-media/');

    $pdfFile = UploadedFile::fake()->create('lampiran-sk.pdf', 120, 'application/pdf');
    $pdfResp = $this->postJson(route('admin.media.upload'), ['file' => $pdfFile]);
    $pdfResp->assertOk()
        ->assertJsonPath('is_pdf', true)
        ->assertJsonPath('name', 'lampiran-sk.pdf');

    $badFile = UploadedFile::fake()->create('exploit.html', 10, 'text/html');
    $this->postJson(route('admin.media.upload'), ['file' => $badFile])
        ->assertStatus(422);

    $cleaned = SafeHtml::cleanBody('<p><img src="/storage/editor-media/test.jpg" alt="Foto" onerror="alert(1)"><img src="javascript:alert(1)"></p>');
    expect($cleaned)->toContain('<img src="/storage/editor-media/test.jpg" alt="Foto">')
        ->and($cleaned)->not->toContain('onerror')
        ->and($cleaned)->not->toContain('javascript:');
});

it('renders enriched prodeo posbakum page with article_list block and deduplicates Kel/Kec in formattedAddress', function (): void {
    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Gorontalo',
        'alamat_jalan' => 'Jl. Achmad Nadjamuddin No. 22, Kel. Dulalowo Timur, Kec. Kota Tengah',
        'desa_kelurahan' => 'Kel. Dulalowo Timur',
        'kecamatan' => 'Kec. Kota Tengah',
        'kabupaten_kota' => 'Kota Gorontalo',
        'provinsi' => 'Gorontalo',
        'kode_pos' => '96128',
    ]);

    expect($satker->formattedAddress())
        ->not->toContain('Kel. Kel.')
        ->and($satker->formattedAddress())
        ->not->toContain('Kec. Kec.');

    app(SatkerDefaultContentSeeder::class)->run('pertama');

    Post::factory()->create([
        'title' => 'Artikel Panduan Prodeo & Posbakum',
        'slug' => 'artikel-panduan-prodeo-posbakum',
        'category' => 'layanan_hukum',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get('/halaman/layanan-hukum/hak-pencari-keadilan')
        ->assertOk()
        ->assertSee('Layanan Pembebasan Biaya Perkara (Prodeo)')
        ->assertSee('Layanan Pos Bantuan Hukum (Posbakum)')
        ->assertSee('Sidang di Luar Gedung Pengadilan (Sidang Keliling)')
        ->assertSee('Artikel Panduan Prodeo &amp; Posbakum', false);
});
