<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Page;
use App\Models\Post;
use App\Models\SatkerSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $satker = SatkerSetting::current();

        $pages = collect();
        $posts = collect();
        $documents = collect();

        if (mb_strlen($query) >= 2) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $query);
            $like = "%{$escaped}%";

            $pages = Page::query()
                ->where('is_published', true)
                ->where(function ($q) use ($like): void {
                    $q->where('title', 'like', $like)
                        ->orWhere('summary', 'like', $like)
                        ->orWhere('criteria_code', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                })
                ->orderBy('title')
                ->limit(20)
                ->get()
                ->map(function (Page $page) use ($satker): Page {
                    if ($satker !== null) {
                        $page->title = $satker->replacePlaceholders($page->title);
                    }

                    return $page;
                });

            $posts = Post::query()
                ->where('is_published', true)
                ->where(function ($q) use ($like): void {
                    $q->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('body', 'like', $like);
                })
                ->orderByDesc('published_at')
                ->limit(20)
                ->get();

            $documents = Document::query()
                ->where('is_published', true)
                ->where(function ($q) use ($like): void {
                    $q->where('title', 'like', $like)
                        ->orWhere('status_label', 'like', $like);
                })
                ->orderByDesc('year')
                ->limit(20)
                ->get();
        }

        return view('public.search', [
            'query' => $query,
            'pages' => $pages,
            'posts' => $posts,
            'documents' => $documents,
            'collections' => Document::collectionTypes(),
            'categories' => Post::categoryLabels(),
        ]);
    }
}
