<?php

declare(strict_types=1);

use App\Http\Controllers\PublicComplianceController;
use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DocumentManager;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\PostManager;
use App\Livewire\Admin\SatkerSettingsManager;
use App\Models\CriteriaItem;
use App\Models\Document;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\LegacyMigration\LegacyHtmlTransformer;
use App\Support\IndonesianTitleNormalizer;
use App\Support\SafeHtml;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('public search renders external document link button alongside local pdf downloads', function (): void {
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    Document::factory()->create([
        'collection_type' => 'lra',
        'title' => 'LRA Semester I Tahun 2025 Eksternal',
        'year' => 2025,
        'file_path' => null,
        'external_url' => 'https://drive.google.com/file/d/1TestExternalDocId123/view',
        'is_published' => true,
    ]);

    Document::factory()->create([
        'collection_type' => 'lra',
        'title' => 'LRA Semester II Tahun 2025 Lokal',
        'year' => 2025,
        'file_path' => 'documents/lra/sample-lra.pdf',
        'external_url' => null,
        'is_published' => true,
    ]);

    $this->get('/cari?q=LRA+Semester')
        ->assertOk()
        ->assertSee('LRA Semester I Tahun 2025 Eksternal')
        ->assertSee('Buka Tautan')
        ->assertSee('https://drive.google.com/file/d/1TestExternalDocId123/view')
        ->assertSee('LRA Semester II Tahun 2025 Lokal')
        ->assertSee('Unduh PDF');
});

test('SafeHtml wraps tables in responsive overflow container idempotently', function (): void {
    $raw = '<p>Pengantar</p><table><thead><tr><th>Kolom</th></tr></thead><tbody><tr><td>Nilai</td></tr></tbody></table>';
    $once = SafeHtml::clean($raw);
    $twice = SafeHtml::clean($once);

    expect($once)->toContain('<div class="prose-table-wrap"><table>')
        ->and($twice)->toBe($once)
        ->and(substr_count($twice, 'prose-table-wrap'))->toBe(1);
});

test('IndonesianTitleNormalizer normalizes all-caps titles while preserving judicial acronyms', function (): void {
    $allCaps = 'APEL PAGI PA GORONTALO DAN EVALUASI ZI WBK/WBBM, PTSP, SIPP, LKJIP, DIPA 1446H DI PTA GORONTALO';
    $normalized = IndonesianTitleNormalizer::normalizeIfAllCaps($allCaps);

    expect($normalized)->toBe('Apel Pagi PA Gorontalo dan Evaluasi ZI WBK/WBBM, PTSP, SIPP, LKjIP, DIPA 1446H di PTA Gorontalo');

    $mixed = 'Ketua PA Gorontalo Hadiri Rapat Koordinasi e-Court Tahun 2026';
    expect(IndonesianTitleNormalizer::normalizeIfAllCaps($mixed))->toBe($mixed);
});

test('LegacyHtmlTransformer strips wrapped duplicate hero images, http images, and UTF-8 NBSP excerpts', function (): void {
    $transformer = new LegacyHtmlTransformer;

    $html = '<p><a href="/storage/editor-media/abc123def4567890.webp"><img src="/storage/editor-media/abc123def4567890.webp" alt="Hero"></a></p>'
        .'<p><img src="http://legacy.example.go.id/images/broken.png" alt="Blocked"></p>'
        .'<p>Kunjungi&nbsp;portal <a href="http://pa-gorontalo.go.id/berita">lama</a> untuk info.</p>';

    $cleaned = $transformer->sanitizePostBodyDuplicates($html, 'posts/abc123def4567890.webp');

    expect($cleaned)->not->toContain('abc123def4567890.webp')
        ->and($cleaned)->not->toContain('http://legacy.example.go.id')
        ->and($cleaned)->toContain('https://pa-gorontalo.go.id/berita')
        ->and($cleaned)->toContain('Kunjungi');

    $excerpt = $transformer->extractExcerpt("<p>Halo\xC2\xA0\xC2\xA0Dunia&nbsp;Peradilan</p>", 120);
    expect($excerpt)->toBe('Halo Dunia Peradilan')
        ->and($excerpt)->not->toContain("\xC2\xA0");
});

