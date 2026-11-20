<?php

namespace App\Models;

use App\Http\Controllers\PublicHomeController;
use Database\Factories\HomeSliderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'subtitle',
    'image_path',
    'image_alt',
    'cta_label',
    'cta_url',
    'sort_order',
    'is_active',
])]
class HomeSlider extends Model
{
    /** @use HasFactory<HomeSliderFactory> */
    use HasFactory;

    public const int MIN_ACTIVE_SLIDES = 3;

    public const int MAX_ACTIVE_SLIDES = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            PublicHomeController::flushCache();
        });

        static::deleted(function (): void {
            PublicHomeController::flushCache();
        });
    }

    public static function activeCount(): int
    {
        return static::query()->where('is_active', true)->count();
    }

    public static function canActivateMore(): bool
    {
        return static::activeCount() < self::MAX_ACTIVE_SLIDES;
    }
}
