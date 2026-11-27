<?php

use App\Models\Page;
use App\Models\SatkerSetting;
use App\Services\SimtepaJsonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('renders native SIMTEPA JSON profiles without exposing sensitive personal data and supports switching to original iframe', function (): void {
    Cache::flush();
    config(['satker.simtepa_fetch_in_tests' => true]);

    $token = 'a87bd82962cda7f528247a68ee4ac2a5';
    $jsonUrl = "https://simtepa.mahkamahagung.go.id/share/profil_ketua/json/{$token}";

    Http::fake([
        $jsonUrl => Http::response([
            [
                'nama_gelar' => 'Dr. H. Ahmad Hakim, S.H., M.H.',
                'jabatan' => 'Ketua Pengadilan Agama',
                'pangkat_ruang' => 'Pembina Utama Madya',
                'golongan' => 'IV/d',
                'tmt_jabatan_terakhir' => '2025-01-15',
                'FotoFormal' => '7853.jpg',
                'tempat_lahir' => 'RAHASIA-KOTA',
                'tgl_lahir' => '1970-01-01',
                'nip' => '197001011999031001',
                'riwayat_pendidikan' => '<ul><li>S3 Ilmu Hukum</li></ul><script>alert(1)</script>',
                'riwayat_pekerjaan' => '<p>Ketua PA Uji</p>',
            ],
        ], 200),
    ]);

    SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Uji',
        'simtepa_pegawai_url' => "https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/{$token}",
    ]);

    Page::factory()->create([
        'slug' => 'profil/pejabat-pegawai',
        'title' => 'Profil Pejabat & Pegawai',
        'is_published' => true,
        'blocks' => [
            [
                'type' => 'embed_fallback',
                'heading' => 'Daftar Pegawai SIMTEPA',
                'source_key' => 'simtepa_pegawai',
                'fallback_mode' => 'officials_table',
            ],
        ],
    ]);

    $this->get('/halaman/profil/pejabat-pegawai')
        ->assertOk()
        ->assertSee('lg:col-span-12', false)
        ->assertSee('Tampilan Ringkas SIMTEPA (1 aparatur)')
        ->assertSee('Dr. H. Ahmad Hakim, S.H., M.H.')
        ->assertSee('Ketua Pengadilan Agama')
        ->assertSee('Pembina Utama Madya (IV/d)')
        ->assertSee('https://img.badilag.net/cache/foto_formal/300/7853.jpg')
        ->assertSee('S3 Ilmu Hukum')
        ->assertDontSee('RAHASIA-KOTA')
        ->assertDontSee('1970-01-01')
        ->assertDontSee('197001011999031001')
        ->assertDontSee('<script>alert(1)</script>', false);

    // Explicit switch to original SIMTEPA iframe mode
    $this->get('/halaman/profil/pejabat-pegawai?embed_mode=iframe')
        ->assertOk()
        ->assertSee('Tampilan Ringkas (Native)')
        ->assertSee('Layar Penuh')
        ->assertSee('data-simtepa-iframe', false);
});

it('rejects non-SIMTEPA hosts in SimtepaJsonService and renders full-width SIPP stage with fullscreen controls', function (): void {
    $service = app(SimtepaJsonService::class);
    expect($service->isAllowedSimtepaJsonUrl('https://evil.example.test/share/profil_ketua/json/abc'))->toBeFalse()
        ->and($service->isAllowedSimtepaJsonUrl('http://simtepa.mahkamahagung.go.id/share/profil_ketua/json/abc'))->toBeFalse()
        ->and($service->isAllowedSimtepaJsonUrl('https://simtepa.mahkamahagung.go.id/share/profil_ketua/json/abc'))->toBeTrue();

    SatkerSetting::factory()->setupCompleted()->create([
        'nama_satker' => 'Pengadilan Agama Uji',
        'sipp_url' => 'https://sipp.pa-uji.go.id/',
    ]);

    Page::factory()->create([
        'slug' => 'layanan-hukum/sipp',
        'title' => 'Sistem Informasi Penelusuran Perkara',
        'is_published' => true,
        'blocks' => [
            [
                'type' => 'embed_fallback',
                'heading' => 'SIPP Online',
                'source_key' => 'sipp_perkara',
                'fallback_mode' => 'court_schedule',
            ],
        ],
    ]);

    $this->get('/halaman/layanan-hukum/sipp')
        ->assertOk()
        ->assertSee('lg:col-span-12', false)
        ->assertSee('Layar Penuh')
        ->assertSee('Adaptif')
        ->assertSee('Muat Ulang Embed')
        ->assertSee('https://sipp.pa-uji.go.id/');
});
