<?php

use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\PublicPostController;
use App\Livewire\Admin\DocumentManager;
use App\Livewire\Admin\PostManager;
use App\Livewire\Admin\SatkerSettingsManager;
use App\Livewire\Admin\SliderManager;
use App\Models\Official;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\LocalCaptchaService;
use App\Services\SimtepaJsonService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function waveOneSuperAdmin(): User
{
    test()->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole('super_admin');

    return $user;
}

it('serves an accessible Indonesian arithmetic challenge on /captcha-text that cannot be bypassed by echoing text', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();

    $response = $this->get(route('captcha.text'))->assertOk();
    $prompt = trim($response->getContent());

    expect($prompt)
        ->toStartWith('Berapa hasil ')
        ->toEndWith('? (ketik angka)')
        ->not->toMatch('/\d/');

    $service = app(LocalCaptchaService::class);

    // Echoing the prompt text directly must fail
    $storedHash = session(LocalCaptchaService::SESSION_KEY);
    expect($service->verify($prompt))->toBeFalse();

    // Re-seed session with same signed answer and verify actual solved answer passes
    session([
        LocalCaptchaService::SESSION_KEY => $storedHash,
        LocalCaptchaService::SESSION_EXPIRES_KEY => now()->addMinutes(5)->timestamp,
    ]);

    $wordToInt = [
        'lima belas' => 15,
        'empat belas' => 14,
        'tiga belas' => 13,
        'dua belas' => 12,
        'sebelas' => 11,
        'sepuluh' => 10,
        'sembilan' => 9,
        'delapan' => 8,
        'tujuh' => 7,
        'enam' => 6,
        'lima' => 5,
        'empat' => 4,
        'tiga' => 3,
        'dua' => 2,
        'satu' => 1,
    ];

    preg_match('/^Berapa hasil (.+?) (ditambah|dikurangi) (.+?)\? \(ketik angka\)$/', $prompt, $matches);
    expect($matches)->toHaveCount(4);

    $left = $wordToInt[$matches[1]];
    $right = $wordToInt[$matches[3]];
    $expectedAnswer = $matches[2] === 'ditambah' ? (string) ($left + $right) : (string) ($left - $right);

    expect($service->verify($expectedAnswer))->toBeTrue();
});

it('uses inert DOMParser in admin rich editor previewHtml to avoid DOM XSS execution order', function (): void {
    $layout = file_get_contents(resource_path('views/components/layouts/admin.blade.php'));

    expect($layout)
        ->toContain("new DOMParser().parseFromString(raw, 'text/html')")
        ->not->toContain('container.innerHTML = raw;');
});

it('rejects spoofed PDFs, active PDF JavaScript/Launch payloads, and raw GIF uploads', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    SatkerSetting::factory()->setupCompleted()->create();

    $admin = waveOneSuperAdmin();
    $this->actingAs($admin);

    // Spoofed file missing %PDF- magic header
    $spoofedPdf = UploadedFile::fake()->createWithContent('fake.pdf', '<html><script>alert(1)</script></html>');
    $this->postJson(route('admin.media.upload'), ['file' => $spoofedPdf])
        ->assertStatus(422);

    // PDF with active /JavaScript token
    $jsPdf = UploadedFile::fake()->createWithContent('active.pdf', "%PDF-1.4\n1 0 obj<</S /JavaScript /JS (app.alert(1))>>endobj\n%%EOF");
    $this->postJson(route('admin.media.upload'), ['file' => $jsPdf])
        ->assertStatus(422);

    Livewire::test(DocumentManager::class)
        ->set('collection_type', 'dip_ppid')
        ->set('title', 'PDF Berbahaya')
        ->set('year', 2026)
        ->set('pdf_file', $jsPdf)
        ->call('save')
        ->assertHasErrors(['pdf_file']);

    // Valid benign PDF (including harmless /OpenAction zoom) must pass
    $cleanPdf = UploadedFile::fake()->createWithContent('resmi.pdf', "%PDF-1.4\n1 0 obj<</OpenAction [3 0 R /FitH null]>>endobj\n%%EOF");
    $this->postJson(route('admin.media.upload'), ['file' => $cleanPdf])
        ->assertOk()
        ->assertJsonPath('is_pdf', true);

    // Raw GIF upload must be rejected across editor media, slider, post, and satker settings
    $gifHeader = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
    $gifFile = UploadedFile::fake()->createWithContent('anim.gif', $gifHeader);

    $this->postJson(route('admin.media.upload'), ['file' => $gifFile])
        ->assertStatus(422);

    Livewire::test(SliderManager::class)
        ->set('title', 'Slide GIF')
        ->set('image_alt', 'Alt slide')
        ->set('image', $gifFile)
        ->call('save')
        ->assertHasErrors(['image']);

    Livewire::test(PostManager::class)
        ->set('title', 'Berita GIF')
        ->set('body', '<p>Isi berita</p>')
        ->set('featured_image_alt', 'Alt berita')
        ->set('featured_image', $gifFile)
        ->call('save')
        ->assertHasErrors(['featured_image']);

    Livewire::test(SatkerSettingsManager::class)
        ->set('logo', $gifFile)
        ->call('save')
        ->assertHasErrors(['logo']);
});

