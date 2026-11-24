<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LegacyMigration\PaGorontaloMigrationExtractor;
use Illuminate\Console\Command;

class PrepareJoomlaMigrationCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'migration:prepare-joomla
        {--source=D:\\Project\\#CMS JOOMLA : Direktori root proyek Joomla 3 lama}
        {--sql= : Path berkas SQL dump (default: <source>/pagoron1_db1.sql)}
        {--output= : Direktori output bundle JSON (default: database/data/legacy-pa-gorontalo)}
        {--prefix=ydfui_ : Prefix tabel Joomla di berkas SQL}
        {--disk=public : Disk Laravel Storage tujuan untuk media hasil sanitasi}
        {--skip-visits : Lewati agregasi tabel zt_visitor_counter}
        {--dry-run : Simulasi ekstraksi tanpa menulis berkas JSON atau media}';

    /**
     * @var string
     */
    protected $description = 'Ekstrak data Joomla PA Gorontalo (pagoron1_db1.sql), sanitasi & konversi media ke WebP, dan hasilkan bundle JSON untuk Seeder';

    public function handle(PaGorontaloMigrationExtractor $extractor): int
    {
        $source = rtrim((string) $this->option('source'), '\\/');
        $sqlOption = trim((string) ($this->option('sql') ?? ''));
        $sqlPath = $sqlOption !== '' ? $sqlOption : $source.DIRECTORY_SEPARATOR.'pagoron1_db1.sql';
        $outputOption = trim((string) ($this->option('output') ?? ''));
        $outputDir = $outputOption !== '' ? $outputOption : database_path('data/legacy-pa-gorontalo');
        $prefix = (string) $this->option('prefix');
        $disk = (string) $this->option('disk');
        $dryRun = (bool) $this->option('dry-run');
        $skipVisits = (bool) $this->option('skip-visits');

        if (! is_dir($source)) {
            $this->error("Direktori sumber Joomla tidak ditemukan: {$source}");

            return self::FAILURE;
        }

        if (! is_file($sqlPath)) {
            $this->error("Berkas SQL dump tidak ditemukan: {$sqlPath}");

            return self::FAILURE;
        }

        $this->info('Memulai persiapan migrasi Joomla PA Gorontalo'.($dryRun ? ' [DRY RUN]' : '').'...');

        $report = $extractor->extract(
            joomlaRoot: $source,
            sqlPath: $sqlPath,
            outputDir: $outputDir,
            tablePrefix: $prefix,
            disk: $disk,
            dryRun: $dryRun,
            skipVisits: $skipVisits,
            progress: fn (string $msg) => $this->line("  → {$msg}"),
        );

        $counts = $report['counts'];
        $media = $report['media'];

        $this->newLine();
        $this->table(
            ['Modul', 'Jumlah'],
            [
                ['Satker Settings', (string) $counts['satker_settings']],
                ['Home Sliders', "{$counts['sliders_total']} ({$counts['sliders_active']} aktif)"],
                ['Pages (SINOVA + Ekstra)', (string) $counts['pages_total']],
                ['Extra Submenus', (string) $counts['extra_menus']],
                ['Documents (PDF Lokal + GDrive)', (string) $counts['documents_total']],
                ['Posts (Publikasi)', (string) $counts['posts_total']],
                ['Redirects (301)', (string) $counts['redirects_total']],
                ['Page Visits (Hari)', "{$counts['page_visits_days']} hari (".number_format((int) $counts['page_visits_sum']).' kunjungan)'],
                ['Sample Joomla Dibuang', (string) $counts['sample_articles_excluded']],
            ],
        );

        $this->info(sprintf(
            'Media: %d gambar baru, %d PDF lokal, %d dedup, %d skip eksisting, %d ditolak keamanan (%.2f MB -> %.2f MB, hemat %.1f%%).',
            $media['images_processed'],
            $media['pdfs_processed'],
            $media['dedup_hits'],
            $media['skipped_existing'],
            $media['rejected_count'],
            $media['before_bytes'] / 1048576,
            $media['after_bytes'] / 1048576,
            $media['saved_percent'],
        ));

        if (! $dryRun) {
            $this->info("Bundle JSON & laporan audit tersimpan di: {$outputDir}");
        }

        $this->info("Selesai dalam {$report['duration_seconds']} detik.");

        return self::SUCCESS;
    }
}
