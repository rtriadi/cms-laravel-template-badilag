<?php

namespace Database\Factories;

use App\Models\CaseStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseStatistic>
 */
class CaseStatisticFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prev = fake()->numberBetween(5, 40);
        $in = fake()->numberBetween(20, 120);
        $out = fake()->numberBetween(15, $prev + $in);

        return [
            'year' => fake()->numberBetween(2020, 2026),
            'month' => fake()->unique()->numberBetween(1, 12),
            'sisa_bulan_lalu' => $prev,
            'perkara_masuk' => $in,
            'perkara_putus' => $out,
        ];
    }
}
