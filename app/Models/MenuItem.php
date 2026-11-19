<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'location',
    'title',
    'type',
    'page_id',
    'collection_slug',
    'url',
    'icon',
    'open_in_new_tab',
    'is_visible',
    'sort_order',
    'criteria_code',
    'tingkat_scope',
])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function depth(): int
    {
        $depth = 1;
        $current = $this->parent;

        while ($current !== null && $depth < 5) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }

    public function resolvedUrl(): string
    {
        return match ($this->type) {
            'page' => $this->page ? route('public.page', ['slug' => $this->page->slug], false) : ($this->url ?: '#'),
            'collection' => $this->collection_slug ? '/'.ltrim($this->collection_slug, '/') : ($this->url ?: '#'),
            'category' => $this->collection_slug ? route('public.posts', ['kategori' => $this->collection_slug], false) : '/publikasi',
            'group' => '#',
            default => $this->url ?: '#',
        };
    }
}
