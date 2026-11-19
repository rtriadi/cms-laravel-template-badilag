<?php

namespace App\Livewire\Admin;

use App\Models\Document;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class DocumentManager extends Component
{
    use WithFileUploads;

    public string $collectionFilter = 'lkjip';

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

        $reqCol = (string) request()->query('koleksi', '');
        if ($reqCol !== '' && array_key_exists($reqCol, Document::collectionTypes())) {
            $this->collection_type = $reqCol;
            $this->collectionFilter = $reqCol;
        }
    }

    public function updatedCollectionFilter(string $value): void
    {
        if ($this->editingId === null) {
            $this->collection_type = $value;
        }
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

        $this->validate([
            'collection_type' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'external_url' => ['nullable', 'url', 'max:500'],
        ], [
            'title.required' => 'Judul dokumen wajib diisi.',
            'pdf_file.mimes' => 'Berkas dokumen harus berformat PDF.',
        ]);

        $existing = $this->editingId !== null ? Document::query()->findOrFail($this->editingId) : null;
        $filePath = $existing?->file_path;

        if ($this->pdf_file !== null) {
            $filePath = $this->pdf_file->store('documents/'.$this->collection_type, 'public');
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
        Document::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Dokumen berhasil dihapus.';
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
        $documents = Document::query()
            ->where('collection_type', $this->collectionFilter)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.document-manager', [
            'documents' => $documents,
            'collections' => Document::collectionTypes(),
        ])->title('Dokumen SAKIP, Keuangan & Publik');
    }
}
