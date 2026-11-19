<?php

namespace Database\Factories;

use App\Models\CriteriaGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CriteriaGroup>
 */
class CriteriaGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'juknis_version' => '2026',
            'aspect' => 'konten',
            'code' => fake()->unique()->numerify('9.#.#'),
            'name' => fake()->words(3, true),
            'weight' => 10.0,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
