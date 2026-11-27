<?php

namespace App\Models;

use App\Http\Controllers\PublicHomeController;
use App\Livewire\Admin\Dashboard;
use Database\Factories\PostCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'post_id',
    'parent_id',
    'author_name',
    'author_email',
    'body',
    'status',
    'is_official_reply',
    'ip_hash',
    'moderated_by',
    'moderated_at',
])]
#[Hidden([
    'author_email',
    'ip_hash',
])]
class PostComment extends Model
{
    /** @use HasFactory<PostCommentFactory> */
    use HasFactory, LogsActivity;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_REJECTED = 'rejected';

    public const string STATUS_SPAM = 'spam';

    public const string PENDING_COUNT_CACHE_KEY = 'admin.pending_comments_count';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_official_reply' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $comment): void {
            Cache::forget(self::PENDING_COUNT_CACHE_KEY);
            Dashboard::flushMetricsCache();
            if ($comment->status === self::STATUS_APPROVED || $comment->getOriginal('status') === self::STATUS_APPROVED) {
                PublicHomeController::flushCache();
            }
        });

        static::deleted(function (self $comment): void {
            Cache::forget(self::PENDING_COUNT_CACHE_KEY);
            Dashboard::flushMetricsCache();
            if ($comment->status === self::STATUS_APPROVED) {
                PublicHomeController::flushCache();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('comment')
            ->logOnly(['post_id', 'status', 'is_official_reply'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<PostComment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function approvedReplies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('status', self::STATUS_APPROVED)
            ->orderBy('created_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Moderasi',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_SPAM => 'Spam',
        ];
    }

    public static function cachedPendingCount(): int
    {
        $cached = Cache::get(self::PENDING_COUNT_CACHE_KEY);

        if (is_int($cached)) {
            return $cached;
        }

        $count = static::query()->where('status', self::STATUS_PENDING)->count();
        Cache::put(self::PENDING_COUNT_CACHE_KEY, $count, 60);

        return $count;
    }
}
