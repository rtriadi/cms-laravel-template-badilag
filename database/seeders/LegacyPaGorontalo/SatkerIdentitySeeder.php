<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use App\Models\SatkerSetting;
use Illuminate\Database\Seeder;

class SatkerIdentitySeeder extends Seeder
{
    public function run(): void
    {
        if (! LegacyBundleReader::exists('satker.json')) {
            return;
        }

        $payload = LegacyBundleReader::readJson('satker.json');

        SatkerSetting::withoutEvents(function () use ($payload): void {
            $setting = SatkerSetting::query()->first() ?? new SatkerSetting;

            $setting->fill([
                'nama_satker' => (string) ($payload['nama_satker'] ?? 'Pengadilan Agama Gorontalo'),
                'jenis_badan' => (string) ($payload['jenis_badan'] ?? 'PA'),
                'tingkat' => (string) ($payload['tingkat'] ?? 'pertama'),
                'is_tingkat_locked' => (bool) ($payload['is_tingkat_locked'] ?? true),
                'kode_satker' => $payload['kode_satker'] ?? null,
                'tahun_berdiri' => isset($payload['tahun_berdiri']) ? (int) $payload['tahun_berdiri'] : null,
                'alamat_jalan' => $payload['alamat_jalan'] ?? null,
                'desa_kelurahan' => $payload['desa_kelurahan'] ?? null,
                'kecamatan' => $payload['kecamatan'] ?? null,
                'kabupaten_kota' => $payload['kabupaten_kota'] ?? null,
                'provinsi' => $payload['provinsi'] ?? null,
                'kode_pos' => $payload['kode_pos'] ?? null,
                'telepon' => $payload['telepon'] ?? null,
                'email' => $payload['email'] ?? null,
                'website_url' => $payload['website_url'] ?? null,
                'logo_path' => $payload['logo_path'] ?? $setting->logo_path,
                'foto_gedung_path' => $payload['foto_gedung_path'] ?? $setting->foto_gedung_path,
                'sipp_url' => $payload['sipp_url'] ?? null,
                'ecourt_url' => $payload['ecourt_url'] ?? null,
                'direktori_putusan_url' => $payload['direktori_putusan_url'] ?? null,
                'siwas_url' => $payload['siwas_url'] ?? null,
                'simtepa_pegawai_url' => $payload['simtepa_pegawai_url'] ?? null,
                'simtepa_struktur_url' => $payload['simtepa_struktur_url'] ?? null,
                'skm_url' => $payload['skm_url'] ?? null,
                'spak_url' => $payload['spak_url'] ?? null,
                'is_setup_completed' => (bool) ($payload['is_setup_completed'] ?? true),
            ]);

            $setting->save();
        });
    }
}
