<?php

namespace Database\Factories;

use App\Models\CourtSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtSchedule>
 */
class CourtScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal_sidang' => fake()->dateTimeBetween('now', '+14 days')->format('Y-m-d'),
            'nomor_perkara' => fake()->numerify('###/Pdt.G/2026/PA.Bdg'),
            'para_pihak' => 'Pemohon vs Termohon',
            'ruang_sidang' => 'Ruang Sidang '.fake()->numberBetween(1, 3),
            'agenda' => 'Sidang Pertama',
        ];
    }
}
