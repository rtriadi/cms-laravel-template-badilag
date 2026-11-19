<?php

namespace Database\Factories;

use App\Models\HomeSlider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeSlider>
 */
class HomeSliderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Layanan {{satker.nama}}',
            'subtitle' => 'Portal informasi dan pelayanan publik satuan kerja.',
            'image_alt' => 'Banner layanan {{satker.nama}}',
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
