<?php

use App\Models\SatkerSetting;
use Database\Seeders\RoleAndPermissionSeeder;

it('menerjemahkan pesan framework ke bahasa Indonesia', function (): void {
    expect(__('validation.required', ['attribute' => 'email']))->toBe('Kolom email wajib diisi.')
        ->and(__('auth.failed'))->toBe('Kredensial yang diberikan tidak cocok dengan data kami.')
        ->and(__('pagination.next'))->toBe('Berikutnya &raquo;');
});

it('menampilkan pesan validasi Indonesia pada formulir login', function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    SatkerSetting::factory()->setupCompleted()->create();

    $this->post(route('login'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors([
            'email' => 'Kolom email wajib diisi.',
            'password' => 'Kolom password wajib diisi.',
        ]);
});
