<?php

namespace App\Jobs;

use App\Support\MysqlDumpBinaryResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunDatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public const string STATUS_CACHE_KEY = 'satker.manual_backup_status';

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public ?int $requestedById = null,
        public string $requestedByName = 'Super Admin',
    ) {}

    public function handle(): void
    {
        self::storeStatus('running', 'Sedang membuat arsip backup database di latar belakang...', $this->requestedByName);
        self::ensureDumpBinaryConfigured();

        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);
            $output = trim(Artisan::output());
        } catch (Throwable $e) {
            report($e);
            $reason = self::summarizeFailure($e->getMessage());
            self::storeStatus('failed', 'Backup latar belakang gagal: '.$reason, $this->requestedByName);

            activity('backup')
                ->causedBy($this->requestedById)
                ->withProperties(['reason' => $reason, 'queued' => true])
                ->log('Backup database latar belakang gagal dijalankan');

            return;
        }

        if ($exitCode !== 0) {
            $reason = self::summarizeFailure($output);
            self::storeStatus('failed', 'Backup latar belakang gagal. '.$reason, $this->requestedByName);

            activity('backup')
                ->causedBy($this->requestedById)
                ->withProperties([
                    'exit_code' => $exitCode,
                    'reason' => $reason,
                    'queued' => true,
                ])
                ->log('Backup database latar belakang gagal dijalankan');

            return;
        }

        self::storeStatus('completed', 'Backup database latar belakang selesai. Muat ulang halaman untuk melihat arsip terbaru.', $this->requestedByName);

        activity('backup')
            ->causedBy($this->requestedById)
            ->withProperties(['queued' => true])
            ->log('Backup database latar belakang berhasil dibuat');
    }

    public static function storeStatus(string $state, string $message, string $actorName): void
    {
        Cache::put(self::STATUS_CACHE_KEY, [
            'state' => $state,
            'message' => $message,
            'actor' => $actorName,
            'updated_at' => now()->format('d M Y, H:i'),
        ], now()->addHours(24));
    }

    public static function ensureDumpBinaryConfigured(): void
    {
        $connection = (string) config('database.default', 'mysql');
        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            return;
        }

        $configured = (string) config("database.connections.{$connection}.dump.dump_binary_path", '');
        if ($configured !== '') {
            return;
        }

        $resolved = MysqlDumpBinaryResolver::resolve();
        if ($resolved !== '') {
            config()->set("database.connections.{$connection}.dump.dump_binary_path", $resolved);
        }
    }

    public static function summarizeFailure(string $output): string
    {
        $normalized = strtolower($output);

        if (str_contains($normalized, 'mysqldump') && (str_contains($normalized, 'not recognized') || str_contains($normalized, 'not found'))) {
            return 'Perintah mysqldump tidak ditemukan di peladen. Tetapkan MYSQL_DUMP_BINARY_PATH pada berkas .env ke direktori bin MySQL (misalnya C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin).';
        }

        if (str_contains($normalized, 'access denied')) {
            return 'Akses database ditolak saat dump. Periksa DB_USERNAME dan DB_PASSWORD pada berkas .env.';
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $output) ?: [])));
        foreach ($lines as $line) {
            if (stripos($line, 'Backup failed because:') !== false || stripos($line, 'Error') !== false) {
                return mb_strimwidth($line, 0, 220, '...');
            }
        }

        return 'Periksa log sistem di storage/logs/laravel.log untuk rincian teknis.';
    }
}
