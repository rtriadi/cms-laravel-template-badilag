<?php

namespace App\Livewire;

use App\Models\SatkerSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SatkerDefaultContentSeeder;
use Database\Seeders\SinovaCriteriaSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class SetupWizard extends Component
{
    public int $step = 1;

    // Step 1: Identitas & Tingkat
    public string $nama_satker = '';

    public string $jenis_badan = 'PA';

    public string $tingkat = 'pertama';

    public string $kode_satker = '';

    public ?int $tahun_berdiri = null;

    // Step 2: Alamat & Kontak
    public string $alamat_jalan = '';

    public string $desa_kelurahan = '';

    public string $kecamatan = '';

    public string $kabupaten_kota = '';

    public string $provinsi = '';

    public string $kode_pos = '';

    public string $telepon = '';

    public string $email = '';

    public string $website_url = '';

    // Step 3: Tautan Integrasi
    public string $sipp_url = '';

    public string $ecourt_url = 'https://ecourt.mahkamahagung.go.id';

    public string $direktori_putusan_url = 'https://putusan3.mahkamahagung.go.id';

    public string $siwas_url = 'https://siwas.mahkamahagung.go.id';

    public string $simtepa_pegawai_url = '';

    public string $simtepa_struktur_url = '';

    public string $skm_url = '';

    public string $spak_url = '';

    // Step 4: Akun Super Admin
    public string $admin_name = '';

    public string $admin_email = '';

    public string $admin_password = '';

    public string $admin_password_confirmation = '';

    public function mount(): void
    {
        if (SatkerSetting::current()?->is_setup_completed) {
            $this->redirectRoute('login');
        }
    }

    public function updatedJenisBadan(string $value): void
    {
        if (in_array($value, ['PTA', 'MS_ACEH'], true)) {
            $this->tingkat = 'banding';
            if ($this->sipp_url === '') {
                $this->sipp_url = 'https://banding.mahkamahagung.go.id/';
            }
        } else {
            $this->tingkat = 'pertama';
        }
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));
        $this->step = min(4, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function finishSetup(): void
    {
        if (SatkerSetting::current()?->is_setup_completed) {
            abort(403, 'Setup awal sudah selesai dijalankan.');
        }

        $this->validate(array_merge(
            $this->rulesForStep(1),
            $this->rulesForStep(2),
            $this->rulesForStep(3),
            $this->rulesForStep(4),
        ));

        $admin = DB::transaction(function (): User {
            $setting = SatkerSetting::query()->firstOrNew();
            $setting->fill([
                'nama_satker' => $this->nama_satker,
                'jenis_badan' => $this->jenis_badan,
                'tingkat' => $this->tingkat,
                'kode_satker' => $this->kode_satker ?: null,
                'tahun_berdiri' => $this->tahun_berdiri,
                'alamat_jalan' => $this->alamat_jalan,
                'desa_kelurahan' => $this->desa_kelurahan ?: null,
                'kecamatan' => $this->kecamatan ?: null,
                'kabupaten_kota' => $this->kabupaten_kota ?: null,
                'provinsi' => $this->provinsi,
                'kode_pos' => $this->kode_pos ?: null,
                'telepon' => $this->telepon,
                'email' => $this->email,
                'website_url' => $this->website_url ?: null,
                'sipp_url' => $this->sipp_url ?: null,
                'ecourt_url' => $this->ecourt_url ?: null,
                'direktori_putusan_url' => $this->direktori_putusan_url ?: null,
                'siwas_url' => $this->siwas_url ?: null,
                'simtepa_pegawai_url' => $this->simtepa_pegawai_url ?: null,
                'simtepa_struktur_url' => $this->simtepa_struktur_url ?: null,
                'skm_url' => $this->skm_url ?: null,
                'spak_url' => $this->spak_url ?: null,
            ]);
            $setting->completeSetup();

            app(RoleAndPermissionSeeder::class)->run();
            app(SinovaCriteriaSeeder::class)->run();
            app(SatkerDefaultContentSeeder::class)->run($this->tingkat);

            $user = User::query()->create([
                'name' => $this->admin_name,
                'email' => strtolower(trim($this->admin_email)),
                'password' => Hash::make($this->admin_password),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('super_admin');

            return $user;
        });

        Auth::login($admin);

        $this->redirectRoute('admin.two-factor.setup');
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'nama_satker' => ['required', 'string', 'max:255'],
                'jenis_badan' => ['required', 'in:PA,PTA,MS,MS_ACEH'],
                'tingkat' => ['required', 'in:pertama,banding'],
                'kode_satker' => ['nullable', 'string', 'max:50'],
                'tahun_berdiri' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            ],
            2 => [
                'alamat_jalan' => ['required', 'string', 'max:255'],
                'desa_kelurahan' => ['nullable', 'string', 'max:120'],
                'kecamatan' => ['nullable', 'string', 'max:120'],
                'kabupaten_kota' => ['nullable', 'string', 'max:120'],
                'provinsi' => ['required', 'string', 'max:120'],
                'kode_pos' => ['nullable', 'string', 'max:10'],
                'telepon' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
                'website_url' => ['nullable', 'url', 'max:255'],
            ],
            3 => [
                'sipp_url' => ['nullable', 'url', 'max:255'],
                'ecourt_url' => ['nullable', 'url', 'max:255'],
                'direktori_putusan_url' => ['nullable', 'url', 'max:255'],
                'siwas_url' => ['nullable', 'url', 'max:255'],
                'simtepa_pegawai_url' => ['nullable', 'url', 'max:255'],
                'simtepa_struktur_url' => ['nullable', 'url', 'max:255'],
                'skm_url' => ['nullable', 'url', 'max:255'],
                'spak_url' => ['nullable', 'url', 'max:255'],
            ],
            4 => [
                'admin_name' => ['required', 'string', 'max:255'],
                'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'admin_password' => ['required', 'confirmed', Password::defaults()],
            ],
            default => [],
        };
    }

    public function render(): View
    {
        return view('livewire.setup-wizard');
    }
}
