<?php

namespace App\Console\Commands;

use App\Services\CmsUpdateCheckService;
use Illuminate\Console\Command;

class CmsCheckUpdateCommand extends Command
{
    protected $signature = 'cms:check-update {--force : Abaikan masa berlaku cache dan periksa GitHub sekarang}';

    protected $description = 'Memeriksa ketersediaan pembaruan SatkerCMS dari repositori GitHub.';

    public function handle(CmsUpdateCheckService $updateChecker): int
    {
        $state = $updateChecker->check(force: (bool) $this->option('force'));

        $this->line('Kanal Pembaruan  : '.($state['channel'] ?? 'stable'));
        $this->line('Versi Terpasang  : '.($state['installed_version'] ?? '-'));
        $this->line('Versi GitHub     : '.($state['latest_version'] ?? '-'));

        if (! empty($state['last_error'])) {
            $this->warn('Catatan: '.(string) $state['last_error']);

            return self::SUCCESS;
        }

        if (($state['update_available'] ?? false) === true) {
            $this->info('Pembaruan tersedia: '.($state['latest_version'] ?? '').'. Jalankan `php artisan cms:update` untuk memperbarui aplikasi.');
        } else {
            $this->info('SatkerCMS sudah berada pada versi terbaru.');
        }

        return self::SUCCESS;
    }
}
