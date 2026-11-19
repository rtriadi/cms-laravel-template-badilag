<?php

namespace Database\Factories;

use App\Models\Official;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Official>
 */
class OfficialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_gelar' => fake('id_ID')->name(),
            'jabatan' => 'Ketua Pengadilan',
            'kategori' => 'pimpinan',
            'pangkat_golongan' => 'Pembina Utama Muda (IV/c)',
            'tmt_jabatan' => '2025-01-01',
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
