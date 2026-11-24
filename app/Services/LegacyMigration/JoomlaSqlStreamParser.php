<?php

declare(strict_types=1);

namespace App\Services\LegacyMigration;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class JoomlaSqlStreamParser
{
    /**
     * Stream-parse selected tables from a phpMyAdmin / mysqldump SQL file without loading the entire dump into memory.
     *
     * @param  list<string>  $targetTables
     * @return array<string, list<array<string, mixed>>>
     */
    public function extractTables(string $sqlPath, array $targetTables): array
    {
        if (! is_file($sqlPath) || ! is_readable($sqlPath)) {
            throw new RuntimeException("Berkas SQL tidak ditemukan atau tidak dapat dibaca: {$sqlPath}");
        }

        $targetLookup = array_fill_keys($targetTables, true);
        $tableColumns = [];
        $results = [];
        foreach ($targetTables as $table) {
            $results[$table] = [];
        }

        $handle = fopen($sqlPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Gagal membuka berkas SQL: {$sqlPath}");
        }

        $currentCreateTable = null;
        $activeInsertTable = null;
        /** @var list<string> $activeInsertCols */
        $activeInsertCols = [];
        $skippingInsert = false;
        $buffer = '';

        try {
            while (($line = fgets($handle)) !== false) {
                if ($currentCreateTable !== null) {
                    if (str_starts_with(ltrim($line), ')')) {
                        $currentCreateTable = null;

                        continue;
                    }

                    if (preg_match('/^\s+`([^`]+)`\s+/', $line, $colMatch) === 1) {
                        $tableColumns[$currentCreateTable][] = $colMatch[1];
                    }

                    continue;
                }

                if ($skippingInsert) {
                    if ($this->endsStatementOutsideQuotes($line)) {
                        $skippingInsert = false;
                    }

                    continue;
                }

                if ($activeInsertTable !== null) {
                    $buffer .= $line;

                    if ($this->endsStatementOutsideQuotes($buffer)) {
                        $this->appendParsedTuples($buffer, $activeInsertCols, $results[$activeInsertTable]);
                        $activeInsertTable = null;
                        $activeInsertCols = [];
                        $buffer = '';
                    }

                    continue;
                }

                if (preg_match('/^CREATE TABLE(?: IF NOT EXISTS)?\s+`([^`]+)`/i', $line, $createMatch) === 1) {
                    $currentCreateTable = $createMatch[1];
                    $tableColumns[$currentCreateTable] = [];

                    continue;
                }

                if (preg_match('/^INSERT(?: IGNORE)? INTO `([^`]+)`\s*(?:\(([^)]+)\))?\s*VALUES\s*(.*)$/is', $line, $insertMatch) === 1) {
                    $table = $insertMatch[1];
                    $colsRaw = $insertMatch[2] ?? '';
                    $rest = $insertMatch[3] ?? '';

                    if (! isset($targetLookup[$table])) {
                        if (! $this->endsStatementOutsideQuotes($rest)) {
                            $skippingInsert = true;
                        }

                        continue;
                    }

                    if (trim($colsRaw) !== '') {
                        $activeInsertCols = array_map(
                            static fn (string $c): string => trim($c, " \t\n\r\0\x0B`"),
                            explode(',', $colsRaw),
                        );
                    } else {
                        $activeInsertCols = $tableColumns[$table] ?? [];
                    }

                    if ($this->endsStatementOutsideQuotes($rest)) {
                        $this->appendParsedTuples($rest, $activeInsertCols, $results[$table]);
                        $activeInsertCols = [];
                    } else {
                        $activeInsertTable = $table;
                        $buffer = $rest;
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        return $results;
    }

    /**
     * Stream-aggregate daily visit totals from `ydfui_zt_visitor_counter` (or equivalent table)
     * using minimal memory even for 2.5M+ rows.
     *
     * @return array<string, int> Map of 'YYYY-MM-DD' => total_visits sorted ascending by date
     */
    public function aggregateVisitorCounterByDate(
        string $sqlPath,
        string $tableName = 'ydfui_zt_visitor_counter',
        string $timezone = 'Asia/Makassar',
    ): array {
        if (! is_file($sqlPath) || ! is_readable($sqlPath)) {
            throw new RuntimeException("Berkas SQL tidak ditemukan atau tidak dapat dibaca: {$sqlPath}");
        }

        $handle = fopen($sqlPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Gagal membuka berkas SQL: {$sqlPath}");
        }

        $tz = new DateTimeZone($timezone);
        $offsetSeconds = $tz->getOffset(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $minTimestamp = strtotime('2015-01-01 00:00:00 UTC');
        $maxTimestamp = time() + 86400;

        /** @var array<string, int> $dailyTotals */
        $dailyTotals = [];
        $inTargetInsert = false;
        $insertPrefix = "INSERT INTO `{$tableName}`";
        $insertIgnorePrefix = "INSERT IGNORE INTO `{$tableName}`";

        try {
            while (($line = fgets($handle)) !== false) {
                if (! $inTargetInsert) {
                    if (str_starts_with($line, $insertPrefix) || str_starts_with($line, $insertIgnorePrefix)) {
                        $inTargetInsert = true;
                        $this->collectVisitorMatches($line, $offsetSeconds, (int) $minTimestamp, $maxTimestamp, $dailyTotals);
                        if (str_ends_with(rtrim($line), ';')) {
                            $inTargetInsert = false;
                        }
                    }

                    continue;
                }

                $this->collectVisitorMatches($line, $offsetSeconds, (int) $minTimestamp, $maxTimestamp, $dailyTotals);

                if (str_ends_with(rtrim($line), ';')) {
                    $inTargetInsert = false;
                }
            }
        } finally {
            fclose($handle);
        }

        ksort($dailyTotals);

        return $dailyTotals;
    }

    /**
     * Parse SQL VALUES tuple list into associative rows.
     *
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $output
     */
    private function appendParsedTuples(string $valuesSql, array $columns, array &$output): void
    {
        $len = strlen($valuesSql);
        $i = 0;

        while ($i < $len) {
            while ($i < $len && $valuesSql[$i] !== '(') {
                $i++;
            }

            if ($i >= $len) {
                break;
            }

            $i++; // skip '('
            $rowValues = [];

            while ($i < $len) {
                while ($i < $len && ($valuesSql[$i] === ' ' || $valuesSql[$i] === "\t" || $valuesSql[$i] === "\r" || $valuesSql[$i] === "\n")) {
                    $i++;
                }

                if ($i >= $len) {
                    break;
                }

                if ($valuesSql[$i] === ')') {
                    $i++;

                    break;
                }

                if ($valuesSql[$i] === "'") {
                    $i++; // skip opening quote
                    $str = '';
                    while ($i < $len) {
                        $ch = $valuesSql[$i];
                        if ($ch === '\\' && $i + 1 < $len) {
                            $nxt = $valuesSql[$i + 1];
                            $str .= match ($nxt) {
                                'n' => "\n",
                                'r' => "\r",
                                't' => "\t",
                                '0' => "\0",
                                'b' => "\x08",
                                'Z' => "\x1A",
                                default => $nxt,
                            };
                            $i += 2;
                        } elseif ($ch === "'") {
                            if ($i + 1 < $len && $valuesSql[$i + 1] === "'") {
                                $str .= "'";
                                $i += 2;
                            } else {
                                $i++;

                                break;
                            }
                        } else {
                            $str .= $ch;
                            $i++;
                        }
                    }

                    $rowValues[] = $str;

                    while ($i < $len && ($valuesSql[$i] === ' ' || $valuesSql[$i] === "\t" || $valuesSql[$i] === "\r" || $valuesSql[$i] === "\n")) {
                        $i++;
                    }

                    if ($i < $len && $valuesSql[$i] === ',') {
                        $i++;
                    } elseif ($i < $len && $valuesSql[$i] === ')') {
                        $i++;

                        break;
                    }
                } else {
                    $start = $i;
                    while ($i < $len && $valuesSql[$i] !== ',' && $valuesSql[$i] !== ')') {
                        $i++;
                    }

                    $token = trim(substr($valuesSql, $start, $i - $start));
                    if (strcasecmp($token, 'NULL') === 0) {
                        $rowValues[] = null;
                    } elseif (preg_match('/^-?\d+$/', $token) === 1) {
                        $rowValues[] = (int) $token;
                    } elseif (is_numeric($token)) {
                        $rowValues[] = (float) $token;
                    } else {
                        $rowValues[] = $token;
                    }

                    if ($i < $len && $valuesSql[$i] === ',') {
                        $i++;
                    } elseif ($i < $len && $valuesSql[$i] === ')') {
                        $i++;

                        break;
                    }
                }
            }

            if ($rowValues !== []) {
                $assoc = [];
                foreach ($rowValues as $idx => $val) {
                    $key = $columns[$idx] ?? (string) $idx;
                    $assoc[$key] = $val;
                }
                $output[] = $assoc;
            }
        }
    }

    /**
     * Verify whether a SQL fragment ends with a semicolon outside any single-quoted string literal.
     */
    private function endsStatementOutsideQuotes(string $sql): bool
    {
        $trimmed = rtrim($sql);
        if (! str_ends_with($trimmed, ';')) {
            return false;
        }

        $len = strlen($trimmed);
        $inQuote = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $trimmed[$i];
            if ($inQuote) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $i++;
                } elseif ($ch === "'") {
                    if ($i + 1 < $len && $trimmed[$i + 1] === "'") {
                        $i++;
                    } else {
                        $inQuote = false;
                    }
                }
            } elseif ($ch === "'") {
                $inQuote = true;
            }
        }

        return ! $inQuote;
    }

    /**
     * @param  array<string, int>  $dailyTotals
     */
    private function collectVisitorMatches(
        string $line,
        int $offsetSeconds,
        int $minTimestamp,
        int $maxTimestamp,
        array &$dailyTotals,
    ): void {
        if (preg_match_all('/\(\s*\d+\s*,\s*(\d{9,11})\s*,\s*(\d+)\s*,/', $line, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $m) {
                $ts = (int) $m[1];
                if ($ts < $minTimestamp || $ts > $maxTimestamp) {
                    continue;
                }

                $visits = max(1, (int) $m[2]);
                $dateKey = gmdate('Y-m-d', $ts + $offsetSeconds);
                $dailyTotals[$dateKey] = ($dailyTotals[$dateKey] ?? 0) + $visits;
            }
        }
    }
}
