<div class="fixed right-4 bottom-20 z-40 sm:right-6 lg:bottom-6" data-a11y-widget>
    <button
        id="a11y-toggle-btn"
        type="button"
        aria-expanded="false"
        aria-controls="a11y-panel"
        class="pub-btn inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-full border-2 border-white bg-obsidian px-4 py-2.5 text-xs font-bold text-white shadow-lg hover:bg-primary"
    >
        <svg class="size-4 shrink-0 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="4.5" r="2"></circle>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8.5h14M12 8.5v5.5m0 0-3 6m3-6 3 6"></path>
        </svg>
        <span>Aksesibilitas</span>
    </button>

    <div
        id="a11y-panel"
        role="dialog"
        aria-modal="false"
        aria-labelledby="a11y-panel-title"
        hidden
        class="mt-3 max-h-[calc(100vh-7rem)] w-80 max-w-[calc(100vw-2rem)] overflow-y-auto overscroll-contain rounded-xl border border-hairline bg-white p-4 text-ink shadow-2xl"
    >
        <div class="flex items-center justify-between border-b border-hairline pb-2.5">
            <div>
                <span class="font-mono text-2xs font-bold text-primary uppercase">WCAG 2.2 AA/AAA</span>
                <h2 id="a11y-panel-title" class="font-serif text-base font-bold text-ink">
                    Menu Aksesibilitas
                </h2>
            </div>
            <button
                id="a11y-close-btn"
                type="button"
                class="min-h-11 cursor-pointer rounded-md border border-hairline px-3 text-xs font-semibold text-ink hover:bg-mist"
            >
                Tutup
            </button>
        </div>

        <div class="mt-3 space-y-3 text-xs">
            <!-- Pembaca Suara Bahasa Indonesia (TTS) -->
            <div class="rounded-lg border border-hairline bg-ivory p-3">
                <p class="font-bold text-ink">Pembaca Halaman (Suara id-ID)</p>
                <p id="a11y-tts-status" class="mt-0.5 text-xs text-ink-muted">Siap membacakan teks halaman.</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <button
                        id="a11y-tts-read-page"
                        type="button"
                        class="min-h-11 cursor-pointer rounded-md bg-primary px-2.5 py-1.5 font-semibold text-white hover:bg-primary-hover"
                    >
                        Baca Halaman
                    </button>
                    <button
                        id="a11y-tts-read-selection"
                        type="button"
                        class="min-h-11 cursor-pointer rounded-md border border-primary bg-white px-2.5 py-1.5 font-semibold text-primary hover:bg-primary-soft"
                    >
                        Baca Pilihan
                    </button>
                    <button
                        id="a11y-tts-stop"
                        type="button"
                        class="col-span-2 min-h-11 cursor-pointer rounded-md border border-hairline bg-white px-2.5 py-1.5 font-semibold text-ink hover:bg-mist"
                    >
                        Hentikan Pembacaan
                    </button>
                </div>
            </div>

            <!-- Ukuran Teks 100% - 200% -->
            <div class="rounded-lg border border-hairline bg-white p-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-ink">Ukuran Teks</span>
                    <span id="a11y-scale-label" class="font-mono font-bold text-primary">100%</span>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <button
                        id="a11y-scale-down"
                        type="button"
                        class="min-h-11 cursor-pointer rounded-md border border-hairline bg-ivory px-3 font-semibold text-ink hover:bg-hairline/40"
                    >
                        Perkecil Teks
                    </button>
                    <button
                        id="a11y-scale-up"
                        type="button"
                        class="min-h-11 cursor-pointer rounded-md border border-hairline bg-ivory px-3 font-semibold text-ink hover:bg-hairline/40"
                    >
                        Perbesar Teks (Max 200%)
                    </button>
                </div>
            </div>

            <!-- Toggles Visual & Motorik -->
            <div class="grid grid-cols-1 gap-2">
                <button
                    type="button"
                    data-a11y-toggle="hc"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Mode Kontras Tinggi</span>
                    <span data-a11y-state="hc" class="font-mono text-2xs text-ink-muted">Nonaktif</span>
                </button>
                <button
                    type="button"
                    data-a11y-toggle="dyslexia"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Spasi Ramah Disleksia</span>
                    <span data-a11y-state="dyslexia" class="font-mono text-2xs text-ink-muted">Nonaktif</span>
                </button>
                <button
                    type="button"
                    data-a11y-toggle="links"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Sorot Semua Tautan</span>
                    <span data-a11y-state="links" class="font-mono text-2xs text-ink-muted">Nonaktif</span>
                </button>
                <button
                    type="button"
                    data-a11y-toggle="motion"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Hentikan Animasi</span>
                    <span data-a11y-state="motion" class="font-mono text-2xs text-ink-muted">Nonaktif</span>
                </button>
            </div>

            <div class="flex items-center justify-between pt-1">
                <a href="{{ route('public.accessibility') }}" class="font-semibold text-primary underline">
                    Panduan Aksesibilitas
                </a>
                <button
                    id="a11y-reset-btn"
                    type="button"
                    class="min-h-11 cursor-pointer rounded-md border border-red-200 px-3 font-semibold text-red-700 hover:bg-red-50"
                >
                    Reset Pengaturan
                </button>
            </div>
        </div>
    </div>
</div>
