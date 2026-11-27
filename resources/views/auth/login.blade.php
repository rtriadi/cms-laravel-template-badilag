<x-layouts.auth :title="'Masuk Admin'">
    <div class="studio-card p-5 shadow-sm sm:p-8">
        <div class="mb-6 space-y-2">
            <span class="inline-flex rounded-md bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary">
                Langkah 1 dari 2
            </span>
            <h2 class="font-serif text-2xl font-bold text-ink">Masuk ke Panel Admin</h2>
            <p class="text-sm text-ink-muted">
                Gunakan email dinas dan kata sandi akun pengelola Anda.
            </p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <flux:field>
                <flux:label for="email">Email pengelola <span class="text-red-700">*</span></flux:label>
                <flux:input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@pa-satker.go.id" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label for="password">Kata sandi <span class="text-red-700">*</span></flux:label>
                <flux:input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label for="captcha">Kode keamanan (Captcha lokal) <span class="text-red-700">*</span></flux:label>
                <div class="flex flex-col gap-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <img
                            id="local-captcha-img"
                            src="{{ route('captcha.image') }}"
                            width="180"
                            height="48"
                            alt="Gambar kode keamanan captcha"
                            class="h-12 w-36 rounded-lg border border-primary/30 bg-primary-soft sm:w-44"
                        >
                        <p id="local-captcha-text" role="status" aria-live="polite" hidden class="flex min-h-12 max-w-full items-center justify-center rounded-lg border border-primary/30 bg-primary-soft px-3.5 py-2 text-xs font-semibold leading-snug text-primary-deep"></p>
                        <button
                            type="button"
                            id="local-captcha-refresh"
                            aria-label="Muat ulang kode keamanan"
                            class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink hover:bg-mist sm:px-3"
                        >
                            Ulang
                        </button>
                        <button
                            type="button"
                            id="local-captcha-mode"
                            aria-controls="local-captcha-text"
                            aria-expanded="false"
                            class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 text-xs font-semibold text-ink hover:bg-mist sm:px-3"
                        >
                            Mode teks
                        </button>
                    </div>
                    <flux:input id="captcha" name="captcha" type="text" required autocomplete="off" aria-describedby="captcha-help" placeholder="Ketik kode gambar atau jawaban angka" />
                </div>
                <p id="captcha-help" class="mt-1 text-xs text-ink-muted">
                    Ketik 5 karakter pada gambar, atau jawab pertanyaan angka pada mode teks. Huruf besar dan kecil tidak dibedakan.
                </p>
                <flux:error name="captcha" />
            </flux:field>

            <div class="flex items-center justify-between pt-1">
                <label for="remember" class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 text-sm text-ink-body">
                    <input id="remember" name="remember" type="checkbox" class="size-4.5 rounded border-[#768780] text-primary focus:ring-primary">
                    <span>Ingat perangkat ini</span>
                </label>
            </div>

            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                Masuk dan Lanjut ke 2FA
            </flux:button>
        </form>

        <div class="mt-5 rounded-xl border border-hairline bg-mist px-4 py-3 text-xs leading-relaxed text-ink-body">
            <p class="font-semibold text-ink">Akses terbatas untuk aparatur satker</p>
            <p class="mt-0.5 text-ink-muted">Percobaan masuk dibatasi maksimal 5 kali per menit dan wajib menggunakan kode 2FA TOTP.</p>
        </div>

        <script>
            (() => {
                const image = document.getElementById('local-captcha-img');
                const text = document.getElementById('local-captcha-text');
                const refresh = document.getElementById('local-captcha-refresh');
                const mode = document.getElementById('local-captcha-mode');
                let textMode = false;

                const loadImage = () => {
                    image.hidden = false;
                    text.hidden = true;
                    image.src = @js(route('captcha.image')) + '?t=' + Date.now();
                };

                const loadText = async () => {
                    try {
                        const response = await fetch(@js(route('captcha.text')), { cache: 'no-store' });
                        text.textContent = (await response.text()).trim();
                        text.hidden = false;
                        image.hidden = true;
                    } catch (error) {
                        textMode = false;
                        mode.setAttribute('aria-expanded', 'false');
                        mode.textContent = 'Mode teks';
                        loadImage();
                    }
                };

                refresh.addEventListener('click', () => (textMode ? loadText() : loadImage()));

                mode.addEventListener('click', () => {
                    textMode = ! textMode;
                    mode.setAttribute('aria-expanded', textMode ? 'true' : 'false');
                    mode.textContent = textMode ? 'Mode gambar' : 'Mode teks';
                    if (textMode) {
                        loadText();
                    } else {
                        loadImage();
                    }
                });
            })();
        </script>
    </div>
</x-layouts.auth>