test('public document list block paginates large collections and supports inline search', function (): void {
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    Page::factory()->create([
        'slug' => 'layanan-hukum/arsip-regulasi-uji',
        'title' => 'Arsip Regulasi Uji',
        'is_published' => true,
        'blocks' => [
            [
                'type' => 'document_list',
                'collection_type' => 'regulasi_sk',
                'heading' => 'Daftar Regulasi Uji',
            ],
        ],
    ]);

    for ($i = 1; $i <= 24; $i++) {
        Document::factory()->create([
            'collection_type' => 'regulasi_sk',
            'title' => sprintf('SK Ketua Nomor %02d Tahun 2025', $i),
            'year' => 2025,
            'file_path' => null,
            'external_url' => "https://drive.google.com/file/d/1RegulasiTestDoc{$i}xxxx/view",
            'is_published' => true,
        ]);
    }

    $this->get('/halaman/layanan-hukum/arsip-regulasi-uji')
        ->assertOk()
        ->assertSee('Menampilkan 1-20 dari 24 dokumen')
        ->assertSee('hal_regulasi_sk=2');

    $this->get('/halaman/layanan-hukum/arsip-regulasi-uji?cari_dok=Nomor+24')
        ->assertOk()
        ->assertSee('SK Ketua Nomor 24 Tahun 2025')
        ->assertDontSee('SK Ketua Nomor 01 Tahun 2025');
});

test('admin dashboard reports true published post count and managers paginate cleanly', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Post::factory()->count(8)->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->assertViewHas('publishedPostsCount', 8)
        ->assertSee('Publikasi Tayang');

    for ($i = 1; $i <= 30; $i++) {
        Redirect::query()->create([
            'from_path' => "/legacy-route-{$i}",
            'to_path' => '/publikasi',
            'status_code' => 301,
        ]);
    }

    Livewire::actingAs($admin)
        ->test(AuditAndBackupManager::class)
        ->set('tab', 'redirects')
        ->assertSee('/legacy-route-30')
        ->set('redirectSearch', 'legacy-route-15')
        ->assertSee('/legacy-route-15')
        ->assertDontSee('/legacy-route-30');

    for ($i = 1; $i <= 25; $i++) {
        Document::factory()->create([
            'collection_type' => 'lra',
            'title' => sprintf('Dokumen LRA Uji %02d', $i),
            'year' => $i <= 5 ? 2024 : 2025,
            'file_path' => null,
            'external_url' => "https://drive.google.com/file/d/1LraTestDoc{$i}xxxxxx/view",
        ]);
    }

    Livewire::actingAs($admin)
        ->test(DocumentManager::class)
        ->set('collectionFilter', 'lra')
        ->assertViewHas('documents', fn ($docs): bool => $docs->count() === 20 && $docs->total() === 25)
        ->set('yearFilter', '2024')
        ->assertViewHas('documents', fn ($docs): bool => $docs->count() === 5 && $docs->total() === 5)
        ->set('search', 'Uji 03')
        ->assertSee('Dokumen LRA Uji 03')
        ->assertDontSee('Dokumen LRA Uji 04');

    Page::factory()->count(3)->create(['is_published' => true]);

    Livewire::actingAs($admin)
        ->test(PageManager::class)
        ->assertDontSee('Simpan Halaman');
});

