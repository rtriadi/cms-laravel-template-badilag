<?php

namespace App\Livewire\Admin;

use App\Models\CriteriaItem;
use App\Models\Document;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class PageManager extends Component
{
    public string $search = '';

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $summary = '';

    public string $category_tag = 'berkala';

    public string $criteria_code = '';

    public string $tingkat_scope = 'all';

    public bool $is_published = true;

    /** @var array<int, array<string, mixed>> */
    public array $blocks = [];

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->blocks = [
            ['type' => 'richtext', 'heading' => 'Informasi Utama', 'content' => '<p>Isi konten halaman di sini.</p>'],
        ];
    }

    public function updatedTitle(string $value): void
    {
        if ($this->editingId === null && $this->slug === '') {
            $this->slug = Str::slug($value);
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

        $page = Page::query()->findOrFail($id);
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->summary = (string) ($page->summary ?? '');
        $this->category_tag = (string) ($page->category_tag ?? 'berkala');
        $this->criteria_code = (string) ($page->criteria_code ?? '');
        $this->tingkat_scope = $page->tingkat_scope;
        $this->is_published = (bool) $page->is_published;
        $this->blocks = is_array($page->blocks) && $page->blocks !== []
            ? $page->blocks
            : [['type' => 'richtext', 'heading' => $page->title, 'content' => '']];
    }

    public function addBlock(string $type): void
    {
        $this->authorizeAccess();

        $this->blocks[] = match ($type) {
            'document_list' => ['type' => 'document_list', 'heading' => 'Daftar Dokumen', 'collection_type' => 'lkjip'],
            'embed_fallback' => ['type' => 'embed_fallback', 'heading' => 'Data Terintegrasi', 'source_key' => 'simtepa_pegawai', 'fallback_mode' => 'officials_table', 'note' => 'Menampilkan data cadangan lokal apabila tautan embed eksternal tidak merespons.'],
            'external_portal' => ['type' => 'external_portal', 'heading' => 'Portal Eksternal', 'url_source' => 'sipp_url', 'default_url' => '', 'description' => ''],
            'map_yurisdiksi' => ['type' => 'map_yurisdiksi', 'heading' => 'Peta Wilayah Yurisdiksi', 'latitude' => '-6.9175', 'longitude' => '107.6191', 'zoom' => 11],
            'satker_contact' => ['type' => 'satker_contact', 'heading' => 'Alamat & Foto Gedung Satker'],
            default => ['type' => 'richtext', 'heading' => 'Bagian Konten', 'content' => '<p></p>'],
        };
    }

    public function removeBlock(int $index): void
    {
        $this->authorizeAccess();
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $normalizedSlug = collect(explode('/', trim($this->slug, '/')))
            ->map(fn (string $segment): string => Str::slug($segment))
            ->filter()
            ->implode('/');

        $this->slug = $normalizedSlug !== '' ? $normalizedSlug : Str::slug($this->title);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($this->editingId)],
            'category_tag' => ['nullable', 'in:berkala,serta_merta,setiap_saat'],
            'tingkat_scope' => ['required', 'in:all,pertama,banding'],
        ], [
            'title.required' => 'Judul halaman wajib diisi.',
            'slug.unique' => 'Slug URL sudah dipakai oleh halaman lain.',
        ]);

        $payload = [
            'title' => trim($this->title),
            'slug' => $this->slug,
            'summary' => trim($this->summary) ?: null,
            'category_tag' => $this->category_tag ?: null,
            'criteria_code' => trim($this->criteria_code) ?: null,
            'tingkat_scope' => $this->tingkat_scope,
            'is_published' => $this->is_published,
            'published_at' => $this->is_published ? now() : null,
            'blocks' => array_values($this->blocks),
            'author_id' => auth()->id(),
        ];

        if ($this->editingId !== null) {
            $page = Page::query()->findOrFail($this->editingId);
            $page->update($payload);
            $this->statusNotice = 'Halaman berhasil diperbarui. Jika slug berubah, redirect 301 dibuat otomatis.';
        } else {
            $page = Page::query()->create($payload);
            $this->statusNotice = 'Halaman baru berhasil dibuat.';
        }

        if (filled($page->criteria_code)) {
            CriteriaItem::query()
                ->where('code', $page->criteria_code)
                ->whereNull('linked_page_id')
                ->update(['linked_page_id' => $page->id]);
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        Page::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Halaman berhasil dihapus.';
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->slug = '';
        $this->summary = '';
        $this->category_tag = 'berkala';
        $this->criteria_code = '';
        $this->tingkat_scope = 'all';
        $this->is_published = true;
        $this->blocks = [
            ['type' => 'richtext', 'heading' => 'Informasi Utama', 'content' => '<p></p>'],
        ];
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-pages') || $user->hasRole('super_admin')),
            403
        );
    }

    public function render(): View
    {
        $pages = Page::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('slug', 'like', $term)->orWhere('criteria_code', 'like', $term));
            })
            ->orderBy('title')
            ->get();

        return view('livewire.admin.page-manager', [
            'pages' => $pages,
            'criteriaOptions' => CriteriaItem::query()->orderBy('code')->get(['code', 'name']),
            'collectionTypes' => Document::collectionTypes(),
        ])->title('Halaman & Page Builder');
    }
}
