<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') - SatkerCMS</title>
    {{-- Halaman galat sengaja tanpa Vite/DB agar tetap tampil saat aplikasi bermasalah. --}}
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px; background: #f7f5ef; color: #1b2b26;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height: 1.6;
        }
        .card { max-width: 560px; width: 100%; background: #fff; border: 1px solid #dcd8cc; border-radius: 14px; padding: 40px 32px; }
        .code { font-size: 56px; font-weight: 800; color: #056e4f; letter-spacing: -.03em; margin: 0; }
        h1 { font-size: 20px; margin: 8px 0 12px; }
        p { margin: 0 0 20px; color: #4a5b55; font-size: 14px; }
        a.btn {
            display: inline-block; min-height: 44px; padding: 10px 20px; border-radius: 10px;
            background: #056e4f; color: #fff; text-decoration: none; font-weight: 700; font-size: 14px;
        }
        a.btn:hover, a.btn:focus-visible { background: #04523b; }
        a.btn:focus-visible { outline: 3px solid #ff9900; outline-offset: 2px; }
        .meta { margin-top: 16px; font-size: 12px; color: #6b7a74; }
    </style>
</head>
<body>
    <main class="card">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        @hasSection('action')
            @yield('action')
        @else
            <a class="btn" href="{{ url('/') }}">Kembali ke Beranda</a>
        @endif
        <p class="meta">SatkerCMS &middot; Sistem Informasi Satuan Kerja Peradilan Agama</p>
    </main>
</body>
</html>