it('caches per-path redirect hits and misses without populating full table map and flushes on mutation', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();
    Cache::flush();

    $redirect = Redirect::query()->create([
        'from_path' => '/halaman/lama-cache',
        'to_path' => '/publikasi',
        'status_code' => 301,
    ]);

    // First lookup populates per-path cache (not full table map)
    expect(Redirect::findForPath('/halaman/lama-cache'))->toMatchArray([
        'id' => $redirect->id,
        'to_path' => '/publikasi',
        'status_code' => 301,
    ]);
    expect(Redirect::findForPath('/halaman/tidak-ada'))->toBeNull();
    expect(Cache::has(Redirect::CACHE_KEY))->toBeFalse();

    // Second lookup for both hit and miss should execute zero SQL queries
    DB::flushQueryLog();
    DB::enableQueryLog();
    expect(Redirect::findForPath('/halaman/lama-cache')['to_path'] ?? null)->toBe('/publikasi');
    expect(Redirect::findForPath('/halaman/tidak-ada'))->toBeNull();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toHaveCount(0);

    // Updating redirect bumps cache version so next lookup sees fresh target
    $redirect->update(['to_path' => '/aksesibilitas']);
    expect(Redirect::findForPath('/halaman/lama-cache')['to_path'] ?? null)->toBe('/aksesibilitas');

    // Middleware redirects cleanly and increments hits during terminate()
    $this->get('/halaman/lama-cache')
        ->assertRedirect('/aksesibilitas')
        ->assertStatus(301);

    expect($redirect->fresh()->hits)->toBe(1);
});

