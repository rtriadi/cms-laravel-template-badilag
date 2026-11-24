<?php

use App\Models\CaseStatistic;
use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Services\SinovaCriteriaImporter;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;

it('seeds all 18 sinova criteria groups and items from juknis-2026.json', function (): void {
    $this->seed(SinovaCriteriaSeeder::class);

    expect(CriteriaGroup::query()->count())->toBe(18)
        ->and(CriteriaItem::query()->count())->toBe(40)
        ->and(CriteriaItem::query()->where('item_type', 'link')->count())->toBe(35)
        ->and(CriteriaItem::query()->where('code', '1.a.2.f')->first()?->tingkat_scope)->toBe('pertama')
        ->and(CriteriaItem::query()->where('code', '1.b.1')->first()?->name)->toBe('Visi dan Misi Pengadilan')
        ->and(CriteriaItem::query()->where('code', '1.b.p.d')->first()?->name)->toBe('Alamat dan Kontak Pengadilan')
        ->and(CriteriaItem::query()->where('code', '1.a.4.4.a')->first()?->default_route)->toBe('/statistik-pengunjung');
});

it('adapts default content non-destructively between tingkat pertama and banding', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['tingkat' => 'pertama']);
    $this->seed(SinovaCriteriaSeeder::class);
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    expect(MenuItem::query()->whereNull('parent_id')->where('location', 'header')->count())->toBe(6);

    $sisaPanjarPage = Page::query()->where('slug', 'layanan-hukum/sisa-panjar')->first();
    expect($sisaPanjarPage)->not->toBeNull()
        ->and($sisaPanjarPage->is_published)->toBeTrue();

    // Switch to banding: sisa-panjar must be deactivated, not deleted
    app(SatkerDefaultContentSeeder::class)->run('banding');

    expect($sisaPanjarPage->fresh()->is_published)->toBeFalse()
        ->and(Page::query()->where('slug', 'layanan-hukum/sisa-panjar')->exists())->toBeTrue();
});

it('previews diff and preserves manual urls when re-importing criteria json', function (): void {
    $this->seed(SinovaCriteriaSeeder::class);

    $item = CriteriaItem::query()->where('code', '1.a.2.a')->firstOrFail();
    $item->update([
        'manual_url' => 'https://sipp.pa-bandung.go.id',
        'use_manual_url' => true,
    ]);

    $payload = json_decode((string) file_get_contents(database_path('data/juknis-2026.json')), true, 512, JSON_THROW_ON_ERROR);
    $payload['groups'][0]['items'][] = [
        'code' => '1.a.1.c',
        'name' => 'Kriteria Baru Uji Coba',
        'tingkat_scope' => 'all',
        'item_type' => 'link',
        'sort_order' => 3,
    ];

    $importer = app(SinovaCriteriaImporter::class);
    $diff = $importer->previewDiff($payload);

    expect($diff['added_items'])->toContain('1.a.1.c');

    $importer->import($payload);

    expect(CriteriaItem::query()->where('code', '1.a.1.c')->exists())->toBeTrue()
        ->and($item->fresh()->manual_url)->toBe('https://sipp.pa-bandung.go.id')
        ->and($item->fresh()->use_manual_url)->toBeTrue();
});

it('automatically creates 301 redirect when page slug changes and handles incoming redirect', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();

    $page = Page::factory()->create([
        'title' => 'Visi Misi',
        'slug' => 'visi-misi-lama',
    ]);

    $page->update(['slug' => 'visi-misi-baru']);

    $redirect = Redirect::query()->where('from_path', '/halaman/visi-misi-lama')->first();
    expect($redirect)->not->toBeNull()
        ->and($redirect->to_path)->toBe('/halaman/visi-misi-baru')
        ->and($redirect->status_code)->toBe(301);

    $response = $this->get('/halaman/visi-misi-lama');
    $response->assertRedirect('/halaman/visi-misi-baru')
        ->assertStatus(301);

    expect($redirect->fresh()->hits)->toBe(1);
});

it('automatically computes sisa saat ini on case statistics', function (): void {
    $stat = CaseStatistic::query()->create([
        'year' => 2026,
        'month' => 11,
        'sisa_bulan_lalu' => 120,
        'perkara_masuk' => 85,
        'perkara_putus' => 95,
    ]);

    expect($stat->sisa_saat_ini)->toBe(110);
});
