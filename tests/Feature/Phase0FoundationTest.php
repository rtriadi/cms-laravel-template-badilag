<?php

use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

it('sets security hardening headers and strips server fingerprint headers', function (): void {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeaderMissing('X-Powered-By')
        ->assertHeaderMissing('Server');
});

it('sets strict transport security header on https requests', function (): void {
    $response = $this->get('https://localhost/');

    $response->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('seeds all six standard satker roles with expected permissions', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    $expectedRoles = [
        'super_admin',
        'admin_konten',
        'operator_kepaniteraan',
        'operator_kesekretariatan',
        'operator_ppid_humas',
        'penilai_internal',
    ];

    foreach ($expectedRoles as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue();
    }

    $superAdminRole = Role::findByName('super_admin', 'web');
    $penilaiRole = Role::findByName('penilai_internal', 'web');
    $kepaniteraanRole = Role::findByName('operator_kepaniteraan', 'web');

    expect($superAdminRole->hasPermissionTo('change-satker-tingkat'))->toBeTrue()
        ->and($superAdminRole->hasPermissionTo('manage-users'))->toBeTrue()
        ->and($kepaniteraanRole->hasPermissionTo('manage-kepaniteraan'))->toBeTrue()
        ->and($kepaniteraanRole->hasPermissionTo('manage-kesekretariatan'))->toBeFalse()
        ->and($penilaiRole->hasPermissionTo('view-compliance-dashboard'))->toBeTrue()
        ->and($penilaiRole->hasPermissionTo('manage-sinova-links'))->toBeFalse();
});

it('caches current satker setting and invalidates cache on update', function (): void {
    Cache::flush();

    $setting = SatkerSetting::factory()->create([
        'nama_satker' => 'Pengadilan Agama Bandung',
    ]);

    expect(SatkerSetting::current()?->nama_satker)->toBe('Pengadilan Agama Bandung');

    $setting->update(['nama_satker' => 'Pengadilan Agama Cimahi']);

    expect(SatkerSetting::current()?->nama_satker)->toBe('Pengadilan Agama Cimahi');
});

it('replaces satker placeholders accurately', function (): void {
    $setting = SatkerSetting::factory()->create([
        'nama_satker' => 'Pengadilan Agama Soreang',
        'jenis_badan' => 'PA',
        'tingkat' => 'pertama',
        'kode_satker' => '401234',
        'alamat_jalan' => 'Jl. Raya Soreang KM 17',
        'desa_kelurahan' => 'Pamekaran',
        'kecamatan' => 'Soreang',
        'kabupaten_kota' => 'Kabupaten Bandung',
        'provinsi' => 'Jawa Barat',
        'kode_pos' => '40912',
        'telepon' => '(022) 5891234',
        'email' => 'info@pa-soreang.go.id',
        'website_url' => 'https://pa-soreang.go.id',
    ]);

    $rendered = $setting->replacePlaceholders(
        'Selamat datang di {{satker.nama}} ({{satker.tingkat}} - {{satker.kode}}), {{satker.kota}}, {{satker.alamat}}.'
    );

    expect($rendered)->toBe(
        'Selamat datang di Pengadilan Agama Soreang (Tingkat Pertama - 401234), Kabupaten Bandung, Jl. Raya Soreang KM 17, Kel. Pamekaran, Kec. Soreang, Kabupaten Bandung, Jawa Barat, 40912.'
    );
});

it('locks tingkat after setup and allows only super admin to change it', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $adminKonten = User::factory()->create();
    $adminKonten->assignRole('admin_konten');

    $setting = SatkerSetting::factory()->create([
        'tingkat' => 'pertama',
        'is_tingkat_locked' => false,
        'is_setup_completed' => false,
    ]);

    $setting->completeSetup();

    expect($setting->fresh()->is_setup_completed)->toBeTrue()
        ->and($setting->fresh()->is_tingkat_locked)->toBeTrue();

    expect(fn () => $setting->update(['tingkat' => 'banding']))
        ->toThrow(AuthorizationException::class);

    expect(fn () => $setting->changeTingkat('banding', $adminKonten))
        ->toThrow(AuthorizationException::class);

    $setting->changeTingkat('banding', $superAdmin);

    expect($setting->fresh()->tingkat)->toBe('banding');

    $latestActivity = Activity::query()->where('log_name', 'satker')->latest('id')->first();
    expect($latestActivity)->not->toBeNull()
        ->and($latestActivity->causer_id)->toBe($superAdmin->id);
});
