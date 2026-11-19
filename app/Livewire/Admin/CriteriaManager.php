<?php

namespace App\Livewire\Admin;

use App\Models\CriteriaGroup;
use App\Models\CriteriaItem;
use App\Services\SinovaCriteriaImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('components.layouts.admin')]
class CriteriaManager extends Component
{
    use WithFileUploads;

    public string $activeVersion = '2026';

    public string $tab = 'items';

    // Form Kelompok Kriteria
    public ?int $editingGroupId = null;

    public string $group_code = '';

    public string $group_name = '';

    public string $group_aspect = 'konten';

    public float $group_weight = 0.0;

    public int $group_sort_order = 1;

    // Form Butir Kriteria
    public ?int $editingItemId = null;

    public ?int $criteria_group_id = null;

    public string $item_code = '';

    public string $item_name = '';

    public string $item_description = '';

    public string $tingkat_scope = 'all';

    public string $item_type = 'page';

    public string $default_route = '';

    public int $item_sort_order = 1;

    // Impor JSON
    public mixed $jsonFile = null;

    public string $jsonInput = '';

    /** @var array<string, mixed>|null */
    public ?array $previewDiff = null;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
        $firstGroup = CriteriaGroup::query()->orderBy('sort_order')->first();
        $this->criteria_group_id = $firstGroup?->id;
    }

    public function editGroup(int $id): void
    {
        $this->authorizeAccess();
        $group = CriteriaGroup::query()->findOrFail($id);

        $this->editingGroupId = $group->id;
        $this->group_code = $group->code;
        $this->group_name = $group->name;
        $this->group_aspect = $group->aspect;
        $this->group_weight = (float) $group->weight;
        $this->group_sort_order = (int) $group->sort_order;
        $this->tab = 'groups';
    }

    public function saveGroup(): void
    {
        $this->authorizeAccess();

        $this->validate([
            'group_code' => ['required', 'string', 'max:32'],
            'group_name' => ['required', 'string', 'max:255'],
            'group_aspect' => ['required', 'in:konten,uiux,performa'],
            'group_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'group_sort_order' => ['required', 'integer', 'min:1'],
        ], [
            'group_code.required' => 'Kode kelompok wajib diisi.',
            'group_name.required' => 'Nama kelompok wajib diisi.',
        ]);

        CriteriaGroup::query()->updateOrCreate(
            ['id' => $this->editingGroupId],
            [
                'juknis_version' => $this->activeVersion,
                'code' => trim($this->group_code),
                'name' => trim($this->group_name),
                'aspect' => $this->group_aspect,
                'weight' => $this->group_weight,
                'sort_order' => $this->group_sort_order,
            ],
        );

        $this->reset(['editingGroupId', 'group_code', 'group_name', 'group_weight']);
        $this->group_aspect = 'konten';
        $this->group_sort_order = (int) CriteriaGroup::query()->max('sort_order') + 1;
        $this->statusNotice = 'Kelompok kriteria SINOVA berhasil disimpan.';
    }

    public function deleteGroup(int $id): void
    {
        $this->authorizeAccess();
        CriteriaGroup::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Kelompok kriteria berhasil dihapus.';
    }

    public function editItem(int $id): void
    {
        $this->authorizeAccess();
        $item = CriteriaItem::query()->findOrFail($id);

        $this->editingItemId = $item->id;
        $this->criteria_group_id = $item->criteria_group_id;
        $this->item_code = $item->code;
        $this->item_name = $item->name;
        $this->item_description = (string) ($item->description ?? '');
        $this->tingkat_scope = $item->tingkat_scope;
        $this->item_type = $item->item_type;
        $this->default_route = (string) ($item->default_route ?? '');
        $this->item_sort_order = (int) $item->sort_order;
        $this->tab = 'items';
    }

    public function newItem(): void
    {
        $this->authorizeAccess();
        $this->reset(['editingItemId', 'item_code', 'item_name', 'item_description', 'default_route']);
        $this->tingkat_scope = 'all';
        $this->item_type = 'page';
        $this->item_sort_order = 1;
    }

    public function saveItem(): void
    {
        $this->authorizeAccess();

        $this->validate([
            'criteria_group_id' => ['required', 'exists:criteria_groups,id'],
            'item_code' => ['required', 'string', 'max:32', Rule::unique('criteria_items', 'code')->ignore($this->editingItemId)],
            'item_name' => ['required', 'string', 'max:255'],
            'tingkat_scope' => ['required', 'in:all,pertama,banding'],
            'item_type' => ['required', 'in:link,page,collection,external_app,system'],
            'default_route' => ['nullable', 'string', 'max:255'],
            'item_sort_order' => ['required', 'integer', 'min:1'],
        ], [
            'criteria_group_id.required' => 'Kelompok kriteria wajib dipilih.',
            'item_code.required' => 'Kode butir wajib diisi.',
            'item_code.unique' => 'Kode butir tersebut sudah digunakan.',
            'item_name.required' => 'Nama butir kriteria wajib diisi.',
        ]);

        CriteriaItem::query()->updateOrCreate(
            ['id' => $this->editingItemId],
            [
                'criteria_group_id' => $this->criteria_group_id,
                'code' => trim($this->item_code),
                'name' => trim($this->item_name),
                'description' => trim($this->item_description) ?: null,
                'tingkat_scope' => $this->tingkat_scope,
                'item_type' => $this->item_type,
                'default_route' => trim($this->default_route) ?: null,
                'sort_order' => $this->item_sort_order,
            ],
        );

        $this->newItem();
        $this->statusNotice = 'Butir kriteria SINOVA berhasil disimpan.';
    }

    public function deleteItem(int $id): void
    {
        $this->authorizeAccess();
        CriteriaItem::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Butir kriteria berhasil dihapus.';
    }

    public function previewImport(SinovaCriteriaImporter $importer): void
    {
        $this->authorizeAccess();
        $this->previewDiff = null;

        try {
            $payload = $this->decodeJsonPayload();
            $this->previewDiff = $importer->previewDiff($payload);
            $this->statusNotice = 'Pratinjau perbedaan Juknis berhasil dibuat. Periksa sebelum menerapkan impor.';
        } catch (Throwable $exception) {
            $this->addError('jsonInput', 'Gagal membaca JSON: '.$exception->getMessage());
        }
    }

    public function applyImport(SinovaCriteriaImporter $importer): void
    {
        $this->authorizeAccess();

        try {
            $payload = $this->decodeJsonPayload();
            $result = $importer->import($payload);
            $this->previewDiff = null;
            $this->jsonInput = '';
            $this->jsonFile = null;
            $this->statusNotice = "Impor kriteria berhasil: {$result['groups_synced']} kelompok dan {$result['items_synced']} butir disinkronkan (tautan manual dan halaman yang sudah terhubung tetap dipertahankan).";
        } catch (Throwable $exception) {
            $this->addError('jsonInput', 'Gagal mengimpor JSON: '.$exception->getMessage());
        }
    }

    public function exportJson(SinovaCriteriaImporter $importer): StreamedResponse
    {
        $this->authorizeAccess();
        $data = $importer->export($this->activeVersion);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(function () use ($json): void {
            echo $json !== false ? $json : '{}';
        }, "juknis-sinova-{$this->activeVersion}.json", [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonPayload(): array
    {
        $raw = $this->jsonInput;

        if ($this->jsonFile !== null) {
            $this->validate([
                'jsonFile' => ['file', 'max:2048', 'mimes:json,txt'],
            ]);
            $contents = file_get_contents($this->jsonFile->getRealPath());
            $raw = $contents !== false ? $contents : '';
            $this->jsonInput = $raw;
        }

        $decoded = json_decode(trim($raw), true);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Konten bukan format JSON objek yang valid.');
        }

        return $decoded;
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-criteria') || $user->hasRole('super_admin')),
            403
        );
    }

    public function render(): View
    {
        $groups = CriteriaGroup::query()
            ->with('items')
            ->where('juknis_version', $this->activeVersion)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.admin.criteria-manager', [
            'groups' => $groups,
        ])->title('Manajemen Kriteria & Versi Juknis SINOVA');
    }
}
