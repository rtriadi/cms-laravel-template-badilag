<?php

declare(strict_types=1);

use App\Models\CriteriaItem;
use App\Models\Document;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use Database\Seeders\LegacyPaGorontalo\LegacyBundleReader;
use Database\Seeders\PaGorontaloMigrationSeeder;
use Illuminate\Support\Facades\Storage;

function buildLegacyFixtureWorkspace(): array
{
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'satker_joomla_e2e_'.bin2hex(random_bytes(5));
    $joomla = $base.DIRECTORY_SEPARATOR.'joomla';
    $bundle = $base.DIRECTORY_SEPARATOR.'bundle';

    mkdir($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'2026', 0775, true);
    mkdir($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'front_image', 0775, true);
    mkdir($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'banners', 0775, true);
    mkdir($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'file', 0775, true);

    $makeImg = static function (string $path, int $w, int $h, int $r): void {
        $im = imagecreatetruecolor($w, $h);
        $col = imagecolorallocate($im, $r, 110, 79);
        imagefilledrectangle($im, 0, 0, $w, $h, $col);
        imagepng($im, $path);
        unset($im);
    };

    $makeImg($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'front_image'.DIRECTORY_SEPARATOR.'mahkamah_agung.png', 256, 256, 5);
    $makeImg($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'gedung-kantor.jpg', 900, 500, 15);
    for ($i = 1; $i <= 6; $i++) {
        $makeImg($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'2026'.DIRECTORY_SEPARATOR."slide{$i}.png", 1000, 420, 20 + ($i * 20));
    }
    $makeImg($joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'berita'.DIRECTORY_SEPARATOR.'2026'.DIRECTORY_SEPARATOR.'berita1.png', 960, 540, 80);

    // Valid PDF & fake malware bypass.pdf
    file_put_contents(
        $joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'Reviu-IKU-Pengadilan-Agama-Gorontalo.pdf',
        '%PDF-1.4'."\n".str_repeat('A', 1024)."\n%%EOF",
    );
    file_put_contents(
        $joomla.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'banners'.DIRECTORY_SEPARATOR.'bypass.pdf',
        '/* cleaned malware artifact */',
    );

    $sliderParams = json_encode([
        'sliders' => [
            'text' => [
                'title' => ['Slide 1', 'Slide 2', 'Slide 3', 'Slide 4', 'Slide 5', 'Slide 6'],
                'pretitle' => ['Sub 1', '', '', '', '', 'Sub 6'],
                'image' => [
                    'images/berita/2026/slide1.png',
                    'images/berita/2026/slide2.png',
                    'images/berita/2026/slide3.png',
                    'images/berita/2026/slide4.png',
                    'images/berita/2026/slide5.png',
                    'images/berita/2026/slide6.png',
                ],
                'link' => ['https://pa-gorontalo.go.id/informasi-pengadilan/1076-pengumuman-resmi', '', '', '', '', ''],
                'state' => ['published', 'published', 'published', 'published', 'published', 'published'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $sliderParamsSql = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $sliderParams);

    $sql = <<<SQL
CREATE TABLE `ydfui_categories` (
  `id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL
);
INSERT INTO `ydfui_categories` (`id`, `path`, `title`) VALUES
(19, 'sample-data-articles/joomla', 'Joomla!'),
(114, 'informasi-pengadilan', 'Informasi Pengadilan'),
(115, 'index-berita', 'Berita Seputar Pengadilan');

CREATE TABLE `ydfui_content` (
  `id` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `introtext` mediumtext NOT NULL,
  `fulltext` mediumtext NOT NULL,
  `state` tinyint(3) NOT NULL,
  `catid` int(10) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `publish_up` datetime NOT NULL,
  `images` text NOT NULL,
  `hits` int(10) NOT NULL
);
INSERT INTO `ydfui_content` (`id`, `title`, `alias`, `introtext`, `fulltext`, `state`, `catid`, `created`, `modified`, `publish_up`, `images`, `hits`) VALUES
(5, 'Sample Park Site', 'sample-park', '<p>Should be excluded</p>', '', 1, 19, '2011-01-01 00:00:00', '2011-01-01 00:00:00', '2011-01-01 00:00:00', '{}', 10),
(74, 'Visi Misi PA Gorontalo', 'visi-misi', '<p>Terwujudnya Pengadilan Agama Gorontalo yang Agung.</p>', '', 1, 80, '2022-01-01 00:00:00', '2022-01-01 00:00:00', '2022-01-01 00:00:00', '{}', 50),
(236, 'Sejarah Pengadilan', 'sejarah', '<p>Sejarah panjang PA Gorontalo sejak 1957.</p>', '', 1, 81, '2020-01-01 00:00:00', '2020-01-01 00:00:00', '2020-01-01 00:00:00', '{}', 100),
(373, 'Zona Integritas', 'zona-integritas', '<table><tr><td>LKE PMPZI 2026</td><td><a href="https://drive.google.com/file/d/1ZiEvidence1234567890/view">Bukti</a></td></tr></table>', '', 1, 103, '2026-01-01 00:00:00', '2026-01-01 00:00:00', '2026-01-01 00:00:00', '{}', 200),
(87, 'Rencana Strategis', 'renstra', '<table><tr><td>Renstra 2025-2029</td><td><a href="https://drive.google.com/file/d/1RenstraFileId123456/view?usp=sharing">Unduh</a></td></tr></table>', '', 1, 92, '2025-01-01 00:00:00', '2025-01-01 00:00:00', '2025-01-01 00:00:00', '{}', 80),
(1076, 'Pengumuman Resmi', 'pengumuman-resmi', '<p>Isi pengumuman resmi PA Gorontalo.</p>', '', 1, 114, '2026-10-01 08:00:00', '2026-10-01 08:00:00', '2026-10-01 08:00:00', '{}', 320),
(1200, 'Apel Pagi PA Gorontalo', 'apel-pagi-pa-gorontalo', '<p><img src="images/berita/2026/berita1.png" alt="IMG 01" /></p><p>Kegiatan apel pagi berlangsung khidmat.</p>', '', 1, 115, '2026-11-20 07:30:00', '2026-11-20 07:30:00', '2026-11-20 07:30:00', '{}', 99);

CREATE TABLE `ydfui_content_frontpage` (
  `content_id` int(11) NOT NULL,
  `ordering` int(11) NOT NULL
);
INSERT INTO `ydfui_content_frontpage` (`content_id`, `ordering`) VALUES (1200, 1);

CREATE TABLE `ydfui_menu` (
  `id` int(11) NOT NULL,
  `menutype` varchar(24) NOT NULL,
  `title` varchar(255) NOT NULL,
  `path` varchar(1024) NOT NULL,
  `link` varchar(1024) NOT NULL,
  `published` tinyint(4) NOT NULL,
  `client_id` tinyint(4) NOT NULL,
  `params` text NOT NULL
);
INSERT INTO `ydfui_menu` (`id`, `menutype`, `title`, `path`, `link`, `published`, `client_id`, `params`) VALUES
(492, 'mainmenu', 'Sejarah Pengadilan', 'tentang-pengadian/info-pengadilan/sejarah-pengadilan', 'index.php?option=com_content&view=article&id=236', 1, 0, '{}'),
(839, 'mainmenu', 'Profil Ketua', 'tentang-pengadian/profile-pengadilan/p-ketua', 'index.php?option=com_wrapper&view=wrapper', 1, 0, '{"url":"https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/a87bd82962cda7f528247a68ee4ac2a5"}'),
(866, 'mainmenu', 'SK PPID 2026', 'transparan/ppid/2026-05-05', 'https://drive.google.com/file/d/1SkPpid2026DocId12345/view?usp=sharing', 1, 0, '{}');

CREATE TABLE `ydfui_modules` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `module` varchar(50) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `client_id` tinyint(2) NOT NULL,
  `params` text NOT NULL
);
INSERT INTO `ydfui_modules` (`id`, `title`, `content`, `module`, `published`, `client_id`, `params`) VALUES
(111, 'SP Smart Slider', '', 'mod_sp_smart_slider', 1, 0, '{$sliderParamsSql}'),
(153, 'Informasi Perkara Ghaib', '<p>{source}</p><p>&lt;table&gt;&lt;tr&gt;&lt;td&gt;&lt;a href="https://drive.google.com/file/d/1GhaibDocId123456789/view"&gt;501/Pdt.G/2026/PA.Gtlo MELVIN&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;</p><p>{/source}</p>', 'mod_custom', 1, 0, '{}');

CREATE TABLE `ydfui_zt_visitor_counter` (
  `id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `visits` int(11) NOT NULL,
  `guests` int(11) NOT NULL,
  `bots` int(11) NOT NULL,
  `members` int(11) NOT NULL,
  `ipaddress` varchar(64) NOT NULL,
  `useragent` varchar(64) NOT NULL
);
INSERT INTO `ydfui_zt_visitor_counter` (`id`, `timestamp`, `visits`, `guests`, `bots`, `members`, `ipaddress`, `useragent`) VALUES
(1, 1777600800, 10, 1, 0, 0, '1.1.1.1', 'chrome'),
(2, 1777600900, 5, 1, 0, 0, '1.1.1.2', 'firefox');
SQL;

    file_put_contents($joomla.DIRECTORY_SEPARATOR.'pagoron1_db1.sql', $sql);

    return [$base, $joomla, $bundle];
}

test('runs end-to-end migration command, is idempotent on repeat seed, and renders public portal 200 ok', function (): void {
    Storage::fake('public');
    [$base, $joomla, $bundle] = buildLegacyFixtureWorkspace();

    try {
        $this->artisan('satker:migrate-joomla', [
            '--source' => $joomla,
            '--output' => $bundle,
        ])->assertSuccessful();

        // 1. Satker identity + SIMTEPA token
        $satker = SatkerSetting::current();
        expect($satker)->not->toBeNull()
            ->and($satker->nama_satker)->toBe('Pengadilan Agama Gorontalo')
            ->and($satker->extractSimtepaToken())->toBe('a87bd82962cda7f528247a68ee4ac2a5')
            ->and($satker->is_setup_completed)->toBeTrue();

        // 2. Sliders capped at 5 active (6 total, 5 active)
        expect(HomeSlider::query()->count())->toBe(6)
            ->and(HomeSlider::query()->where('is_active', true)->count())->toBe(5);

        // 3. Posts exclude sample-data and keep real posts with WebP featured image
        expect(Post::query()->count())->toBe(2)
            ->and(Post::query()->where('slug', 'sample-park')->exists())->toBeFalse();

        $berita = Post::query()->where('slug', 'apel-pagi-pa-gorontalo')->firstOrFail();
        expect($berita->category)->toBe('berita')
            ->and($berita->views_count)->toBe(99)
            ->and($berita->is_pinned)->toBeTrue()
            ->and($berita->featured_image_path)->toStartWith('posts/')
            ->and($berita->featured_image_path)->toEndWith('.webp');

        // 4. Documents: local valid PDF + Renstra GDrive + SK PPID GDrive + Ghaib Sourcerer GDrive (no bypass.pdf!)
        expect(Document::query()->where('collection_type', 'iku')->whereNotNull('file_path')->exists())->toBeTrue()
            ->and(Document::query()->where('collection_type', 'renstra')->exists())->toBeTrue()
            ->and(Document::query()->where('collection_type', 'dip_ppid')->exists())->toBeTrue()
            ->and(Document::query()->where('collection_type', 'panggilan_ghaib')->exists())->toBeTrue()
            ->and(Document::query()->where('file_path', 'like', '%bypass%')->exists())->toBeFalse();

        // 5. SINOVA criteria linked & extra pages created + header menu stays <= 6 roots
        expect(CriteriaItem::query()->whereNotNull('linked_page_id')->count())->toBeGreaterThan(20)
            ->and(Page::query()->where('slug', 'profil/sejarah-pengadilan')->exists())->toBeTrue()
            ->and(Page::query()->where('slug', 'transparansi/zona-integritas')->exists())->toBeTrue()
            ->and(MenuItem::query()->where('location', 'header')->whereNull('parent_id')->count())->toBe(6);

        // 6. Visitor counter aggregated
        expect(PageVisit::query()->where('visit_date', '2026-05-01')->value('visits_count'))->toBe(15);

        // 7. Capture counts & re-run seeder for strict idempotency
        $snapshot = [
            'sliders' => HomeSlider::query()->count(),
            'pages' => Page::query()->count(),
            'menus' => MenuItem::query()->count(),
            'documents' => Document::query()->count(),
            'posts' => Post::query()->count(),
            'redirects' => Redirect::query()->count(),
            'visits' => PageVisit::query()->count(),
        ];

        LegacyBundleReader::setBasePath($bundle);
        try {
            $this->seed(PaGorontaloMigrationSeeder::class);
        } finally {
            LegacyBundleReader::resetBasePath();
        }

        expect([
            'sliders' => HomeSlider::query()->count(),
            'pages' => Page::query()->count(),
            'menus' => MenuItem::query()->count(),
            'documents' => Document::query()->count(),
            'posts' => Post::query()->count(),
            'redirects' => Redirect::query()->count(),
            'visits' => PageVisit::query()->count(),
        ])->toBe($snapshot);

        // 8. Public routes render 200 OK & 301 redirect works
        $this->get('/')->assertOk();
        $this->get('/publikasi')->assertOk()->assertSee('Apel Pagi PA Gorontalo');
        $this->get('/publikasi/apel-pagi-pa-gorontalo')->assertOk();
        $this->get('/halaman/profil/visi-misi')->assertOk();
        $this->get('/halaman/profil/sejarah-pengadilan')->assertOk();
        $this->get('/halaman/transparansi/zona-integritas')->assertOk();
        $this->get('/tentang-pengadian/info-pengadilan/sejarah-pengadilan')
            ->assertRedirect('/halaman/profil/sejarah-pengadilan')
            ->assertStatus(301);
    } finally {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($base);
    }
});
