<?php

use App\Livewire\Admin\SliderManager;
use App\Models\HomeSlider;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\ImageOptimizerService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('resizes and converts uploaded images to compressed WebP via ImageOptimizerService', function (): void {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('banner.png', 1800, 1000);
    $path = app(ImageOptimizerService::class)->storeOptimized($file, 'sliders', 1280, 82);

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    $bytes = Storage::disk('public')->get($path);
    $info = @getimagesizefromstring($bytes);

    expect($info)->toBeArray()
        ->and($info[0])->toBe(1280)
        ->and($info[1])->toBe(711)
        ->and($info[2])->toBe(IMAGETYPE_WEBP);
});

it('automatically optimizes slider image upload to WebP in SliderManager', function (): void {
    Storage::fake('public');
    app(RoleAndPermissionSeeder::class)->run();
    SatkerSetting::factory()->setupCompleted()->create();

    $admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
    $admin->assignRole('super_admin');

    $upload = UploadedFile::fake()->image('hero.png', 1600, 900);

    Livewire::actingAs($admin)
        ->test(SliderManager::class)
        ->set('title', 'Banner Teroptimasi')
        ->set('image', $upload)
        ->set('image_alt', 'Banner portal resmi')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $slide = HomeSlider::query()->where('title', 'Banner Teroptimasi')->firstOrFail();

    expect($slide->image_path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($slide->image_path);

    $info = @getimagesizefromstring(Storage::disk('public')->get($slide->image_path));
    expect($info)->toBeArray()->and($info[0])->toBeLessThanOrEqual(1280);
});

it('converts uploaded editor images to WebP while preserving PDF uploads', function (): void {
    Storage::fake('public');
    app(RoleAndPermissionSeeder::class)->run();
    SatkerSetting::factory()->setupCompleted()->create();

    $admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
    $admin->assignRole('super_admin');

    $imageResp = $this->actingAs($admin)->postJson(route('admin.media.upload'), [
        'file' => UploadedFile::fake()->image('artikel.png', 1500, 1000),
    ]);

    $imageResp->assertOk()
        ->assertJson(['is_pdf' => false]);

    $imageLocation = (string) $imageResp->json('location');
    expect($imageLocation)->toEndWith('.webp');
    Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $imageLocation), '/'));

    $pdfResp = $this->actingAs($admin)->postJson(route('admin.media.upload'), [
        'file' => UploadedFile::fake()->create('dokumen.pdf', 120, 'application/pdf'),
    ]);

    $pdfResp->assertOk()
        ->assertJson(['is_pdf' => true]);

    $pdfLocation = (string) $pdfResp->json('location');
    expect($pdfLocation)->toEndWith('.pdf');
    Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $pdfLocation), '/'));
});

it('generates responsive WebP variants without upscaling and deletes variants cleanly', function (): void {
    Storage::fake('public');

    $optimizer = app(ImageOptimizerService::class);
    $file = UploadedFile::fake()->image('hero-wide.png', 1600, 900);
    $path = $optimizer->storeOptimized($file, 'sliders', 1280, 82, 'public', [480, 800, 1600]);

    $var480 = ImageOptimizerService::variantPath($path, 480);
    $var800 = ImageOptimizerService::variantPath($path, 800);
    $var1600 = ImageOptimizerService::variantPath($path, 1600);

    Storage::disk('public')->assertExists($path);
    Storage::disk('public')->assertExists($var480);
    Storage::disk('public')->assertExists($var800);
    Storage::disk('public')->assertMissing($var1600);

    $info480 = @getimagesizefromstring(Storage::disk('public')->get($var480));
    expect($info480)->toBeArray()->and($info480[0])->toBe(480);

    $optimizer->deleteWithVariants($path);

    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertMissing($var480);
    Storage::disk('public')->assertMissing($var800);
});
