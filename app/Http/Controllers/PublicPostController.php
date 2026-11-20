<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SatkerSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicPostController extends Controller
{
    public function index(Request $request): View
    {
        $rawCategory = (string) $request->query('kategori', '');
        $categories = Post::categoryLabels();
        $category = array_key_exists($rawCategory, $categories) ? $rawCategory : '';

        $posts = Post::query()
            ->select(['id', 'title', 'slug', 'category', 'excerpt', 'featured_image_path', 'featured_image_alt', 'is_pinned', 'is_published', 'published_at', 'created_at'])
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

    public function show(string $slug): View
    {
        $post = Post::query()
            ->where('slug', $slug)
            ->publishedActive()
            ->firstOrFail();

        $related = Post::query()
            ->select(['id', 'title', 'slug', 'published_at'])
            ->publishedActive()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('public.posts.show', [
            'post' => $post,
            'related' => $related,
            'categories' => Post::categoryLabels(),
            'satker' => SatkerSetting::current(),
        ]);
    }
}
