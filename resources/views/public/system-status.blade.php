@php
    $sysDesc = 'Dokumentasi versi perangkat lunak SatkerCMS, Laravel, PHP non-EOL, dan standar autentikasi 2FA portal resmi ' . ($satker?->nama_satker ?? 'Peradilan Agama') . '.';
@endphp
<x-layouts.public title="Status Keamanan & Versi Sistem" :description="$sysDesc" :canonical="route('public.system-status')">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Keamanan &amp; Transparansi Infrastruktur</span>
            <h1 class="mt-2 font-serif text-2xl font-bold text-white sm:text-3xl">
                Dokumentasi Versi Perangkat Lunak &amp; Keamanan Portal
            </h1>
            <p class="mt-2 text-sm text-emerald-100">
                Halaman ini mendokumentasikan versi sistem resmi yang aktif sebagai bukti pemeliharaan keamanan dan penggunaan versi perangkat lunak non-EOL (End of Life), sementara header sidik jari peladen (<code>X-Powered-By</code> / <code>Server</code>) disembunyikan pada respons HTTP.
            </p>
        </header>

        <article class="rounded-xl border border-hairline bg-white p-6 shadow-2xs sm:p-8">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-hairline bg-ivory p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Perangkat Lunak CMS</dt>
                    <dd class="mt-1 font-mono text-base font-bold text-primary-deep">SatkerCMS v{{ $cmsVersion }}</dd>
                    <p class="mt-1 text-xs text-ink-muted">Sistem Manajemen Konten Website</p>
                </div>

                <div class="rounded-lg border border-hairline bg-ivory p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Framework &amp; PHP</dt>
                    <dd class="mt-1 font-mono text-base font-bold text-primary-deep">Laravel {{ $laravelVersion }} | PHP {{ $phpVersion }}</dd>
                    <p class="mt-1 text-xs {{ $isPhpNonEol ? 'text-emerald-800' : 'text-red-700' }}">
                        Status PHP: {{ $isPhpNonEol ? 'Didukung Resmi (Non-EOL)' : 'Perlu Pembaruan' }}
                    </p>
                </div>

                <div class="rounded-lg border border-hairline bg-ivory p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Autentikasi Panel Pengelola</dt>
                    <dd class="mt-1 text-sm font-bold text-ink">Wajib Multi-Factor Authentication (2FA TOTP)</dd>
                    <p class="mt-1 text-xs text-ink-muted">Seluruh akun admin wajib mengonfirmasi TOTP 2FA serta verifikasi captcha lokal saat masuk.</p>
                </div>

                <div class="rounded-lg border border-hairline bg-ivory p-4">
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Protokol &amp; Header Keamanan</dt>
                    <dd class="mt-1 font-mono text-sm font-bold text-ink">{{ $isHttps ? 'HTTPS Aktif' : 'HTTP Lokal / Reverse Proxy' }}</dd>
                    <p class="mt-1 text-xs text-ink-muted">Menerapkan CSP, X-Frame-Options SAMEORIGIN, X-Content-Type-Options nosniff, dan penyembunyian sidik jari peladen.</p>
                </div>
            </dl>
        </article>
    </div>
</x-layouts.public>
