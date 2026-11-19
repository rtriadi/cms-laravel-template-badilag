<x-layouts.auth :title="'Masuk Admin'">
    <div class="mx-auto w-full max-w-md rounded-xl border border-hairline bg-white p-6 sm:p-8">
        <div class="mb-6">
            <h2 class="font-serif text-xl font-bold text-ink">Masuk Pengelola SatkerCMS</h2>
            <p class="mt-1 text-sm text-ink-muted">
                Akses dibatasi maksimal 5 percobaan per menit dan wajib menggunakan verifikasi dua faktor (2FA).
            </p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-md border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <flux:field>
                <flux:label for="email">Alamat Email Resmi</flux:label>
                <flux:input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@pa-satker.go.id" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label for="password">Kata Sandi</flux:label>
                <flux:input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label for="captcha">Kode Keamanan (Captcha Lokal)</flux:label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <img
                            id="local-captcha-img"
                            src="{{ route('captcha.image') }}"
                            width="180"
                            height="48"
                            alt="Gambar kode keamanan captcha"
                            class="h-12 w-44 rounded-md border border-primary/30 bg-primary-soft"
                        >
                        <button
                            type="button"
                            onclick="document.getElementById('local-captcha-img').src='{{ route('captcha.image') }}?t='+Date.now()"
                            aria-label="Muat ulang gambar captcha"
                            class="inline-flex min-h-11 items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                        >
                            Ulang
                        </button>
                    </div>
                    <flux:input id="captcha" name="captcha" type="text" required autocomplete="off" placeholder="Ketik 5 karakter" class="flex-1" />
                </div>
                <flux:error name="captcha" />
            </flux:field>

            <div class="flex items-center justify-between">
                <label for="remember" class="inline-flex items-center gap-2 text-sm text-ink-body">
                    <input id="remember" name="remember" type="checkbox" class="size-4 rounded border-hairline text-primary focus:ring-primary">
                    <span>Ingat sesi ini</span>
                </label>
            </div>

            <flux:button type="submit" variant="primary" class="min-h-11 w-full bg-primary font-semibold text-white hover:bg-primary-hover">
                Lanjut ke Verifikasi 2FA
            </flux:button>
        </form>
    </div>
</x-layouts.auth>

