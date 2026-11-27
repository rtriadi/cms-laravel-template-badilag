<?php

use App\Livewire\SetupWizard;
use App\Models\CriteriaItem;
use App\Models\MenuItem;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\LocalCaptchaService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('redirects public routes to setup wizard before setup completion', function (): void {
    $this->get('/')->assertRedirect(route('setup'));
});

it('completes 4-step setup wizard, locks tingkat, seeds sinova, and redirects super admin to 2fa setup', function (): void {
    Livewire::test(SetupWizard::class)
        ->set('nama_satker', 'Pengadilan Agama Soreang')
        ->set('jenis_badan', 'PA')
        ->set('tingkat', 'pertama')
        ->set('kode_satker', '401999')
        ->set('tahun_berdiri', 2018)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->set('alamat_jalan', 'Jl. Raya Soreang KM 17')
        ->set('provinsi', 'Jawa Barat')
        ->set('telepon', '(022) 5891111')
        ->set('email', 'info@pa-soreang.go.id')
        ->call('nextStep')
        ->assertSet('step', 3)
        ->set('sipp_url', 'https://sipp.pa-soreang.go.id')
        ->call('nextStep')
        ->assertSet('step', 4)
        ->set('admin_name', 'Admin TI Soreang')
        ->set('admin_email', 'admin@pa-soreang.go.id')
        ->set('admin_password', 'RahasiaKuat#2026')
        ->set('admin_password_confirmation', 'RahasiaKuat#2026')
        ->call('finishSetup')
        ->assertRedirect(route('admin.two-factor.setup'));

    $setting = SatkerSetting::current();
    expect($setting)->not->toBeNull()
        ->and($setting->is_setup_completed)->toBeTrue()
        ->and($setting->is_tingkat_locked)->toBeTrue()
        ->and($setting->tingkat)->toBe('pertama');

    $admin = User::query()->where('email', 'admin@pa-soreang.go.id')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('super_admin'))->toBeTrue();

    expect(CriteriaItem::query()->count())->toBe(40)
        ->and(MenuItem::query()->whereNull('parent_id')->count())->toBe(6);
});

it('renders login page and local captcha svg image', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();

    $this->get(route('login'))->assertOk();
    $this->get(route('captcha.image'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

it('serves a text captcha alternative for screen readers without leaking raw answers', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();

    $response = $this->get(route('captcha.text'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    $prompt = trim($response->getContent());
    expect($prompt)
        ->toStartWith('Berapa hasil ')
        ->toEndWith('? (ketik angka)')
        ->not->toMatch('/\d/');
    $this->assertNotNull(session(LocalCaptchaService::SESSION_KEY));
});

it('rejects wrong local captcha on login', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    SatkerSetting::factory()->setupCompleted()->create();

    $user = User::factory()->create([
        'email' => 'operator@pa-example.go.id',
        'password' => bcrypt('PasswordKuat#2026'),
    ]);
    $user->assignRole('admin_konten');

    $this->withSession([LocalCaptchaService::SESSION_KEY => Hash::make('K7M4P')])
        ->post(route('login'), [
            'email' => 'operator@pa-example.go.id',
            'password' => 'PasswordKuat#2026',
            'captcha' => 'WRONG',
        ])
        ->assertSessionHasErrors('captcha');

    $this->assertGuest();
});

it('accepts valid local captcha on login and enforces mandatory 2fa confirmation on admin panel', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    SatkerSetting::factory()->setupCompleted()->create();

    $user = User::factory()->create([
        'email' => 'operator@pa-example.go.id',
        'password' => bcrypt('PasswordKuat#2026'),
        'two_factor_confirmed_at' => null,
    ]);
    $user->assignRole('admin_konten');

    $this->withSession([LocalCaptchaService::SESSION_KEY => Hash::make('K7M4P')])
        ->post(route('login'), [
            'email' => 'operator@pa-example.go.id',
            'password' => 'PasswordKuat#2026',
            'captcha' => 'K7M4P',
        ])
        ->assertRedirect('/panel-admin');

    $this->assertAuthenticatedAs($user);

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.two-factor.setup'));

    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertOk();
});
