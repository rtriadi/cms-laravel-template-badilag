<?php

namespace App\Console\Commands;

use App\Models\HomeSlider;
use App\Models\Post;
use App\Models\SatkerSetting;
use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;

class OptimizeExistingMediaCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'satker:optimize-media';

    /**
     * @var string
     */
    protected $description = 'Konversi dan resize gambar eksisting (slider, foto satker, dan artikel) menjadi WebP terkompresi';

    public function handle(ImageOptimizerService $optimizer): int
    {
        $totalBefore = 0;
        $totalAfter = 0;
        $optimizedCount = 0;

        $setting = SatkerSetting::query()->first();
        if ($setting !== null) {
            $dirty = false;

            if ($setting->foto_gedung_path) {
                $oldPath = $setting->foto_gedung_path;
                $res = $optimizer->optimizeStoredFile($oldPath, 1280, 82);
                if ($res['optimized']) {
                    $setting->foto_gedung_path = $res['path'];
                    $dirty = true;
                    $optimizedCount++;
                    $totalBefore += $res['before_bytes'];
                    $totalAfter += $res['after_bytes'];
                    $this->line(sprintf('[Satker Foto Gedung] %s -> %s (%.1f KB -> %.1f KB)', $oldPath, $res['path'], $res['before_bytes'] / 1024, $res['after_bytes'] / 1024));
                }
            }

            if ($setting->logo_path) {
                $oldLogo = $setting->logo_path;
                $res = $optimizer->optimizeStoredFile($oldLogo, 512, 88);
                if ($res['optimized']) {
                    $setting->logo_path = $res['path'];
                    $dirty = true;
                    $optimizedCount++;
                    $totalBefore += $res['before_bytes'];
                    $totalAfter += $res['after_bytes'];
                    $this->line(sprintf('[Satker Logo] %s -> %s (%.1f KB -> %.1f KB)', $oldLogo, $res['path'], $res['before_bytes'] / 1024, $res['after_bytes'] / 1024));
                }
            }

            if ($dirty) {
                $setting->save();
            }
        }

        foreach (HomeSlider::query()->whereNotNull('image_path')->get() as $slide) {
            $oldPath = (string) $slide->image_path;
            $res = $optimizer->optimizeStoredFile($oldPath, 1280, 82);
            if ($res['optimized']) {
                $slide->update(['image_path' => $res['path']]);
                $optimizedCount++;
                $totalBefore += $res['before_bytes'];
                $totalAfter += $res['after_bytes'];
                $this->line(sprintf('[Slider #%d] %s -> %s (%.1f KB -> %.1f KB)', $slide->id, $oldPath, $res['path'], $res['before_bytes'] / 1024, $res['after_bytes'] / 1024));
            }
        }

        foreach (Post::query()->whereNotNull('featured_image_path')->get() as $post) {
            $oldPath = (string) $post->featured_image_path;
            $res = $optimizer->optimizeStoredFile($oldPath, 1000, 82);
            if ($res['optimized']) {
                $post->update(['featured_image_path' => $res['path']]);
                $optimizedCount++;
                $totalBefore += $res['before_bytes'];
                $totalAfter += $res['after_bytes'];
                $this->line(sprintf('[Post #%d] %s -> %s (%.1f KB -> %.1f KB)', $post->id, $oldPath, $res['path'], $res['before_bytes'] / 1024, $res['after_bytes'] / 1024));
            }
        }

        if ($optimizedCount === 0) {
            $this->info('Semua gambar eksisting sudah teroptimasi.');
        } else {
            $this->info(sprintf(
                'Selesai: %d berkas dioptimasi (%.1f KB -> %.1f KB, hemat %.1f%%).',
                $optimizedCount,
                $totalBefore / 1024,
                $totalAfter / 1024,
                $totalBefore > 0 ? (($totalBefore - $totalAfter) / $totalBefore) * 100 : 0
            ));
        }

        return self::SUCCESS;
    }
}
