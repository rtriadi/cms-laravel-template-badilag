<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostComment>
 */
class PostCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'parent_id' => null,
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'body' => fake()->sentence(12),
            'status' => PostComment::STATUS_APPROVED,
            'is_official_reply' => false,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'moderated_by' => null,
            'moderated_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PostComment::STATUS_PENDING,
            'moderated_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => PostComment::STATUS_APPROVED,
            'moderated_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => PostComment::STATUS_REJECTED,
            'moderated_at' => now(),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (): array => [
            'status' => PostComment::STATUS_SPAM,
            'moderated_at' => now(),
        ]);
    }

    public function officialReply(): static
    {
        return $this->state(fn (): array => [
            'is_official_reply' => true,
            'status' => PostComment::STATUS_APPROVED,
            'author_email' => null,
            'moderated_at' => now(),
        ]);
    }
}
