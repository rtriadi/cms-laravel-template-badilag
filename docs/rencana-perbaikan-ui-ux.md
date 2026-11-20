# Rencana & Catatan Perbaikan UI/UX SatkerCMS

Acuan temuan: [docs/ui-ux-audit.md](ui-ux-audit.md). Setiap task di bawah memakai ID temuan dari laporan tersebut.

Status: `[x]` selesai, `[~]` sebagian, `[ ]` belum.

## Fase 0 — Prasyarat

| Task | Status | Catatan |
| --- | --- | --- |
| Runtime PHP 8.5 untuk verifikasi | [x] | `C:\laragon\bin\php\php-8.5.12-1-Win32-vs17-x64\php.exe`; `artisan --version` → Laravel 13.43.0 |
| Baseline test hijau | [x] | Bug `SatkerSetting::formattedAddress()` (dedup `Kec.`/`Kel.`) diperbaiki; 44/44 lulus |
| Inventaris irisan kode | [x] | `layouts/auth.blade.php` tidak dipakai; `bg-gray-*`, `text-[10px]/[11px]`, locale framework dipetakan |

## Fase 1 — Quick wins, risiko rendah

| ID | Task | Status | Bukti / catatan |
| --- | --- | --- | --- |
| R-01 | Ekstrak CSS duplikat ke `resources/css/shared.css` | [x] | Diimpor `app.css` + `public.css` |
| T-06 | Perbaiki mode kontras tinggi (hapus override semua `div`, tambah `text-shadow`) | [x] | `resources/css/shared.css` |
| R-02 | Hapus layout auth ganda | [x] | `resources/views/layouts/auth.blade.php` dihapus |
| K-01 | Slider: tombol Jeda/Lanjutkan, `aria-live`, jeda saat hover/fokus/tab tersembunyi, hormati `prefers-reduced-motion` | [x] | `public/home.blade.php` |
| T-05 | Semantik carousel: `role="group"`, `aria-roledescription="slide"`, label "Slide n dari N", `aria-current` pada dot | [x] | `public/home.blade.php` |
| T-02 | Panel aksesibilitas: `max-h`, `overflow-y-auto`, `overscroll-contain` | [x] | `accessibility-widget.blade.php` |
| S-06 | Tanggal locale-aware | [x] | Helper `App\Support\Tanggal` dipakai di 10 view |
| S-07 | Section publik tanpa heading diberi accessible name | [x] | `public/page.blade.php` |
| S-12 | `aria-current` pada filter kategori & tahun | [x] | `public/posts/index.blade.php`, `blocks/document-list.blade.php` |
| S-11 | Target sentuh hasil pencarian & tautan sekunder | [x] | `min-h-11` pada tombol unduh; `min-h-10` pada daftar isi |
| S-08 | Progress bar dashboard: `role="progressbar"` + `aria-value*` | [x] | `admin/dashboard.blade.php` |
| S-03 | Tombol Edit/Hapus kriteria ≥44 px | [x] | `admin/criteria-manager.blade.php` |
| S-04 | Menu manager: `role="status"`, `aria-pressed`, `aria-label` aksi, token warna | [x] | `admin/menu-manager.blade.php` |
| S-05 | Manajemen pengguna: tombol tampilkan/sembunyikan kata sandi | [x] | `admin/user-role-manager.blade.php` |
| S-09 | Panel editor tidak berkedip saat muat | [x] | `x-cloak` pada `page-manager`, `post-manager` |
| S-10 | Umpan balik salin tautan + akordeon kriteria | [x] | `sinova-links.blade.php`; `confirm()` diganti peringatan di pesan status |
| R-03 | Header publik sticky di ponsel | [x] | `sticky top-0 z-30 ... lg:static` |
| T-07 | Skala tipografi minimum 12 px | [x] | Token `--text-2xs: 0.75rem`; 76 pemakaian `text-[10px]/[11px]` diganti `text-2xs` |
| R-05 | Warna di luar token | [x] | `bg-gray-*`/`hover:bg-gray-*` → `bg-mist`/`hover:bg-hairline/40` |

## Fase 2 — Interaksi & kepatuhan

| ID | Task | Status | Bukti / catatan |
| --- | --- | --- | --- |
| K-02 | Focus trap + restore untuk tiga overlay | [x] | `window.createFocusTrap` (`resources/js/app.js`); drawer publik, command palette, modal tingkat |
| T-04 | Command palette dapat dioperasikan keyboard | [x] | Combobox APG: `aria-activedescendant`, panah, Enter, Escape, `focus:ring-2`; item aktif terlihat |
| S-01 | Tab sesuai APG | [x] | Tiga pengelola diubah dari `role="tab"/tablist` menjadi grup tombol `aria-pressed` (pola yang benar untuk panel Livewire tanpa `tabpanel`), bukan tab APG setengah jadi |
| S-02 | Modal konfirmasi tingkat: trap fokus + klik latar menutup | [x] | `satker-settings-manager.blade.php` |
| T-03 | Captcha aksesibel | [x] | Tombol "Mode teks" (`GET /captcha-text`), petunjuk `aria-describedby`, kode 5 karakter teks; pembatas nyata = throttle login 5/menit. Route dikecualikan dari `TrackPageVisitMiddleware` dan `EnsureSetupCompletedMiddleware`, plus test `serves a text captcha alternative for screen readers` |
| T-08 | Umpan balik simpan & perlindungan perubahan | [~] | Hook Livewire global menonaktifkan tombol submit + `aria-busy` selama request; `beforeunload` untuk form `wire:submit` yang kotor; `role="status"` pada pesan hasil. Belum ada spinner visual per tombol |

## Fase 3 — Sistemik

| ID | Task | Status | Bukti / catatan |
| --- | --- | --- | --- |
| T-01 | Pesan framework berbahasa Indonesia | [x] | `lang/id/{validation,auth,passwords,pagination}.php`; `config/app.php` default `id`; test `LocalizationTest` |
| R-04 | Dua tab stop untuk tujuan yang sama (dot slider vs tombol) | [ ] | Kandidat: hapus dot dari urutan tab (`tabindex="-1"`) atau gabungkan dengan tombol prev/next |
| — | Uji manual pembaca layar & kontras tinggi di 5 halaman | [ ] | Butuh browser/screen reader nyata; belum dijalankan di sesi ini |

## Verifikasi

```powershell
& C:\laragon\bin\php\php-8.5.12-1-Win32-vs17-x64\php.exe artisan test --compact
npm run build
```

Hasil terakhir: **47 test lulus (341 assertion)**, Pint bersih, build sukses.

Catatan lingkungan Windows:

- Jangan menjalankan dua proses test bersamaan; keduanya menulis `storage/framework/views` dan `rename()` bisa gagal `Access is denied`. Pastikan tidak ada sisa proses `artisan test` sebelum menjalankan ulang.
- `php artisan view:cache` tidak memvalidasi sintaks PHP hasil kompilasi. Setelah mengubah Blade, jalankan test atau `php -l` pada berkas hasil kompilasi.

Checklist manual setelah build:

1. Beranda: slider bisa dijeda, dot mengumumkan slide aktif, header tetap terlihat saat scroll di ponsel.
2. Login: captcha mode teks menampilkan kode, `Ulang` memuat kode baru, petunjuk terbaca screen reader.
3. Admin: Ctrl+K → panah/Enter/Escape; simpan form → tombol nonaktif sementara; tutup tab dengan isian kotor → peringatan.
4. Mode kontras tinggi: teks di atas gambar hero tetap terbaca.
5. Tanggal tampil dalam bahasa Indonesia (`19 November 2026`).
