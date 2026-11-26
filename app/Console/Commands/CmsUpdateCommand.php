<?php

namespace App\Console\Commands;

use App\Services\CmsUpdateCheckService;
use App\Services\CmsZeroDowntimeUpdater;
use App\Services\SinovaHealthCheckService;
use App\Support\CmsVersion;
use App\Support\MysqlDumpBinaryResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CmsUpdateCommand extends Command
{
    protected $signature = 'cms:update
        {--dry-run : Tampilkan informasi versi, mode server, dan cek kesiapan SINOVA tanpa mengubah berkas atau database}
        {--local-only : Jalankan penemuan paket, migrasi, pemanasan cache, dan cek SINOVA pada kode lokal saat ini}
        {--tag= : Pasang tag rilis spesifik dari GitHub Releases (mis. v1.0.1)}
        {--skip-backup : Lewati backup database otomatis sebelum pembaruan}
        {--yes : Jalankan pembaruan tanpa konfirmasi interaktif}';

    protected $description = 'Menjalankan pembaruan zero-downtime SatkerCMS dari GitHub Releases beserta migrasi, cache, dan cek kesehatan SINOVA.';

    public function handle(
        SinovaHealthCheckService $healthCheck,
        CmsUpdateCheckService $updateChecker,
        CmsZeroDowntimeUpdater $updater,
    ): int {
        $installedVersion = CmsVersion::current();
        $layout = $updater->detectLayout();

        $this->info('SatkerCMS Zero-Downtime Updater');
        $this->line('Versi CMS     : '.$installedVersion);
        $this->line('Versi Laravel : '.app()->version());
        $this->line('Versi PHP     : '.PHP_VERSION);
        $this->line('Mode Peladen  : '.$layout['mode']);

        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            $this->warn('Peringatan: Versi PHP di bawah 8.4 mendekati masa EOL. Disarankan menggunakan PHP 8.5.');
        }

        if ((bool) $this->option('dry-run')) {
            return $this->handleDryRun($healthCheck, $updateChecker);
        }

        if ((bool) $this->option('local-only')) {
            return $this->handleLocalOnly($healthCheck);
        }

        $requestedOption = $this->option('tag');
        $requestedTag = is_string($requestedOption) && trim($requestedOption) !== '' ? trim($requestedOption) : null;

        if ($requestedTag === null && strtolower((string) config('satker.update.channel', 'stable')) === 'dev') {
            $this->comment('Kanal pembaruan saat ini diatur ke `dev`. Untuk memperbarui dari cabang pengembangan, jalankan:');
            $this->line('  git pull && php artisan cms:update --local-only');

            return self::SUCCESS;
        }

        try {
            $release = $updateChecker->fetchReleaseByTag($requestedTag);
        } catch (Throwable $e) {
            $this->error('Gagal memeriksa rilis GitHub: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($requestedTag === null && version_compare($release['version'], $installedVersion, '<=')) {
            $this->info("SatkerCMS sudah menggunakan versi terbaru ({$installedVersion}).");

            return self::SUCCESS;
        }

        $this->line("Rilis Target  : {$release['version']} ({$release['tag']})");

        if (! (bool) $this->option('yes') && $this->input->isInteractive()) {
            if (! $this->confirm("Lanjutkan pembaruan zero-downtime ke versi {$release['version']}?", true)) {
                $this->comment('Pembaruan dibatalkan oleh pengguna.');

                return self::SUCCESS;
            }
        }

        if (! (bool) $this->option('skip-backup')) {
            $this->info('Menjalankan backup database pra-pembaruan...');
            if (! $this->runDatabaseBackup()) {
                $this->error('Backup database gagal dijalankan. Pembaruan dihentikan demi keamanan data (gunakan --skip-backup hanya bila sudah mencadangkan manual).');

                return self::FAILURE;
            }
        }

        $this->info("Mengunduh, memverifikasi SHA-256, dan menerapkan rilis {$release['version']}...");

        try {
            $result = $updater->updateToRelease($release);
        } catch (Throwable $e) {
            $this->error('Pembaruan gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Pembaruan berhasil dari {$result['previous_version']} ke {$result['version']} (mode: {$result['mode']}).");

        return self::SUCCESS;
    }

    private function handleDryRun(SinovaHealthCheckService $healthCheck, CmsUpdateCheckService $updateChecker): int
    {
        if ((bool) config('satker.update.enabled', true)) {
            $state = $updateChecker->check(force: false);
            if (($state['update_available'] ?? false) === true) {
                $this->info('Status GitHub : Tersedia versi baru '.($state['latest_version'] ?? '').'.');
            } elseif (! empty($state['latest_version'])) {
                $this->line('Status GitHub : Sudah mutakhir ('.($state['latest_version'] ?? '').').');
            }
        }

        $summary = $healthCheck->scoreSummary();
        $this->info("Dry-run selesai. Kesiapan link SINOVA saat ini: {$summary['filled']}/{$summary['total']}.");

        return self::SUCCESS;
    }

    private function handleLocalOnly(SinovaHealthCheckService $healthCheck): int
    {
        $this->clearLocalBootstrapCache();

        $this->info('1/4 Memperbarui peta paket terpasang...');
        Artisan::call('package:discover', ['--ansi' => false], $this->getOutput());

        $this->info('2/4 Menjalankan migrasi basis data...');
        Artisan::call('migrate', ['--force' => true], $this->getOutput());

        $this->info('3/4 Menyegarkan cache konfigurasi dan tampilan...');
        if (app()->environment('production')) {
            Artisan::call('config:cache', [], $this->getOutput());
            Artisan::call('route:cache', [], $this->getOutput());
            Artisan::call('view:cache', [], $this->getOutput());
        } else {
            Artisan::call('optimize:clear', [], $this->getOutput());
        }

        $this->info('4/4 Menjalankan pemeriksaan kesehatan tautan SINOVA...');
        $counts = $healthCheck->checkAll(probeExternal: false);
        $this->info("Pemeriksaan selesai: {$counts['healthy']} siap, {$counts['warning']} perlu perbaikan, {$counts['broken']} bermasalah, {$counts['empty']} kosong.");

        return self::SUCCESS;
    }

    private function clearLocalBootstrapCache(): void
    {
        $cacheDir = base_path('bootstrap/cache');
        if (! is_dir($cacheDir)) {
            return;
        }

        foreach (glob($cacheDir.DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function runDatabaseBackup(): bool
    {
        $connection = (string) config('database.default', 'mysql');
        if (in_array($connection, ['mysql', 'mariadb'], true)) {
            $configured = (string) config("database.connections.{$connection}.dump.dump_binary_path", '');
            if ($configured === '') {
                $resolved = MysqlDumpBinaryResolver::resolve();
                if ($resolved !== '') {
                    config()->set("database.connections.{$connection}.dump.dump_binary_path", $resolved);
                }
            }
        }

        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ], $this->getOutput());

            return $exitCode === 0;
        } catch (Throwable $e) {
            $this->warn($e->getMessage());

            return false;
        }
    }
}
