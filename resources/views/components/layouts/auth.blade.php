<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk Pengelola' }} - SatkerCMS</title>
    @if (is_file(public_path('build/fonts-manifest.json')) || \Illuminate\Support\Facades\Vite::isRunningHot())
        {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>try{localStorage.setItem('flux.appearance','light')}catch(e){}document.documentElement.classList.remove('dark');</script>
</head>
<body class="min-h-screen bg-mist font-sans text-ink antialiased">
    <div class="h-1 w-full bg-gold" aria-hidden="true"></div>

    <div class="mx-auto flex min-h-[calc(100vh-4px)] w-full max-w-6xl flex-col justify-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6 text-center">
            <p class="font-mono text-xs font-semibold tracking-wide text-primary uppercase">
                SatkerCMS · Badilag SK 2722/DJA/SK.TI1/X/2026
            </p>
            @if ($satker = \App\Models\SatkerSetting::current())
                <h1 class="mt-1 font-serif text-2xl font-bold text-ink">
                    {{ $satker->nama_satker }}
                </h1>
            @else
                <h1 class="mt-1 font-serif text-2xl font-bold text-ink">
                    Instalasi Awal SatkerCMS
                </h1>
            @endif
        </div>

        {{ $slot }}
    </div>

    @fluxScripts
</body>
</html>

