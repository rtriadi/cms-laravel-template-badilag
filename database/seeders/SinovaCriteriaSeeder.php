<?php

namespace Database\Seeders;

use App\Services\SinovaCriteriaImporter;
use Illuminate\Database\Seeder;

class SinovaCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/juknis-2026.json');
        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        app(SinovaCriteriaImporter::class)->import($payload);
    }
}
