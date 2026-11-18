# Rencana Implementasi Fase 0: Fondasi

Acuan: [PRD-CMS-Pengadilan-Agama.md](PRD-CMS-Pengadilan-Agama.md) v1.0

## Kondisi lingkungan (sudah dicek)
- PHP **8.3.6** (Laragon). Laravel 13 masih mendukungnya, tapi **disarankan naik ke PHP 8.4** (dukungan keamanan lebih panjang, dan versi PHP ikut dinilai di kriteria 3.a).
- Composer 2.9.5, Node 24.22.1.
- Folder proyek sudah berisi `docs/` dan PDF. Laravel di-scaffold di subfolder sementara lalu dipindahkan ke root, sehingga file yang ada tetap aman.

## Langkah

| # | Tugas | Perintah / Output |
|---|---|---|
| 1 | Scaffold Laravel 13 + Livewire starter kit (Flux, Pest) | `laravel new` (starter kit Livewire) atau `composer create-project laravel/laravel:^13.0`, lalu `composer require livewire/livewire:^4.0 livewire/flux` |
| 2 | **Laravel Boost** | `composer require laravel/boost --dev` → `php artisan boost:install` (pilih agent: Antigravity/Gemini, Claude Code, Cursor; aktifkan MCP + guidelines + skills) |
| 3 | Aturan proyek untuk AI | `.ai/guidelines/satker-cms.md`: ringkasan PRD, konvensi (Form Request, Action class, Policy, Flux components, Bahasa Indonesia untuk UI) |
| 4 | Tailwind v4 theme | Token `--color-primary: #056e4f`, `--color-secondary: #ff9900`, tema Flux mengikuti token ini |
| 5 | Auth & keamanan | Fortify: login, **2FA TOTP wajib**, recovery codes, rate limit 5/menit, captcha lokal, prefix admin dari config (`ADMIN_PATH`) |
| 6 | RBAC | spatie/laravel-permission + seeder 6 peran (§3 PRD) |
| 7 | Settings satker | Tabel `settings` + model `Satker` (nama, jenis PA/MS, **tingkat** terkunci, kode, tahun berdiri, alamat, kontak, URL SIPP/e-Court/Direktori/SIWAS/SIMTEPA) |
| 8 | Setup Wizard (Livewire + Flux) | Wizard multi-langkah saat instalasi pertama, membuat Super Admin + data satker |
| 9 | Security headers & hardening | Middleware: HSTS, CSP, X-Frame-Options, Referrer-Policy, hapus `X-Powered-By`; contoh config Nginx/Apache di `docs/deploy/` |
| 10 | Activity log & backup | spatie/laravel-activitylog, spatie/laravel-backup (jadwal mingguan) |
| 11 | Layout admin Flux | Sidebar (Dashboard, Konten, Menu, Link SINOVA, Kriteria, Pengguna, Pengaturan), ⌘K command palette |
| 12 | Testing & CI | Pest: auth + 2FA wajib, wizard, role access; GitHub Actions (pint, larastan, pest, composer audit) |

## Definisi selesai
- `php artisan test` hijau, Larastan level 6 bersih.
- Install baru → wizard → login wajib 2FA → dashboard Flux tampil dengan nama satker.
- `boost:install` terkonfigurasi dan MCP Boost bisa dipanggil agent.

## Keputusan yang dibutuhkan sebelum mulai
1. Naik ke PHP 8.4 di Laragon dulu, atau mulai dengan 8.3?
2. Database lokal: MySQL (Laragon) atau SQLite untuk development?
3. Lisensi **Flux Pro** sudah ada? Tanpa Pro, kita pakai komponen Flux gratis dulu.