test('HandleRedirectsMiddleware performs indexed lookup and SatkerSetting memoizes per request', function (): void {
    SatkerSetting::factory()->create(['nama_satker' => 'PA Uji Memo']);
    Redirect::query()->create([
        'from_path' => '/jalur-lama-indexed',
        'to_path' => '/publikasi',
        'status_code' => 301,
    ]);

    Cache::forget(Redirect::CACHE_KEY);

    $this->get('/jalur-lama-indexed')
        ->assertRedirect('/publikasi')
        ->assertStatus(301);

    expect(Cache::has(Redirect::CACHE_KEY))->toBeFalse();

    $first = SatkerSetting::current();
    $second = SatkerSetting::current();
    expect($first)->toBe($second)
        ->and($first?->nama_satker)->toBe('PA Uji Memo');

    $first?->update(['nama_satker' => 'PA Uji Diperbarui']);
    expect(SatkerSetting::current()?->nama_satker)->toBe('PA Uji Diperbarui');
});

test('regional timezone config defaults to WITA and public homepage displays service hours', function (): void {
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    expect(config('app.timezone'))->toBe('Asia/Makassar')
        ->and(config('satker.service_hours.timezone_label'))->toBe('WITA');

    $this->get('/')
        ->assertOk()
        ->assertSee('Jam Pelayanan Pengadilan (PTSP)')
        ->assertSee('WITA');
});

test('post detail renders article body directly without forced cover banner while keeping og:image and editor mode tabs', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    $post = Post::factory()->create([
        'title' => 'Diskusi Hukum Isbat Wakaf Terpadu',
        'slug' => 'diskusi-hukum-isbat-wakaf-terpadu',
        'body' => '<p><strong>Paragraf pembuka langsung berita.</strong></p><p><img src="/storage/editor-media/inline-photo.webp" alt="Dokumentasi kegiatan" /></p>',
        'featured_image_path' => 'posts/cover-thumb-only.webp',
        'is_published' => true,
        'published_at' => now()->subHour(),
    ]);

    $this->get(route('public.posts.show', ['slug' => $post->slug]))
        ->assertOk()
        ->assertSee('<meta property="og:image" content="'.asset('storage/posts/cover-thumb-only.webp').'">', false)
        ->assertSee('Paragraf pembuka langsung berita.')
        ->assertSee('/storage/editor-media/inline-photo.webp', false)
        ->assertDontSee('class="mb-6 max-h-96 w-full rounded-lg object-cover"', false);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Livewire::actingAs($admin)
        ->test(PostManager::class)
        ->call('edit', $post->id)
        ->assertSee('role="tablist"', false)
        ->assertSee('Editor')
        ->assertSee('Code')
        ->assertSee('Preview')
        ->assertSee('Rapikan Indentasi HTML');
});

test('public page breadcrumb section label matches header menu title', function (): void {
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    $page = Page::factory()->create([
        'slug' => 'transparansi/renstra-uji',
        'title' => 'Rencana Strategis (Renstra)',
        'is_published' => true,
        'blocks' => [['type' => 'richtext', 'heading' => 'Renstra', 'content' => '<p>Isi Renstra</p>']],
    ]);

    $parentMenu = MenuItem::query()->create([
        'location' => 'header',
        'title' => 'Transparansi',
        'type' => 'group',
        'sort_order' => 4,
        'is_visible' => true,
        'tingkat_scope' => 'all',
    ]);

    MenuItem::query()->create([
        'location' => 'header',
        'parent_id' => $parentMenu->id,
        'page_id' => $page->id,
        'title' => 'Rencana Strategis (Renstra)',
        'type' => 'page',
        'sort_order' => 1,
        'is_visible' => true,
        'tingkat_scope' => 'all',
    ]);
    MenuItem::flushPublicCache();

    $this->get('/halaman/transparansi/renstra-uji')
        ->assertOk()
        ->assertSee('<span class="text-emerald-200">Transparansi</span>', false)
        ->assertDontSee('Transparansi Kinerja &amp; Anggaran (SAKIP)', false);
});

