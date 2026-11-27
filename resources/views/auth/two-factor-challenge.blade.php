<x-layouts.auth :title="'Verifikasi Dua Faktor (2FA)'">
    <div x-data="{ recovery: false }" class="studio-card p-6 shadow-sm sm:p-8">
        <div class="mb-6 space-y-2">
            <span class="inline-flex rounded-md bg-[#fff4e0] px-2.5 py-1 text-xs font-semibold text-[#8a4b00]">
                Langkah 2 dari 2 &middot; Wajib
            </span>
            <h2 class="font-serif text-2xl font-bold text-ink">Masukkan Kode 2FA</h2>
            <p class="text-sm text-ink-muted" x-show="! recovery">
                Buka aplikasi autentikator (Google Authenticator / Authy) lalu masukkan 6 digit angka.
            </p>
            <p class="text-sm text-ink-muted" x-cloak x-show="recovery">
                Masukkan salah satu kode pemulihan darurat (recovery code) akun Anda.
            </p>
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
            @csrf

            <div x-show="! recovery">
                <flux:field>
                    <flux:label for="code">Kode 6 digit TOTP <span class="text-red-700">*</span></flux:label>
                    <flux:input id="code" name="code" type="text" inputmode="numeric" autofocus autocomplete="one-time-code" placeholder="000000" class="font-mono tracking-[0.25em]" />
                    <flux:error name="code" />
                </flux:field>
            </div>

            <div x-cloak x-show="recovery">
                <flux:field>
                    <flux:label for="recovery_code">Kode pemulihan darurat <span class="text-red-700">*</span></flux:label>
                    <flux:input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" placeholder="xxxx-xxxx" class="font-mono" />
                    <flux:error name="recovery_code" />
                </flux:field>
            </div>

            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                Verifikasi dan Buka Dashboard
            </flux:button>

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline pt-4 text-sm">
                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-xs font-semibold text-ink-muted hover:text-ink">
                    &larr; Kembali ke login
                </a>
                <button
                    type="button"
                    class="inline-flex min-h-11 cursor-pointer items-center text-xs font-semibold text-primary hover:underline"
                    x-show="! recovery"
                    x-on:click="recovery = true; $nextTick(() => { document.getElementById('recovery_code').focus(); })"
                >
                    Gunakan kode pemulihan
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 cursor-pointer items-center text-xs font-semibold text-primary hover:underline"
                    x-cloak
                    x-show="recovery"
                    x-on:click="recovery = false; $nextTick(() => { document.getElementById('code').focus(); })"
                >
                    Gunakan kode autentikator
                </button>
            </div>
        </form>
    </div>
</x-layouts.auth>
