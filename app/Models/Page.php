<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'title',
    'slug',
    'summary',
    'blocks',
    'category_tag',
    'criteria_code',
    'tingkat_scope',
    'is_published',
    'published_at',
    'meta_description',
    'author_id',
])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            MenuItem::flushPublicCache();
        });

        static::deleted(function (): void {
            MenuItem::flushPublicCache();
        });

        static::updating(function (self $page): void {
            if ($page->isDirty('slug')) {
                $oldSlug = (string) $page->getOriginal('slug');
                $newSlug = (string) $page->slug;

                if ($oldSlug !== '' && $newSlug !== '' && $oldSlug !== $newSlug) {
                    Redirect::query()->where('from_path', '/halaman/'.$newSlug)->delete();

                    Redirect::query()->updateOrCreate(
                        ['from_path' => '/halaman/'.$oldSlug],
                        [
                            'to_path' => '/halaman/'.$newSlug,
                            'status_code' => 301,
                        ],
                    );
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('page')
            ->logOnly(['title', 'slug', 'criteria_code', 'is_published'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function publicUrl(bool $absolute = true): string
    {
        return route('public.page', ['slug' => $this->slug], $absolute);
    }
}
