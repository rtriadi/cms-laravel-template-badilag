<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageVisitsSeeder extends Seeder
{
    public function run(): void
    {
        if (! LegacyBundleReader::exists('page_visits.json')) {
            return;
        }

        $visits = LegacyBundleReader::readJson('page_visits.json');
        if ($visits === []) {
            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($visits as $item) {
            if (! is_array($item) || empty($item['visit_date'])) {
                continue;
            }

            $date = (string) $item['visit_date'];
            $path = (string) ($item['path'] ?? '/');
            $count = max(1, (int) ($item['visits_count'] ?? 1));

            $rows[$date.'|'.$path] = [
                'visit_date' => $date,
                'path' => $path,
                'visits_count' => $count,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('page_visits')->upsert(
                $chunk,
                ['visit_date', 'path'],
                ['visits_count', 'updated_at'],
            );
        }
    }
}