test('replacing satker logo and building photo deletes old files and variants without TypeError', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    Storage::fake('public');

    Storage::disk('public')->put('satker/old-logo.webp', 'old-logo');
    Storage::disk('public')->put('satker/old-logo-128w.webp', 'old-logo-128');
    Storage::disk('public')->put('satker/old-gedung.webp', 'old-gedung');
    Storage::disk('public')->put('satker/old-gedung-480w.webp', 'old-gedung-480');

    SatkerSetting::factory()->create([
        'is_setup_completed' => true,
        'logo_path' => 'satker/old-logo.webp',
        'foto_gedung_path' => 'satker/old-gedung.webp',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Livewire::actingAs($admin)
        ->test(SatkerSettingsManager::class)
        ->set('logo', UploadedFile::fake()->image('logo-baru.png', 256, 256))
        ->set('foto_gedung', UploadedFile::fake()->image('gedung-baru.jpg', 960, 540))
        ->call('save')
        ->assertHasNoErrors();

    expect(Storage::disk('public')->exists('satker/old-logo.webp'))->toBeFalse()
        ->and(Storage::disk('public')->exists('satker/old-logo-128w.webp'))->toBeFalse()
        ->and(Storage::disk('public')->exists('satker/old-gedung.webp'))->toBeFalse()
        ->and(Storage::disk('public')->exists('satker/old-gedung-480w.webp'))->toBeFalse();
});

test('SatkerDefaultContentSeeder automatically relinks orphaned page menus so all header items render publicly', function (): void {
    SatkerSetting::factory()->create(['is_setup_completed' => true, 'tingkat' => 'pertama']);
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    MenuItem::query()
        ->where('title', 'Prosedur Berperkara')
        ->update(['page_id' => null]);
    MenuItem::query()
        ->where('title', 'Catatan Atas Laporan Keuangan (CALK)')
        ->update(['page_id' => 999999]);
    MenuItem::flushPublicCache();

    app(SatkerDefaultContentSeeder::class)->run('pertama');

    $headerTitles = MenuItem::cachedPublicMenus('pertama')['header']
        ->flatMap(fn (MenuItem $top) => $top->children->pluck('title'))
        ->all();

    expect($headerTitles)->toContain('Prosedur Berperkara')
        ->and($headerTitles)->toContain('Catatan Atas Laporan Keuangan (CALK)');
});

test('custom Bahasa Indonesia pagination stays compact for large datasets without overflowing', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    for ($i = 1; $i <= 260; $i++) {
        Redirect::query()->create([
            'from_path' => "/bulk-legacy-{$i}",
            'to_path' => '/publikasi',
            'status_code' => 301,
        ]);
    }

    $html = Livewire::actingAs($admin)
        ->test(AuditAndBackupManager::class)
        ->set('tab', 'redirects')
        ->html();

    expect($html)->toContain('Menampilkan')
        ->and($html)->toContain('1-25')
        ->and($html)->toContain('260')
        ->and($html)->not->toContain('Showing')
        ->and(substr_count($html, 'gotoPage('))->toBeLessThanOrEqual(6);
});

test('SIMTEPA struktur_organisasi URL embeds directly on Struktur Organisasi page and admin previews', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    $token = 'a87bd82962cda7f528247a68ee4ac2a5';
    $strukturUrl = "https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/{$token}";
    $pegawaiUrl = "https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/{$token}";

    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Gorontalo',
        'simtepa_pegawai_url' => $pegawaiUrl,
        'simtepa_struktur_url' => $strukturUrl,
    ]);

    app(SatkerDefaultContentSeeder::class)->run('pertama');

    expect($satker->resolveEmbedSource('simtepa_struktur'))->toBe([
        'embed_url' => $strukturUrl,
        'tabs' => [],
    ])->and(count($satker->resolveEmbedSource('simtepa_struktur_tabs')['tabs']))->toBe(6);

    $this->get('/halaman/profil/struktur-organisasi')
        ->assertOk()
        ->assertSee('src="'.$strukturUrl.'"', false)
        ->assertDontSee('Pilih Kategori Data Pegawai SIMTEPA:');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $page = Page::query()->where('slug', 'profil/struktur-organisasi')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(PageManager::class)
        ->call('edit', $page->id)
        ->assertSee('SIMTEPA Struktur Organisasi (Link Bagan Struktur)')
        ->assertSee($strukturUrl)
        ->assertDontSee('6 Tab Kategori SIMTEPA');

    Livewire::actingAs($admin)
        ->test(SatkerSettingsManager::class)
        ->set('simtepa_struktur_url', $token)
        ->call('save')
        ->assertHasNoErrors();

    expect(SatkerSetting::current()?->simtepa_struktur_url)->toBe($strukturUrl);
});

