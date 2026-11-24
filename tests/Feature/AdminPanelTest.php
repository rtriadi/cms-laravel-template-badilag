<?php

use App\Livewire\Admin\CriteriaManager;
use App\Livewire\Admin\KepaniteraanManager;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\SatkerSettingsManager;
use App\Livewire\Admin\SinovaLinks;
use App\Livewire\Admin\SliderManager;
use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\CriteriaItem;
use App\Models\HomeSlider;
use App\Models\Official;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Livewire\Livewire;

function makeAdminWithRole(string $role): User
{
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

function seedAdminTestEnvironment(): void
{
    app(RoleAndPermissionSeeder::class)->run();
    SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Bandung',
        'tingkat' => 'pertama',
        'is_tingkat_locked' => true,
    ]);
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');
}

it('allows super_admin to render all 12 admin routes cleanly', function (): void {
    seedAdminTestEnvironment();
    $superAdmin = makeAdminWithRole('super_admin');

    $routes = [
        'admin.dashboard',
        'admin.sinova-links',
        'admin.menus',
        'admin.pages',
        'admin.posts',
        'admin.documents',
        'admin.kepaniteraan',
        'admin.sliders',
        'admin.settings',
        'admin.criteria',
        'admin.users',
        'admin.audit',
        'admin.two-factor.setup',
    ];

    foreach ($routes as $routeName) {
        $this->actingAs($superAdmin)
            ->get(route($routeName))
            ->assertOk();
    }
});

it('enforces RBAC permissions across roles', function (): void {
    seedAdminTestEnvironment();
    $penilai = makeAdminWithRole('penilai_internal');
    $kepaniteraan = makeAdminWithRole('operator_kepaniteraan');

    $this->actingAs($penilai)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($penilai)->get(route('admin.audit'))->assertOk();
    $this->actingAs($penilai)->get(route('admin.sinova-links'))->assertOk();
    $this->actingAs($penilai)->get(route('admin.users'))->assertForbidden();
    $this->actingAs($penilai)->get(route('admin.settings'))->assertForbidden();
    $this->actingAs($penilai)->get(route('admin.pages'))->assertForbidden();
    $this->actingAs($penilai)->get(route('admin.criteria'))->assertForbidden();

    $this->actingAs($kepaniteraan)->get(route('admin.kepaniteraan'))->assertOk();
    $this->actingAs($kepaniteraan)->get(route('admin.sinova-links'))->assertOk();
    $this->actingAs($kepaniteraan)->get(route('admin.users'))->assertForbidden();
});

it('supports SINOVA manual URL overrides, health checks, and CSV export', function (): void {
    seedAdminTestEnvironment();
    $superAdmin = makeAdminWithRole('super_admin');
    $item = CriteriaItem::query()->where('code', '1.a.2.a')->firstOrFail();

    $this->actingAs($superAdmin);

    $component = Livewire::test(SinovaLinks::class)
        ->call('startEdit', $item->id)
        ->set('editingUseManual', true)
        ->set('editingManualUrl', 'https://sipp.pa-bandung.go.id')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->use_manual_url)->toBeTrue()
        ->and($item->manual_url)->toBe('https://sipp.pa-bandung.go.id')
        ->and($item->resolvedUrl(SatkerSetting::current()))->toBe('https://sipp.pa-bandung.go.id');

    $component->call('runHealthCheck')->assertHasNoErrors();

    $csvResponse = $component->call('exportCsv');
    $csvResponse->assertFileDownloaded();
});

it('creates automatic 301 redirect when page slug changes in PageManager', function (): void {
    seedAdminTestEnvironment();
    $admin = makeAdminWithRole('admin_konten');

    $this->actingAs($admin);

    Livewire::test(PageManager::class)
        ->set('title', 'Profil Layanan Mediasi')
        ->set('slug', 'profil/mediasi-awal')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()->where('slug', 'profil/mediasi-awal')->firstOrFail();

    Livewire::test(PageManager::class)
        ->call('edit', $page->id)
        ->set('slug', 'profil/mediasi-terbaru')
        ->call('save')
        ->assertHasNoErrors();

    $redirect = Redirect::query()->where('from_path', '/halaman/profil/mediasi-awal')->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->to_path)->toBe('/halaman/profil/mediasi-terbaru')
        ->and($redirect->status_code)->toBe(301);
});

