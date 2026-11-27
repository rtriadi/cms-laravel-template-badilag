<?php

namespace App\Console\Commands;

use App\Services\CmsUpdateCheckService;
use App\Services\CmsUpdateQueueService;
use App\Support\CmsVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class CmsProcessUpdateQueueCommand extends Command
{
    protected $signature = 'cms:process-update-queue
        {--auto : Periksa dan jalankan pembaruan otomatis terjadwal bila CMS_UPDATE_AUTO=true}
        {--skip-backup : Lewati backup database saat mode --auto}';

    protected $description = 'Memproses antrean tiket pembaruan/rollback dari Panel Admin atau pembaruan otomatis terjadwal.';

    public function handle(
        CmsUpdateQueueService $queue,
        CmsUpdateCheckService $updateChecker,
    ): int {
        if ((bool) $this->option('auto')) {
            return $this->handleAutoUpdate($queue, $updateChecker);
        }

        if (! $queue->hasPendingTriggerFile()) {
            return self::SUCCESS;
        }

        $trigger = $queue->consumeValidTrigger();
        if ($trigger === null) {
            $existing = $queue->getJobStatus();
            if ($existing !== null && ($existing['status'] ?? '') === 'queued') {
                $queue->markFailed($existing, 'Tiket antrean pembaruan ditolak karena tanda tangan tidak valid atau sudah kedaluwarsa.');
            }

            return self::SUCCESS;
        }

        return $this->executeTrigger($queue, $trigger);
    }

    private function handleAutoUpdate(CmsUpdateQueueService $queue, CmsUpdateCheckService $updateChecker): int
    {
        if (! (bool) config('satker.update.auto_update_enabled', false)) {
            return self::SUCCESS;
        }

        if (strtolower((string) config('satker.update.channel', 'stable')) !== 'stable') {
            return self::SUCCESS;
        }

        if ($queue->hasActiveJob()) {
            return self::SUCCESS;
        }

        $state = $updateChecker->check(force: true);
        $latestTag = trim((string) ($state['latest_tag'] ?? ''));

        if (
            ($state['update_available'] ?? false) !== true
            || $latestTag === ''
            || empty($state['zip_url'])
            || empty($state['sha256_url'])
        ) {
            return self::SUCCESS;
        }

        $now = now()->timestamp;
        $trigger = [
            'id' => (string) Str::uuid(),
            'action' => 'update',
            'target_tag' => $latestTag,
            'skip_backup' => (bool) $this->option('skip-backup'),
            'requested_by_id' => null,
            'requested_by_name' => 'Auto-Updater Terjadwal',
            'requested_at' => $now,
            'expires_at' => $now + CmsUpdateQueueService::TRIGGER_TTL_SECONDS,
        ];

        return $this->executeTrigger($queue, $trigger);
    }

    /**
     * @param  array<string, mixed>  $trigger
     */
    private function executeTrigger(CmsUpdateQueueService $queue, array $trigger): int
    {
        $fromVersion = CmsVersion::current();
        $job = $queue->markRunning($trigger, $fromVersion);
        $buffer = new BufferedOutput;

        try {
            if (($trigger['action'] ?? 'update') === 'rollback') {
                $exitCode = Artisan::call('cms:rollback', ['--yes' => true], $buffer);
            } else {
                $args = ['--yes' => true];
                $targetTag = trim((string) ($trigger['target_tag'] ?? ''));
                if ($targetTag !== '') {
                    $args['--tag'] = $targetTag;
                }
                if ((bool) ($trigger['skip_backup'] ?? false)) {
                    $args['--skip-backup'] = true;
                }

                $exitCode = Artisan::call('cms:update', $args, $buffer);
            }

            $rawOutput = trim($buffer->fetch());

            if ($exitCode === self::SUCCESS) {
                $resolvedToVersion = $this->extractTargetVersion($rawOutput, $trigger);
                $successMsg = ($trigger['action'] ?? 'update') === 'rollback'
                    ? "Rollback selesai. Versi aktif sekarang: {$resolvedToVersion}."
                    : "Pembaruan selesai ke versi {$resolvedToVersion}. Silakan muat ulang halaman.";

                $queue->markSucceeded($job, $resolvedToVersion, $successMsg, $rawOutput);

                return self::SUCCESS;
            }

            $failSummary = $this->extractFailureLine($rawOutput);
            $queue->markFailed($job, $failSummary, $rawOutput);

            return self::FAILURE;
        } catch (Throwable $e) {
            $queue->markFailed($job, 'Terjadi galat saat menjalankan pembaruan: '.$e->getMessage(), $buffer->fetch());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $trigger
     */
    private function extractTargetVersion(string $output, array $trigger): string
    {
        if (preg_match('/ke\s+([0-9]+\.[0-9]+\.[0-9]+[^\s)]*)/i', $output, $matches) === 1) {
            return CmsVersion::normalize($matches[1]);
        }

        if (preg_match('/Versi aktif sekarang:\s*([0-9]+\.[0-9]+\.[0-9]+[^\s(]*)/i', $output, $matches) === 1) {
            return CmsVersion::normalize($matches[1]);
        }

        $tag = trim((string) ($trigger['target_tag'] ?? ''));
        if ($tag !== '') {
            return CmsVersion::normalize($tag);
        }

        return CmsVersion::current();
    }

    private function extractFailureLine(string $output): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $output) ?: [])));
        foreach (array_reverse($lines) as $line) {
            if (stripos($line, 'gagal') !== false || stripos($line, 'error') !== false || stripos($line, 'ditolak') !== false) {
                return Str::limit($line, 240);
            }
        }

        return 'Proses pembaruan berhenti dengan status galat. Buka rincian log di bawah untuk detailnya.';
    }
}