test('Pedoman Pengawasan front-page settings and official SINOVA 40-criteria mapping render properly', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SinovaCriteriaSeeder::class);

    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Gorontalo',
        'tingkat' => 'pertama',
    ]);
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Livewire::actingAs($admin)
        ->test(SatkerSettingsManager::class)
        ->set('pta_nama', 'Pengadilan Tinggi Agama Gorontalo')
        ->set('pta_url', 'https://pta-gorontalo.go.id')
        ->set('survei_periode', 'Triwulan III 2026')
        ->set('nilai_skm', '95,40')
        ->set('nilai_spak', '96,10')
        ->set('nilai_spkp', '95,80')
        ->set('video_profil_url', 'https://youtube.com/watch?v=profil123')
        ->set('video_ptsp_url', 'https://youtube.com/watch?v=ptsp456')
        ->set('video_kebijakan_ma_url', 'https://youtube.com/watch?v=ma789')
        ->call('save')
        ->assertHasNoErrors();

    $this->get('/')
        ->assertOk()
        ->assertSee('Pengadilan Tinggi Agama Gorontalo')
        ->assertSee('https://pta-gorontalo.go.id')
        ->assertSee('Triwulan III 2026')
        ->assertSee('95,40')
        ->assertSee('96,10')
        ->assertSee('95,80')
        ->assertSee('https://youtube.com/watch?v=profil123')
        ->assertSee('https://youtube.com/watch?v=ptsp456')
        ->assertSee('https://youtube.com/watch?v=ma789')
        ->assertSee('Galeri Infografis')
        ->assertSee('Informasi Serta Merta');

    $visiItem = CriteriaItem::query()->with('linkedPage')->where('code', '1.b.1')->firstOrFail();
    $alamatItem = CriteriaItem::query()->with('linkedPage')->where('code', '1.b.p.d')->firstOrFail();
    $statsItem = CriteriaItem::query()->where('code', '1.a.4.4.a')->firstOrFail();

    expect($visiItem->linkedPage?->slug)->toBe('profil/visi-misi')
        ->and($alamatItem->linkedPage?->slug)->toBe('profil/alamat-kontak')
        ->and($statsItem->resolvedUrl($satker))->toEndWith('/statistik-pengunjung');
});

