<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'collection_type' => 'lkjip',
            'title' => 'Dokumen '.fake()->words(3, true),
            'year' => (int) now()->year,
            'month' => null,
            'status_label' => 'Final',
            'is_published' => true,
        ];
    }
}
