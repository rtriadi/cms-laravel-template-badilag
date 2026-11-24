<?php

use App\Models\Document;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Illuminate\Support\Facades\Route;

function smokeSeedEnvironment(): void
{
    app(RoleAndPermissionSeeder::class)->run();
    SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Bandung',
        'tingkat' => 'pertama',
        'is_tingkat_locked' => true,
    ]);
    app(SinovaCriteriaSeeder::class)->run();
    app(SatkerDefaultContentSeeder::class)->run('pertama');
}

function smokeUser(string $role): User
{
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

function smokeRouteParams(string $uri): array
{
    if (str_contains($uri, '{slug}')) {
        if (str_starts_with($uri, 'publikasi/')) {
            return ['slug' => Post::query()->value('slug')];
        }

        return ['slug' => Page::query()->value('slug')];
    }

    return [];
}

it('renders every registered GET route without a server error', function (): void {
    smokeSeedEnvironment();

    Post::factory()->create(['slug' => 'uji-smoke-artikel', 'is_published' => true, 'published_at' => now()]);
    Page::factory()->create(['slug' => 'uji-smoke-halaman', 'is_published' => true, 'tingkat_scope' => 'all']);
    Document::factory()->create(['is_published' => true]);

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->reject(fn ($route) => str_starts_with($route->uri(), '_') || str_starts_with($route->uri(), 'livewire'))
        // Flux Free ships Pro asset routes (editor/phone) whose files are absent in this build.
        ->reject(fn ($route) => str_starts_with($route->uri(), 'flux/'))
        ->reject(fn ($route) => $route->getName() === 'setup');

    expect($routes)->not->toBeEmpty();

    $superAdmin = smokeUser('super_admin');
    $failures = [];

    foreach ($routes as $route) {
        $params = smokeRouteParams($route->uri());
        if (str_contains($route->uri(), '{') && $params === []) {
            continue;
        }

        $uri = $route->uri() === '/' ? '/' : '/'.ltrim($route->uri(), '/');
        foreach ($params as $key => $value) {
            $uri = str_replace('{'.$key.'}', (string) $value, $uri);
        }

        $response = $this->actingAs($superAdmin)->get($uri);

        if ($response->getStatusCode() >= 500) {
            $failures[] = "{$uri} => {$response->getStatusCode()}";
        }
    }

    expect($failures)->toBe([]);
});

it('never returns a server error on any admin route for any of the six roles', function (): void {
    smokeSeedEnvironment();

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->filter(fn ($route) => str_starts_with($route->uri(), (string) config('satker.admin_path')))
        ->map(fn ($route) => '/'.ltrim($route->uri(), '/'));

    expect($routes)->not->toBeEmpty();

    $failures = [];

    foreach (['super_admin', 'admin_konten', 'operator_kepaniteraan', 'operator_kesekretariatan', 'operator_ppid_humas', 'penilai_internal'] as $role) {
        $user = smokeUser($role);

        foreach ($routes as $uri) {
            $response = $this->actingAs($user)->get($uri);
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $failures[] = "{$role} {$uri} => {$status}";
            }
        }
    }

    expect($failures)->toBe([]);
});

it('submits a public comment end to end and rejects a wrong captcha', function (): void {
    smokeSeedEnvironment();

    $post = Post::factory()->create([
        'slug' => 'uji-komentar-smoke',
        'is_published' => true,
        'published_at' => now(),
        'comments_enabled' => true,
    ]);

    $this->get('/publikasi/'.$post->slug)->assertOk();

    $plainCode = $this->get('/captcha-text')->assertOk()->getContent();
    expect($plainCode)->toBeString()->not->toBe('');

    $this->post('/publikasi/'.$post->slug.'/komentar', [
        'author_name' => 'Warga Uji',
        'author_email' => 'warga@example.test',
        'body' => 'Mohon informasi jadwal sidang terbaru.',
        'captcha' => 'SALAH',
        'website' => '',
    ])->assertSessionHasErrors();

    expect($post->comments()->count())->toBe(0);

    $validCode = $this->get('/captcha-text')->assertOk()->getContent();

    $this->post('/publikasi/'.$post->slug.'/komentar', [
        'author_name' => 'Bot Spam',
        'body' => 'Isi komentar spam yang lolos panjang minimal.',
        'captcha' => $validCode,
        'website_hp' => 'https://spam.example',
    ])->assertSessionHasErrors('website_hp');

    expect($post->comments()->count())->toBe(0);

    $validCode = $this->get('/captcha-text')->assertOk()->getContent();

    $this->post('/publikasi/'.$post->slug.'/komentar', [
        'author_name' => 'Warga Uji',
        'author_email' => 'warga@example.test',
        'body' => 'Mohon informasi jadwal sidang terbaru.',
        'captcha' => $validCode,
        'website_hp' => '',
    ])->assertRedirect();

    expect($post->comments()->count())->toBe(1);
    expect($post->comments()->first()->status)->toBe('pending');
});

it('sets robots noindex nofollow on auth and admin layouts', function (): void {
    smokeSeedEnvironment();

    $superAdmin = smokeUser('super_admin');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