test('CMS settings manages Tautan Instansi, Sosial Media, and Access CCTV Online (ACO) and renders them publicly', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Gorontalo',
        'tingkat' => 'pertama',
    ]);
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Livewire::actingAs($admin)
        ->test(SatkerSettingsManager::class)
        ->set('cctv_aco_url', 'https://cctv.badilag.net/display/satker/cc7008afd87c3973a92f2b8c73effdb2')
        ->set('cctv_aco_label', 'Access CCTV Online (ACO) PA Gorontalo')
        ->set('social_facebook_url', 'https://www.facebook.com/pagorontalo/')
        ->set('social_instagram_url', 'https://www.instagram.com/pengadilan_agama_gorontalo/')
        ->set('social_tiktok_url', 'https://www.tiktok.com/@pengadilanagamagtlo')
        ->set('social_youtube_url', 'https://www.youtube.com/@pengadilanagamagorontalo9342')
        ->call('addRelatedLink')
        ->set('related_links.0', [
            'code' => 'MARI',
            'title' => 'Mahkamah Agung RI',
            'subtitle' => 'Website Mahkamah Agung Republik Indonesia',
            'url' => 'https://www.mahkamahagung.go.id',
        ])
        ->call('addRelatedLink')
        ->set('related_links.1', [
            'code' => 'PEMPROV',
            'title' => 'Pemerintah Provinsi Gorontalo',
            'subtitle' => 'Website Pemerintah Provinsi Gorontalo',
            'url' => 'https://gorontaloprov.go.id',
        ])
        ->call('moveRelatedLinkUp', 1)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = SatkerSetting::current();
    expect($fresh?->resolvedRelatedLinks()[0]['code'] ?? null)->toBe('PEMPROV')
        ->and(count($fresh?->socialChannels() ?? []))->toBe(4);

    $this->get('/')
        ->assertOk()
        ->assertSee('Tautan Instansi Terkait')
        ->assertSee('Pemerintah Provinsi Gorontalo')
        ->assertSee('https://gorontaloprov.go.id')
        ->assertSee('Access CCTV Online (ACO) PA Gorontalo')
        ->assertSee('https://cctv.badilag.net/display/satker/cc7008afd87c3973a92f2b8c73effdb2')
        ->assertSee('Sosial Media Resmi')
        ->assertSee('https://www.facebook.com/pagorontalo/')
        ->assertSee('https://www.instagram.com/pengadilan_agama_gorontalo/')
        ->assertSee('https://www.tiktok.com/@pengadilanagamagtlo')
        ->assertSee('https://www.youtube.com/@pengadilanagamagorontalo9342');
});

test('SafeHtml preserves safe TinyMCE editorial styles while stripping dangerous CSS declarations', function (): void {
    $raw = '<p style="text-align: center; position: fixed; z-index: 9999;"><strong>Judul Tengah</strong></p>'
        .'<p style="text-align: center;"><img src="/storage/editor-media/foto.webp" width="500" height="330" style="display: block; margin-left: auto; margin-right: auto; background-image: url(javascript:alert(1));"></p>'
        .'<p style="text-align: justify; text-indent: 2.5em; color: #111827; font-size: 15px;">Paragraf rata kiri-kanan dengan alinea menjorok.</p>'
        .'<p style="text-indent: -9999px;">Indent negatif dibuang</p>'
        .'<div style="position: fixed; top: 0;">Berbahaya</div>';

    $clean = SafeHtml::cleanBody($raw);

    expect($clean)
        ->toContain('style="text-align: center;"')
        ->toContain('style="text-align: justify; text-indent: 2.5em; color: #111827; font-size: 15px;"')
        ->not->toContain('-9999px')
        ->not->toContain('position:')
        ->not->toContain('z-index')
        ->not->toContain('background-image')
        ->not->toContain('javascript:')
        ->toContain('<div>Berbahaya</div>');
});

test('post detail uses two-column layout with right sidebar for related posts, latest posts, and categories', function (): void {
    SatkerSetting::factory()->create(['is_setup_completed' => true]);

    $main = Post::factory()->create([
        'title' => 'Berita Utama Diskusi Hukum',
        'slug' => 'berita-utama-diskusi-hukum',
        'category' => 'berita',
        'body' => '<p style="text-indent: 2.5em; text-align: justify;">Paragraf alinea pertama.</p>',
        'is_published' => true,
        'published_at' => now()->subHour(),
    ]);

    Post::factory()->create([
        'title' => 'Berita Terkait Kategori Sama',
        'slug' => 'berita-terkait-kategori-sama',
        'category' => 'berita',
        'featured_image_path' => 'posts/terkait-thumb.webp',
        'is_published' => true,
        'published_at' => now()->subHours(2),
    ]);

    Post::factory()->create([
        'title' => 'Pengumuman Terbaru Lintas Kategori',
        'slug' => 'pengumuman-terbaru-lintas-kategori',
        'category' => 'pengumuman',
        'is_published' => true,
        'published_at' => now()->subMinutes(30),
    ]);

    $this->get(route('public.posts.show', ['slug' => $main->slug]))
        ->assertOk()
        ->assertSee('lg:grid-cols-12', false)
        ->assertSee('lg:col-span-8', false)
        ->assertSee('lg:col-span-4', false)
        ->assertSee('style="text-indent: 2.5em; text-align: justify;"', false)
        ->assertSee('Publikasi Terkait')
        ->assertSee('Berita Terkait Kategori Sama')
        ->assertSee('Publikasi Terbaru')
        ->assertSee('Pengumuman Terbaru Lintas Kategori')
        ->assertSee('Kategori Publikasi');
});

