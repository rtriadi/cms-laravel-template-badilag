<?php

namespace App\Models;

use App\Http\Controllers\PublicComplianceController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicPostController;
use App\Livewire\Admin\Dashboard;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'title',
    'slug',
    'category',
    'excerpt',
    'body',
    'featured_image_path',
    'featured_image_alt',
    'is_pinned',
    'is_published',
    'views_count',
    'comments_enabled',
    'published_at',
    'expires_at',
    'author_id',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'views_count' => 'integer',
            'comments_enabled' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            PublicHomeController::flushCache();
            PublicComplianceController::flushCache();
            PublicPostController::flushCache();
            Dashboard::flushMetricsCache();
        });

        static::deleted(function (): void {
            Cache::forget(PostComment::PENDING_COUNT_CACHE_KEY);
            PublicHomeController::flushCache();
            PublicComplianceController::flushCache();
            PublicPostController::flushCache();
            Dashboard::flushMetricsCache();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('post')
            ->logOnly(['title', 'slug', 'category', 'is_published', 'comments_enabled'])
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

    /**
     * @return HasMany<PostComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(PostComment::class)->where('status', PostComment::STATUS_APPROVED);
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function pendingComments(): HasMany
    {
        return $this->hasMany(PostComment::class)->where('status', PostComment::STATUS_PENDING);
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function approvedTopLevelComments(): HasMany
    {
        return $this->hasMany(PostComment::class)
            ->where('status', PostComment::STATUS_APPROVED)
            ->whereNull('parent_id');
    }

    /**
     * Scope published posts that have not passed their expiration timestamp.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublishedActive(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'berita' => 'Berita Pelayanan',
            'pengumuman' => 'Pengumuman Resmi',
            'layanan_hukum' => 'Artikel Layanan & Bantuan Hukum (Prodeo/Posbakum)',
            'inovasi' => 'Inovasi Satker',
            'opini' => 'Opini & Gagasan',
            'infografis' => 'Galeri Infografis',
            'serta_merta' => 'Informasi Serta Merta',
        ];
    }
}
