<?php

namespace App\Livewire\Admin;

use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class DocumentManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $collectionFilter = 'lkjip';

    public string $search = '';

    public string $yearFilter = '';

    #[Locked]
    public ?int $editingId = null;

    public string $collection_type = 'lkjip';

    public string $title = '';

    public int $year = 2026;

    public ?int $month = null;

    public string $status_label = 'Final';

    public mixed $pdf_file = null;

    public string $external_url = '';

    /** @var array<string, string> */
    public array $meta = [];

    public bool $is_published = true;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->year = (int) now()->year;

        $allowedCollections = $this->allowedCollections();
        $firstAllowed = array_key_first($allowedCollections) ?? 'lkjip';
        $this->collection_type = $firstAllowed;
        $this->collectionFilter = $firstAllowed;

        $reqCol = (string) request()->query('koleksi', '');
        if ($reqCol !== '' && array_key_exists($reqCol, $allowedCollections)) {
            $this->collection_type = $reqCol;
            $this->collectionFilter = $reqCol;
        }
    }

    public function updatedCollectionFilter(string $value): void
    {
        if ($this->editingId === null && array_key_exists($value, $this->allowedCollections())) {
            $this->collection_type = $value;
        }
        $this->yearFilter = '';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingYearFilter(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();
        $doc = Document::query()->findOrFail($id);
        $this->authorizeCollection($doc->collection_type);

        $this->editingId = $doc->id;
        $this->collection_type = $doc->collection_type;
        $this->title = $doc->title;
        $this->year = $doc->year;
        $this->month = $doc->month;
        $this->status_label = (string) ($doc->status_label ?? 'Final');
        $this->external_url = (string) ($doc->external_url ?? '');
        $this->meta = is_array($doc->metadata) ? $doc->metadata : [];
        $this->is_published = (bool) $doc->is_published;
        $this->pdf_file = null;
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $allCollections = array_keys(Document::collectionTypes());

        $this->validate([
            'collection_type' => ['required', 'string', Rule::in($allCollections)],
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'status_label' => ['nullable', 'string', 'max:80'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'external_url' => ['nullable', 'url:http,https', 'max:500'],
        ], [
            'collection_type.in' => 'Jenis koleksi dokumen tidak valid.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'pdf_file.mimes' => 'Berkas dokumen harus berformat PDF.',
            'external_url.url' => 'Tautan eksternal harus berupa URL http(s):// yang valid.',
        ]);

        $this->authorizeCollection($this->collection_type);

        $existing = $this->editingId !== null ? Document::query()->findOrFail($this->editingId) : null;
        if ($existing !== null) {
            $this->authorizeCollection($existing->collection_type);
        }

        $oldFilePath = $existing?->file_path;
        $filePath = $oldFilePath;
        $targetDisk = $this->is_published ? 'public' : 'local';

        if ($this->pdf_file !== null) {
            $filePath = $this->pdf_file->store('documents/'.$this->collection_type, $targetDisk);
        } elseif ($existing !== null && is_string($oldFilePath) && $oldFilePath !== '') {
            $this->syncDocumentStorageVisibility($oldFilePath, $this->is_published);
        }

        $cleanMeta = array_filter($this->meta, fn (mixed $v): bool => trim((string) $v) !== '');

        $data = [
            'collection_type' => $this->collection_type,
            'title' => trim($this->title),
            'year' => $this->year,
            'month' => $this->month ?: null,
            'status_label' => trim($this->status_label) ?: null,
            'file_path' => $filePath,
            'external_url' => trim($this->external_url) ?: null,
            'metadata' => $cleanMeta !== [] ? $cleanMeta : null,
            'is_published' => $this->is_published,
            'created_by' => auth()->id(),
        ];

        if ($existing !== null) {
            $existing->update($data);
            $existing->touch();
            if ($this->pdf_file !== null && is_string($oldFilePath) && $oldFilePath !== '' && $oldFilePath !== $filePath) {
                Storage::disk('public')->delete($oldFilePath);
                Storage::disk('local')->delete($oldFilePath);
            }
            $this->statusNotice = 'Dokumen berhasil diperbarui.';
        } else {
            Document::query()->create($data);
            $this->statusNotice = 'Dokumen baru berhasil diunggah.';
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        $doc = Document::query()->findOrFail($id);
        $this->authorizeCollection($doc->collection_type);

        $filePath = $doc->file_path;
        $doc->delete();

        if (is_string($filePath) && $filePath !== '') {
            Storage::disk('public')->delete($filePath);
            Storage::disk('local')->delete($filePath);
        }

        $this->statusNotice = 'Dokumen berhasil dihapus.';
    }

    private function syncDocumentStorageVisibility(string $filePath, bool $shouldBePublic): void
    {
        $publicDisk = Storage::disk('public');
        $privateDisk = Storage::disk('local');

        if (! $shouldBePublic && $publicDisk->exists($filePath)) {
            $bytes = $publicDisk->get($filePath);
            if (is_string($bytes)) {
                $privateDisk->put($filePath, $bytes);
                $publicDisk->delete($filePath);
            }

            return;
        }

        if ($shouldBePublic && ! $publicDisk->exists($filePath) && $privateDisk->exists($filePath)) {
            $bytes = $privateDisk->get($filePath);
            if (is_string($bytes)) {
                $publicDisk->put($filePath, $bytes);
                $privateDisk->delete($filePath);
            }
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->collection_type = $this->collectionFilter;
        $this->title = '';
        $this->year = (int) now()->year;
        $this->month = null;
        $this->status_label = 'Final';
        $this->pdf_file = null;
        $this->external_url = '';
        $this->meta = [];
        $this->is_published = true;
        $this->resetValidation();
    }

    /**
     * @return array<string, string>
     */
    private function allowedCollections(): array
    {
        $user = auth()->user();
        $all = Document::collectionTypes();

        if ($user === null) {
            return [];
        }

        if ($user->hasAnyRole(['super_admin', 'admin_konten'])) {
            return $all;
        }

        $permMap = Document::collectionPermissions();

        return array_filter(
            $all,
            fn (string $label, string $key): bool => isset($permMap[$key]) && $user->can($permMap[$key]),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function authorizeCollection(string $collectionType): void
    {
        abort_unless(array_key_exists($collectionType, $this->allowedCollections()), 403);
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->hasAnyRole(['super_admin', 'admin_konten', 'operator_kesekretariatan', 'operator_kepaniteraan', 'operator_ppid_humas'])),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeAccess();
        $allowedCollections = $this->allowedCollections();

        if (! array_key_exists($this->collectionFilter, $allowedCollections)) {
            $this->collectionFilter = array_key_first($allowedCollections) ?? 'lkjip';
        }

        $availableYears = Document::query()
            ->where('collection_type', $this->collectionFilter)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->all();

        $documents = Document::query()
            ->where('collection_type', $this->collectionFilter)
            ->when($this->yearFilter !== '', fn ($q) => $q->where('year', (int) $this->yearFilter))
            ->when(trim($this->search) !== '', function ($q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($sub) => $sub->where('title', 'like', $term)->orWhere('status_label', 'like', $term));
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.admin.document-manager', [
            'documents' => $documents,
            'availableYears' => $availableYears,
            'collections' => $allowedCollections,
        ])->title('Dokumen SAKIP, Keuangan & Publik');
    }
}
