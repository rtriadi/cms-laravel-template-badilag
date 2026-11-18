<?php

namespace Database\Factories;

use App\Models\SatkerSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SatkerSetting>
 */
class SatkerSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kota = fake('id_ID')->city();

        return [
            'nama_satker' => "Pengadilan Agama {$kota}",
            'jenis_badan' => 'PA',
            'tingkat' => 'pertama',
            'is_tingkat_locked' => false,
            'kode_satker' => (string) fake()->numerify('401###'),
            'tahun_berdiri' => fake()->numberBetween(1980, 2024),
            'alamat_jalan' => fake('id_ID')->streetAddress(),
            'desa_kelurahan' => 'Sukamaju',
            'kecamatan' => 'Tengah',
            'kabupaten_kota' => $kota,
            'provinsi' => 'Jawa Barat',
            'kode_pos' => fake()->numerify('4####'),
            'telepon' => fake('id_ID')->phoneNumber(),
            'email' => 'info@pa-example.go.id',
            'website_url' => 'https://pa-example.go.id',
            'sipp_url' => 'https://sipp.pa-example.go.id',
            'ecourt_url' => 'https://ecourt.mahkamahagung.go.id',
            'direktori_putusan_url' => 'https://putusan3.mahkamahagung.go.id',
            'siwas_url' => 'https://siwas.mahkamahagung.go.id',
            'is_setup_completed' => false,
        ];
    }

    public function banding(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nama_satker' => 'Pengadilan Tinggi Agama Bandung',
            'jenis_badan' => 'PTA',
            'tingkat' => 'banding',
            'sipp_url' => 'https://banding.mahkamahagung.go.id/',
        ]);
    }

    public function setupCompleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_tingkat_locked' => true,
            'is_setup_completed' => true,
        ]);
    }
}
