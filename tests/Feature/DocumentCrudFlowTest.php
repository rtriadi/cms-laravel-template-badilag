<?php

use App\Livewire\Admin\DocumentManager;
use App\Models\Document;
use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function documentFlowUser(string $role): User
{
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

beforeEach(function (): void {
    app(RoleAndPermissionSeeder::class)->run();
    SatkerSetting::factory()->setupCompleted()->create(['tingkat' => 'pertama', 'is_tingkat_locked' => true]);
    Storage::fake('public');
    Storage::fake('local');
});

it('uploads, moves between disks, and deletes a document through DocumentManager', function (): void {
    $user = documentFlowUser('operator_kesekretariatan');

    Livewire::actingAs($user)
        ->test(DocumentManager::class)
        ->set('collection_type', 'lkjip')
        ->set('title', 'LKjIP Tahun 2026')
        ->set('year', 2026)
        ->set('status_label', 'Final')
        ->set('is_published', true)
        ->set('pdf_file', UploadedFile::fake()->create('lkjip-2026.pdf', 120, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $doc = Document::query()->sole();
    expect($doc->file_path)->toStartWith('documents/lkjip/');
    Storage::disk('public')->assertExists($doc->file_path);
    Storage::disk('local')->assertMissing($doc->file_path);

    $path = $doc->file_path;

    Livewire::actingAs($user)
        ->test(DocumentManager::class)
        ->call('edit', $doc->id)
        ->set('is_published', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($doc->refresh()->is_published)->toBeFalse();
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);

    Livewire::actingAs($user)
        ->test(DocumentManager::class)
        ->call('delete', $doc->id)
        ->assertHasNoErrors();

    expect(Document::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

it('blocks a document collection outside the operator department', function (): void {
    $user = documentFlowUser('operator_kepaniteraan');

    Livewire::actingAs($user)
        ->test(DocumentManager::class)
        ->set('collection_type', 'lkjip')
        ->set('title', 'Percobaan Lintas Departemen')
        ->set('year', 2026)
        ->call('save')
        ->assertForbidden();

    expect(Document::query()->count())->toBe(0);
});
