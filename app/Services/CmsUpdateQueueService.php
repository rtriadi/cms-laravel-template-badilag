<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class CmsUpdateQueueService
{
    public const int TRIGGER_TTL_SECONDS = 1800;

    public const int STALE_RUNNING_SECONDS = 900;

    /**
     * @return array<string, mixed>
     */
    public function enqueueUpdate(
        string $targetTag,
        bool $skipBackup,
        ?int $userId,
        string $userName,
        string $fromVersion,
    ): array {
        if ($this->hasActiveJob()) {
            throw new RuntimeException('Sudah ada proses pembaruan yang sedang menunggu atau berjalan.');
        }

        $cleanTag = trim($targetTag);
        if ($cleanTag === '' || preg_match('/^v?\d+\.\d+\.\d+(-[A-Za-z0-9._-]+)?$/', $cleanTag) !== 1) {
            throw new RuntimeException('Format tag rilis tujuan tidak valid.');
        }

        $now = now()->timestamp;
        $trigger = [
            'id' => (string) Str::uuid(),
            'action' => 'update',
            'target_tag' => $cleanTag,
            'skip_backup' => $skipBackup,
            'requested_by_id' => $userId,
            'requested_by_name' => trim($userName) !== '' ? trim($userName) : 'Super Admin',
            'requested_at' => $now,
            'expires_at' => $now + self::TRIGGER_TTL_SECONDS,
        ];
        $trigger['signature'] = $this->signPayload($trigger);

        $jobStatus = [
            'id' => $trigger['id'],
            'action' => 'update',
            'status' => 'queued',
            'target_tag' => $cleanTag,
            'from_version' => $fromVersion,
            'to_version' => null,
            'skip_backup' => $skipBackup,
            'requested_by_name' => $trigger['requested_by_name'],
            'queued_at' => $now,
            'started_at' => null,
            'finished_at' => null,
            'message' => "Menunggu antrean cron schedule:run untuk memasang {$cleanTag} (estimasi <= 1 menit).",
            'output_excerpt' => null,
        ];

        $this->writeJsonAtomically($this->triggerPath(), $trigger);
        $this->writeJsonAtomically($this->jobStatusPath(), $jobStatus);

        return $jobStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function enqueueRollback(?int $userId, string $userName, string $fromVersion): array
    {
        if ($this->hasActiveJob()) {
            throw new RuntimeException('Sudah ada proses pembaruan yang sedang menunggu atau berjalan.');
        }

        $now = now()->timestamp;
        $trigger = [
            'id' => (string) Str::uuid(),
            'action' => 'rollback',
            'target_tag' => '',
            'skip_backup' => true,
            'requested_by_id' => $userId,
            'requested_by_name' => trim($userName) !== '' ? trim($userName) : 'Super Admin',
            'requested_at' => $now,
            'expires_at' => $now + self::TRIGGER_TTL_SECONDS,
        ];
        $trigger['signature'] = $this->signPayload($trigger);

        $jobStatus = [
            'id' => $trigger['id'],
            'action' => 'rollback',
            'status' => 'queued',
            'target_tag' => null,
            'from_version' => $fromVersion,
            'to_version' => null,
            'skip_backup' => true,
            'requested_by_name' => $trigger['requested_by_name'],
            'queued_at' => $now,
            'started_at' => null,
            'finished_at' => null,
            'message' => 'Menunggu antrean cron schedule:run untuk menjalankan rollback ke versi sebelumnya (estimasi <= 1 menit).',
            'output_excerpt' => null,
        ];

        $this->writeJsonAtomically($this->triggerPath(), $trigger);
        $this->writeJsonAtomically($this->jobStatusPath(), $jobStatus);

        return $jobStatus;
    }

    public function hasPendingTriggerFile(): bool
    {
        return is_file($this->triggerPath());
    }

    /**
     * Reads and immediately removes the trigger file so every signed ticket executes strictly once.
     *
     * @return ?array<string, mixed>
     */
    public function consumeValidTrigger(): ?array
    {
        $path = $this->triggerPath();
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        @unlink($path);

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        $signature = isset($decoded['signature']) && is_string($decoded['signature']) ? $decoded['signature'] : '';
        if ($signature === '') {
            return null;
        }

        $expected = $this->signPayload($decoded);
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $expiresAt = (int) ($decoded['expires_at'] ?? 0);
        if ($expiresAt < now()->timestamp) {
            return null;
        }

        $action = (string) ($decoded['action'] ?? '');
        if (! in_array($action, ['update', 'rollback'], true)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $trigger
     * @return array<string, mixed>
     */
    public function markRunning(array $trigger, string $fromVersion): array
    {
        $now = now()->timestamp;
        $action = (string) ($trigger['action'] ?? 'update');
        $targetTag = isset($trigger['target_tag']) && is_string($trigger['target_tag']) && trim($trigger['target_tag']) !== ''
            ? trim($trigger['target_tag'])
            : null;

        $status = [
            'id' => (string) ($trigger['id'] ?? Str::uuid()),
            'action' => $action,
            'status' => 'running',
            'target_tag' => $targetTag,
            'from_version' => $fromVersion,
            'to_version' => null,
            'skip_backup' => (bool) ($trigger['skip_backup'] ?? false),
            'requested_by_name' => (string) ($trigger['requested_by_name'] ?? 'Super Admin'),
            'queued_at' => (int) ($trigger['requested_at'] ?? $now),
            'started_at' => $now,
            'finished_at' => null,
            'message' => $action === 'rollback'
                ? 'Sedang memulihkan SatkerCMS ke versi sebelumnya...'
                : 'Sedang mengunduh, memverifikasi SHA-256, dan memasang '.($targetTag ?? 'rilis terbaru').'...',
            'output_excerpt' => null,
        ];

        $this->writeJsonAtomically($this->jobStatusPath(), $status);

        return $status;
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public function markSucceeded(array $job, string $toVersion, string $message, string $outputExcerpt = ''): array
    {
        $updated = array_merge($job, [
            'status' => 'succeeded',
            'to_version' => $toVersion,
            'finished_at' => now()->timestamp,
            'message' => $message,
            'output_excerpt' => $this->sanitizeOutputExcerpt($outputExcerpt),
        ]);

        $this->writeJsonAtomically($this->jobStatusPath(), $updated);

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public function markFailed(array $job, string $message, string $outputExcerpt = ''): array
    {
        $updated = array_merge($job, [
            'status' => 'failed',
            'finished_at' => now()->timestamp,
            'message' => $message,
            'output_excerpt' => $this->sanitizeOutputExcerpt($outputExcerpt),
        ]);

        $this->writeJsonAtomically($this->jobStatusPath(), $updated);

        return $updated;
    }

    /**
     * @return ?array<string, mixed>
     */
    public function getJobStatus(): ?array
    {
        $path = $this->jobStatusPath();
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        $status = (string) ($decoded['status'] ?? '');
        $now = now()->timestamp;

        if ($status === 'running') {
            $startedAt = (int) ($decoded['started_at'] ?? 0);
            if ($startedAt > 0 && ($now - $startedAt) > self::STALE_RUNNING_SECONDS) {
                return $this->markFailed(
                    $decoded,
                    'Proses pembaruan melebihi batas waktu tunggu 15 menit. Periksa storage/logs/laravel.log sebelum mencoba ulang.'
                );
            }
        }

        if ($status === 'queued' && ! is_file($this->triggerPath())) {
            $queuedAt = (int) ($decoded['queued_at'] ?? 0);
            if ($queuedAt > 0 && ($now - $queuedAt) > self::TRIGGER_TTL_SECONDS) {
                return $this->markFailed(
                    $decoded,
                    'Antrean pembaruan kedaluwarsa karena cron schedule:run belum mengeksekusinya dalam 30 menit.'
                );
            }
        }

        return $decoded;
    }

    public function hasActiveJob(): bool
    {
        $status = $this->getJobStatus();
        if ($status === null) {
            return false;
        }

        return in_array((string) ($status['status'] ?? ''), ['queued', 'running'], true);
    }

    public function cancelQueued(): bool
    {
        $status = $this->getJobStatus();
        if ($status === null || ($status['status'] ?? '') !== 'queued') {
            return false;
        }

        if (is_file($this->triggerPath())) {
            @unlink($this->triggerPath());
        }

        $this->clearJobStatus();

        return true;
    }

    public function clearJobStatus(): void
    {
        $path = $this->jobStatusPath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function triggerPath(): string
    {
        $configured = trim((string) config('satker.update.trigger_path', ''));

        return $configured !== '' ? $configured : storage_path('app/cms-update-trigger.json');
    }

    public function jobStatusPath(): string
    {
        $configured = trim((string) config('satker.update.job_status_path', ''));

        return $configured !== '' ? $configured : storage_path('app/cms-update-job.json');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signPayload(array $payload): string
    {
        $canonical = implode('|', [
            (string) ($payload['id'] ?? ''),
            (string) ($payload['action'] ?? ''),
            (string) ($payload['target_tag'] ?? ''),
            ((bool) ($payload['skip_backup'] ?? false)) ? '1' : '0',
            (string) ($payload['requested_by_id'] ?? ''),
            (string) ($payload['requested_at'] ?? ''),
            (string) ($payload['expires_at'] ?? ''),
        ]);

        return hash_hmac('sha256', $canonical, (string) config('app.key'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeJsonAtomically(string $path, array $data): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Gagal menyiapkan direktori status pembaruan: {$dir}");
        }

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded)) {
            throw new RuntimeException('Gagal mengodekan data antrean pembaruan ke JSON.');
        }

        @file_put_contents($path, $encoded, LOCK_EX);
    }

    private function sanitizeOutputExcerpt(string $rawOutput): ?string
    {
        $clean = trim(preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $rawOutput) ?? '');
        if ($clean === '') {
            return null;
        }

        return Str::limit($clean, 1200);
    }
}
