<?php

namespace App\Models;

use App\Support\SafeHtml;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::flushPublicCache();
        });

        static::deleted(function (): void {
            self::flushPublicCache();
        });
    }

    public static function flushPublicCache(): void
    {
        Cache::forget('public_menus.pertama');
        Cache::forget('public_menus.banding');
        Cache::forget('public_menus.all');
    }

    /**
     * Load and cache header, footer, and shortcut menus using plain array payloads
     * compatible with Laravel 13 serializable_classes=false cache drivers.
     *
     * @return array{header: Collection<int, self>, footer: Collection<int, self>, shortcut: Collection<int, self>}
     */
    public static function cachedPublicMenus(string $activeTingkat): array
    {
        $cacheKey = 'public_menus.'.$activeTingkat;
        $raw = Cache::get($cacheKey);

        if (! is_array($raw) || ! isset($raw['header'], $raw['footer'], $raw['shortcut'])) {
            $pageScope = static fn ($pq) => $pq
                ->select(['id', 'slug', 'is_published', 'tingkat_scope'])
                ->where('is_published', true)
                ->whereIn('tingkat_scope', ['all', $activeTingkat]);

            $header = static::query()
                ->with([
                    'page' => $pageScope,
                    'children' => fn ($q) => $q
                        ->where('is_visible', true)
                        ->whereIn('tingkat_scope', ['all', $activeTingkat])
                        ->with([
                            'page' => $pageScope,
                            'children' => fn ($gq) => $gq
                                ->where('is_visible', true)
                                ->whereIn('tingkat_scope', ['all', $activeTingkat])
                                ->with(['page' => $pageScope])
                                ->orderBy('sort_order'),
                        ])
                        ->orderBy('sort_order'),
                ])
                ->where('location', 'header')
                ->whereNull('parent_id')
                ->where('is_visible', true)
                ->whereIn('tingkat_scope', ['all', $activeTingkat])
                ->orderBy('sort_order')
                ->limit(6)
                ->get();

            $footer = static::query()
                ->with(['page' => $pageScope])
                ->where('location', 'footer')
                ->whereNull('parent_id')
                ->where('is_visible', true)
                ->whereIn('tingkat_scope', ['all', $activeTingkat])
                ->orderBy('sort_order')
                ->get();

            $shortcut = static::query()
                ->with(['page' => $pageScope])
                ->where('location', 'shortcut')
                ->whereNull('parent_id')
                ->where('is_visible', true)
                ->whereIn('tingkat_scope', ['all', $activeTingkat])
                ->orderBy('sort_order')
                ->limit(8)
                ->get();

            $headerFiltered = self::filterAccessibleMenus($header);
            $footerFiltered = self::filterAccessibleMenus($footer);
            $shortcutFiltered = self::filterAccessibleMenus($shortcut);

            $raw = [
                'header' => $headerFiltered->map(fn (self $m): array => self::dehydrateMenuNode($m))->all(),
                'footer' => $footerFiltered->map(fn (self $m): array => self::dehydrateMenuNode($m))->all(),
                'shortcut' => $shortcutFiltered->map(fn (self $m): array => self::dehydrateMenuNode($m))->all(),
            ];

            Cache::forever($cacheKey, $raw);
        }

        return [
            'header' => new Collection(array_map([self::class, 'hydrateMenuNode'], $raw['header'])),
            'footer' => new Collection(array_map([self::class, 'hydrateMenuNode'], $raw['footer'])),
            'shortcut' => new Collection(array_map([self::class, 'hydrateMenuNode'], $raw['shortcut'])),
        ];
    }

    /**
     * Filter out menu items targeting unpublished or out-of-scope pages so draft titles and slugs never leak publicly.
     *
     * @param  Collection<int, self>  $items
     * @return Collection<int, self>
     */
    private static function filterAccessibleMenus(Collection $items): Collection
    {
        return $items
            ->filter(function (self $item): bool {
                if ($item->type === 'page') {
                    return $item->relationLoaded('page') && $item->page !== null;
                }

                return true;
            })
            ->map(function (self $item): self {
                if ($item->relationLoaded('children')) {
                    $item->setRelation('children', self::filterAccessibleMenus($item->children));
                }

                return $item;
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private static function dehydrateMenuNode(self $item): array
    {
        return [
            'attributes' => $item->getAttributes(),
            'page' => $item->relationLoaded('page') && $item->page !== null
                ? $item->page->getAttributes()
                : null,
            'children' => $item->relationLoaded('children')
                ? $item->children->map(fn (self $c): array => self::dehydrateMenuNode($c))->all()
                : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function hydrateMenuNode(array $node): self
    {
        $item = (new static)->newFromBuilder($node['attributes'] ?? []);
        $pageAttrs = $node['page'] ?? null;
        $item->setRelation('page', is_array($pageAttrs) ? (new Page)->newFromBuilder($pageAttrs) : null);

        $childrenNodes = is_array($node['children'] ?? null) ? $node['children'] : [];
        $item->setRelation(
            'children',
            new Collection(array_map([self::class, 'hydrateMenuNode'], $childrenNodes))
        );

        return $item;
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
        $raw = match ($this->type) {
            'page' => ($this->page !== null && ($this->page->is_published ?? true))
                ? route('public.page', ['slug' => $this->page->slug], false)
                : ($this->url ?: '#'),
            'collection' => $this->collection_slug ? '/'.ltrim($this->collection_slug, '/') : ($this->url ?: '#'),
            'category' => $this->collection_slug ? route('public.posts', ['kategori' => $this->collection_slug], false) : '/publikasi',
            'group' => '#',
            default => $this->url ?: '#',
        };

        if ($raw === '#') {
            return '#';
        }

        return SafeHtml::cleanUrl($raw) ?? '#';
    }
}
