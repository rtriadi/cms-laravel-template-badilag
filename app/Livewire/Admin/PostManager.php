<?php

namespace App\Livewire\Admin;

use App\Models\Post;
use App\Services\ImageOptimizerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class PostManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public string $search = '';

    /** @var array<int, int|string> */
    public array $selectedIds = [];

    public string $bulkAction = '';

    public bool $editorOpen = false;

    #[Locked]
    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $category = 'berita';

    public string $excerpt = '';

    public string $body = '';

    public mixed $featured_image = null;

    #[Locked]
    public ?string $existingFeaturedImagePath = null;

    public string $featured_image_alt = '';

    public bool $is_pinned = false;

    public bool $is_published = true;

    public bool $comments_enabled = true;

    public string $expiresAt = '';

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();

        $reqCategory = (string) request()->query('kategori', '');
        if ($reqCategory !== '' && array_key_exists($reqCategory, Post::categoryLabels())) {
            $this->category = $reqCategory;
            $this->editorOpen = true;
        }

        $editId = request()->integer('edit');
        if ($editId > 0 && Post::query()->whereKey($editId)->exists()) {
            $this->edit($editId);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
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
        $post = Post::query()->findOrFail($id);

        $this->editingId = $post->id;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->category = $post->category;
        $this->excerpt = (string) ($post->excerpt ?? '');
        $this->body = $post->body;
        $this->featured_image_alt = (string) ($post->featured_image_alt ?? '');
        $this->existingFeaturedImagePath = $post->featured_image_path;
        $this->is_pinned = (bool) $post->is_pinned;
        $this->is_published = (bool) $post->is_published;
        $this->comments_enabled = (bool) ($post->comments_enabled ?? true);
        $this->expiresAt = $post->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->featured_image = null;
        $this->editorOpen = true;
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $this->slug = Str::slug($this->slug ?: $this->title);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($this->editingId)],
            'category' => ['required', Rule::in(array_keys(Post::categoryLabels()))],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=8000,max_height=8000'],
            'featured_image_alt' => ['required_with:featured_image', 'nullable', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date'],
        ], [
            'title.required' => 'Judul publikasi wajib diisi.',
            'body.required' => 'Isi konten publikasi wajib diisi.',
            'featured_image_alt.required_with' => 'Teks alternatif (alt text) gambar wajib diisi demi aksesibilitas WCAG.',
        ]);

        $existing = $this->editingId !== null ? Post::query()->findOrFail($this->editingId) : null;
        $oldImagePath = $existing?->featured_image_path;
        $imagePath = $oldImagePath;

        if ($this->featured_image !== null) {
            if (ImageOptimizerService::exceedsPixelLimit($this->featured_image)) {
                $this->addError('featured_image', 'Total piksel gambar melebihi batas aman 25 megapiksel.');

                return;
            }

            $imagePath = app(ImageOptimizerService::class)->storeOptimized($this->featured_image, 'posts', 1000, 82, 'public', [400, 720]);
        }

        $data = [
            'title' => trim($this->title),
            'slug' => $this->slug,
            'category' => $this->category,
            'excerpt' => trim($this->excerpt) ?: null,
            'body' => $this->body,
            'featured_image_path' => $imagePath,
            'featured_image_alt' => trim($this->featured_image_alt) ?: null,
            'is_pinned' => $this->is_pinned,
            'is_published' => $this->is_published,
            'comments_enabled' => $this->comments_enabled,
            'expires_at' => $this->expiresAt !== '' ? $this->expiresAt : null,
            'published_at' => $this->is_published ? ($existing?->published_at ?? now()) : null,
            'author_id' => auth()->id(),
        ];

        if ($existing !== null) {
            $existing->update($data);
            if ($this->featured_image !== null && is_string($oldImagePath) && $oldImagePath !== '' && $oldImagePath !== $imagePath) {
                app(ImageOptimizerService::class)->deleteWithVariants($oldImagePath);
            }
            $this->statusNotice = 'Publikasi berhasil diperbarui.';
        } else {
            Post::query()->create($data);
            $this->statusNotice = 'Publikasi baru berhasil ditambahkan.';
        }

        $this->resetForm();
        $this->editorOpen = false;
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        $post = Post::query()->findOrFail($id);
        $oldImagePath = $post->featured_image_path;
        $post->delete();
        if (is_string($oldImagePath) && $oldImagePath !== '') {
            app(ImageOptimizerService::class)->deleteWithVariants($oldImagePath);
        }
        $this->statusNotice = 'Publikasi berhasil dihapus.';
    }

    public function applyBulkAction(): void
    {
        $this->authorizeAccess();

        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selectedIds), static fn (int $id): bool => $id > 0)));
        if ($ids === [] || $this->bulkAction === '') {
            return;
        }

        $this->validate([
            'bulkAction' => ['required', Rule::in(['publish', 'draft', 'pin', 'unpin', 'delete'])],
        ]);

        $optimizer = app(ImageOptimizerService::class);
        $posts = Post::query()->whereIn('id', $ids)->get();

        foreach ($posts as $post) {
            match ($this->bulkAction) {
                'publish' => $post->update([
                    'is_published' => true,
                    'published_at' => $post->published_at ?? now(),
                ]),
                'draft' => $post->update(['is_published' => false]),
                'pin' => $post->update(['is_pinned' => true]),
                'unpin' => $post->update(['is_pinned' => false]),
                'delete' => (function () use ($post, $optimizer): void {
                    $img = $post->featured_image_path;
                    $post->delete();
                    if (is_string($img) && $img !== '') {
                        $optimizer->deleteWithVariants($img);
                    }
                })(),
            };
        }

        $count = $posts->count();
        $this->selectedIds = [];
        $this->bulkAction = '';
        $this->statusNotice = "Aksi massal berhasil diterapkan pada {$count} publikasi.";
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->slug = '';
        $this->category = 'berita';
        $this->excerpt = '';
        $this->body = '';
        $this->featured_image = null;
        $this->existingFeaturedImagePath = null;
        $this->featured_image_alt = '';
        $this->is_pinned = false;
        $this->is_published = true;
        $this->comments_enabled = true;
        $this->expiresAt = '';
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-ppid-humas') || $user->hasAnyRole(['super_admin', 'admin_konten'])),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeAccess();

        $agg = Post::query()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END), 0) as published, COALESCE(SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END), 0) as draft, COALESCE(SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END), 0) as pinned')
            ->first();

        $statusCounts = [
            'all' => (int) ($agg?->total ?? 0),
            'published' => (int) ($agg?->published ?? 0),
            'draft' => (int) ($agg?->draft ?? 0),
            'pinned' => (int) ($agg?->pinned ?? 0),
        ];

        $posts = $this->editorOpen
            ? null
            : Post::query()
                ->select(['id', 'title', 'slug', 'category', 'featured_image_path', 'featured_image_alt', 'is_pinned', 'is_published', 'views_count', 'comments_enabled', 'published_at', 'created_at'])
                ->withCount(['approvedComments', 'pendingComments'])
                ->when($this->categoryFilter !== '', fn ($q) => $q->where('category', $this->categoryFilter))
                ->when($this->statusFilter === 'published', fn ($q) => $q->where('is_published', true))
                ->when($this->statusFilter === 'draft', fn ($q) => $q->where('is_published', false))
                ->when($this->statusFilter === 'pinned', fn ($q) => $q->where('is_pinned', true))
                ->when(trim($this->search) !== '', function ($q): void {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($sub) => $sub->where('title', 'like', $term)->orWhere('slug', 'like', $term));
                })
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->paginate(20);

        return view('livewire.admin.post-manager', [
            'posts' => $posts,
            'statusCounts' => $statusCounts,
            'categories' => Post::categoryLabels(),
        ])->title('Berita & Publikasi');
    }
}
