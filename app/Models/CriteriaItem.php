<?php

namespace App\Models;

use Database\Factories\CriteriaItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'criteria_group_id',
    'code',
    'name',
    'description',
    'tingkat_scope',
    'item_type',
    'linked_page_id',
    'linked_menu_id',
    'default_route',
    'manual_url',
    'use_manual_url',
    'health_status',
    'http_status',
    'health_message',
    'estimated_score',
    'last_checked_at',
    'sort_order',
])]
class CriteriaItem extends Model
{
    /** @use HasFactory<CriteriaItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_manual_url' => 'boolean',
            'http_status' => 'integer',
            'estimated_score' => 'integer',
            'last_checked_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CriteriaGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CriteriaGroup::class, 'criteria_group_id');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'linked_page_id');
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function linkedMenu(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'linked_menu_id');
    }

    public function resolvedUrl(?SatkerSetting $satker = null): ?string
    {
        if ($this->use_manual_url && filled($this->manual_url)) {
            return $this->manual_url;
        }

        if ($this->linkedPage !== null && $this->linkedPage->is_published) {
            return route('public.page', ['slug' => $this->linkedPage->slug]);
        }

        if ($this->linkedMenu !== null && $this->linkedMenu->is_visible) {
            $menuUrl = $this->linkedMenu->resolvedUrl();

            if ($menuUrl !== '#') {
                return str_starts_with($menuUrl, 'http') ? $menuUrl : url($menuUrl);
            }
        }

        if (filled($this->default_route)) {
            return url($this->default_route);
        }

        if (filled($this->manual_url)) {
            return $this->manual_url;
        }

        if ($satker !== null && $this->code === '1.a.2.1' && filled($satker->sipp_url)) {
            return $satker->sipp_url;
        }

        return null;
    }

    public function isApplicableFor(?string $tingkat): bool
    {
        if ($tingkat === null || $this->tingkat_scope === 'all') {
            return true;
        }

        return $this->tingkat_scope === $tingkat;
    }
}
