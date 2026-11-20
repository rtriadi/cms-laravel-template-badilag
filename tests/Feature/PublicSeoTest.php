<?php

use App\Livewire\Admin\PageManager;
use App\Models\Page;
use App\Models\PageVisit;
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

it('renders self-referencing pagination canonical, prev/next links, and category H1 on publications index', function (): void {
    seedSeoPortal();

    for ($i = 1; $i <= 12; $i++) {
        Post::factory()->create([
            'title' => "Berita Peradilan Seri {$i}",
            'slug' => "berita-peradilan-seri-{$i}",
            'category' => 'berita',
            'is_published' => true,
            'published_at' => now()->subDays($i),
        ]);
    }

    $page1 = $this->get(route('public.posts'));
    $page1->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('public.posts').'">', false)
        ->assertSee('<link rel="next" href="', false)
        ->assertDontSee('<link rel="prev"', false);

    $page2Url = route('public.posts', ['page' => 2]);
    $page2 = $this->get($page2Url);
    $page2->assertOk()
        ->assertSee('<link rel="canonical" href="'.$page2Url.'">', false)
        ->assertSee('<link rel="prev" href="', false)
        ->assertSee('(Halaman 2)', false);

    $catResp = $this->get(route('public.posts', ['kategori' => 'berita']));
    $catResp->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Berita Pelayanan');
});

it('renders unique meta descriptions on compliance pages, avoids duplicate satker name in title, and completes JSON-LD schemas', function (): void {
    seedSeoPortal();

    $paths = [
        route('public.accessibility'),
        route('public.system-status'),
        route('public.visitor-stats'),
        route('public.sitemap'),
    ];

    $descriptions = [];
    foreach ($paths as $url) {
        $html = (string) $this->get($url)->assertOk()->getContent();
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        expect($m[1] ?? '')->not->toBeEmpty()
            ->and(mb_strlen(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')))->toBeLessThanOrEqual(155);
        $descriptions[] = $m[1];
    }

    expect(array_unique($descriptions))->toHaveCount(4);

    // Visi Misi page title already contains satker name -> must not duplicate it
    $visiHtml = (string) $this->get('/halaman/profil/visi-misi')->assertOk()->getContent();
    expect($visiHtml)
        ->toContain('<title>Visi dan Misi Pengadilan Agama Gorontalo</title>')
        ->not->toContain('Pengadilan Agama Gorontalo - Pengadilan Agama Gorontalo');

    // BreadcrumbList position 2 must contain item URL
    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $visiHtml, $ldMatch);
    $graph = json_decode($ldMatch[1] ?? '{}', true)['@graph'] ?? [];
    $breadcrumb = collect($graph)->firstWhere('@type', 'BreadcrumbList');
    expect($breadcrumb['itemListElement'][1]['item'] ?? null)->not->toBeNull();

    // NewsArticle without featured image falls back to satker image and includes author.url
    $post = Post::factory()->create([
        'title' => 'Pengumuman Tanpa Gambar Sampul',
        'slug' => 'pengumuman-tanpa-gambar-sampul',
        'category' => 'pengumuman',
        'featured_image_path' => null,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $postHtml = (string) $this->get(route('public.posts.show', ['slug' => $post->slug]))->assertOk()->getContent();
    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $postHtml, $postLdMatch);
    $postGraph = json_decode($postLdMatch[1] ?? '{}', true)['@graph'] ?? [];
    $article = collect($postGraph)->firstWhere('@type', 'NewsArticle');
    expect($article['image'] ?? [])->not->toBeEmpty()
        ->and($article['author']['url'] ?? null)->toBe(route('home'));
});

it('serves stateless cacheable sitemap.xml with stable lastmod, hides custom ADMIN_PATH in robots.txt, and filters bots/prefetch from visit tracking', function (): void {
    seedSeoPortal();

    $sitemap1 = $this->get('/sitemap.xml');
    $sitemap1->assertOk();
    expect((string) $sitemap1->headers->get('Cache-Control'))->toContain('public')
        ->and($sitemap1->headers->getCookies())->toBeEmpty();

    $sitemap2 = $this->get('/sitemap.xml');
    expect($sitemap2->getContent())->toBe($sitemap1->getContent());

    $robotsDefault = $this->get('/robots.txt');
    $robotsDefault->assertOk();
    expect((string) $robotsDefault->headers->get('Cache-Control'))->toContain('public')
        ->and($robotsDefault->headers->getCookies())->toBeEmpty();

    // Custom stealth ADMIN_PATH must not be disclosed in robots.txt
    config()->set('satker.admin_path', 'gerbang-rahasia-satker');
    $this->get('/robots.txt')
        ->assertOk()
        ->assertDontSee('gerbang-rahasia-satker');

    // Search endpoint emits X-Robots-Tag noindex header
    $this->get(route('public.search', ['q' => 'prodeo']))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');

    // Visit tracking increments reliably across repeated normal requests, while ignoring bots and prefetches
    PageVisit::query()->delete();

    $this->get(route('public.accessibility'))->assertOk();
    $this->get(route('public.accessibility'))->assertOk();
    $this->withHeaders(['Sec-Purpose' => 'prefetch'])->get(route('public.accessibility'))->assertOk();
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
        ->get(route('public.accessibility'))
        ->assertOk();

    expect((int) PageVisit::query()->where('path', '/aksesibilitas')->sum('visits_count'))->toBe(2);
});
