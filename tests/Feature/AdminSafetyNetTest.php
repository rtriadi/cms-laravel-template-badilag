<?php

use App\Livewire\Admin\AuditAndBackupManager;
use App\Livewire\Admin\PostManager;
use App\Livewire\Admin\UserRoleManager;
use App\Models\LinkCheckLog;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\SinovaHealthCheckService;
use App\Support\MysqlDumpBinaryResolver;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

function safetyUser(string $role): User
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
});

it('lets a super admin reset another admin two factor authentication', function (): void {
    $superAdmin = safetyUser('super_admin');
    $target = safetyUser('admin_konten');

    Livewire::actingAs($superAdmin)
        ->test(UserRoleManager::class)
        ->call('resetTwoFactor', $target->id)
        ->assertHasNoErrors();

    $target->refresh();
    expect($target->two_factor_secret)->toBeNull()
        ->and($target->two_factor_recovery_codes)->toBeNull()
        ->and($target->two_factor_confirmed_at)->toBeNull();

    expect(Activity::query()
        ->where('description', 'Mereset 2FA akun admin')
        ->where('subject_id', $target->id)
        ->exists())->toBeTrue();
});

it('forbids non super admins from resetting two factor authentication', function (): void {
    $operator = safetyUser('operator_kepaniteraan');
    $operator->givePermissionTo('manage-users');
    $target = safetyUser('admin_konten');

    Livewire::actingAs($operator)
        ->test(UserRoleManager::class)
        ->call('resetTwoFactor', $target->id)
        ->assertForbidden();

    expect($target->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('lists and downloads backup archives from the private disk', function (): void {
    Storage::fake('local');
    $superAdmin = safetyUser('super_admin');
    $archive = trim((string) config('backup.backup.name', 'SatkerCMS'), '/').'/2026-01-01-02-00-00.zip';
    Storage::disk('local')->put($archive, 'zip-content');

    Livewire::actingAs($superAdmin)
        ->test(AuditAndBackupManager::class)
        ->set('tab', 'backup')
        ->assertSee('2026-01-01-02-00-00.zip')
        ->call('downloadBackup', '2026-01-01-02-00-00.zip')
        ->assertFileDownloaded('2026-01-01-02-00-00.zip');
});

it('stores an expiry date and hides expired posts from the public scope', function (): void {
    $superAdmin = safetyUser('super_admin');

    Livewire::actingAs($superAdmin)
        ->test(PostManager::class)
        ->set('title', 'Pengumuman Berbatas Waktu')
        ->set('slug', 'pengumuman-berbatas-waktu')
        ->set('category', 'berita')
        ->set('body', 'Isi pengumuman berbatas waktu.')
        ->set('expiresAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::query()->where('slug', 'pengumuman-berbatas-waktu')->sole();
    expect($post->expires_at)->not->toBeNull()
        ->and(Post::query()->publishedActive()->whereKey($post->id)->exists())->toBeTrue();

    $post->update(['expires_at' => now()->subMinute()]);

    expect(Post::query()->publishedActive()->whereKey($post->id)->exists())->toBeFalse();
});

it('shows an actionable error notice instead of fake success when backup command fails', function (): void {
    Storage::fake('local');
    $superAdmin = safetyUser('super_admin');

    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:run', ['--only-db' => true, '--disable-notifications' => true])
        ->andReturn(1);
    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Backup failed because: \'"mysqldump"\' is not recognized as an internal or external command.');

    Livewire::actingAs($superAdmin)
        ->test(AuditAndBackupManager::class)
        ->set('tab', 'backup')
        ->call('runBackup')
        ->assertSet('statusNotice', null)
        ->assertSee('Backup database gagal dijalankan.')
        ->assertSee('MYSQL_DUMP_BINARY_PATH');

    expect(Activity::query()
        ->where('log_name', 'backup')
        ->where('description', 'Backup database manual gagal dijalankan')
        ->exists())->toBeTrue();
});

it('normalizes configured mysqldump path in MysqlDumpBinaryResolver', function (): void {
    $resolvedDir = MysqlDumpBinaryResolver::resolve('C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\');
    expect($resolvedDir)->toBe('C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin');

    $resolvedFromFile = MysqlDumpBinaryResolver::resolve(__FILE__);
    expect($resolvedFromFile)->toBe(str_replace('\\', '/', __DIR__));
});

it('replaces stale link audit logs on re-scan without accumulating duplicates', function (): void {
    $superAdmin = safetyUser('super_admin');
    $publishedPage = Page::factory()->create(['title' => 'Halaman Aktif', 'slug' => 'halaman-aktif', 'is_published' => true]);
    $draftPage = Page::factory()->create(['title' => 'Halaman Draf', 'slug' => 'halaman-draf', 'is_published' => false]);

    MenuItem::factory()->create([
        'title' => 'Menu Sehat',
        'type' => 'page',
        'page_id' => $publishedPage->id,
        'is_visible' => true,
        'tingkat_scope' => 'all',
    ]);
    MenuItem::factory()->create([
        'title' => 'Menu Draf',
        'type' => 'page',
        'page_id' => $draftPage->id,
        'is_visible' => true,
        'tingkat_scope' => 'all',
    ]);

    LinkCheckLog::factory()->count(5)->create([
        'source_type' => 'menu',
        'source_label' => 'Menu Lama Sudah Dihapus',
        'is_broken' => true,
    ]);

    $service = app(SinovaHealthCheckService::class);
    $first = $service->checkAll('pertama', probeExternal: false);
    $second = $service->checkAll('pertama', probeExternal: false);

    expect($first['menu_checked'])->toBe(2)
        ->and($first['menu_broken'])->toBe(1)
        ->and($second['menu_checked'])->toBe(2)
        ->and($second['menu_broken'])->toBe(1)
        ->and(LinkCheckLog::query()->count())->toBe(2)
        ->and(LinkCheckLog::query()->where('source_label', 'Menu Lama Sudah Dihapus')->exists())->toBeFalse()
        ->and(LinkCheckLog::query()->where('is_broken', true)->count())->toBe(1)
        ->and(LinkCheckLog::query()->where('is_broken', false)->count())->toBe(1);

    Livewire::actingAs($superAdmin)
        ->test(AuditAndBackupManager::class)
        ->set('tab', 'links')
        ->assertSee('Menu Draf')
        ->assertSee('Menu Sehat')
        ->set('linkFilter', 'broken')
        ->assertSee('Menu Draf')
        ->assertDontSee('Menu Sehat');
});
