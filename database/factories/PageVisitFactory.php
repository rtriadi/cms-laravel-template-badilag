<?php

namespace Database\Factories;

use App\Models\PageVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageVisit>
 */
class PageVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'path' => '/halaman/'.fake()->unique()->slug(2),
            'visits_count' => fake()->numberBetween(1, 250),
        ];
    }
}
