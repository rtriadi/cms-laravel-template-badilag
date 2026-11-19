<x-layouts.auth :title="'Konfirmasi Kata Sandi'">
    <div class="mx-auto w-full max-w-md rounded-xl border border-hairline bg-white p-6 sm:p-8">
        <div class="mb-6">
            <h2 class="font-serif text-xl font-bold text-ink">Konfirmasi Kata Sandi</h2>
            <p class="mt-1 text-sm text-ink-muted">
                Demi keamanan, masukkan ulang kata sandi Anda sebelum melanjutkan aksi sensitif.
            </p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <flux:field>
                <flux:label for="password">Kata Sandi</flux:label>
                <flux:input id="password" name="password" type="password" required autocomplete="current-password" autofocus />
                <flux:error name="password" />
            </flux:field>

            <flux:button type="submit" variant="primary" class="min-h-11 w-full bg-primary font-semibold text-white hover:bg-primary-hover">
                Konfirmasi
            </flux:button>
        </form>
    </div>
</x-layouts.auth>

