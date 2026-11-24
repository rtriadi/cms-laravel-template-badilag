<?php

declare(strict_types=1);

use App\Services\LegacyMigration\JoomlaSqlStreamParser;

test('extracts selected tables with multi-line inserts, escaped quotes, and semicolons inside html', function (): void {
    $tmpSql = tempnam(sys_get_temp_dir(), 'joomla_sql_');
    expect($tmpSql)->not->toBeFalse();

    $sql = <<<'SQL'
CREATE TABLE `ydfui_skipped` (
  `id` int(11) NOT NULL,
  `payload` text
);
INSERT INTO `ydfui_skipped` (`id`, `payload`) VALUES
(1, 'should not be loaded; really;'),
(2, 'ignored row');

CREATE TABLE `ydfui_content` (
  `id` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `introtext` mediumtext NOT NULL,
  `hits` int(10) DEFAULT NULL
);

INSERT INTO `ydfui_content` (`id`, `title`, `introtext`, `hits`) VALUES
(10, 'Jum\'at Berkah', '<p style="color: #000000; font-weight: bold;">Baris 1;\nBaris 2</p>', 42),
(11, 'Artikel ''Kutipan'' Ganda', '<p>Isi normal</p>', NULL);
SQL;

    file_put_contents($tmpSql, $sql);

    try {
        $parser = new JoomlaSqlStreamParser;
        $tables = $parser->extractTables($tmpSql, ['ydfui_content']);

        expect($tables)->toHaveKey('ydfui_content')
            ->and($tables['ydfui_content'])->toHaveCount(2)
            ->and($tables['ydfui_content'][0]['id'])->toBe(10)
            ->and($tables['ydfui_content'][0]['title'])->toBe("Jum'at Berkah")
            ->and($tables['ydfui_content'][0]['introtext'])->toContain('color: #000000;')
            ->and($tables['ydfui_content'][0]['introtext'])->toContain("\nBaris 2")
            ->and($tables['ydfui_content'][0]['hits'])->toBe(42)
            ->and($tables['ydfui_content'][1]['title'])->toBe("Artikel 'Kutipan' Ganda")
            ->and($tables['ydfui_content'][1]['hits'])->toBeNull();
    } finally {
        @unlink($tmpSql);
    }
});

test('aggregates visitor counter rows per date in Asia/Makassar timezone', function (): void {
    $tmpSql = tempnam(sys_get_temp_dir(), 'joomla_visits_');
    expect($tmpSql)->not->toBeFalse();

    // 2026-05-01 00:30:00 WITA = 2026-04-30 16:30:00 UTC = 1777566600
    // 2026-05-01 10:00:00 WITA = 2026-05-01 02:00:00 UTC = 1777600800
    // 2026-05-02 09:00:00 WITA = 2026-05-02 01:00:00 UTC = 1777683600
    $sql = <<<'SQL'
INSERT INTO `ydfui_zt_visitor_counter` (`id`, `timestamp`, `visits`, `guests`, `bots`, `members`, `ipaddress`, `useragent`) VALUES
(1, 1777566600, 1, 1, 0, 0, '1.1.1.1', 'chrome'),
(2, 1777600800, 3, 1, 0, 0, '1.1.1.2', 'firefox'),
(3, 1777683600, 2, 1, 0, 0, '1.1.1.3', 'safari'),
(4, 100000000, 99, 1, 0, 0, '1.1.1.4', 'ancient');
SQL;

    file_put_contents($tmpSql, $sql);

    try {
        $parser = new JoomlaSqlStreamParser;
        $daily = $parser->aggregateVisitorCounterByDate($tmpSql, 'ydfui_zt_visitor_counter', 'Asia/Makassar');

        expect($daily)->toBe([
            '2026-05-01' => 4,
            '2026-05-02' => 2,
        ]);
    } finally {
        @unlink($tmpSql);
    }
});
