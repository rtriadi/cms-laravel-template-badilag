<x-layouts.auth :title="'Konfirmasi Kata Sandi'">
    <div class="studio-card p-6 shadow-sm sm:p-8">
        <div class="mb-6 space-y-2">
            <span class="inline-flex rounded-md bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary">
                Konfirmasi Keamanan
            </span>
            <h2 class="font-serif text-2xl font-bold text-ink">Konfirmasi Kata Sandi</h2>
            <p class="text-sm text-ink-muted">
                Demi keamanan akun, masukkan ulang kata sandi Anda sebelum melanjutkan aksi sensitif.
            </p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <flux:field>
                <flux:label for="password">Kata sandi <span class="text-red-700">*</span></flux:label>
                <flux:input id="password" name="password" type="password" required autocomplete="current-password" autofocus placeholder="Masukkan kata sandi saat ini" />
                <flux:error name="password" />
            </flux:field>

            <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                Konfirmasi dan Lanjutkan
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
