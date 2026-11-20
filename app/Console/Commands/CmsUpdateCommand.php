<?php

namespace App\Console\Commands;

use App\Services\SinovaHealthCheckService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CmsUpdateCommand extends Command
{
    protected $signature = 'cms:update {--dry-run : Tampilkan informasi versi dan cek sistem tanpa menjalankan migrasi}';

    protected $description = 'Menjalankan prosedur pembaruan SatkerCMS (migrasi, pembersihan cache, dan cek kesehatan SINOVA; jalankan backup:run sebelumnya).';

    public function handle(SinovaHealthCheckService $healthCheck): int
    {
        $this->info('SatkerCMS Updater');
        $this->line('Versi CMS     : '.config('satker.cms_version', '1.0.0-dev'));
        $this->line('Versi Laravel : '.app()->version());
        $this->line('Versi PHP     : '.PHP_VERSION);

        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            $this->warn('Peringatan: Versi PHP di bawah 8.4 mendekati masa EOL. Disarankan menggunakan PHP 8.5.');
        }

        if ($this->option('dry-run')) {
            $summary = $healthCheck->scoreSummary();
            $this->info("Dry-run selesai. Kesiapan link SINOVA saat ini: {$summary['filled']}/{$summary['total']}.");

            return self::SUCCESS;
        }

        $this->info('1/3 Menjalankan migrasi basis data...');
        Artisan::call('migrate', ['--force' => true], $this->getOutput());

        $this->info('2/3 Membersihkan cache konfigurasi dan tampilan...');
        Artisan::call('optimize:clear', [], $this->getOutput());

        $this->info('3/3 Menjalankan pemeriksaan kesehatan tautan SINOVA...');
        $counts = $healthCheck->checkAll(probeExternal: false);
        $this->info("Pemeriksaan selesai: {$counts['healthy']} siap, {$counts['warning']} perlu perbaikan, {$counts['broken']} bermasalah, {$counts['empty']} kosong.");

        return self::SUCCESS;
    }
}
