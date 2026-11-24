<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use App\Models\Post;
use App\Services\LegacyMigration\LegacyHtmlTransformer;
use App\Support\IndonesianTitleNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostsSeeder extends Seeder
{
    public function run(): void
    {
        if (! LegacyBundleReader::exists('posts.ndjson')) {
            return;
        }

        $allowedCategories = Post::categoryLabels();
        $transformer = new LegacyHtmlTransformer;
        $pinCutoff = now()->subYear()->timestamp;

        Post::withoutEvents(function () use ($allowedCategories, $transformer, $pinCutoff): void {
            foreach (LegacyBundleReader::streamNdjson('posts.ndjson') as $row) {
                $slug = trim((string) ($row['slug'] ?? ''));
                $rawTitle = trim((string) ($row['title'] ?? ''));
                if ($slug === '' || $rawTitle === '') {
                    continue;
                }

                $title = Str::limit(IndonesianTitleNormalizer::normalizeIfAllCaps($rawTitle), 250, '...');
                $category = (string) ($row['category'] ?? 'berita');
                if (! array_key_exists($category, $allowedCategories)) {
                    $category = 'berita';
                }

                $featuredImagePath = isset($row['featured_image_path']) && trim((string) $row['featured_image_path']) !== ''
                    ? trim((string) $row['featured_image_path'])
                    : null;

                $rawBody = (string) ($row['body'] ?? '<p>'.e($title).'</p>');
                $cleanBody = $transformer->sanitizePostBodyDuplicates($rawBody, null);
                if ($cleanBody === '') {
                    $cleanBody = '<p>'.e($title).'</p>';
                }

                $excerpt = $transformer->extractExcerpt($cleanBody, 220);
                if ($excerpt === null && ! empty($row['excerpt'])) {
                    $excerpt = $transformer->extractExcerpt((string) $row['excerpt'], 220);
                }

                $publishedAt = $row['published_at'] ?? $row['created_at'] ?? now();
                $publishedTimestamp = is_string($publishedAt) ? strtotime($publishedAt) : false;
                $isPinned = (bool) ($row['is_pinned'] ?? false);
                if ($isPinned && $publishedTimestamp !== false && $publishedTimestamp < $pinCutoff) {
                    $isPinned = false;
                }

                $plainBodyLength = mb_strlen(trim((string) preg_replace(
                    '/[\s\xC2\xA0\p{Z}]+/u',
                    ' ',
                    html_entity_decode(strip_tags($cleanBody), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                )));
                $isPublished = (bool) ($row['is_published'] ?? true);
                if (
                    $slug === 'kegiatan-pengadilan'
                    || ($featuredImagePath === null && ! str_contains($cleanBody, '<img') && $plainBodyLength < 20)
                ) {
                    $isPublished = false;
                }

                $post = Post::query()->firstOrNew(['slug' => $slug]);
                $post->fill([
                    'title' => $title,
                    'category' => $category,
                    'excerpt' => $excerpt,
                    'body' => $cleanBody,
                    'featured_image_path' => $featuredImagePath,
                    'featured_image_alt' => $featuredImagePath !== null ? Str::limit($title, 240, '') : null,
                    'is_pinned' => $isPinned,
                    'is_published' => $isPublished,
                    'views_count' => max(0, (int) ($row['views_count'] ?? 0)),
                    'comments_enabled' => (bool) ($row['comments_enabled'] ?? true),
                    'published_at' => $publishedAt,
                ]);

                if (! empty($row['created_at'])) {
                    $post->created_at = (string) $row['created_at'];
                }
                if (! empty($row['updated_at'])) {
                    $post->updated_at = (string) $row['updated_at'];
                }

                $post->save();
            }
        });
    }
}