it('caches post sidebar counts and page section lists, and skips fallback official queries on live embed pages', function (): void {
    SatkerSetting::factory()->setupCompleted()->create([
        'sipp_url' => 'https://sipp.pa-gorontalo.go.id',
    ]);
    Cache::flush();

    $post = Post::factory()->create([
        'slug' => 'berita-cache-uji',
        'category' => 'berita',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get(route('public.posts.show', ['slug' => $post->slug]))->assertOk();
    expect(Cache::has(PublicPostController::CATEGORY_COUNTS_CACHE_KEY))->toBeTrue()
        ->and(Cache::has(PublicPostController::LATEST_SIDEBAR_CACHE_KEY))->toBeTrue();

    // Creating a new post flushes cached post sidebar keys
    Post::factory()->create([
        'slug' => 'berita-cache-baru',
        'category' => 'pengumuman',
        'is_published' => true,
        'published_at' => now(),
    ]);
    expect(Cache::has(PublicPostController::CATEGORY_COUNTS_CACHE_KEY))->toBeFalse()
        ->and(Cache::has(PublicPostController::LATEST_SIDEBAR_CACHE_KEY))->toBeFalse();

    // Live embed page without ?fallback=1 must not query officials table
    Official::factory()->create(['is_active' => true, 'nama_gelar' => 'Pejabat Cadangan']);
    $page = Page::factory()->create([
        'slug' => 'profil/uji-embed-hemat-query',
        'title' => 'Uji Embed Hemat Query',
        'is_published' => true,
        'tingkat_scope' => 'all',
        'blocks' => [[
            'type' => 'embed_fallback',
            'source_key' => 'sipp_perkara',
            'fallback_mode' => 'officials_table',
        ]],
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('public.page', ['slug' => $page->slug]))->assertOk();
    $liveQueries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
    DB::disableQueryLog();

    expect($liveQueries)->not->toContain('"officials"')
        ->and($liveQueries)->not->toContain('`officials`');

    // Explicit ?fallback=1 loads fallback officials
    $this->get(route('public.page', ['slug' => $page->slug, 'fallback' => 1]))
        ->assertOk()
        ->assertSee('Pejabat Cadangan');

    // Section cache version bumps on Page save
    $verBefore = (int) Cache::get(PublicPageController::SECTION_CACHE_VERSION_KEY, 1);
    $page->update(['title' => 'Uji Embed Diperbarui']);
    expect((int) Cache::get(PublicPageController::SECTION_CACHE_VERSION_KEY, 1))->toBeGreaterThan($verBefore);
});

it('hardens SimtepaJsonService against custom ports, credentials, path traversal, and HTTP redirects', function (): void {
    $service = app(SimtepaJsonService::class);

    expect($service->isAllowedSimtepaJsonUrl('https://simtepa.mahkamahagung.go.id:8443/share/profil_ketua/json/abc'))->toBeFalse()
        ->and($service->isAllowedSimtepaJsonUrl('https://user@simtepa.mahkamahagung.go.id/share/profil_ketua/json/abc'))->toBeFalse()
        ->and($service->isAllowedSimtepaJsonUrl('https://simtepa.mahkamahagung.go.id/share/../admin/json/abc'))->toBeFalse()
        ->and($service->isAllowedSimtepaJsonUrl('https://simtepa.mahkamahagung.go.id/share/profil_ketua/json/abc123'))->toBeTrue();

    config()->set('satker.simtepa_fetch_in_tests', true);
    Cache::flush();

    Http::fake([
        'https://simtepa.mahkamahagung.go.id/*' => Http::response('', 302, [
            'Location' => 'http://127.0.0.1/secret',
        ]),
    ]);

    $result = $service->fetchProfiles('https://simtepa.mahkamahagung.go.id/share/profil_ketua/json/redirecttest');
    expect($result)->toBeNull();
});

it('serves cached pages and posts cleanly on database cache store with Laravel 13 serializable_classes=false', function (): void {
    config()->set('cache.default', 'database');
    config()->set('cache.serializable_classes', false);
    Cache::purge('database');
    Cache::flush();

    SatkerSetting::factory()->setupCompleted()->create();

    $page = Page::factory()->create([
        'slug' => 'layanan-hukum/sipp-uji-db-cache',
        'title' => 'SIPP Uji Cache Database',
        'is_published' => true,
        'tingkat_scope' => 'all',
        'blocks' => [['type' => 'richtext', 'content' => '<p>Konten uji</p>']],
    ]);

    $post = Post::factory()->create([
        'slug' => 'berita-uji-db-cache',
        'title' => 'Berita Uji Cache Database',
        'category' => 'berita',
        'is_published' => true,
        'published_at' => now(),
    ]);

    // First request populates database cache; second request reads serialized cache rows without __PHP_Incomplete_Class
    $this->get(route('public.page', ['slug' => $page->slug]))->assertOk()->assertSee('SIPP Uji Cache Database');
    $this->get(route('public.page', ['slug' => $page->slug]))->assertOk()->assertSee('SIPP Uji Cache Database');

    $this->get(route('public.posts.show', ['slug' => $post->slug]))->assertOk()->assertSee('Berita Uji Cache Database');
    $this->get(route('public.posts.show', ['slug' => $post->slug]))->assertOk()->assertSee('Berita Uji Cache Database');
});
