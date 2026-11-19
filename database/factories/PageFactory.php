<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'summary' => fake()->sentence(),
            'blocks' => [
                [
                    'type' => 'richtext',
                    'heading' => $title,
                    'content' => '<p>'.fake()->paragraph().'</p>',
                ],
            ],
            'category_tag' => 'berkala',
            'tingkat_scope' => 'all',
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
