<?php

use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\Document;
use App\Models\Official;
use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\SatkerSetting;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;

function seedPublicPortal(): SatkerSetting
{
    app(RoleAndPermissionSeeder::class)->run();
    $satker = SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Soreang',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'is_tingkat_locked' => true,
        'telepon' => '(022) 5891234',
        'email' => 'info@pa-soreang.go.id',
        'simtepa_pegawai_url' => null,
        'simtepa_struktur_url' => null,
    ]);
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');

    return $satker;
}

it('renders public homepage with dynamic menus, sliders, serta merta alert, and accessibility widget', function (): void {
    seedPublicPortal();

    Post::factory()->create([
        'title' => 'Pemberitahuan Gangguan Sistem Layanan Sementara',
        'slug' => 'serta-merta-gangguan-sistem',
        'category' => 'serta_merta',
        'is_pinned' => true,
        'is_published' => true,
        'published_at' => now(),
    ]);

    Post::factory()->create([
        'title' => 'Inovasi Layanan Antrian PTSP Digital',
        'slug' => 'inovasi-antrian-ptsp',
        'category' => 'berita',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Pengadilan Agama Soreang')
        ->assertSee('Portal Resmi Pengadilan Agama Soreang')
        ->assertSee('Profil Pengadilan')
        ->assertSee('Layanan Hukum')
        ->assertSee('Pemberitahuan Gangguan Sistem Layanan Sementara')
        ->assertSee('Inovasi Layanan Antrian PTSP Digital')
        ->assertSee('a11y-toggle-btn')
        ->assertSee('Baca Halaman');
});

it('sanitizes richtext blocks and filters document_list by year', function (): void {
    seedPublicPortal();

    Page::factory()->create([
        'title' => 'Uji Keamanan Blok',
        'slug' => 'uji/keamanan-blok',
        'is_published' => true,
        'blocks' => [
            [
                'type' => 'richtext',
                'heading' => 'Konten Terfilter',
                'content' => '<p>Teks aman</p><script>alert("xss")</script><a href="javascript:alert(1)" onclick="steal()">Tautan</a>',
            ],
            [
                'type' => 'document_list',
                'heading' => 'Arsip LKjIP',
                'collection_type' => 'lkjip',
            ],
        ],
    ]);

    Document::factory()->create([
        'collection_type' => 'lkjip',
        'title' => 'LKjIP Tahun 2025',
        'year' => 2025,
        'is_published' => true,
    ]);

    Document::factory()->create([
        'collection_type' => 'lkjip',
        'title' => 'LKjIP Tahun 2026',
        'year' => 2026,
        'is_published' => true,
    ]);

    $response = $this->get('/halaman/uji/keamanan-blok?tahun=2025');

    $response->assertOk()
        ->assertSee('Teks aman')
        ->assertDontSee('alert("xss")', false)
        ->assertDontSee('javascript:alert', false)
        ->assertDontSee('onclick=', false)
        ->assertSee('LKjIP Tahun 2025')
        ->assertDontSee('LKjIP Tahun 2026');
});

it('switches embed_fallback automatically when external url is empty and toggles fallback via query', function (): void {
    $satker = seedPublicPortal();

    $ketua = Official::factory()->create([
        'nama_gelar' => 'Dr. Siti Aminah, S.Ag., M.H.',
        'jabatan' => 'Ketua',
        'kategori' => 'pimpinan',
        'is_active' => true,
    ]);

    Official::factory()->create([
        'nama_gelar' => 'Budi Santoso, S.H.',
        'jabatan' => 'Staf Pelaksana',
        'kategori' => 'pegawai',
        'parent_id' => $ketua->id,
        'is_active' => true,
    ]);

    CourtSchedule::factory()->create([
        'tanggal_sidang' => '2026-11-20',
        'nomor_perkara' => '77/Pdt.G/2026/PA.Sor',
        'para_pihak' => 'Pemohon vs Termohon',
    ]);

    CaseStatistic::factory()->create([
        'year' => 2026,
        'month' => 9,
        'sisa_bulan_lalu' => 10,
        'perkara_masuk' => 30,
        'perkara_putus' => 25,
    ]);

    // When SIMTEPA URL is empty, org chart falls back and excludes staf/pegawai & honorer
    $this->get('/halaman/profil/struktur-organisasi')
        ->assertOk()
        ->assertSee('Mode Data Cadangan Lokal')
        ->assertSee('Dr. Siti Aminah, S.Ag., M.H.')
        ->assertDontSee('Budi Santoso, S.H.');

    // Officials table includes all active officials without PII columns
    $this->get('/halaman/profil/pejabat-pegawai')
        ->assertOk()
        ->assertSee('Dr. Siti Aminah, S.Ag., M.H.')
        ->assertSee('Budi Santoso, S.H.');

    // Configure external SIMTEPA pegawai URL -> displays iframe, and ?fallback=1 forces local fallback
    $satker->update(['simtepa_pegawai_url' => 'https://simtepa.badilag.net/embed/contoh']);

    $this->get('/halaman/profil/pejabat-pegawai')
        ->assertOk()
        ->assertSee('https://simtepa.badilag.net/embed/contoh')
        ->assertSee('<iframe', false);

    $this->get('/halaman/profil/pejabat-pegawai?fallback=1')
        ->assertOk()
        ->assertSee('Mode Data Cadangan Lokal')
        ->assertSee('Budi Santoso, S.H.')
        ->assertDontSee('<iframe', false);

    // Schedules & stats show SIPP iframe when sipp_url is configured, fallback via ?fallback=1, and auto-fallback when sipp_url is null
    $this->get('/halaman/layanan-hukum/jadwal-sidang?fallback=1')->assertOk()->assertSee('77/Pdt.G/2026/PA.Sor');
    $this->get('/halaman/layanan-hukum/statistik-perkara?fallback=1')->assertOk()->assertSee('2026-09');

    $satker->update(['sipp_url' => null]);
    $this->get('/halaman/layanan-hukum/jadwal-sidang')->assertOk()->assertSee('77/Pdt.G/2026/PA.Sor');
    $this->get('/halaman/layanan-hukum/statistik-perkara')->assertOk()->assertSee('2026-09');

    // Jurisdiction map and contact blocks render cleanly
    $this->get('/halaman/profil/wilayah-yurisdiksi')->assertOk()->assertSee('openstreetmap.org');
    $this->get('/halaman/profil/alamat-kontak')->assertOk()->assertSee('(022) 5891234');
});

it('renders publications, search results, compliance routes, xml sitemap, and cookie-free visit tracking', function (): void {
    seedPublicPortal();

    Post::factory()->create([
        'title' => 'Pengumuman Sidang Keliling Kecamatan Ciwidey',
        'slug' => 'sidang-keliling-ciwidey',
        'category' => 'pengumuman',
        'is_published' => true,
        'published_at' => now(),
    ]);

    Document::factory()->create([
        'collection_type' => 'dipa',
        'title' => 'DIPA Tahun Anggaran 2026 Ciwidey',
        'year' => 2026,
        'is_published' => true,
    ]);

    $this->get(route('public.posts', ['kategori' => 'pengumuman']))
        ->assertOk()
        ->assertSee('Pengumuman Sidang Keliling Kecamatan Ciwidey');

    $this->get(route('public.posts.show', ['slug' => 'sidang-keliling-ciwidey']))
        ->assertOk()
        ->assertSee('Pengumuman Sidang Keliling Kecamatan Ciwidey');

    $this->get(route('public.search', ['q' => 'Ciwidey']))
        ->assertOk()
        ->assertSee('Pengumuman Sidang Keliling Kecamatan Ciwidey')
        ->assertSee('DIPA Tahun Anggaran 2026 Ciwidey');

    $this->get(route('public.accessibility'))
        ->assertOk()
        ->assertSee('Fitur Bantuan pada Widget Aksesibilitas')
        ->assertDontSee('Kriteria SINOVA');

    $this->get('/halaman/layanan-hukum/sipp')
        ->assertOk()
        ->assertDontSee('Kriteria SINOVA')
        ->assertDontSee('1.a.2.1');

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('1.a.2.1')
        ->assertDontSee('1.a.2.2');

    $statusResp = $this->get(route('public.system-status'));
    $statusResp->assertOk()
        ->assertSee(PHP_VERSION)
        ->assertSee(app()->version())
        ->assertHeaderMissing('X-Powered-By');

    expect(PageVisit::query()->where('path', '/status-sistem')->sum('visits_count'))->toBeGreaterThanOrEqual(1);

    $this->get(route('public.visitor-stats'))->assertOk()->assertSee('Statistik Pengunjung Portal');
    $this->get(route('public.sitemap'))->assertOk()->assertSee('/halaman/transparansi/lkjip');

    $xmlResp = $this->get(route('public.sitemap.xml'));
    $xmlResp->assertOk()
        ->assertSee('<urlset', false)
        ->assertSee('/halaman/transparansi/lkjip');

    expect(str_contains((string) $xmlResp->headers->get('Content-Type'), 'application/xml'))->toBeTrue();
});

it('redirects old page slug cleanly with HTTP 301 after slug change', function (): void {
    seedPublicPortal();

    $page = Page::factory()->create([
        'title' => 'Informasi Awal',
        'slug' => 'info/lama',
        'is_published' => true,
    ]);

    $page->update(['slug' => 'info/baru']);

    $this->get('/halaman/info/lama')
        ->assertRedirect('/halaman/info/baru')
        ->assertStatus(301);

    $this->get('/halaman/info/baru')->assertOk()->assertSee('Informasi Awal');
});
