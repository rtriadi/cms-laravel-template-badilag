<?php

namespace App\Livewire\Admin;

use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\ImageOptimizerService;
use Database\Seeders\SatkerDefaultContentSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class SatkerSettingsManager extends Component
{
    use WithFileUploads;

    public string $nama_satker = '';

    public string $jenis_badan = 'PA';

    public string $tingkat = 'pertama';

    public string $kode_satker = '';

    public ?int $tahun_berdiri = null;

    public string $alamat_jalan = '';

    public string $desa_kelurahan = '';

    public string $kecamatan = '';

    public string $kabupaten_kota = '';

    public string $provinsi = '';

    public string $kode_pos = '';

    public string $telepon = '';

    public string $email = '';

    public string $website_url = '';

    public string $sipp_url = '';

    public string $ecourt_url = '';

    public string $direktori_putusan_url = '';

    public string $siwas_url = '';

    public string $simtepa_pegawai_url = '';

    public string $simtepa_struktur_url = '';

    public string $skm_url = '';

    public string $spak_url = '';

    public mixed $logo = null;

    public mixed $foto_gedung = null;

    public ?string $existingLogoPath = null;

    public ?string $existingFotoGedungPath = null;

    // Ubah tingkat peradilan (konfirmasi ganda Super Admin)
    public bool $showTingkatModal = false;

    public string $pendingTingkat = 'pertama';

    public string $confirmationPhrase = '';

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->fillFromSetting(SatkerSetting::current());
    }

    public function save(): void
    {
        $this->authorizeAccess();

        foreach (['simtepa_pegawai_url', 'simtepa_struktur_url'] as $simField) {
            $trimmedSim = trim($this->{$simField});
            if (preg_match('/^[a-f0-9]{32}$/i', $trimmedSim) === 1) {
                $this->{$simField} = 'https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/'.strtolower($trimmedSim);
            }
        }

        $this->validate([
            'nama_satker' => ['required', 'string', 'max:255'],
            'jenis_badan' => ['required', 'in:PA,PTA,MS,MS_ACEH'],
            'kode_satker' => ['nullable', 'string', 'max:32'],
            'tahun_berdiri' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y'))],
            'alamat_jalan' => ['nullable', 'string', 'max:255'],
            'desa_kelurahan' => ['nullable', 'string', 'max:120'],
            'kecamatan' => ['nullable', 'string', 'max:120'],
            'kabupaten_kota' => ['nullable', 'string', 'max:120'],
            'provinsi' => ['nullable', 'string', 'max:120'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'telepon' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:160'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'sipp_url' => ['nullable', 'url:http,https', 'max:500'],
            'ecourt_url' => ['nullable', 'url:http,https', 'max:500'],
            'direktori_putusan_url' => ['nullable', 'url:http,https', 'max:500'],
            'siwas_url' => ['nullable', 'url:http,https', 'max:500'],
            'simtepa_pegawai_url' => ['nullable', 'url:http,https', 'max:500'],
            'simtepa_struktur_url' => ['nullable', 'url:http,https', 'max:500'],
            'skm_url' => ['nullable', 'url:http,https', 'max:500'],
            'spak_url' => ['nullable', 'url:http,https', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048', 'dimensions:max_width=8000,max_height=8000'],
            'foto_gedung' => ['nullable', 'image', 'max:4096', 'dimensions:max_width=8000,max_height=8000'],
        ], [
            'nama_satker.required' => 'Nama satuan kerja wajib diisi.',
            'email.email' => 'Format surel resmi satuan kerja tidak valid.',
        ]);

        $setting = SatkerSetting::current() ?? new SatkerSetting;

        if ($this->logo !== null && ImageOptimizerService::exceedsPixelLimit($this->logo)) {
            $this->addError('logo', 'Total piksel logo melebihi batas aman 25 megapiksel.');

            return;
        }

        if ($this->foto_gedung !== null && ImageOptimizerService::exceedsPixelLimit($this->foto_gedung)) {
            $this->addError('foto_gedung', 'Total piksel foto gedung melebihi batas aman 25 megapiksel.');

            return;
        }

        $optimizer = app(ImageOptimizerService::class);

        $oldLogoPath = $setting->logo_path;
        $logoPath = $oldLogoPath;
        if ($this->logo !== null) {
            $logoPath = $optimizer->storeOptimized($this->logo, 'satker', 512, 88, 'public', [128]);
        }

        $oldFotoPath = $setting->foto_gedung_path;
        $fotoPath = $oldFotoPath;
        if ($this->foto_gedung !== null) {
            $fotoPath = $optimizer->storeOptimized($this->foto_gedung, 'satker', 1280, 82, 'public', [480, 800]);
        }

        $setting->fill([
            'nama_satker' => trim($this->nama_satker),
            'jenis_badan' => $this->jenis_badan,
            'kode_satker' => trim($this->kode_satker) ?: null,
            'tahun_berdiri' => $this->tahun_berdiri ?: null,
            'alamat_jalan' => trim($this->alamat_jalan) ?: null,
            'desa_kelurahan' => trim($this->desa_kelurahan) ?: null,
            'kecamatan' => trim($this->kecamatan) ?: null,
            'kabupaten_kota' => trim($this->kabupaten_kota) ?: null,
            'provinsi' => trim($this->provinsi) ?: null,
            'kode_pos' => trim($this->kode_pos) ?: null,
            'telepon' => trim($this->telepon) ?: null,
            'email' => trim($this->email) ?: null,
            'website_url' => trim($this->website_url) ?: null,
            'logo_path' => $logoPath,
            'foto_gedung_path' => $fotoPath,
            'sipp_url' => trim($this->sipp_url) ?: null,
            'ecourt_url' => trim($this->ecourt_url) ?: null,
            'direktori_putusan_url' => trim($this->direktori_putusan_url) ?: null,
            'siwas_url' => trim($this->siwas_url) ?: null,
            'simtepa_pegawai_url' => trim($this->simtepa_pegawai_url) ?: null,
            'simtepa_struktur_url' => trim($this->simtepa_struktur_url) ?: null,
            'skm_url' => trim($this->skm_url) ?: null,
            'spak_url' => trim($this->spak_url) ?: null,
        ]);

        if (! $setting->exists) {
            $setting->tingkat = $this->tingkat;
            $setting->is_tingkat_locked = true;
            $setting->is_setup_completed = true;
        }

        $setting->save();

        if ($this->logo !== null && is_string($oldLogoPath) && $oldLogoPath !== '' && $oldLogoPath !== $logoPath) {
            $optimizer->deleteWithVariants($oldLogoPath, [128]);
        }

        if ($this->foto_gedung !== null && is_string($oldFotoPath) && $oldFotoPath !== '' && $oldFotoPath !== $fotoPath) {
            $optimizer->deleteWithVariants($oldFotoPath, [480, 800]);
        }

        $this->logo = null;
        $this->foto_gedung = null;
        $this->existingLogoPath = $setting->logo_path;
        $this->existingFotoGedungPath = $setting->foto_gedung_path;
        $this->statusNotice = 'Identitas satuan kerja dan tautan integrasi berhasil diperbarui.';
    }

    public function openTingkatModal(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('super_admin'), 403);

        $this->pendingTingkat = $this->tingkat === 'pertama' ? 'banding' : 'pertama';
        $this->confirmationPhrase = '';
        $this->showTingkatModal = true;
    }

    public function closeTingkatModal(): void
    {
        $this->showTingkatModal = false;
        $this->confirmationPhrase = '';
        $this->resetValidation('confirmationPhrase');
    }

    public function confirmChangeTingkat(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('super_admin'), 403);

        $this->validate([
            'pendingTingkat' => ['required', 'in:pertama,banding'],
            'confirmationPhrase' => ['required', 'string'],
        ]);

        $expected = strtoupper($this->pendingTingkat);
        if (strtoupper(trim($this->confirmationPhrase)) !== $expected) {
            $this->addError('confirmationPhrase', "Ketik '{$expected}' untuk mengonfirmasi perubahan tingkat peradilan.");

            return;
        }

        $setting = SatkerSetting::current();
        if ($setting === null) {
            $this->addError('confirmationPhrase', 'Pengaturan satker belum tersedia.');

            return;
        }

        DB::transaction(function () use ($setting, $user): void {
            $setting->changeTingkat($this->pendingTingkat, $user);
            app(SatkerDefaultContentSeeder::class)->run($this->pendingTingkat);
        });

        $this->tingkat = $this->pendingTingkat;
        $this->showTingkatModal = false;
        $this->confirmationPhrase = '';
        $this->statusNotice = 'Tingkat peradilan berhasil diperbarui menjadi '.config("satker.tingkat.{$this->tingkat}", $this->tingkat).' dan struktur menu bawaan disesuaikan.';
    }

    private function fillFromSetting(?SatkerSetting $setting): void
    {
        if ($setting === null) {
            return;
        }

        $this->nama_satker = $setting->nama_satker;
        $this->jenis_badan = $setting->jenis_badan;
        $this->tingkat = $setting->tingkat;
        $this->kode_satker = (string) ($setting->kode_satker ?? '');
        $this->tahun_berdiri = $setting->tahun_berdiri;
        $this->alamat_jalan = (string) ($setting->alamat_jalan ?? '');
        $this->desa_kelurahan = (string) ($setting->desa_kelurahan ?? '');
        $this->kecamatan = (string) ($setting->kecamatan ?? '');
        $this->kabupaten_kota = (string) ($setting->kabupaten_kota ?? '');
        $this->provinsi = (string) ($setting->provinsi ?? '');
        $this->kode_pos = (string) ($setting->kode_pos ?? '');
        $this->telepon = (string) ($setting->telepon ?? '');
        $this->email = (string) ($setting->email ?? '');
        $this->website_url = (string) ($setting->website_url ?? '');
        $this->sipp_url = (string) ($setting->sipp_url ?? '');
        $this->ecourt_url = (string) ($setting->ecourt_url ?? '');
        $this->direktori_putusan_url = (string) ($setting->direktori_putusan_url ?? '');
        $this->siwas_url = (string) ($setting->siwas_url ?? '');
        $this->simtepa_pegawai_url = (string) ($setting->simtepa_pegawai_url ?? '');
        $this->simtepa_struktur_url = (string) ($setting->simtepa_struktur_url ?? '');
        $this->skm_url = (string) ($setting->skm_url ?? '');
        $this->spak_url = (string) ($setting->spak_url ?? '');
        $this->existingLogoPath = $setting->logo_path;
        $this->existingFotoGedungPath = $setting->foto_gedung_path;
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-satker-settings') || $user->hasRole('super_admin')),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeAccess();

        return view('livewire.admin.satker-settings-manager', [
            'satker' => SatkerSetting::current(),
            'isSuperAdmin' => auth()->user()?->hasRole('super_admin') ?? false,
            'jenisBadanOptions' => config('satker.jenis_badan', []),
            'tingkatOptions' => config('satker.tingkat', []),
        ])->title('Identitas Satker & Integrasi');
    }
}
