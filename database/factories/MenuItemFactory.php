<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => 'header',
            'title' => fake()->words(2, true),
            'type' => 'external',
            'url' => '/publikasi',
            'open_in_new_tab' => false,
            'is_visible' => true,
            'sort_order' => 1,
            'tingkat_scope' => 'all',
        ];
    }
}
