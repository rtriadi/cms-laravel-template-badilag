<div class="fixed right-4 bottom-4 z-40 sm:right-6 sm:bottom-6" data-a11y-widget>
    <button
        id="a11y-toggle-btn"
        type="button"
        aria-expanded="false"
        aria-controls="a11y-panel"
        class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-full border-2 border-white bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-lg transition-colors hover:bg-primary-hover"
    >
        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
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
        class="mt-3 w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-hairline bg-white p-4 text-ink shadow-2xl"
    >
        <div class="flex items-center justify-between border-b border-hairline pb-2.5">
            <h2 id="a11y-panel-title" class="font-serif text-base font-bold text-ink">
                Menu Aksesibilitas
            </h2>
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
            <div class="rounded-lg border border-hairline bg-mist p-3">
                <p class="font-bold text-ink">Pembaca Halaman (Suara id-ID)</p>
                <p id="a11y-tts-status" class="mt-0.5 text-[11px] text-ink-muted">Siap membacakan teks halaman.</p>
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
                        class="col-span-2 min-h-11 cursor-pointer rounded-md border border-hairline bg-white px-2.5 py-1.5 font-semibold text-ink hover:bg-gray-50"
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
                        class="min-h-11 cursor-pointer rounded-md border border-hairline bg-mist px-3 font-semibold text-ink hover:bg-gray-100"
                    >
                        Perkecil Teks
                    </button>
                    <button
                        id="a11y-scale-up"
                        type="button"
                        class="min-h-11 cursor-pointer rounded-md border border-hairline bg-mist px-3 font-semibold text-ink hover:bg-gray-100"
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
                    <span data-a11y-state="hc" class="font-mono text-[11px] text-ink-muted">Nonaktif</span>
                </button>
                <button
                    type="button"
                    data-a11y-toggle="dyslexia"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Spasi Ramah Disleksia</span>
                    <span data-a11y-state="dyslexia" class="font-mono text-[11px] text-ink-muted">Nonaktif</span>
                </button>
                <button
                    type="button"
                    data-a11y-toggle="links"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Sorot Semua Tautan</span>
                    <span data-a11y-state="links" class="font-mono text-[11px] text-ink-muted">Nonaktif</span>
                </button>
                <button
                    type="button"
                    data-a11y-toggle="motion"
                    aria-pressed="false"
                    class="flex min-h-11 cursor-pointer items-center justify-between rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist"
                >
                    <span>Hentikan Animasi</span>
                    <span data-a11y-state="motion" class="font-mono text-[11px] text-ink-muted">Nonaktif</span>
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

<script>
    (() => {
        const STORAGE_KEY = 'satkercms_a11y_v1';
        const toggleBtn = document.getElementById('a11y-toggle-btn');
        const closeBtn = document.getElementById('a11y-close-btn');
        const panel = document.getElementById('a11y-panel');
        const scaleLabel = document.getElementById('a11y-scale-label');
        const ttsStatus = document.getElementById('a11y-tts-status');

        if (!toggleBtn || !panel) {
            return;
        }

        const defaultState = { scale: 100, hc: false, dyslexia: false, links: false, motion: false };
        let state = { ...defaultState };

        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                state = { ...defaultState, ...JSON.parse(saved) };
            }
        } catch (e) {
            state = { ...defaultState };
        }

        const applyState = () => {
            const clampedScale = Math.min(200, Math.max(100, Number(state.scale) || 100));
            state.scale = clampedScale;
            document.documentElement.style.fontSize = clampedScale + '%';
            document.documentElement.classList.toggle('a11y-hc', Boolean(state.hc));
            document.documentElement.classList.toggle('a11y-dyslexia', Boolean(state.dyslexia));
            document.documentElement.classList.toggle('a11y-highlight-links', Boolean(state.links));
            document.documentElement.classList.toggle('a11y-no-motion', Boolean(state.motion));

            if (scaleLabel) {
                scaleLabel.textContent = clampedScale + '%';
            }

            ['hc', 'dyslexia', 'links', 'motion'].forEach((key) => {
                const btn = panel.querySelector(`[data-a11y-toggle="${key}"]`);
                const badge = panel.querySelector(`[data-a11y-state="${key}"]`);
                const active = Boolean(state[key]);
                if (btn) {
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                }
                if (badge) {
                    badge.textContent = active ? 'Aktif' : 'Nonaktif';
                    badge.classList.toggle('text-primary', active);
                    badge.classList.toggle('font-bold', active);
                }
            });

            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            } catch (e) {
                // Ignore storage quota issues
            }
        };

        const openPanel = () => {
            panel.hidden = false;
            toggleBtn.setAttribute('aria-expanded', 'true');
            closeBtn?.focus();
        };

        const closePanel = (returnFocus = false) => {
            panel.hidden = true;
            toggleBtn.setAttribute('aria-expanded', 'false');
            if (returnFocus) {
                toggleBtn.focus();
            }
        };

        toggleBtn.addEventListener('click', () => {
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel(false);
            }
        });

        closeBtn?.addEventListener('click', () => closePanel(true));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !panel.hidden) {
                closePanel(true);
            }
        });

        document.getElementById('a11y-scale-up')?.addEventListener('click', () => {
            state.scale = Math.min(200, state.scale + 25);
            applyState();
        });

        document.getElementById('a11y-scale-down')?.addEventListener('click', () => {
            state.scale = Math.max(100, state.scale - 25);
            applyState();
        });

        panel.querySelectorAll('[data-a11y-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.getAttribute('data-a11y-toggle');
                if (key && Object.prototype.hasOwnProperty.call(state, key)) {
                    state[key] = !state[key];
                    applyState();
                }
            });
        });

        document.getElementById('a11y-reset-btn')?.addEventListener('click', () => {
            state = { ...defaultState };
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }
            applyState();
            if (ttsStatus) {
                ttsStatus.textContent = 'Pengaturan aksesibilitas dikembalikan ke awal.';
            }
        });

        const speakText = (text) => {
            if (!('speechSynthesis' in window)) {
                if (ttsStatus) {
                    ttsStatus.textContent = 'Peramban tidak mendukung fitur pembaca suara Web Speech API.';
                }
                return;
            }
            const cleanText = (text || '').replace(/\s+/g, ' ').trim();
            if (!cleanText) {
                if (ttsStatus) {
                    ttsStatus.textContent = 'Tidak ada teks yang dipilih untuk dibacakan.';
                }
                return;
            }
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(cleanText.slice(0, 6000));
            utterance.lang = 'id-ID';
            utterance.rate = 0.95;
            utterance.onstart = () => {
                if (ttsStatus) {
                    ttsStatus.textContent = 'Sedang membacakan teks (id-ID)...';
                }
            };
            utterance.onend = () => {
                if (ttsStatus) {
                    ttsStatus.textContent = 'Pembacaan selesai.';
                }
            };
            window.speechSynthesis.speak(utterance);
        };

        document.getElementById('a11y-tts-read-page')?.addEventListener('click', () => {
            const mainEl = document.getElementById('konten-utama');
            speakText(mainEl ? mainEl.innerText : document.body.innerText);
        });

        document.getElementById('a11y-tts-read-selection')?.addEventListener('click', () => {
            const selected = window.getSelection ? window.getSelection().toString() : '';
            speakText(selected);
        });

        document.getElementById('a11y-tts-stop')?.addEventListener('click', () => {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }
            if (ttsStatus) {
                ttsStatus.textContent = 'Pembacaan suara dihentikan.';
            }
        });

        applyState();
    })();
</script>

