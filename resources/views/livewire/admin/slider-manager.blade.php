<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div @class([
        'rounded-lg border px-4 py-3 text-xs font-medium',
        'border-emerald-200 bg-emerald-50 text-emerald-900' => $activeCount >= \App\Models\HomeSlider::MIN_ACTIVE_SLIDES && $activeCount <= \App\Models\HomeSlider::MAX_ACTIVE_SLIDES,
        'border-amber-300 bg-amber-50 text-ink' => $activeCount < \App\Models\HomeSlider::MIN_ACTIVE_SLIDES || $activeCount > \App\Models\HomeSlider::MAX_ACTIVE_SLIDES,
    ])>
        Status Slide Aktif: <strong>{{ $activeCount }} / {{ \App\Models\HomeSlider::MAX_ACTIVE_SLIDES }}</strong> (Wajib 3 sampai 5 slide gambar statis sesuai SK Dirjen Badilag Bab III.C.3, tanpa video atau live streaming di beranda).
    </div>

    @error('slider_limit')
        <div role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Form Slider -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">
                    {{ $editingId ? 'Edit Slide Beranda' : 'Tambah Slide Beranda' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="cursor-pointer text-xs font-semibold text-primary hover:underline">
                        Slide Baru
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="sl_title">Judul Slide</flux:label>
                    <flux:input id="sl_title" wire:model="title" required placeholder="Contoh: Pelayanan Prima Berintegritas" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="sl_sub">Subjudul (Opsional)</flux:label>
                    <flux:input id="sl_sub" wire:model="subtitle" placeholder="Keterangan singkat pada banner" />
                    <flux:error name="subtitle" />
                </flux:field>

                <flux:field>
                    <flux:label for="sl_img">Gambar Banner (Maks 2 MB, disarankan WebP/JPEG)</flux:label>
                    <input id="sl_img" type="file" wire:model="image" accept="image/*" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-hairline file:bg-mist file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary" />
                    <div wire:loading wire:target="image" class="mt-1 text-xs text-primary">Mengunggah gambar slide...</div>
                    <flux:error name="image" />
                </flux:field>

                <flux:field>
                    <flux:label for="sl_alt">Teks Alternatif Gambar (Alt Text Wajib WCAG)</flux:label>
                    <flux:input id="sl_alt" wire:model="image_alt" required placeholder="Deskripsi visual banner untuk pembaca layar" />
                    <flux:error name="image_alt" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="sl_cta_lbl">Teks Tombol CTA</flux:label>
                        <flux:input id="sl_cta_lbl" wire:model="cta_label" placeholder="Lihat Layanan" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="sl_cta_url">URL Tombol CTA (Non-Video)</flux:label>
                        <flux:input id="sl_cta_url" wire:model="cta_url" placeholder="/halaman/layanan/standar-pelayanan" />
                        <flux:error name="cta_url" />
                    </flux:field>
                </div>

                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-xs font-medium text-ink">
                    <input type="checkbox" wire:model="is_active" class="size-4 rounded border-hairline text-primary">
                    <span>Aktifkan pada slider beranda</span>
                </label>
                <flux:error name="is_active" />

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Slide' : 'Simpan Slide Beranda' }}
                </flux:button>
            </form>
        </div>

        <!-- Daftar Slide -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
            <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Daftar Slide Beranda ({{ $sliders->count() }})</h2>
            @if ($sliders->isEmpty())
                <p class="py-10 text-center text-sm text-ink-muted">Belum ada slide tersimpan.</p>
            @else
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($sliders as $slide)
                        <li wire:key="slide-{{ $slide->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                @if ($slide->image_path)
                                    <img src="{{ asset('storage/' . $slide->image_path) }}" alt="{{ $slide->image_alt }}" width="88" height="50" class="h-12 w-22 shrink-0 rounded border border-hairline object-cover" />
                                @else
                                    <div class="flex h-12 w-22 shrink-0 items-center justify-center rounded border border-hairline bg-primary-soft font-mono text-2xs font-bold text-primary">
                                        BANNER #{{ $loop->iteration }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">{{ $slide->title }}</p>
                                    <p class="truncate text-xs text-ink-muted">Alt: {{ $slide->image_alt }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 text-xs">
                                <button type="button" wire:click="toggleActive({{ $slide->id }})" @class([
                                    'min-h-11 cursor-pointer rounded border px-3 py-1 font-semibold',
                                    'border-emerald-300 bg-emerald-50 text-emerald-800' => $slide->is_active,
                                    'border-hairline bg-mist text-ink-muted' => ! $slide->is_active,
                                ])>
                                    {{ $slide->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                                <button type="button" wire:click="edit({{ $slide->id }})" class="min-h-11 cursor-pointer rounded border border-primary px-3 py-1 font-semibold text-primary hover:bg-primary-soft">
                                    Edit
                                </button>
                                <button type="button" wire:click="delete({{ $slide->id }})" wire:confirm="Hapus slide ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-3 py-1 font-semibold text-red-700 hover:bg-red-50">
                                    Hapus
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
