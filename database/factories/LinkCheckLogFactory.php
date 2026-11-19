<?php

namespace Database\Factories;

use App\Models\LinkCheckLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkCheckLog>
 */
class LinkCheckLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_type' => 'criteria',
            'source_label' => '1.a.2.1 SIPP',
            'url' => 'https://example.go.id',
            'http_status' => 200,
            'is_broken' => false,
            'error_message' => null,
            'checked_at' => now(),
        ];
    }
}
