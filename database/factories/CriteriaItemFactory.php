<?php

namespace Database\Factories;

use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CriteriaItem>
 */
class CriteriaItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'criteria_group_id' => CriteriaGroup::factory(),
            'code' => fake()->unique()->numerify('9.#.#.#'),
            'name' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'tingkat_scope' => 'all',
            'item_type' => 'page',
            'health_status' => 'empty',
            'estimated_score' => 0,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
