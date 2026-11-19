<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('post')
            ->logOnly(['title', 'slug', 'category', 'is_published'])
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
