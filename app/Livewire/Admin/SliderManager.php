<?php

namespace App\Livewire\Admin;

use App\Models\HomeSlider;
use App\Services\ImageOptimizerService;
use App\Support\SafeHtml;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class SliderManager extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $editingId = null;

    public string $title = '';

    public string $subtitle = '';

    public mixed $image = null;

    public string $image_alt = '';

    public string $cta_label = '';

    public string $cta_url = '';

    public bool $is_active = true;

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();
        $slide = HomeSlider::query()->findOrFail($id);

        $this->editingId = $slide->id;
        $this->title = $slide->title;
        $this->subtitle = (string) ($slide->subtitle ?? '');
        $this->image_alt = $slide->image_alt;
        $this->cta_label = (string) ($slide->cta_label ?? '');
        $this->cta_url = (string) ($slide->cta_url ?? '');
        $this->is_active = (bool) $slide->is_active;
        $this->image = null;
    }

    public function createNew(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=8000,max_height=8000'],
            'image_alt' => ['required', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Judul slide wajib diisi.',
            'image_alt.required' => 'Teks alternatif (alt text) slide wajib diisi untuk aksesibilitas.',
        ]);

        // Guardrail Bab III.A.5: dilarang video / live streaming di beranda
        $trimmedUrl = trim($this->cta_url);
        if ($trimmedUrl !== '' && SafeHtml::cleanUrl($trimmedUrl) === null) {
            $this->addError('cta_url', 'URL tombol CTA harus diawali http(s):// atau jalur relatif /.');

            return;
        }

        $lowerUrl = strtolower($trimmedUrl);
        foreach (['youtube.com', 'youtu.be', 'vimeo.com', 'twitch.tv', '.mp4', '.m3u8'] as $forbidden) {
            if (str_contains($lowerUrl, $forbidden)) {
                $this->addError('cta_url', 'Tautan video atau live streaming tidak diizinkan pada slider beranda sesuai pedoman kecepatan SK Dirjen Badilag.');

                return;
            }
        }

        $existing = $this->editingId !== null ? HomeSlider::query()->findOrFail($this->editingId) : null;

        if ($this->is_active) {
            $otherActive = HomeSlider::query()
                ->where('is_active', true)
                ->when($existing !== null, fn ($q) => $q->where('id', '!=', $existing->id))
                ->count();

            if ($otherActive >= HomeSlider::MAX_ACTIVE_SLIDES) {
                $this->addError('is_active', 'Maksimal 5 slide aktif di beranda sesuai SK Dirjen Badilag Bab III.C.3. Nonaktifkan salah satu slide terlebih dahulu.');

                return;
            }
        }

        $oldImagePath = $existing?->image_path;
        $imagePath = $oldImagePath;
        if ($this->image !== null) {
            if (ImageOptimizerService::exceedsPixelLimit($this->image)) {
                $this->addError('image', 'Total piksel gambar melebihi batas aman 25 megapiksel.');

                return;
            }

            $imagePath = app(ImageOptimizerService::class)->storeOptimized($this->image, 'sliders', 1280, 82, 'public', [480, 800]);
        }

        $data = [
            'title' => trim($this->title),
            'subtitle' => trim($this->subtitle) ?: null,
            'image_path' => $imagePath,
            'image_alt' => trim($this->image_alt),
            'cta_label' => trim($this->cta_label) ?: null,
            'cta_url' => trim($this->cta_url) ?: null,
            'is_active' => $this->is_active,
        ];

        if ($existing !== null) {
            $existing->update($data);
            if ($this->image !== null && is_string($oldImagePath) && $oldImagePath !== '' && $oldImagePath !== $imagePath) {
                app(ImageOptimizerService::class)->deleteWithVariants($oldImagePath);
            }
            $this->statusNotice = 'Slide beranda berhasil diperbarui.';
        } else {
            $maxOrder = (int) HomeSlider::query()->max('sort_order');
            HomeSlider::query()->create([...$data, 'sort_order' => $maxOrder + 1]);
            $this->statusNotice = 'Slide baru berhasil ditambahkan.';
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAccess();
        $slide = HomeSlider::query()->findOrFail($id);

        if (! $slide->is_active && ! HomeSlider::canActivateMore()) {
            $this->addError('slider_limit', 'Maksimal 5 slide aktif di beranda sesuai SK Dirjen Badilag.');

            return;
        }

        $slide->update(['is_active' => ! $slide->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        $slide = HomeSlider::query()->findOrFail($id);
        $oldImagePath = $slide->image_path;
        $slide->delete();
        if (is_string($oldImagePath) && $oldImagePath !== '') {
            app(ImageOptimizerService::class)->deleteWithVariants($oldImagePath);
        }
        $this->statusNotice = 'Slide berhasil dihapus.';
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->subtitle = '';
        $this->image = null;
        $this->image_alt = '';
        $this->cta_label = '';
        $this->cta_url = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->can('manage-pages') || $user->hasAnyRole(['super_admin', 'admin_konten'])),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeAccess();

        $sliders = HomeSlider::query()->orderBy('sort_order')->get();
        $activeCount = $sliders->where('is_active', true)->count();

        return view('livewire.admin.slider-manager', [
            'sliders' => $sliders,
            'activeCount' => $activeCount,
        ])->title('Slider Beranda (3-5 Slide)');
    }
}
