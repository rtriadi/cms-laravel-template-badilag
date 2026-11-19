<x-layouts.auth :title="'Verifikasi Dua Faktor (2FA)'">
    <div x-data="{ recovery: false }" class="mx-auto w-full max-w-md rounded-xl border border-primary bg-white p-6 sm:p-8">
        <div class="mb-6">
            <h2 class="font-serif text-xl font-bold text-primary-deep">Verifikasi Authenticator (2FA TOTP)</h2>
            <p class="mt-1 text-sm text-ink-body" x-show="! recovery">
                Masukkan 6 digit kode dari aplikasi autentikator Anda.
            </p>
            <p class="mt-1 text-sm text-ink-body" x-cloak x-show="recovery">
                Masukkan salah satu kode pemulihan darurat (recovery code) Anda.
            </p>
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
            @csrf

            <div x-show="! recovery">
                <flux:field>
                    <flux:label for="code">Kode Authenticator (6 Digit)</flux:label>
                    <flux:input id="code" name="code" type="text" inputmode="numeric" autofocus autocomplete="one-time-code" placeholder="000000" />
                    <flux:error name="code" />
                </flux:field>
            </div>

            <div x-cloak x-show="recovery">
                <flux:field>
                    <flux:label for="recovery_code">Kode Pemulihan</flux:label>
                    <flux:input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" placeholder="xxxx-xxxx" />
                    <flux:error name="recovery_code" />
                </flux:field>
            </div>

            <div class="flex items-center justify-between text-sm">
                <button
                    type="button"
                    class="font-medium text-primary underline underline-offset-2"
                    x-show="! recovery"
                    x-on:click="recovery = true; $nextTick(() => { document.getElementById('recovery_code').focus(); })"
                >
                    Gunakan kode pemulihan
                </button>
                <button
                    type="button"
                    class="font-medium text-primary underline underline-offset-2"
                    x-cloak
                    x-show="recovery"
                    x-on:click="recovery = false; $nextTick(() => { document.getElementById('code').focus(); })"
                >
                    Gunakan kode authenticator
                </button>
            </div>

            <flux:button type="submit" variant="primary" class="min-h-11 w-full bg-primary font-semibold text-white hover:bg-primary-hover">
                Verifikasi &amp; Masuk
            </flux:button>
        </form>
    </div>
</x-layouts.auth>

