<x-layouts.public title="Status Keamanan & Versi Sistem">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <article class="rounded-xl border border-hairline bg-white p-6 sm:p-8">
            <h1 class="font-serif text-2xl font-bold text-ink sm:text-3xl">
                Dokumentasi Versi Perangkat Lunak &amp; Keamanan Portal
            </h1>
            <p class="mt-2 text-sm text-ink-body">
                Halaman ini mendokumentasikan versi sistem resmi yang aktif sebagai bukti pemeliharaan keamanan dan penggunaan versi perangkat lunak non-EOL (End of Life), sementara header sidik jari peladen (<code>X-Powered-By</code> / <code>Server</code>) disembunyikan pada respons HTTP.
            </p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-hairline bg-mist p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Perangkat Lunak CMS</dt>
                    <dd class="mt-1 font-mono text-base font-bold text-primary-deep">SatkerCMS v{{ $cmsVersion }}</dd>
                    <p class="mt-1 text-xs text-ink-muted">Sistem Manajemen Konten Website</p>
                </div>

                <div class="rounded-lg border border-hairline bg-mist p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Framework &amp; PHP</dt>
                    <dd class="mt-1 font-mono text-base font-bold text-primary-deep">Laravel {{ $laravelVersion }} | PHP {{ $phpVersion }}</dd>
                    <p class="mt-1 text-xs {{ $isPhpNonEol ? 'text-emerald-800' : 'text-red-700' }}">
                        Status PHP: {{ $isPhpNonEol ? 'Didukung Resmi (Non-EOL)' : 'Perlu Pembaruan' }}
                    </p>
                </div>

                <div class="rounded-lg border border-hairline bg-mist p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Autentikasi Panel Pengelola</dt>
                    <dd class="mt-1 text-sm font-bold text-ink">Wajib Multi-Factor Authentication (2FA TOTP)</dd>
                    <p class="mt-1 text-xs text-ink-muted">Seluruh akun admin wajib mengonfirmasi TOTP 2FA serta verifikasi captcha lokal saat masuk.</p>
                </div>

            </dl>
        </article>
    </div>
</x-layouts.public>

