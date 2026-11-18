# SatkerCMS Project Guidelines

CMS website profil siap pakai untuk satuan kerja Pengadilan Agama / Mahkamah Syar'iyah (Tingkat Pertama dan Tingkat Banding) sesuai **SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026** (penilaian **SINOVA**).

## Tech Stack
- PHP 8.5 (non-EOL, versi PHP & CMS wajib terdokumentasi di `/status-sistem`, sementara header `Server` / `X-Powered-By` disembunyikan).
- Laravel 13.x, Livewire 4.x, Flux UI (versi Free; jangan gunakan komponen Flux Pro seperti `<flux:table>` atau `<flux:editor>` tanpa lisensi).
- Tailwind CSS v4, Laravel Fortify (login admin wajib 2FA TOTP), Pest 4.

## Brand Identity & Accessibility
- Warna Utama (Primary): `#056e4f` (dikunci sesuai Bab III.B.1).
- Warna Sekunder (Secondary): `#ff9900` (dikunci; hanya dipakai sebagai aksen dengan teks gelap, bukan latar teks putih, demi kontras WCAG AA).
- Target waktu muat beranda < 5 detik; slider beranda dibatasi 3-5 slide tanpa video/live streaming.
- Widget aksesibilitas custom (TTS Web Speech API id-ID, kontras tinggi, perbesar teks sampai 200%, HTML semantik & ARIA).

## Architecture Conventions
- Gunakan Form Request atau atribut validasi Livewire untuk semua input.
- Gunakan Action classes di `app/Actions/` untuk logika bisnis yang dapat dipakai ulang.
- Gunakan Policies dan `spatie/laravel-permission` dengan 6 peran standar: `super_admin`, `admin_konten`, `operator_kepaniteraan`, `operator_kesekretariatan`, `operator_ppid_humas`, `penilai_internal`.
- Catat perubahan penting menggunakan `spatie/laravel-activitylog`.
- Terapkan security headers lewat `SecurityHeadersMiddleware` dan hindari ekspos data pribadi (NIP/tanggal lahir/kontak pribadi) di halaman publik.
- UI dan pesan validasi untuk pengguna akhir menggunakan Bahasa Indonesia.

## Checkpoints & Workflow
- **CHECKPOINT FIGMA (WAJIB):** Sebelum memulai implementasi tampilan **halaman website utama (public frontend)**, STOP dan beri tahu Master terlebih dahulu agar Master dapat menyiapkan prototipe di Figma (terhubung via Figma MCP desktop bridge).
- **Git Workflow:** Gunakan commit kecil dan atomik dengan Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `test:`) per unit pekerjaan yang logis.
