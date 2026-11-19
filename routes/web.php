<?php

use App\Livewire\Admin\TwoFactorSetup;
use App\Livewire\SetupWizard;
use App\Services\LocalCaptchaService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::get('/halaman/{slug}', fn (string $slug) => view('welcome'))
    ->where('slug', '.*')
    ->name('public.page');

Route::get('/publikasi', fn () => view('welcome'))->name('public.posts');

Route::get('/setup', SetupWizard::class)->name('setup');

Route::get('/captcha-image', function (LocalCaptchaService $captcha): Response {
    return response($captcha->generateSvg(), 200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
})->name('captcha.image');

$adminPath = trim((string) config('satker.admin_path', 'panel-admin'), '/');

Route::prefix($adminPath)
    ->middleware(['auth', 'two-factor.required'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('/keamanan/2fa', TwoFactorSetup::class)
            ->withoutMiddleware('two-factor.required')
            ->name('two-factor.setup');

        Route::get('/', fn () => view('welcome'))->name('dashboard');
    });
