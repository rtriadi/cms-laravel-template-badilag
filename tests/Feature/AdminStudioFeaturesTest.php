<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\PostManager;
use App\Livewire\Admin\SinovaLinks;
use App\Models\CriteriaItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\ImageOptimizerService;
use App\Services\SinovaHealthCheckService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makeStudioAdmin(string $role): User
{
    app(RoleAndPermissionSeeder::class)->run();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

it('saves WordPress-style quick drafts from Dashboard Studio and enforces RBAC', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    app(RoleAndPermissionSeeder::class)->run();
    app(SinovaCriteriaSeeder::class)->run();

    $penilai = makeStudioAdmin('penilai_internal');
    $this->actingAs($penilai);

    Livewire::test(Dashboard::class)
        ->set('quickDraftTitle', 'Draf Ditolak Penilai')
        ->set('quickDraftBody', 'Seharusnya tidak diizinkan oleh kebijakan akses.')
        ->call('saveQuickDraft')
        ->assertForbidden();

    $adminKonten = makeStudioAdmin('admin_konten');
    $this->actingAs($adminKonten);

    Livewire::test(Dashboard::class)
        ->set('quickDraftTitle', 'Rencana Sidang Keliling Triwulan IV')
        ->set('quickDraftCategory', 'pengumuman')
        ->set('quickDraftBody', 'Koordinasi pelaksanaan sidang keliling dengan KUA setempat.')
        ->call('saveQuickDraft')
        ->assertHasNoErrors();

    $draft = Post::query()->where('slug', 'rencana-sidang-keliling-triwulan-iv')->firstOrFail();
    expect($draft->is_published)->toBeFalse()
        ->and($draft->category)->toBe('pengumuman');
});

it('reorders and duplicates blocks cleanly in 3-pane PageManager Studio', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeStudioAdmin('admin_konten');
    $this->actingAs($admin);

    $page = Page::factory()->create([
        'title' => 'Uji Urutan Blok',
        'slug' => 'uji/urutan-blok',
        'blocks' => [
            ['type' => 'richtext', 'heading' => 'Blok Pertama', 'content' => '<p>Satu</p>'],
            ['type' => 'document_list', 'heading' => 'Blok Kedua', 'collection_type' => 'lkjip'],
        ],
    ]);

    Livewire::test(PageManager::class)
        ->call('edit', $page->id)
        ->call('moveBlockUp', 1)
        ->assertSet('blocks.0.heading', 'Blok Kedua')
        ->assertSet('blocks.1.heading', 'Blok Pertama')
        ->call('duplicateBlock', 0)
        ->assertSet('blocks.1.heading', 'Blok Kedua (Salinan)')
        ->call('moveBlockDown', 1)
        ->assertSet('blocks.2.heading', 'Blok Kedua (Salinan)')
        ->call('save')
        ->assertHasNoErrors();

    expect(count($page->fresh()->blocks))->toBe(3);
});

it('executes bulk publish, draft, pin, and deletes WebP variants cleanly in PostManager', function (): void {
    Storage::fake('public');
    SatkerSetting::factory()->setupCompleted()->create();
    $admin = makeStudioAdmin('admin_konten');
    $this->actingAs($admin);

    Storage::disk('public')->put('posts/cover-uji.webp', 'main-bytes');
    Storage::disk('public')->put('posts/cover-uji-400w.webp', 'var-bytes');

    $p1 = Post::factory()->create([
        'title' => 'Publikasi Draf Satu',
        'slug' => 'publikasi-draf-satu',
        'is_published' => false,
        'is_pinned' => false,
    ]);
    $p2 = Post::factory()->create([
        'title' => 'Publikasi Hapus Gambar',
        'slug' => 'publikasi-hapus-gambar',
        'featured_image_path' => 'posts/cover-uji.webp',
        'is_published' => true,
    ]);

    Livewire::test(PostManager::class)
        ->set('selectedIds', [(string) $p1->id])
        ->set('bulkAction', 'publish')
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    expect($p1->fresh()->is_published)->toBeTrue();

    Livewire::test(PostManager::class)
        ->set('selectedIds', [(string) $p1->id])
        ->set('bulkAction', 'pin')
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    expect($p1->fresh()->is_pinned)->toBeTrue();

    Livewire::test(PostManager::class)
        ->set('selectedIds', [(string) $p2->id])
        ->set('bulkAction', 'delete')
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    expect(Post::query()->whereKey($p2->id)->exists())->toBeFalse()
        ->and(Storage::disk('public')->exists('posts/cover-uji.webp'))->toBeFalse()
        ->and(Storage::disk('public')->exists('posts/cover-uji-400w.webp'))->toBeFalse();
});

it('filters criteria by health status in SinovaLinks and runs precomputed checkAll cleanly', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['tingkat' => 'pertama']);
    $superAdmin = makeStudioAdmin('super_admin');
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    $this->actingAs($superAdmin);

    $counts = app(SinovaHealthCheckService::class)->checkAll('pertama', probeExternal: false);
    expect($counts['checked'])->toBeGreaterThan(20);

    $attentionItem = CriteriaItem::query()->whereIn('health_status', ['broken', 'empty', 'warning'])->first();

    Livewire::test(SinovaLinks::class)
        ->set('healthFilter', 'healthy')
        ->assertStatus(200)
        ->set('healthFilter', $attentionItem?->health_status === 'warning' ? 'warning' : 'attention')
        ->assertStatus(200);
});

it('keeps warm homepage lean in SQL query count and avoids em dashes in public views', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['tingkat' => 'pertama']);
    app(RoleAndPermissionSeeder::class)->run();
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');
    ImageOptimizerService::clearMemo();

    // Warm caches with first request
    $this->get(route('home'))->assertOk();

    // Subsequent warm request should rely on cached menus, satker settings, and home payload
    DB::enableQueryLog();
    DB::flushQueryLog();
    $resp = $this->get(route('home'));
    $resp->assertOk();
    $warmQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($warmQueries)->toBeLessThanOrEqual(4)
        ->and((string) $resp->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'")
        ->and($resp->headers->has('X-Powered-By'))->toBeFalse()
        ->and((string) $resp->getContent())->not->toContain("\u{2014}");
});
