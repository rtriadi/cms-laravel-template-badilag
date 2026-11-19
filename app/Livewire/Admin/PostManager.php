<?php

namespace App\Livewire\Admin;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class PostManager extends Component
{
    use WithFileUploads;

    public string $categoryFilter = '';

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $category = 'berita';

    public string $excerpt = '';

    public string $body = '';

    public mixed $featured_image = null;

    public string $featured_image_alt = '';

    public bool $is_pinned = false;

    public bool $is_published = true;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();

        $reqCategory = (string) request()->query('kategori', '');
        if ($reqCategory !== '' && array_key_exists($reqCategory, Post::categoryLabels())) {
            $this->category = $reqCategory;
        }

        $editId = request()->integer('edit');
        if ($editId > 0 && Post::query()->whereKey($editId)->exists()) {
            $this->edit($editId);
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
        $this->is_pinned = (bool) $post->is_pinned;
        $this->is_published = (bool) $post->is_published;
        $this->featured_image = null;
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
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'featured_image_alt' => ['required_with:featured_image', 'nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Judul publikasi wajib diisi.',
            'body.required' => 'Isi konten publikasi wajib diisi.',
            'featured_image_alt.required_with' => 'Teks alternatif (alt text) gambar wajib diisi demi aksesibilitas WCAG.',
        ]);

        $existing = $this->editingId !== null ? Post::query()->findOrFail($this->editingId) : null;
        $imagePath = $existing?->featured_image_path;

        if ($this->featured_image !== null) {
            $imagePath = $this->featured_image->store('posts', 'public');
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
            'published_at' => $this->is_published ? ($existing?->published_at ?? now()) : null,
            'author_id' => auth()->id(),
        ];

        if ($existing !== null) {
            $existing->update($data);
            $this->statusNotice = 'Publikasi berhasil diperbarui.';
        } else {
            Post::query()->create($data);
            $this->statusNotice = 'Publikasi baru berhasil ditambahkan.';
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        Post::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Publikasi berhasil dihapus.';
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
        $this->featured_image_alt = '';
        $this->is_pinned = false;
        $this->is_published = true;
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
        $posts = Post::query()
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.admin.post-manager', [
            'posts' => $posts,
            'categories' => Post::categoryLabels(),
        ])->title('Berita & Publikasi');
    }
}
