<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Http\Controllers\PublicComplianceController;
use App\Http\Controllers\PublicHomeController;
use App\Models\Document;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SatkerSetting;
use App\Services\SinovaHealthCheckService;
use Database\Seeders\LegacyPaGorontalo\DocumentsSeeder;
use Database\Seeders\LegacyPaGorontalo\LegacyBundleReader;
use Database\Seeders\LegacyPaGorontalo\PagesAndMenusSeeder;
use Database\Seeders\LegacyPaGorontalo\PageVisitsSeeder;
use Database\Seeders\LegacyPaGorontalo\PostsSeeder;
use Database\Seeders\LegacyPaGorontalo\RedirectsSeeder;
use Database\Seeders\LegacyPaGorontalo\SatkerIdentitySeeder;
use Database\Seeders\LegacyPaGorontalo\SlidersSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class PaGorontaloMigrationSeeder extends Seeder
{
    public function run(): void
    {
        if (! LegacyBundleReader::exists('satker.json')) {
            throw new RuntimeException(
                'Bundle migrasi tidak ditemukan di '.LegacyBundleReader::basePath()
                .'. Jalankan `php artisan migration:prepare-joomla` atau `php artisan satker:migrate-joomla` terlebih dahulu.'
            );
        }

        // 1. Prasyarat SINOVA: 6 peran RBAC, kriteria Badilag 2026, 35 halaman & 6 menu bawaan Tingkat Pertama
        $this->call([
            RoleAndPermissionSeeder::class,
            SinovaCriteriaSeeder::class,
        ]);

        // 2. Identitas satker lebih dulu agar placeholder {{satker.nama}} dan tingkat='pertama' aktif
        $this->call([
            SatkerIdentitySeeder::class,
        ]);

        // 3. Seed kerangka halaman & menu standar SINOVA Tingkat Pertama (idempoten)
        app(SatkerDefaultContentSeeder::class)->run('pertama');

        // 4. Jalankan seluruh sub-seeder data migrasi PA Gorontalo sekaligus
        $this->call([
            SlidersSeeder::class,
            PagesAndMenusSeeder::class,
            DocumentsSeeder::class,
            PostsSeeder::class,
            RedirectsSeeder::class,
            PageVisitsSeeder::class,
        ]);

        // 5. Bersihkan cache identitas satker lalu segarkan status & skor kesehatan SINOVA (offline-safe)
        SatkerSetting::forgetMemo();
        Cache::forget(SatkerSetting::CACHE_KEY);
        $sinovaSummary = app(SinovaHealthCheckService::class)->checkAll('pertama', probeExternal: false);

        // 6. Bersihkan seluruh cache publik 1x di akhir
        Cache::forget(Redirect::CACHE_KEY);
        MenuItem::flushPublicCache();
        PublicHomeController::flushCache();
        PublicComplianceController::flushCache();

        // 7. Catat 1 entri audit ringkasan migrasi (menghindari ribuan log per-record)
        activity('migration')
            ->withProperties([
                'bundle_path' => LegacyBundleReader::basePath(),
                'sliders' => HomeSlider::query()->count(),
                'pages' => Page::query()->count(),
                'documents' => Document::query()->count(),
                'posts' => Post::query()->count(),
                'redirects' => Redirect::query()->count(),
                'page_visits_days' => PageVisit::query()->count(),
                'sinova_health' => $sinovaSummary,
            ])
            ->log('Menjalankan PaGorontaloMigrationSeeder untuk migrasi data Joomla PA Gorontalo');
    }
}
