<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_path' => '/halaman/lama-'.fake()->unique()->slug(1),
            'to_path' => '/halaman/baru-'.fake()->slug(1),
            'status_code' => 301,
            'hits' => 0,
        ];
    }
}
