<?php

namespace App\Http\Controllers;

use App\Actions\Posts\RecordPostViewAction;
use App\Actions\Posts\SubmitPostCommentAction;
use App\Http\Requests\StorePostCommentRequest;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Services\LocalCaptchaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicPostController extends Controller
{
    public const string CATEGORY_COUNTS_CACHE_KEY = 'public_posts.category_counts';

    public const string LATEST_SIDEBAR_CACHE_KEY = 'public_posts.latest_sidebar.v2';

    public static function flushCache(): void
    {
        Cache::forget(self::CATEGORY_COUNTS_CACHE_KEY);
        Cache::forget(self::LATEST_SIDEBAR_CACHE_KEY);
    }

    public function index(Request $request): View
    {
        $rawCategory = (string) $request->query('kategori', '');
        $categories = Post::categoryLabels();
        $category = array_key_exists($rawCategory, $categories) ? $rawCategory : '';

        $posts = Post::query()
            ->select(['id', 'title', 'slug', 'category', 'excerpt', 'featured_image_path', 'featured_image_alt', 'is_pinned', 'is_published', 'views_count', 'comments_enabled', 'published_at', 'created_at'])
            ->withCount('approvedComments')
            ->publishedActive()
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        return view('public.posts.index', [
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $category,
            'satker' => SatkerSetting::current(),
        ]);
    }

    public function show(Request $request, string $slug, RecordPostViewAction $recordView): View
    {
        $post = Post::query()
            ->where('slug', $slug)
            ->publishedActive()
            ->firstOrFail();

        $recordView->execute($post, $request);

        $comments = $post->approvedTopLevelComments()
            ->select(['id', 'post_id', 'parent_id', 'author_name', 'body', 'is_official_reply', 'created_at'])
            ->with(['approvedReplies' => function ($q): void {
                $q->select(['id', 'post_id', 'parent_id', 'author_name', 'body', 'is_official_reply', 'created_at'])
                    ->orderBy('created_at');
            }])
            ->orderByDesc('created_at')
            ->get();

        $approvedCommentsCount = $post->approvedComments()->count();

        $related = Post::query()
            ->select(['id', 'title', 'slug', 'category', 'featured_image_path', 'featured_image_alt', 'views_count', 'published_at', 'created_at'])
            ->withCount('approvedComments')
            ->publishedActive()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $excludedIds = array_merge([$post->id], $related->pluck('id')->all());

        $cachedLatestRows = Cache::get(self::LATEST_SIDEBAR_CACHE_KEY);
        if (! is_array($cachedLatestRows)) {
            $cachedLatestRows = Post::query()
                ->select(['id', 'title', 'slug', 'category', 'views_count', 'published_at', 'created_at'])
                ->publishedActive()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(12)
                ->get()
                ->map(static fn (Post $item): array => $item->getAttributes())
                ->all();

            Cache::put(self::LATEST_SIDEBAR_CACHE_KEY, $cachedLatestRows, 300);
        }

        $latestPosts = collect($cachedLatestRows)
            ->map(static fn (array $attrs): Post => (new Post)->newFromBuilder($attrs))
            ->reject(fn (Post $item): bool => in_array($item->id, $excludedIds, true))
            ->take(4)
            ->values();

        $categoryCounts = Cache::get(self::CATEGORY_COUNTS_CACHE_KEY);
        if (! is_array($categoryCounts)) {
            $categoryCounts = Post::query()
                ->publishedActive()
                ->selectRaw('category, COUNT(*) as aggregate')
                ->groupBy('category')
                ->pluck('aggregate', 'category')
                ->map(static fn (mixed $v): int => (int) $v)
                ->all();

            Cache::put(self::CATEGORY_COUNTS_CACHE_KEY, $categoryCounts, 300);
        }

        return view('public.posts.show', [
            'post' => $post,
            'comments' => $comments,
            'approvedCommentsCount' => $approvedCommentsCount,
            'related' => $related,
            'latestPosts' => $latestPosts,
            'categoryCounts' => $categoryCounts,
            'categories' => Post::categoryLabels(),
            'satker' => SatkerSetting::current(),
        ]);
    }

    public function storeComment(
        StorePostCommentRequest $request,
        string $slug,
        LocalCaptchaService $captcha,
        SubmitPostCommentAction $submitComment,
    ): RedirectResponse {
        $post = Post::query()
            ->where('slug', $slug)
            ->publishedActive()
            ->firstOrFail();

        $targetUrl = route('public.posts.show', ['slug' => $post->slug]).'#bagian-komentar';

        if (! $post->comments_enabled) {
            return redirect()->to($targetUrl)
                ->with('comment_error', 'Kolom komentar dinonaktifkan untuk publikasi ini.');
        }

        if (! $captcha->verify((string) $request->string('captcha'))) {
            return redirect()->to($targetUrl)
                ->withInput($request->except('captcha'))
                ->withErrors(['captcha' => 'Kode keamanan captcha tidak sesuai atau sudah kedaluwarsa.']);
        }

        $submitComment->execute($post, $request->validated(), $request);

        return redirect()->to($targetUrl)
            ->with('comment_status', 'Terima kasih. Komentar Anda telah diterima dan menunggu moderasi pengelola sebelum ditayangkan.');
    }
}
