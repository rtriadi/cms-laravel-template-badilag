<?php

namespace App\Models;

use Database\Factories\CriteriaGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'juknis_version',
    'aspect',
    'code',
    'name',
    'weight',
    'sort_order',
])]
class CriteriaGroup extends Model
{
    /** @use HasFactory<CriteriaGroupFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<CriteriaItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CriteriaItem::class, 'criteria_group_id')->orderBy('sort_order');
    }
}
