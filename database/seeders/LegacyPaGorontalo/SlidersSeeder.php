<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use App\Models\HomeSlider;
use Illuminate\Database\Seeder;

class SlidersSeeder extends Seeder
{
    /** @var list<string> */
    private const array DEFAULT_PLACEHOLDER_TITLES = [
        'Portal Resmi {{satker.nama}}',
        'Keterbukaan Informasi & SAKIP',
        'Layanan Ramah Kelompok Rentan',
    ];

    public function run(): void
    {
        if (! LegacyBundleReader::exists('sliders.json')) {
            return;
        }

        $slides = LegacyBundleReader::readJson('sliders.json');
        if ($slides === []) {
            return;
        }

        HomeSlider::withoutEvents(function () use ($slides): void {
            // Remove generic placeholder slides from SatkerDefaultContentSeeder once legacy PA Gorontalo slides exist
            HomeSlider::query()
                ->whereIn('title', self::DEFAULT_PLACEHOLDER_TITLES)
                ->delete();

            // Ensure any pre-existing unrelated slides are deactivated so active count stays strictly <= MAX_ACTIVE_SLIDES (5)
            HomeSlider::query()->update(['is_active' => false]);

            $activeCount = 0;

            foreach ($slides as $idx => $slide) {
                if (! is_array($slide)) {
                    continue;
                }

                $title = trim((string) ($slide['title'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $wantsActive = (bool) ($slide['is_active'] ?? false);
                $isActive = $wantsActive && $activeCount < HomeSlider::MAX_ACTIVE_SLIDES;
                if ($isActive) {
                    $activeCount++;
                }

                $sortOrder = (int) ($slide['sort_order'] ?? ($idx + 1));

                HomeSlider::query()->updateOrCreate(
                    ['sort_order' => $sortOrder, 'title' => $title],
                    [
                        'subtitle' => isset($slide['subtitle']) ? str_replace('—', '-', (string) $slide['subtitle']) : null,
                        'image_path' => $slide['image_path'] ?? null,
                        'image_alt' => str_replace('—', '-', (string) ($slide['image_alt'] ?? $title)),
                        'cta_label' => $slide['cta_label'] ?? null,
                        'cta_url' => $slide['cta_url'] ?? null,
                        'is_active' => $isActive,
                    ],
                );
            }
        });
    }
}
