<?php

use App\Http\Controllers\Admin\EditorMediaUploadController;
use App\Http\Controllers\PublicComplianceController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\PublicPostController;
use App\Http\Controllers\PublicSearchController;
use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\CriteriaManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DocumentManager;
use App\Livewire\Admin\KepaniteraanManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\PostManager;
use App\Livewire\Admin\SatkerSettingsManager;
use App\Livewire\Admin\SinovaLinks;
use App\Livewire\Admin\SliderManager;
use App\Livewire\Admin\TwoFactorSetup;
use App\Livewire\Admin\UserRoleManager;
use App\Livewire\SetupWizard;
use App\Services\LocalCaptchaService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicHomeController::class)->name('home');

Route::get('/halaman/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '.*')
    ->name('public.page');

Route::get('/publikasi', [PublicPostController::class, 'index'])->name('public.posts');
Route::get('/publikasi/{slug}', [PublicPostController::class, 'show'])->name('public.posts.show');
Route::get('/cari', PublicSearchController::class)
    ->middleware('throttle:60,1')
    ->name('public.search');

Route::get('/aksesibilitas', [PublicComplianceController::class, 'accessibility'])->name('public.accessibility');
Route::get('/status-sistem', [PublicComplianceController::class, 'systemStatus'])->name('public.system-status');
Route::get('/statistik-pengunjung', [PublicComplianceController::class, 'visitorStats'])->name('public.visitor-stats');
Route::get('/peta-situs', [PublicComplianceController::class, 'sitemapHtml'])->name('public.sitemap');
Route::get('/sitemap.xml', [PublicComplianceController::class, 'sitemapXml'])->name('public.sitemap.xml');
Route::get('/robots.txt', [PublicComplianceController::class, 'robotsTxt'])->name('public.robots');

Route::get('/setup', SetupWizard::class)->name('setup');

Route::get('/captcha-image', function (LocalCaptchaService $captcha): Response {
    return response($captcha->generateSvg(), 200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
})->middleware('throttle:30,1')->name('captcha.image');

Route::get('/captcha-text', function (LocalCaptchaService $captcha): Response {
    return response($captcha->generateCode(), 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
})->middleware('throttle:15,1')->name('captcha.text');

$adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');

Route::prefix($adminPath)
    ->middleware(['auth', 'two-factor.required'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('/keamanan/2fa', TwoFactorSetup::class)
            ->withoutMiddleware('two-factor.required')
            ->name('two-factor.setup');

        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/link-sinova', SinovaLinks::class)->name('sinova-links');
        Route::get('/menu', MenuManager::class)->name('menus');
        Route::get('/halaman', PageManager::class)->name('pages');
        Route::get('/publikasi', PostManager::class)->name('posts');
        Route::get('/dokumen', DocumentManager::class)->name('documents');
        Route::get('/kepaniteraan', KepaniteraanManager::class)->name('kepaniteraan');
        Route::get('/slider', SliderManager::class)->name('sliders');
        Route::get('/pengaturan', SatkerSettingsManager::class)->name('settings');
        Route::get('/kriteria', CriteriaManager::class)->name('criteria');
        Route::get('/pengguna', UserRoleManager::class)->name('users');
        Route::get('/audit', AuditAndBackupManager::class)->name('audit');
        Route::post('/media/upload', EditorMediaUploadController::class)->name('media.upload');
    });
