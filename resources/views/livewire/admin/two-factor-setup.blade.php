<div class="mx-auto w-full max-w-xl rounded-xl border border-primary bg-white p-6 sm:p-8">
    <div class="mb-6">
        <h2 class="font-serif text-2xl font-bold text-primary-deep">Aktivasi Keamanan 2FA (Wajib)</h2>
        <p class="mt-1 text-sm text-ink-body">
            Sesuai SK Dirjen Badilag Kriteria 3.a, seluruh akun pengelola wajib mengaktifkan autentikasi dua faktor (TOTP).
        </p>
    </div>

    @if ($isConfirmed)
        <div class="mb-5 rounded-md border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            Autentikasi dua faktor (2FA TOTP) pada akun Anda sudah aktif.
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center rounded-md bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-hover">
                Masuk ke Dashboard Admin
            </a>
            <flux:button type="button" wire:click="regenerateRecoveryCodes" variant="outline" class="min-h-11">
                Buat Ulang Kode Pemulihan
            </flux:button>
        </div>
    @else
        <div class="space-y-5">
            <div class="rounded-lg border border-hairline bg-mist p-4">
                <p class="mb-3 text-sm font-semibold text-ink">
                    1. Pindai kode QR di aplikasi Authenticator (Google Authenticator / Aegis / Authy)
                </p>
                @if ($qrCodeSvg)
                    <div class="inline-block rounded-md border border-hairline bg-white p-3">
                        {!! $qrCodeSvg !!}
                    </div>
                @endif
                @if ($secretKey)
                    <p class="mt-3 text-xs text-ink-muted">
                        Atau masukkan kunci manual:
                        <code class="rounded bg-white px-2 py-1 font-mono font-semibold text-ink">{{ $secretKey }}</code>
                    </p>
                @endif
            </div>

            <form wire:submit="confirmTwoFactor" class="space-y-4">
                <flux:field>
                    <flux:label for="totp_code">2. Masukkan Kode 6 Digit untuk Konfirmasi</flux:label>
                    <flux:input id="totp_code" wire:model="code" inputmode="numeric" placeholder="000000" required />
                    <flux:error name="code" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full bg-primary font-semibold text-white hover:bg-primary-hover">
                    Konfirmasi &amp; Aktifkan 2FA
                </flux:button>
            </form>
        </div>
    @endif

    @if (count($recoveryCodes) > 0 && ($showRecoveryCodes || ! $isConfirmed))
        <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="text-xs font-bold text-amber-900">
                Simpan Kode Pemulihan Darurat Berikut di Tempat Aman:
            </p>
            <div class="mt-2 grid grid-cols-2 gap-2 font-mono text-xs text-amber-950">
                @foreach ($recoveryCodes as $recoveryCode)
                    <div class="rounded bg-white px-2 py-1">{{ $recoveryCode }}</div>
                @endforeach
            </div>
        </div>
    @endif
</div>
