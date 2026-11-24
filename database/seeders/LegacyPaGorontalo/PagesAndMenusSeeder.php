<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use App\Models\CriteriaItem;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\LegacyMigration\LegacyHtmlTransformer;
use Illuminate\Database\Seeder;

class PagesAndMenusSeeder extends Seeder
{
    public function run(): void
    {
        if (LegacyBundleReader::exists('pages.json')) {
            $pages = LegacyBundleReader::readJson('pages.json');
            $transformer = new LegacyHtmlTransformer;

            Page::withoutEvents(function () use ($pages, $transformer): void {
                foreach ($pages as $def) {
                    if (! is_array($def) || empty($def['slug']) || empty($def['blocks'])) {
                        continue;
                    }

                    $sanitizedBlocks = [];
                    foreach ($def['blocks'] as $block) {
                        if (! is_array($block)) {
                            continue;
                        }

                        if (isset($block['heading']) && is_string($block['heading'])) {
                            $block['heading'] = str_replace('—', '-', $block['heading']);
                        }
                        if (isset($block['description']) && is_string($block['description'])) {
                            $block['description'] = str_replace('—', '-', $block['description']);
                        }
                        if (($block['type'] ?? '') === 'richtext' && isset($block['content']) && is_string($block['content'])) {
                            $block['content'] = $transformer->sanitizePostBodyDuplicates($block['content'], null);
                        }

                        $sanitizedBlocks[] = $block;
                    }

                    if ($sanitizedBlocks === []) {
                        continue;
                    }

                    $slug = trim((string) $def['slug'], '/');
                    $page = Page::query()->firstOrNew(['slug' => $slug]);

                    $page->title = str_replace('—', '-', (string) ($def['title'] ?? $page->title ?? $slug));
                    $page->blocks = $sanitizedBlocks;
                    $page->category_tag = $def['category_tag'] ?? $page->category_tag ?? 'berkala';
                    if (array_key_exists('criteria_code', $def) && $def['criteria_code'] !== null) {
                        $page->criteria_code = (string) $def['criteria_code'];
                    }
                    $page->tingkat_scope = (string) ($def['tingkat_scope'] ?? $page->tingkat_scope ?? 'all');
                    $page->is_published = true;
                    $page->published_at ??= now();
                    $page->save();

                    if (filled($page->criteria_code)) {
                        CriteriaItem::query()
                            ->where('code', $page->criteria_code)
                            ->whereNull('linked_page_id')
                            ->update(['linked_page_id' => $page->id]);
                    }
                }
            });
        }

        if (LegacyBundleReader::exists('menus.json')) {
            $extraMenus = LegacyBundleReader::readJson('menus.json');

            MenuItem::withoutEvents(function () use ($extraMenus): void {
                foreach ($extraMenus as $def) {
                    if (! is_array($def) || empty($def['parent_title']) || empty($def['slug'])) {
                        continue;
                    }

                    $page = Page::query()->where('slug', trim((string) $def['slug'], '/'))->first();
                    if ($page === null) {
                        continue;
                    }

                    $parent = MenuItem::query()
                        ->where('location', 'header')
                        ->whereNull('parent_id')
                        ->where('title', (string) $def['parent_title'])
                        ->first();

                    if ($parent === null) {
                        continue;
                    }

                    MenuItem::query()->updateOrCreate(
                        [
                            'location' => 'header',
                            'parent_id' => $parent->id,
                            'page_id' => $page->id,
                        ],
                        [
                            'title' => (string) ($def['title'] ?? $page->title),
                            'type' => 'page',
                            'sort_order' => (int) ($def['sort_order'] ?? 20),
                            'is_visible' => true,
                            'tingkat_scope' => 'all',
                        ],
                    );
                }
            });
        }
    }
}
