<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use App\Models\Redirect;
use Illuminate\Database\Seeder;

class RedirectsSeeder extends Seeder
{
    public function run(): void
    {
        if (! LegacyBundleReader::exists('redirects.json')) {
            return;
        }

        $redirects = LegacyBundleReader::readJson('redirects.json');
        if ($redirects === []) {
            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($redirects as $item) {
            if (! is_array($item)) {
                continue;
            }

            $from = '/'.ltrim(trim((string) ($item['from_path'] ?? '')), '/');
            $to = trim((string) ($item['to_path'] ?? ''));

            if ($from === '/' || $to === '' || $from === $to || strlen($from) > 255 || strlen($to) > 255) {
                continue;
            }

            $rows[$from] = [
                'from_path' => $from,
                'to_path' => $to,
                'status_code' => (int) ($item['status_code'] ?? 301),
                'hits' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        Redirect::withoutEvents(function () use ($rows): void {
            foreach (array_chunk(array_values($rows), 250) as $chunk) {
                Redirect::query()->upsert(
                    $chunk,
                    ['from_path'],
                    ['to_path', 'status_code', 'updated_at'],
                );
            }
        });
    }
}
