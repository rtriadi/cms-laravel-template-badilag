@php
    $isCompliant = $activeCount >= \App\Models\HomeSlider::MIN_ACTIVE_SLIDES && $activeCount <= \App\Models\HomeSlider::MAX_ACTIVE_SLIDES;
@endphp
<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Slider Beranda</h1>
            <p class="mt-1 text-sm text-ink-body">
                Atur gambar banner utama halaman depan. Untuk menjaga waktu muat di bawah 5 detik sesuai juknis SINOVA, gunakan 3 sampai 5 slide gambar statis tanpa video.
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2 md:flex-nowrap md:justify-end">
            <a href="{{ route('admin.menus') }}" class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink hover:border-primary hover:text-primary sm:min-h-10">
                Kelola Menu Navigasi
            </a>
            <button type="button" wire:click="createNew" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center whitespace-nowrap rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-10">
                + Tambah Slide Baru
            </button>
        </div>
    </div>

    <div @class([
        'flex flex-col gap-2 rounded-xl border px-4 py-3.5 text-xs sm:flex-row sm:items-center sm:justify-between',
        'border-emerald-200 bg-primary-soft text-primary-deep' => $isCompliant,
        'border-amber-300 bg-amber-50 text-amber-950' => ! $isCompliant,
    ])>
        <div>
            <p class="text-sm font-bold">
                {{ $isCompliant ? 'Kepatuhan Slider Terpenuhi: ' : 'Perhatian Jumlah Slide: ' }}{{ $activeCount }} dari {{ \App\Models\HomeSlider::MAX_ACTIVE_SLIDES }} slide aktif
            </p>
            <p class="mt-0.5 text-xs">
                Rasio gambar disarankan 16:9 (1600x900 px) tanpa video atau live streaming sesuai SK Dirjen Badilag Bab III.C.3.
            </p>
        </div>
        <span @class([
            'inline-flex w-fit shrink-0 items-center gap-1.5 rounded-md px-2.5 py-1 font-mono text-xs font-bold',
            'bg-emerald-100 text-emerald-800' => $isCompliant,
            'bg-amber-200/70 text-amber-950' => ! $isCompliant,
        ])>
            {{ $activeCount }} / {{ \App\Models\HomeSlider::MAX_ACTIVE_SLIDES }} Aktif
        </span>
    </div>

    @error('slider_limit')
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Daftar Slide (7 cols) -->
        <section class="space-y-4 lg:col-span-7">
            @if ($sliders->isEmpty())
                <div class="studio-card px-6 py-12 text-center">
                    <p class="text-base font-bold text-ink">Belum ada slide tersimpan</p>
                    <p class="mt-1 text-sm text-ink-muted">Tambahkan minimal 3 slide gambar beranda melalui formulir di samping.</p>
                </div>
            @else
                @foreach ($sliders as $slide)
                    <article wire:key="slide-{{ $slide->id }}" @class([
                        'studio-card flex flex-col gap-4 p-4 sm:flex-row sm:items-center',
                        'border-primary' => $editingId === $slide->id,
                    ])>
                        <!-- 16:9 Thumbnail Preview -->
                        <div class="relative aspect-video w-full shrink-0 overflow-hidden rounded-lg border border-hairline bg-obsidian sm:w-48">
                            @if ($slide->image_path)
                                <img
                                    src="{{ asset('storage/' . $slide->image_path) }}"
                                    alt="{{ $slide->image_alt }}"
                                    width="320"
                                    height="180"
                                    class="size-full object-cover"
                                />
                            @else
                                <div class="flex size-full flex-col justify-between p-3 text-white">
                                    <span class="w-fit rounded bg-gold px-2 py-0.5 font-mono text-[10px] font-bold text-ink">Slide {{ $loop->iteration }}</span>
                                    <span class="font-mono text-[10px] text-[#8dbfac]">16:9 Placeholder</span>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-bold text-ink">{{ $slide->title }}</h2>
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-800' => $slide->is_active,
                                    'bg-mist text-ink-muted' => ! $slide->is_active,
                                ])>
                                    <span class="size-1.5 rounded-full {{ $slide->is_active ? 'bg-emerald-700' : 'bg-ink-muted' }}" aria-hidden="true"></span>
                                    {{ $slide->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            @if ($slide->subtitle)
                                <p class="text-xs leading-relaxed text-ink-body">{{ $slide->subtitle }}</p>
                            @endif

                            <p class="truncate font-mono text-xs text-ink-muted">
                                Alt: {{ $slide->image_alt }}
                                @if ($slide->cta_url)
                                    &middot; {{ $slide->cta_url }}
                                @endif
                            </p>

                            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                                <button type="button" wire:click="toggleActive({{ $slide->id }})" @class([
                                    'inline-flex min-h-11 cursor-pointer items-center rounded-lg border px-3 font-semibold sm:min-h-8',
                                    'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' => $slide->is_active,
                                    'border-hairline bg-mist text-ink hover:bg-white' => ! $slide->is_active,
                                ])>
                                    {{ $slide->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                                <button type="button" wire:click="edit({{ $slide->id }})" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary sm:min-h-8">
                                    Ubah
                                </button>
                                <button type="button" wire:click="delete({{ $slide->id }})" wire:confirm="Hapus slide ini?" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            @endif
        </section>

        <!-- Editor Slide (5 cols) -->
        <aside class="studio-card p-5 sm:p-6 lg:sticky lg:top-20 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3.5">
                <div>
                    <h2 class="text-base font-bold text-ink">
                        {{ $editingId ? 'Ubah Slide Beranda' : 'Tambah Slide Beranda' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-ink-muted">Perubahan langsung memperbarui tampilan banner utama.</p>
                </div>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="min-h-10 cursor-pointer text-xs font-semibold text-primary hover:underline">
                        + Slide Baru
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="sl_img">Gambar Banner 16:9 (Maks. 2 MB)</flux:label>
                    <input id="sl_img" type="file" wire:model="image" accept="image/*" class="block w-full cursor-pointer text-xs text-ink file:mr-3 file:min-h-10 file:cursor-pointer file:rounded-lg file:border file:border-hairline file:bg-mist file:px-3 file:text-xs file:font-semibold file:text-primary" />
                    <div class="mt-2 rounded-xl border border-hairline bg-mist px-3.5 py-2.5 text-xs leading-relaxed text-ink-body">
                        <p class="font-semibold text-ink">Panduan ukuran:</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4 text-[11.5px]">
                            <li>Resolusi ideal <span class="font-mono font-semibold">1600x900 px</span> atau <span class="font-mono">1280x720 px</span> (16:9).</li>
                            <li>Format JPG, PNG, atau WebP (maks. 2 MB, otomatis dikonversi ke WebP).</li>
                            <li>Sisakan ruang aman &plusmn;10% dari tepi agar tidak terpotong di layar mobile.</li>
                        </ul>
                    </div>
                    <div wire:loading wire:target="image" role="status" aria-live="polite" class="mt-1 text-xs font-semibold text-primary">Mengunggah gambar slide...</div>
                    <flux:error name="image" />
                </flux:field>

                <flux:field>
                    <flux:label for="sl_title">Judul utama <span class="text-red-700">*</span></flux:label>
                    <flux:input id="sl_title" wire:model="title" required placeholder="Contoh: Pelayanan Prima Berintegritas" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="sl_sub">Subjudul singkat (opsional)</flux:label>
                    <flux:textarea id="sl_sub" wire:model="subtitle" rows="2" placeholder="Keterangan singkat pada banner" />
                    <flux:error name="subtitle" />
                </flux:field>

                <flux:field>
                    <flux:label for="sl_alt">Teks alternatif gambar <span class="text-red-700">*</span></flux:label>
                    <flux:input id="sl_alt" wire:model="image_alt" required placeholder="Deskripsi visual banner untuk pembaca layar" />
                    <p class="mt-1 text-xs text-ink-muted">Dibaca oleh pembaca layar tunanetra (WCAG AA).</p>
                    <flux:error name="image_alt" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="sl_cta_lbl">Label tombol (opsional)</flux:label>
                        <flux:input id="sl_cta_lbl" wire:model="cta_label" placeholder="Lihat Layanan" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="sl_cta_url">Tautan tujuan</flux:label>
                        <flux:input id="sl_cta_url" wire:model="cta_url" placeholder="/halaman/standar-pelayanan" class="font-mono" />
                        <flux:error name="cta_url" />
                    </flux:field>
                </div>

                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 text-sm font-medium text-ink">
                    <input type="checkbox" wire:model="is_active" class="size-4.5 rounded border-[#768780] text-primary">
                    <span>Aktifkan pada slider beranda</span>
                </label>
                <flux:error name="is_active" />

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Slide' : 'Simpan Slide Beranda' }}
                </flux:button>
            </form>
        </aside>
    </div>
</div>