it('enforces 5 active slider limit and rejects video URLs on homepage slider', function (): void {
    seedAdminTestEnvironment();
    $admin = makeAdminWithRole('admin_konten');
    $this->actingAs($admin);

    // Seeder creates 3 active slides; add 2 more active slides (total 5)
    for ($i = 4; $i <= 5; $i++) {
        HomeSlider::factory()->create([
            'title' => "Slide {$i}",
            'image_alt' => "Banner {$i}",
            'is_active' => true,
            'sort_order' => $i,
        ]);
    }

    // Adding a 6th active slide should fail validation
    Livewire::test(SliderManager::class)
        ->set('title', 'Slide Keenam')
        ->set('image_alt', 'Alt slide 6')
        ->set('is_active', true)
        ->call('save')
        ->assertHasErrors(['is_active']);

    // Video CTA URLs should be rejected
    Livewire::test(SliderManager::class)
        ->set('title', 'Slide Video')
        ->set('image_alt', 'Alt slide video')
        ->set('cta_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->set('is_active', false)
        ->call('save')
        ->assertHasErrors(['cta_url']);
});

it('manages fallback schedules, statistics calculation, and officials without PII', function (): void {
    seedAdminTestEnvironment();
    $operator = makeAdminWithRole('operator_kepaniteraan');
    $this->actingAs($operator);

    Livewire::test(KepaniteraanManager::class)
        ->set('tanggal_sidang', '2026-11-20')
        ->set('nomor_perkara', '99/Pdt.G/2026/PA.Bdg')
        ->set('para_pihak', 'Penggugat vs Tergugat')
        ->set('ruang_sidang', 'Ruang 1')
        ->set('agenda', 'Sidang Pertama')
        ->call('saveSchedule')
        ->assertHasNoErrors();

    expect(CourtSchedule::query()->where('nomor_perkara', '99/Pdt.G/2026/PA.Bdg')->exists())->toBeTrue();

    Livewire::test(KepaniteraanManager::class)
        ->set('tab', 'statistik')
        ->set('stat_year', 2026)
        ->set('stat_month', 10)
        ->set('sisa_bulan_lalu', 20)
        ->set('perkara_masuk', 45)
        ->set('perkara_putus', 35)
        ->call('saveStatistic')
        ->assertHasNoErrors();

    $stat = CaseStatistic::query()->where('year', 2026)->where('month', 10)->firstOrFail();
    expect($stat->sisa_saat_ini)->toBe(30);

    Livewire::test(KepaniteraanManager::class)
        ->set('tab', 'pegawai')
        ->set('nama_gelar', 'Dr. H. Ahmad Fauzi, S.H., M.H.')
        ->set('jabatan', 'Ketua')
        ->set('kategori', 'pimpinan')
        ->call('saveOfficial')
        ->assertHasNoErrors();

    expect(Official::query()->where('jabatan', 'Ketua')->exists())->toBeTrue();
});

it('previews and applies SINOVA JSON criteria update while preserving existing manual URLs', function (): void {
    seedAdminTestEnvironment();
    $superAdmin = makeAdminWithRole('super_admin');
    $this->actingAs($superAdmin);

    $item = CriteriaItem::query()->where('code', '1.a.2.a')->firstOrFail();
    $item->update([
        'manual_url' => 'https://custom-sipp.example.test',
        'use_manual_url' => true,
    ]);

    $payload = json_encode([
        'juknis_version' => '2026',
        'groups' => [
            [
                'code' => '1.a.2',
                'name' => 'Kepaniteraan Diperbarui',
                'aspect' => 'konten',
                'weight' => 15,
                'sort_order' => 2,
                'items' => [
                    [
                        'code' => '1.a.2.a',
                        'name' => 'SIPP Diperbarui',
                        'tingkat_scope' => 'all',
                        'item_type' => 'external_app',
                        'sort_order' => 1,
                    ],
                ],
            ],
        ],
    ]);

    Livewire::test(CriteriaManager::class)
        ->set('tab', 'import')
        ->set('jsonInput', $payload)
        ->call('previewImport')
        ->assertHasNoErrors()
        ->assertSet('previewDiff.updated_items', ['1.a.2.a'])
        ->call('applyImport')
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->name)->toBe('SIPP Diperbarui')
        ->and($item->manual_url)->toBe('https://custom-sipp.example.test')
        ->and($item->use_manual_url)->toBeTrue();
});

it('requires double confirmation from super_admin to change satker tingkat non-destructively', function (): void {
    seedAdminTestEnvironment();
    $superAdmin = makeAdminWithRole('super_admin');
    $adminKonten = makeAdminWithRole('admin_konten');

    // Non-super-admin cannot access SatkerSettingsManager
    $this->actingAs($adminKonten)
        ->get(route('admin.settings'))
        ->assertForbidden();

    $this->actingAs($superAdmin);

    // Wrong confirmation phrase fails
    Livewire::test(SatkerSettingsManager::class)
        ->call('openTingkatModal')
        ->set('pendingTingkat', 'banding')
        ->set('confirmationPhrase', 'SALAH')
        ->call('confirmChangeTingkat')
        ->assertHasErrors(['confirmationPhrase']);

    // Exact phrase applies non-destructive switch
    Livewire::test(SatkerSettingsManager::class)
        ->call('openTingkatModal')
        ->set('pendingTingkat', 'banding')
        ->set('confirmationPhrase', 'BANDING')
        ->call('confirmChangeTingkat')
        ->assertHasNoErrors();

    expect(SatkerSetting::current()?->tingkat)->toBe('banding');
});

it('runs cms:update --dry-run artisan command successfully', function (): void {
    seedAdminTestEnvironment();

    $this->artisan('cms:update', ['--dry-run' => true])
        ->assertExitCode(0);
});
