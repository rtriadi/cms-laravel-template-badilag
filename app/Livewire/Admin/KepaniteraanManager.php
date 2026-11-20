<?php

namespace App\Livewire\Admin;

use App\Models\CaseStatistic;
use App\Models\CourtSchedule;
use App\Models\Official;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class KepaniteraanManager extends Component
{
    public string $tab = 'jadwal';

    // Jadwal Sidang
    public string $tanggal_sidang = '';

    public string $nomor_perkara = '';

    public string $para_pihak = '';

    public string $ruang_sidang = '';

    public string $agenda = '';

    // Statistik Perkara
    public int $stat_year = 2026;

    public int $stat_month = 1;

    public int $sisa_bulan_lalu = 0;

    public int $perkara_masuk = 0;

    public int $perkara_putus = 0;

    // Pejabat & Pegawai (Tanpa NIP/Tgl Lahir/Kontak Pribadi)
    public string $nama_gelar = '';

    public string $jabatan = '';

    public string $kategori = 'pejabat';

    public ?int $parent_id = null;

    public string $pangkat_golongan = '';

    public string $tmt_jabatan = '';

    public string $riwayat_pendidikan = '';

    public string $riwayat_jabatan = '';

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->tanggal_sidang = now()->format('Y-m-d');
        $this->stat_year = (int) now()->year;
        $this->stat_month = (int) now()->month;
    }

    public function saveSchedule(): void
    {
        $this->authorizeKepaniteraanOnly();

        $this->validate([
            'tanggal_sidang' => ['required', 'date'],
            'nomor_perkara' => ['required', 'string', 'max:100'],
            'para_pihak' => ['required', 'string', 'max:255'],
            'ruang_sidang' => ['nullable', 'string', 'max:80'],
            'agenda' => ['nullable', 'string', 'max:255'],
        ]);

        CourtSchedule::query()->create([
            'tanggal_sidang' => $this->tanggal_sidang,
            'nomor_perkara' => trim($this->nomor_perkara),
            'para_pihak' => trim($this->para_pihak),
            'ruang_sidang' => trim($this->ruang_sidang) ?: null,
            'agenda' => trim($this->agenda) ?: null,
        ]);

        $this->reset(['nomor_perkara', 'para_pihak', 'ruang_sidang', 'agenda']);
        $this->statusNotice = 'Jadwal sidang cadangan berhasil ditambahkan.';
    }

    public function deleteSchedule(int $id): void
    {
        $this->authorizeKepaniteraanOnly();
        CourtSchedule::query()->findOrFail($id)->delete();
    }

    public function saveStatistic(): void
    {
        $this->authorizeKepaniteraanOnly();

        $this->validate([
            'stat_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'stat_month' => ['required', 'integer', 'min:1', 'max:12'],
            'sisa_bulan_lalu' => ['required', 'integer', 'min:0'],
            'perkara_masuk' => ['required', 'integer', 'min:0'],
            'perkara_putus' => ['required', 'integer', 'min:0'],
        ]);

        CaseStatistic::query()->updateOrCreate(
            ['year' => $this->stat_year, 'month' => $this->stat_month],
            [
                'sisa_bulan_lalu' => $this->sisa_bulan_lalu,
                'perkara_masuk' => $this->perkara_masuk,
                'perkara_putus' => $this->perkara_putus,
            ],
        );

        $this->statusNotice = 'Statistik perkara bulanan berhasil disimpan (sisa saat ini dihitung otomatis).';
    }

    public function deleteStatistic(int $id): void
    {
        $this->authorizeKepaniteraanOnly();
        CaseStatistic::query()->findOrFail($id)->delete();
    }

    public function saveOfficial(): void
    {
        $this->authorizeAccess();

        $this->validate([
            'nama_gelar' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:pimpinan,pejabat,hakim,pegawai,honorer'],
            'parent_id' => ['nullable', 'exists:officials,id'],
            'pangkat_golongan' => ['nullable', 'string', 'max:80'],
            'tmt_jabatan' => ['nullable', 'string', 'max:60'],
            'riwayat_pendidikan' => ['nullable', 'string', 'max:1000'],
            'riwayat_jabatan' => ['nullable', 'string', 'max:1000'],
        ]);

        Official::query()->create([
            'nama_gelar' => trim($this->nama_gelar),
            'jabatan' => trim($this->jabatan),
            'kategori' => $this->kategori,
            'parent_id' => $this->parent_id ?: null,
            'pangkat_golongan' => trim($this->pangkat_golongan) ?: null,
            'tmt_jabatan' => trim($this->tmt_jabatan) ?: null,
            'riwayat_pendidikan' => trim($this->riwayat_pendidikan) ?: null,
            'riwayat_jabatan' => trim($this->riwayat_jabatan) ?: null,
            'sort_order' => (int) Official::query()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        $this->reset(['nama_gelar', 'jabatan', 'parent_id', 'pangkat_golongan', 'tmt_jabatan', 'riwayat_pendidikan', 'riwayat_jabatan']);
        $this->statusNotice = 'Data aparatur cadangan berhasil disimpan.';
    }

    public function deleteOfficial(int $id): void
    {
        $this->authorizeAccess();
        Official::query()->findOrFail($id)->delete();
    }

    private function authorizeKepaniteraanOnly(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-kepaniteraan') || $user->hasAnyRole(['super_admin', 'admin_konten'])),
            403
        );
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-kepaniteraan') || $user->can('manage-kesekretariatan') || $user->hasAnyRole(['super_admin', 'admin_konten'])),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeAccess();

        return view('livewire.admin.kepaniteraan-manager', [
            'schedules' => CourtSchedule::query()->orderByDesc('tanggal_sidang')->limit(50)->get(),
            'statistics' => CaseStatistic::query()->orderByDesc('year')->orderByDesc('month')->get(),
            'officials' => Official::query()->with('parent')->orderBy('sort_order')->get(),
        ])->title('Data Cadangan Kepaniteraan & Pegawai');
    }
}
