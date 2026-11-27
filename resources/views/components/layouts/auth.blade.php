@props(['title' => 'Masuk Pengelola', 'wide' => false])
@php
    $satker = \App\Models\SatkerSetting::current();
    $isSetupRoute = request()->routeIs('setup');
    $isWideLayout = $isSetupRoute || $wide || request()->routeIs('admin.two-factor.setup');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    @if ($satker?->logo_path)
        <link rel="icon" href="{{ asset('storage/' . $satker->logo_path) }}">
    @endif
    <title>{{ $title }} - {{ $satker?->nama_satker ?? 'SatkerCMS' }}</title>
    @if (is_file(public_path('build/fonts-manifest.json')) || \Illuminate\Support\Facades\Vite::isRunningHot())
        {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>try{localStorage.setItem('flux.appearance','light')}catch(e){}document.documentElement.classList.remove('dark');</script>
</head>
<body class="min-h-screen bg-mist font-sans text-ink antialiased">
    <div class="sticky top-0 z-30 h-[3px] w-full bg-gold" aria-hidden="true"></div>

    @if ($isWideLayout)
        <header class="border-b border-hairline bg-white">
            <div class="mx-auto flex min-h-16 w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($satker?->logo_path)
                        <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border-[1.5px] border-gold bg-white p-1">
                            <img src="{{ asset('storage/' . $satker->logo_path) }}" alt="Logo {{ $satker->nama_satker }}" width="36" height="36" class="size-full object-contain">
                        </span>
                    @else
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border-[1.5px] border-gold bg-white font-mono text-[10px] font-bold text-primary">
                            LOGO
                        </span>
                    @endif
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-serif text-base font-bold text-ink">{{ $satker?->nama_satker ?? 'SatkerCMS' }}</span>
                            <span class="rounded-md bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary">
                                {{ $isSetupRoute ? 'Instalasi Awal' : 'Keamanan Akun' }}
                            </span>
                        </div>
                    </div>
                </div>
                <p class="hidden text-xs text-ink-muted md:block">
                    SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026
                </p>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
            {{ $slot }}
        </main>
    @else
        <div class="flex min-h-[calc(100dvh-3px)] flex-col lg:grid lg:grid-cols-12">
            <!-- Institutional Obsidian Panel -->
            <aside class="flex flex-col justify-between bg-obsidian px-5 py-4 text-[#d3e8df] sm:px-8 lg:col-span-5 lg:px-12 lg:py-12">
                <div class="lg:space-y-8">
                    <div class="flex items-center gap-3.5">
                        @if ($satker?->logo_path)
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border-[1.5px] border-gold bg-white p-1 lg:size-12 lg:p-1.5">
                                <img src="{{ asset('storage/' . $satker->logo_path) }}" alt="Logo {{ $satker->nama_satker }}" width="48" height="48" class="size-full object-contain">
                            </span>
                        @else
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl border-[1.5px] border-gold bg-white font-mono text-xs font-bold text-primary lg:size-12">
                                LOGO
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate font-serif text-base font-bold text-white lg:text-lg">
                                {{ $satker?->nama_satker ?? 'SatkerCMS Peradilan Agama' }}
                            </p>
                            <p class="text-xs text-[#8dbfac]">
                                SatkerCMS &middot; Portal Resmi Peradilan Agama
                            </p>
                        </div>
                    </div>

                    <div class="hidden space-y-3 lg:block">
                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-3xl">
                            Ruang kerja pengelolaan website satuan kerja.
                        </h1>
                        <p class="text-sm leading-relaxed text-[#d3e8df]">
                            Kelola berita, jadwal sidang, arsip SAKIP, dan tautan penilaian SINOVA dari satu tempat yang aman dan mudah digunakan.
                        </p>
                    </div>

                    <ul class="hidden space-y-3.5 lg:block">
                        <li class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#0d4434] text-gold">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 19 6v5.5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-2.5Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                                </svg>
                            </span>
                            <span class="text-sm leading-snug text-[#d3e8df]">Autentikasi 2 langkah (2FA TOTP) wajib bagi seluruh pengelola</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#0d4434] text-gold">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="9" cy="8.5" r="3" />
                                    <path stroke-linecap="round" d="M3.5 19c0-3 2.5-5.5 5.5-5.5s5.5 2.5 5.5 5.5M16 5.8a3 3 0 0 1 0 5.4M17.5 14.2c1.8.8 3 2.6 3 4.8" />
                                </svg>
                            </span>
                            <span class="text-sm leading-snug text-[#d3e8df]">6 peran khusus sesuai tugas Kepaniteraan, Kesekretariatan, dan Humas</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#0d4434] text-gold">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 12h4l2.5-6 4 12 2.5-6h4" />
                                </svg>
                            </span>
                            <span class="text-sm leading-snug text-[#d3e8df]">Setiap perubahan penting tercatat otomatis pada log aktivitas</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-6 hidden border-t border-[#1a4d3e] pt-4 text-xs lg:block">
                    <p class="font-mono text-[11px] text-[#8dbfac]">Acuan: SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026</p>
                    <p class="mt-1 text-[#d3e8df]">Butuh bantuan akses akun? Hubungi Admin TI Satker.</p>
                </div>
            </aside>

            <main class="flex flex-1 items-start justify-center px-4 py-6 sm:items-center sm:px-8 sm:py-10 lg:col-span-7 lg:px-12">
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </main>
        </div>
    @endif

    @fluxScripts
</body>
</html>
