<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h1 class="font-serif text-2xl font-bold text-ink sm:text-3xl">Keamanan 2FA (Autentikasi 2 Langkah)</h1>
            <p class="mt-1 text-sm text-ink-body">
                Sesuai SK Dirjen Badilag Kriteria 3.a, setiap akun pengelola wajib mengaktifkan kode 6 digit TOTP untuk melindungi panel admin.
            </p>
        </div>
        @if ($isConfirmed)
            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-hairline bg-white px-4 text-sm font-semibold text-ink hover:bg-mist">
                &larr; Kembali ke Dashboard
            </a>
        @endif
    </div>

    @if ($isConfirmed)
        <div role="status" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3.5 text-sm">
            <div>
                <p class="font-bold text-primary">Autentikasi 2 langkah (2FA TOTP) aktif pada akun Anda</p>
                <p class="mt-0.5 text-xs text-ink-body">Anda akan diminta kode 6 digit dari aplikasi autentikator saat masuk dari perangkat baru.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                <span class="size-1.5 rounded-full bg-emerald-700" aria-hidden="true"></span>
                Terlindungi
            </span>
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Left Card: Status / QR Setup -->
        <section class="studio-card p-5 sm:p-7 lg:col-span-7">
            @if ($isConfirmed)
                <h2 class="text-base font-bold text-ink">Pengelolaan 2FA Akun</h2>
                <p class="mt-1 text-sm text-ink-muted">
                    Akun <strong>{{ $user->email }}</strong> sudah terlindungi verifikasi 2 langkah. Simpan kode pemulihan darurat di tempat aman apabila sewaktu-waktu perangkat ponsel diganti.
                </p>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-5 text-sm font-semibold text-white transition-colors hover:bg-primary-hover">
                        Masuk ke Dashboard Admin
                    </a>
                    <flux:button type="button" wire:click="regenerateRecoveryCodes" variant="outline" class="min-h-11 cursor-pointer">
                        Buat Ulang Kode Pemulihan
                    </flux:button>
                </div>
            @else
                <h2 class="text-base font-bold text-ink">Pindai kode QR dengan aplikasi autentikator</h2>
                <p class="mt-1 text-sm text-ink-muted">
                    Gunakan Google Authenticator, Microsoft Authenticator, Aegis, atau Authy di ponsel Anda.
                </p>

                <div class="mt-5 flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                    @if ($qrCodeSvg)
                        <div class="shrink-0 rounded-xl border border-hairline bg-white p-3">
                            {!! $qrCodeSvg !!}
                        </div>
                    @endif

                    <ol class="space-y-3 text-sm text-ink-body">
                        <li class="flex items-start gap-2.5">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">1</span>
                            <span>Buka aplikasi autentikator di ponsel lalu pilih Tambah Akun (+).</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">2</span>
                            <span>Pindai kode QR di samping atau masukkan kunci rahasia secara manual.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">3</span>
                            <span>Ketik 6 digit angka yang tampil di aplikasi pada kolom konfirmasi di bawah.</span>
                        </li>
                    </ol>
                </div>

                @if ($secretKey)
                    <div class="mt-5 rounded-xl border border-hairline bg-mist px-4 py-3 text-xs">
                        <span class="font-semibold text-ink-muted">Kunci rahasia manual:</span>
                        <code class="mt-1 block break-all font-mono text-sm font-semibold text-ink">{{ $secretKey }}</code>
                    </div>
                @endif

                <form wire:submit="confirmTwoFactor" class="mt-6 space-y-4 border-t border-hairline pt-5">
                    <flux:field>
                        <flux:label for="totp_code">Konfirmasi kode 6 digit dari aplikasi <span class="text-red-700">*</span></flux:label>
                        <flux:input id="totp_code" wire:model="code" inputmode="numeric" placeholder="000000" required class="font-mono tracking-[0.25em]" />
                        <flux:error name="code" />
                    </flux:field>

                    <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover sm:w-auto sm:px-6">
                        Konfirmasi &amp; Aktifkan 2FA
                    </flux:button>
                </form>
            @endif
        </section>

        <!-- Right Card: Recovery Codes -->
        <aside class="studio-card p-5 sm:p-7 lg:col-span-5" x-data="{ copied: false }">
            <h2 class="text-base font-bold text-ink">Kode pemulihan darurat</h2>
            <p class="mt-1 text-sm text-ink-muted">
                Simpan kode ini di tempat aman (cetak atau simpan di pengelola kata sandi). Dipakai apabila ponsel hilang atau diganti.
            </p>

            @if (count($recoveryCodes) > 0 && ($showRecoveryCodes || ! $isConfirmed))
                <div class="mt-4 grid grid-cols-2 gap-2 rounded-xl border border-hairline bg-mist p-3.5 font-mono text-xs text-ink">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <div class="rounded-lg border border-hairline/80 bg-white px-2.5 py-2 text-center font-semibold">{{ $recoveryCode }}</div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        x-on:click="navigator.clipboard?.writeText(@js(implode("\n", $recoveryCodes))); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink hover:bg-mist"
                    >
                        <span x-show="!copied">Salin Semua Kode</span>
                        <span x-cloak x-show="copied" class="text-primary">Tersalin ke Papan Klip</span>
                    </button>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-hairline bg-mist p-4 text-xs leading-relaxed text-ink-body">
                    Demi keamanan, kode pemulihan disembunyikan setelah 2FA aktif. Klik <strong>Buat Ulang Kode Pemulihan</strong> di panel sebelah kiri (memerlukan konfirmasi kata sandi) jika Anda perlu menerbitkan set kode baru.
                </div>
            @endif
        </aside>
    </div>
</div>