test('visitor stats renders Indonesian dates, human-readable page titles, and 14-day chart without raw path clutter', function (): void {
    SatkerSetting::factory()->create([
        'is_setup_completed' => true,
        'nama_satker' => 'Pengadilan Agama Gorontalo',
    ]);

    Page::factory()->create([
        'slug' => 'layanan-hukum/jadwal-sidang',
        'title' => 'Jadwal Persidangan Harian',
        'is_published' => true,
    ]);

    Post::factory()->create([
        'slug' => 'berita-diskusi-hukum-wakaf',
        'title' => 'PA Gorontalo Gelar Diskusi Hukum Wakaf',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    PageVisit::query()->create([
        'visit_date' => now()->toDateString(),
        'path' => '/',
        'visits_count' => 1250,
    ]);
    PageVisit::query()->create([
        'visit_date' => now()->toDateString(),
        'path' => '/halaman/layanan-hukum/jadwal-sidang',
        'visits_count' => 84,
    ]);
    PageVisit::query()->create([
        'visit_date' => now()->subDay()->toDateString(),
        'path' => '/publikasi/berita-diskusi-hukum-wakaf',
        'visits_count' => 62,
    ]);

    PublicComplianceController::flushCache();

    $this->get(route('public.visitor-stats'))
        ->assertOk()
        ->assertSee('Statistik Pengunjung Portal')
        ->assertSee('Grafik Kunjungan 14 Hari Terakhir')
        ->assertSee('Beranda Portal')
        ->assertSee('Jadwal Persidangan Harian')
        ->assertSee('PA Gorontalo Gelar Diskusi Hukum Wakaf')
        ->assertDontSee('privacy-first analytics');
});

test('SIPP embed includes modal sandbox permissions, reload button, and contextual cross-site notice only when cross-site', function (): void {
    SatkerSetting::factory()->create([
        'is_setup_completed' => true,
        'sipp_url' => 'https://sipp.pa-gorontalo.go.id',
    ]);

    Page::factory()->create([
        'slug' => 'layanan-hukum/jadwal-sidang-uji',
        'title' => 'Jadwal Persidangan Harian',
        'is_published' => true,
        'blocks' => [[
            'type' => 'embed_fallback',
            'heading' => 'Jadwal Persidangan Harian',
            'source_key' => 'sipp_jadwal',
            'fallback_mode' => 'court_schedule',
        ]],
    ]);

    // Cross-site HTTP (lokal/staging): tampilkan peringatan cookie lintas-domain + tombol Muat Ulang Embed
    $this->get('http://localhost/halaman/layanan-hukum/jadwal-sidang-uji')
        ->assertOk()
        ->assertSee('allow-modals allow-popups-to-escape-sandbox', false)
        ->assertSee('Muat Ulang Embed')
        ->assertSee('Catatan Tampilan Embed SIPP:');

    // Same-site HTTPS (produksi satu domain induk): sembunyikan banner lintas-domain, tetap sediakan tombol Muat Ulang Embed
    $this->get('https://pa-gorontalo.go.id/halaman/layanan-hukum/jadwal-sidang-uji')
        ->assertOk()
        ->assertSee('allow-modals allow-popups-to-escape-sandbox', false)
        ->assertSee('Muat Ulang Embed')
        ->assertDontSee('Catatan Tampilan Embed SIPP:');
});
