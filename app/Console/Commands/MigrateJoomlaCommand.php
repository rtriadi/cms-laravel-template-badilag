<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\LegacyPaGorontalo\LegacyBundleReader;
use Database\Seeders\PaGorontaloMigrationSeeder;
use Illuminate\Console\Command;

class MigrateJoomlaCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'satker:migrate-joomla
        {--source=D:\\Project\\#CMS JOOMLA : Direktori root proyek Joomla 3 lama}
        {--sql= : Path berkas SQL dump (default: <source>/pagoron1_db1.sql)}
        {--output= : Direktori bundle JSON (default: database/data/legacy-pa-gorontalo)}
        {--prefix=ydfui_ : Prefix tabel Joomla}
        {--skip-prepare : Langsung jalankan seeder dari bundle JSON yang sudah ada}
        {--skip-visits : Lewati agregasi tabel zt_visitor_counter}
        {--dry-run : Hanya simulasi ekstraksi tanpa mengubah database atau media}';

    /**
     * @var string
     */
    protected $description = 'Jalankan migrasi penuh Joomla PA Gorontalo → SatkerCMS sekaligus (ekstraksi media + seeding seluruh modul)';

    public function handle(): int
    {
        $outputOption = trim((string) ($this->option('output') ?? ''));
        $outputDir = $outputOption !== '' ? $outputOption : database_path('data/legacy-pa-gorontalo');
        $dryRun = (bool) $this->option('dry-run');

        if (! (bool) $this->option('skip-prepare')) {
            $exitCode = $this->call('migration:prepare-joomla', array_filter([
                '--source' => (string) $this->option('source'),
                '--sql' => $this->option('sql') ?: null,
                '--output' => $outputDir,
                '--prefix' => (string) $this->option('prefix'),
                '--skip-visits' => (bool) $this->option('skip-visits'),
                '--dry-run' => $dryRun,
            ], static fn (mixed $v): bool => $v !== null && $v !== false));

            if ($exitCode !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if ($dryRun) {
            $this->comment('[DRY RUN] Tahap seeding database dilewati.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Menjalankan PaGorontaloMigrationSeeder untuk memasukkan seluruh data ke database SatkerCMS...');

        LegacyBundleReader::setBasePath($outputDir);

        try {
            $exitCode = $this->call('db:seed', [
                '--class' => PaGorontaloMigrationSeeder::class,
                '--force' => true,
            ]);
        } finally {
            LegacyBundleReader::resetBasePath();
        }

        if ($exitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('Migrasi penuh Joomla PA Gorontalo → SatkerCMS berhasil diselesaikan!');

        return self::SUCCESS;
    }
}
