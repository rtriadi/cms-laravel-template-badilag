<?php

namespace App\Livewire\Admin;

use App\Models\CriteriaItem;
use App\Models\Document;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class PageManager extends Component
{
    public string $search = '';

    public string $sectionFilter = '';

    public bool $editorOpen = false;

    #[Locked]
    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $summary = '';

    public string $meta_description = '';

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

        $editId = request()->integer('edit');
        $slugParam = trim((string) request()->query('slug', ''), '/');

        if ($editId > 0 && Page::query()->whereKey($editId)->exists()) {
            $this->edit($editId);
        } elseif ($slugParam !== '') {
            $target = Page::query()->where('slug', $slugParam)->first();
            if ($target !== null) {
                $this->edit($target->id);
            }
        }
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
        $this->editorOpen = true;
    }

    public function closeEditor(): void
    {
        $this->editorOpen = false;
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();

        $page = Page::query()->findOrFail($id);
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->summary = (string) ($page->summary ?? '');
        $this->meta_description = (string) ($page->meta_description ?? '');
        $this->category_tag = (string) ($page->category_tag ?? 'berkala');
        $this->criteria_code = (string) ($page->criteria_code ?? '');
        $this->tingkat_scope = $page->tingkat_scope;
        $this->is_published = (bool) $page->is_published;
        $this->blocks = is_array($page->blocks) && $page->blocks !== []
            ? $page->blocks
            : [['type' => 'richtext', 'heading' => $page->title, 'content' => '']];
        $this->editorOpen = true;
    }

    public function addBlock(string $type): void
    {
        $this->authorizeAccess();

        $this->blocks[] = match ($type) {
            'document_list' => ['type' => 'document_list', 'heading' => 'Daftar Dokumen', 'collection_type' => 'lkjip'],
            'article_list' => ['type' => 'article_list', 'heading' => 'Artikel & Informasi Terkait', 'post_category' => 'layanan_hukum'],
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

    public function moveBlockUp(int $index): void
    {
        $this->authorizeAccess();
        if ($index <= 0 || ! isset($this->blocks[$index], $this->blocks[$index - 1])) {
            return;
        }

        [$this->blocks[$index - 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index - 1]];
        $this->blocks = array_values($this->blocks);
    }

    public function moveBlockDown(int $index): void
    {
        $this->authorizeAccess();
        if (! isset($this->blocks[$index], $this->blocks[$index + 1])) {
            return;
        }

        [$this->blocks[$index], $this->blocks[$index + 1]] = [$this->blocks[$index + 1], $this->blocks[$index]];
        $this->blocks = array_values($this->blocks);
    }

    public function duplicateBlock(int $index): void
    {
        $this->authorizeAccess();
        if (! isset($this->blocks[$index])) {
            return;
        }

        $copy = $this->blocks[$index];
        if (isset($copy['heading']) && is_string($copy['heading']) && trim($copy['heading']) !== '') {
            $copy['heading'] = trim($copy['heading']).' (Salinan)';
        }

        array_splice($this->blocks, $index + 1, 0, [$copy]);
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
            'meta_description' => ['nullable', 'string', 'max:160'],
            'category_tag' => ['nullable', 'in:berkala,serta_merta,setiap_saat'],
            'tingkat_scope' => ['required', 'in:all,pertama,banding'],
        ], [
            'title.required' => 'Judul halaman wajib diisi.',
            'slug.unique' => 'Slug URL sudah dipakai oleh halaman lain.',
        ]);

        $existing = $this->editingId !== null ? Page::query()->findOrFail($this->editingId) : null;

        $sanitizedBlocks = array_map(function (array $block): array {
            if (($block['type'] ?? '') === 'external_portal' && isset($block['default_url'])) {
                $block['default_url'] = SafeHtml::cleanUrl((string) $block['default_url']) ?? '';
            }
            if (($block['type'] ?? '') === 'embed_fallback' && isset($block['custom_embed_url'])) {
                $block['custom_embed_url'] = SafeHtml::cleanUrl((string) $block['custom_embed_url']) ?? '';
            }

            return $block;
        }, array_values($this->blocks));

        $payload = [
            'title' => trim($this->title),
            'slug' => $this->slug,
            'summary' => trim($this->summary) ?: null,
            'meta_description' => trim($this->meta_description) ?: null,
            'category_tag' => $this->category_tag ?: null,
            'criteria_code' => trim($this->criteria_code) ?: null,
            'tingkat_scope' => $this->tingkat_scope,
            'is_published' => $this->is_published,
            'published_at' => $this->is_published ? ($existing?->published_at ?? now()) : null,
            'blocks' => $sanitizedBlocks,
            'author_id' => auth()->id(),
        ];

        if ($existing !== null) {
            $page = $existing;
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
        $this->editorOpen = false;
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
        $this->meta_description = '';
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
        $this->authorizeAccess();

        $pages = Page::query()
            ->when($this->sectionFilter !== '', function ($query): void {
                $query->where('slug', 'like', $this->sectionFilter.'/%');
            })
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('slug', 'like', $term)->orWhere('criteria_code', 'like', $term));
            })
            ->orderBy('slug')
            ->get();

        return view('livewire.admin.page-manager', [
            'pages' => $pages,
            'satker' => SatkerSetting::current(),
            'criteriaOptions' => CriteriaItem::query()->orderBy('code')->get(['code', 'name']),
            'collectionTypes' => Document::collectionTypes(),
            'postCategories' => Post::categoryLabels(),
        ])->title('Halaman & Page Builder');
    }
}
