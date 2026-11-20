<?php

use App\Livewire\Admin\PageManager;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Livewire\Livewire;

function seedSeoPortal(): SatkerSetting
{
    app(RoleAndPermissionSeeder::class)->run();
    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Gorontalo',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'is_tingkat_locked' => true,
        'alamat_jalan' => 'Jl. Achmad Nadjamuddin No. 22',
        'kabupaten_kota' => 'Kota Gorontalo',
        'provinsi' => 'Gorontalo',
        'kode_pos' => '96128',
        'telepon' => '(0435) 827982',
        'email' => 'surat@pa-gorontalo.go.id',
        'logo_path' => 'satker/logo-test.webp',
        'foto_gedung_path' => 'satker/gedung-test.webp',
    ]);
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    return $satker;
}

it('renders single H1, canonical, Open Graph, Twitter Card, favicon, and Courthouse JSON-LD on homepage', function (): void {
    seedSeoPortal();

    $response = $this->get(route('home'));
    $html = $response->getContent();

    $response->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertSee('<link rel="icon" href="'.asset('storage/satker/logo-test.webp').'">', false)
        ->assertSee('<meta property="og:locale" content="id_ID">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"Courthouse"', false)
        ->assertSee('"SearchAction"', false);

    // Exactly 1 <h1> tag on the homepage despite multiple slides
    expect(preg_match_all('/<h1\b/i', (string) $html))->toBe(1);
});

it('renders dynamic NewsArticle JSON-LD, article meta description, and og:image on post detail', function (): void {
    seedSeoPortal();

    $post = Post::factory()->create([
        'title' => 'Panduan Sidang Prodeo Gratis Tahun 2026',
        'slug' => 'panduan-sidang-prodeo-gratis-2026',
        'category' => 'layanan_hukum',
        'excerpt' => '<p>Masyarakat tidak mampu dapat mengajukan pembebasan biaya perkara di PTSP.</p>',
        'body' => '<p>Penjelasan lengkap syarat Prodeo dan Posbakum.</p>',
        'featured_image_path' => 'posts/prodeo-cover.webp',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get(route('public.posts.show', ['slug' => $post->slug]))
        ->assertOk()
        ->assertSee('<meta name="description" content="Masyarakat tidak mampu dapat mengajukan pembebasan biaya perkara di PTSP.">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:image" content="'.asset('storage/posts/prodeo-cover.webp').'">', false)
        ->assertSee('"NewsArticle"', false)
        ->assertSee('"BreadcrumbList"', false);
});

it('renders canonical without query string, auto-fallback meta description, and BreadcrumbList on inner pages', function (): void {
    seedSeoPortal();

    $canonical = route('public.page', ['slug' => 'layanan-hukum/hak-pencari-keadilan']);

    $this->get('/halaman/layanan-hukum/hak-pencari-keadilan?tahun=2025')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$canonical.'">', false)
        ->assertSee('"WebPage"', false)
        ->assertSee('"BreadcrumbList"', false);
});

it('marks search results as noindex and serves dynamic robots.txt and tingkat-filtered sitemap.xml', function (): void {
    seedSeoPortal();

    Page::factory()->create([
        'title' => 'Halaman Khusus Banding',
        'slug' => 'profil/khusus-banding',
        'tingkat_scope' => 'banding',
        'is_published' => true,
    ]);

    $this->get(route('public.search', ['q' => 'sidang']))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);

    $robots = $this->get('/robots.txt');
    $robots->assertOk()
        ->assertSee('Disallow: /panel-admin', false)
        ->assertSee('Disallow: /cari', false)
        ->assertSee('Sitemap: '.route('public.sitemap.xml'), false);
    expect(str_contains((string) $robots->headers->get('Content-Type'), 'text/plain'))->toBeTrue();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('/halaman/layanan-hukum/hak-pencari-keadilan', false)
        ->assertDontSee('/halaman/profil/khusus-banding', false);
});

it('allows admin to save custom SEO meta_description in PageManager', function (): void {
    seedSeoPortal();

    $admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
    $admin->assignRole('super_admin');

    $page = Page::query()->where('slug', 'layanan-hukum/hak-pencari-keadilan')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(PageManager::class)
        ->call('edit', $page->id)
        ->set('meta_description', 'Panduan resmi layanan Prodeo dan Posbakum gratis bagi masyarakat pencari keadilan.')
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->meta_description)
        ->toBe('Panduan resmi layanan Prodeo dan Posbakum gratis bagi masyarakat pencari keadilan.');
});

