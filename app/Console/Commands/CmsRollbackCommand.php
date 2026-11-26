<?php

namespace App\Console\Commands;

use App\Services\CmsZeroDowntimeUpdater;
use App\Support\CmsVersion;
use Illuminate\Console\Command;
use Throwable;

class CmsRollbackCommand extends Command
{
    protected $signature = 'cms:rollback
        {--target= : Nama rilis di folder releases/ atau nama snapshot prev-* tujuan}
        {--yes : Jalankan rollback tanpa konfirmasi interaktif}';

    protected $description = 'Mengembalikan SatkerCMS ke rilis atau snapshot versi sebelumnya secara cepat.';

    public function handle(CmsZeroDowntimeUpdater $updater): int
    {
        $currentVersion = CmsVersion::current();
        $layout = $updater->detectLayout();

        $this->info('SatkerCMS Rollback');
        $this->line('Versi Saat Ini : '.$currentVersion);
        $this->line('Mode Peladen   : '.$layout['mode']);

        if (! (bool) $this->option('yes') && $this->input->isInteractive()) {
            if (! $this->confirm('Kembalikan aplikasi ke rilis sebelumnya sekarang?', false)) {
                $this->comment('Rollback dibatalkan.');

                return self::SUCCESS;
            }
        }

        $targetOption = $this->option('target');
        $target = is_string($targetOption) && trim($targetOption) !== '' ? trim($targetOption) : null;

        try {
            $result = $updater->rollback($target);
        } catch (Throwable $e) {
            $this->error('Rollback gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Rollback berhasil. Versi aktif sekarang: {$result['version']} (mode: {$result['mode']}).");

        return self::SUCCESS;
    }
}
