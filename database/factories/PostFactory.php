<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'category' => 'berita',
            'excerpt' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraphs(2, true).'</p>',
            'is_pinned' => false,
            'is_published' => true,
            'views_count' => 0,
            'comments_enabled' => true,
            'published_at' => now(),
        ];
    }
}
