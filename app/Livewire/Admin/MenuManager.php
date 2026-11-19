<?php

namespace App\Livewire\Admin;

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class MenuManager extends Component
{
    public string $locationFilter = 'header';

    public ?int $editingId = null;

    public ?int $parent_id = null;

    public string $location = 'header';

    public string $title = '';

    public string $type = 'page';

    public ?int $page_id = null;

    public string $collection_slug = '';

    public string $url = '';

    public string $criteria_code = '';

    public string $tingkat_scope = 'all';

    public bool $open_in_new_tab = false;

    public bool $is_visible = true;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function createNew(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
        $this->location = $this->locationFilter;
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();

        $item = MenuItem::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->parent_id = $item->parent_id;
        $this->location = $item->location;
        $this->title = $item->title;
        $this->type = $item->type;
        $this->page_id = $item->page_id;
        $this->collection_slug = (string) ($item->collection_slug ?? '');
        $this->url = (string) ($item->url ?? '');
        $this->criteria_code = (string) ($item->criteria_code ?? '');
        $this->tingkat_scope = $item->tingkat_scope;
        $this->open_in_new_tab = (bool) $item->open_in_new_tab;
        $this->is_visible = (bool) $item->is_visible;
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'location' => ['required', 'in:header,footer,shortcut'],
            'type' => ['required', 'in:page,collection,external,category,group'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'tingkat_scope' => ['required', 'in:all,pertama,banding'],
            'url' => ['nullable', 'string', 'max:500'],
        ], [
            'title.required' => 'Nama menu wajib diisi.',
        ]);

        if ($this->parent_id !== null) {
            if ($this->editingId !== null && $this->parent_id === $this->editingId) {
                $this->addError('parent_id', 'Menu tidak dapat menjadi induk dirinya sendiri.');

                return;
            }

            $parent = MenuItem::query()->with('parent.parent')->find($this->parent_id);
            if ($parent !== null && $parent->depth() >= 3) {
                $this->addError('parent_id', 'Kedalaman menu maksimal adalah 3 tingkat sesuai pedoman UI/UX.');

                return;
            }
        }

        $data = [
            'parent_id' => $this->parent_id ?: null,
            'location' => $this->location,
            'title' => trim($this->title),
            'type' => $this->type,
            'page_id' => $this->type === 'page' ? ($this->page_id ?: null) : null,
            'collection_slug' => in_array($this->type, ['collection', 'category'], true) ? (trim($this->collection_slug) ?: null) : null,
            'url' => $this->type === 'external' ? (trim($this->url) ?: null) : null,
            'criteria_code' => trim($this->criteria_code) ?: null,
            'tingkat_scope' => $this->tingkat_scope,
            'open_in_new_tab' => $this->open_in_new_tab,
            'is_visible' => $this->is_visible,
        ];

        if ($this->editingId !== null) {
            MenuItem::query()->findOrFail($this->editingId)->update($data);
            $this->statusNotice = 'Menu berhasil diperbarui.';
        } else {
            $maxOrder = (int) MenuItem::query()
                ->where('location', $this->location)
                ->where('parent_id', $this->parent_id)
                ->max('sort_order');

            MenuItem::query()->create([...$data, 'sort_order' => $maxOrder + 1]);
            $this->statusNotice = 'Menu baru berhasil ditambahkan.';
        }

        $this->resetForm();
    }

    public function moveUp(int $id): void
    {
        $this->authorizeAccess();
        $this->swapOrder($id, 'up');
    }

    public function moveDown(int $id): void
    {
        $this->authorizeAccess();
        $this->swapOrder($id, 'down');
    }

    public function toggleVisible(int $id): void
    {
        $this->authorizeAccess();
        $item = MenuItem::query()->findOrFail($id);
        $item->update(['is_visible' => ! $item->is_visible]);
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        MenuItem::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Menu berhasil dihapus.';
    }

    private function swapOrder(int $id, string $direction): void
    {
        $current = MenuItem::query()->findOrFail($id);
        $query = MenuItem::query()
            ->where('location', $current->location)
            ->where('parent_id', $current->parent_id);

        $neighbor = $direction === 'up'
            ? $query->where('sort_order', '<', $current->sort_order)->orderByDesc('sort_order')->first()
            : $query->where('sort_order', '>', $current->sort_order)->orderBy('sort_order')->first();

        if ($neighbor !== null) {
            $tmp = $current->sort_order;
            $current->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $tmp]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->parent_id = null;
        $this->title = '';
        $this->type = 'page';
        $this->page_id = null;
        $this->collection_slug = '';
        $this->url = '';
        $this->criteria_code = '';
        $this->tingkat_scope = 'all';
        $this->open_in_new_tab = false;
        $this->is_visible = true;
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-menus') || $user->hasRole('super_admin')),
            403
        );
    }

    public function render(): View
    {
        $rootMenus = MenuItem::query()
            ->with(['page', 'children.page', 'children.children.page'])
            ->where('location', $this->locationFilter)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $parentOptions = MenuItem::query()
            ->with('parent')
            ->where('location', $this->location)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (MenuItem $m): bool => $m->depth() < 3 && $m->id !== $this->editingId);

        return view('livewire.admin.menu-manager', [
            'rootMenus' => $rootMenus,
            'parentOptions' => $parentOptions,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'slug']),
        ])->title('Menu Navigasi Dinamis');
    }
}
