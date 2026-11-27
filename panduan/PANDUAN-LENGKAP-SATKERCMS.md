# Panduan Lengkap Penggunaan, Instalasi, dan Tata Kelola SatkerCMS

**Sistem Manajemen Konten Website Profil Satuan Kerja Peradilan Agama dan Mahkamah Syar'iyah**

| Informasi Dokumen | Keterangan |
| :--- | :--- |
| **Nama Aplikasi** | SatkerCMS (CMS Template Website Satuan Kerja Ditjen Badilag) |
| **Versi Aplikasi** | `1.0.0-dev` (Laravel 13.x, Livewire 4.x, PHP 8.5) |
| **Pembuat Aplikasi** | **Rahmat Triadi, S.Kom.** |
| **Dasar Regulasi** | SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 (Evaluasi SINOVA), SK KMA No. 2-144/KMA/SK/VIII/2022 (PPID), dan Pedoman Pengawasan PTA/Bawas |
| **Cakupan Satuan Kerja** | Pengadilan Agama (PA), Pengadilan Tinggi Agama (PTA), Mahkamah Syar'iyah (MS), dan Mahkamah Syar'iyah Aceh (MS Aceh) |
| **Repositori Resmi** | `https://github.com/rtriadi/cms-laravel-template-badilag` |
| **Lisensi** | MIT License |

---

## Kata Pengantar

Website resmi satuan kerja merupakan garda terdepan keterbukaan informasi publik dan pelayanan peradilan di lingkungan Direktorat Jenderal Badan Peradilan Agama (Ditjen Badilag), Mahkamah Agung Republik Indonesia. Melalui **SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026**, standar tata kelola, keamanan, aksesibilitas, kecepatan akses, dan struktur evaluasi website satuan kerja telah ditetapkan secara terukur melalui instrumen penilaian **SINOVA** serta diselaraskan dengan ketentuan keterbukaan informasi publik (**SK KMA No. 2-144/KMA/SK/VIII/2022**) dan **Pedoman Pengawasan** peradilan.

**SatkerCMS** dirancang dan dibangun oleh **Rahmat Triadi, S.Kom.** sebagai solusi CMS siap pakai berbasis Laravel 13 yang mempermudah satuan kerja Pengadilan Agama dan Mahkamah Syar'iyah (baik Tingkat Pertama maupun Tingkat Banding) dalam membangun, mengelola, memigrasi, dan memelihara website resmi yang cepat, aman, ramah disabilitas, serta patuh terhadap seluruh kriteria evaluasi tanpa ketergantungan pada plugin komersial berisiko.

Panduan ini disusun sebagai referensi tunggal bagi Administrator Sistem (TI), Admin Konten, Operator Kepaniteraan, Operator Kesekretariatan, Operator PPID/Humas, serta Tim Penilai Internal satuan kerja.

---

## Bab 1. Pengenalan SatkerCMS dan Landasan Regulasi

### 1.1 Identitas dan Pembuat Aplikasi

SatkerCMS adalah aplikasi manajemen konten (CMS) web profil satuan kerja peradilan agama modern yang dikembangkan oleh **Rahmat Triadi, S.Kom.** dengan pendekatan *compliance-by-design* (kepatuhan terintegrasi sejak perancangan arsitektur).

### 1.2 Landasan Regulasi dan Standar Kepatuhan

Seluruh fitur, struktur menu, dan indikator di dalam SatkerCMS mengacu pada tiga instrumen resmi:

1. **SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026** tentang Petunjuk Teknis Pembaruan, Tata Kelola, dan Evaluasi Website Satuan Kerja (Instrumen Penilaian **SINOVA**):
   - **Aspek 1 (Konten & Informasi):** 12 grup, 34 kriteria URL (Informasi Berkala `1.a` dan Informasi Wajib `1.b`).
   - **Aspek 2 (UI/UX & Aksesibilitas):** Mobile Responsiveness (`2.a.1`), Aksesibilitas WCAG (`2.b.1`), serta Navigasi & Pencarian (`2.c.1`).
   - **Aspek 3 (Performa & Keamanan):** Keamanan Website (`3.a.1`), Kecepatan Akses di bawah 5 detik (`3.b.1`), serta Validitas Tautan bebas error 404 (`3.c.1`).
   - Total keseluruhan terdiri atas **18 grup** dan **40 kriteria evaluasi** (**35 kriteria URL** dan **5 kriteria non-URL**).
2. **SK KMA No. 2-144/KMA/SK/VIII/2022** tentang Standar Pelayanan Informasi Publik di Pengadilan:
   - Pengelompokan informasi berkala, serta merta, setiap saat, dan arsip 7 SOP layanan PPID.
   - Perlindungan data pribadi aparatur dengan tidak menampilkan NIP, tanggal lahir, maupun kontak pribadi pada halaman publik.
3. **Pedoman Pengawasan (Bagian A-6 & A-8):**
   - Ketersediaan komponen halaman depan: tautan PTA/Tingkat Banding, Indeks Survei (SKM, SPAK, SPKP), tautan Video Profil/PTSP/Kebijakan MA, Tautan Instansi Terkait, Sosial Media Resmi, dan tautan Access CCTV Online (ACO) Badilag.

### 1.3 Identitas Visual dan Standar Aksesibilitas

Sesuai ketentuan Bab III.B.1 Juknis Badilag dan standar WCAG 2.2 AA:

| Elemen Identitas | Ketentuan Teknis di SatkerCMS |
| :--- | :--- |
| **Warna Utama (Primary)** | `#056e4f` (Hijau Badilag, dikunci secara sistem) |
| **Warna Gelap Pendukung** | `#063b2b` (Obsidian hijau tua untuk header, hero, dan footer berkontras tinggi) |
| **Warna Sekunder (Accent)** | `#ff9900` (Kuning keemasan, hanya dipasangkan dengan teks gelap `#111827` demi kontras WCAG AA) |
| **Lebar Kontainer Publik** | Seragam `max-w-7xl` di seluruh halaman (beranda, direktori publikasi, detail berita, dan halaman profil) |
| **Target Kecepatan Beranda** | Waktu muat halaman kurang dari 5 detik, konversi gambar otomatis ke WebP, tanpa video/live streaming tertanam di beranda |
| **Slider Beranda** | Dibatasi 3 sampai 5 slide gambar statis HD (rasio rekomendasi `1280 x 720` / 16:9) |
| **Widget Aksesibilitas** | Pembaca layar TTS Web Speech API (`id-ID`), Mode Kontras Tinggi, Perbesar Teks hingga 200%, Skala Abu-abu, Garis Bawah Tautan, dan navigasi keyboard |

### 1.4 Arsitektur dan Tumpukan Teknologi

| Lapisan | Teknologi dan Versi | Peran Utama |
| :--- | :--- | :--- |
| **Bahasa Pemrograman** | PHP 8.5 (minimal 8.3; rekomendasi 8.4/8.5 non-EOL) | Runtime utama server web |
| **Framework Backend** | Laravel `^13.17` | Routing, ORM Eloquent, keamanan, scheduler, queue |
| **Antarmuka Reaktif Admin** | Livewire `^4.0` + Flux UI Free `^2.21` | Panel admin interaktif tanpa ketergantungan komponen berbayar |
| **Styling & Build** | Tailwind CSS v4 + Vite 8 | Bundling aset ringan dan responsif |
| **Autentikasi & 2FA** | Laravel Fortify `^1.43` | Login aman dengan wajib 2FA TOTP dan kode pemulihan |
| **Otorisasi (RBAC)** | `spatie/laravel-permission ^8.5` | 6 peran standar dan 12 izin granular |
| **Audit & Backup** | `spatie/laravel-activitylog ^5.1` + `spatie/laravel-backup ^10.4` | Jejak perubahan data dan pencadangan ZIP terenkripsi |
| **Editor Konten** | TinyMCE 8 GPL (self-hosted, tanpa API key eksternal) | Studio 3-Tab (Editor Visual, Kode HTML, Pratinjau) + unggah WebP/PDF |
| **Basis Data** | MySQL 8.0+ / MariaDB 10.6+ (atau SQLite untuk uji lokal) | Penyimpanan data CMS dan cache |
| **Pengujian Otomatis** | Pest `^4.7` (110 pengujian, 943 asersi) | Verifikasi otomatis kepatuhan, keamanan, dan alur bisnis |

---

## Bab 2. Persyaratan Sistem dan Panduan Instalasi

### 2.1 Spesifikasi Minimum Server

| Komponen | Spesifikasi Minimal | Rekomendasi Produksi |
| :--- | :--- | :--- |
| **PHP** | 8.3 | **8.4 atau 8.5 (Non-EOL)** |
| **Ekstensi PHP Wajib** | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip` | Aktifkan seluruh ekstensi + `opcache` dan `imagick`/`gd` dengan dukungan WebP |
| **Basis Data** | MySQL 8.0 atau MariaDB 10.6 | MySQL 8.0+ / MariaDB 10.11+ |
| **Web Server** | Apache 2.4 (`mod_rewrite`) atau Nginx 1.24+ | Nginx + PHP-FPM dengan SSL/HTTPS aktif |
| **Node.js & NPM** | Node.js 20+ (hanya diperlukan saat build aset) | Node.js 22 LTS |
| **Composer** | Composer 2.6+ | Composer 2.8+ |
| **Memori & Ruang Disk** | RAM 1 GB, Disk 2 GB | RAM 2 GB+, Disk 10 GB SSD |

### 2.2 Instalasi Lokal (Pengembangan / Evaluasi di Windows & Laragon)

1. **Kloning repositori dan pasang dependensi:**

   ```bash
   git clone https://github.com/rtriadi/cms-laravel-template-badilag.git satkercms
   cd satkercms
   composer install
   npm install
   npm run build
   ```

2. **Siapkan berkas konfigurasi `.env` dan kunci aplikasi:**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Sesuaikan pengaturan database di `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, serta `APP_URL=http://127.0.0.1:8000` dan `APP_TIMEZONE=Asia/Makassar` atau `Asia/Jakarta`/`Asia/Jayapura`).

3. **Jalankan migrasi dan tautan penyimpanan:**

   ```bash
   php artisan migrate
   php artisan storage:link
   ```

4. **Jalankan server lokal:**
   - **Cara cepat di Windows:** Jalankan ganda berkas `run-app.bat` (atau jalankan `.\run-app.ps1` via PowerShell) untuk menyalakan server Laravel sekaligus Vite secara praktis.
   - **Cara standar (Lintas OS):** Jalankan `composer run dev` atau `php artisan serve`.
   - Buka browser ke `http://127.0.0.1:8000` (otomatis diarahkan ke `/setup` bila belum dikonfigurasi).

### 2.3 Instalasi di Shared Hosting (cPanel / DirectAdmin)

Sebelum mengunggah ke shared hosting, siapkan paket produksi di komputer lokal terlebih dahulu:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Pastikan direktori `vendor/` dan `public/build/` sudah terbentuk sebelum diarsipkan ke `.zip`.

#### Opsi A: Hosting Mendukung Pengaturan Document Root (Direkomendasikan)

1. Unggah dan ekstrak seluruh kode proyek ke `/home/<user>/satkercms`.
2. Di menu **Domains** pada cPanel, arahkan **Document Root** domain resmi satker ke `/home/<user>/satkercms/public`.
3. Pilih versi **PHP 8.4 / 8.5** di menu **Select PHP Version**, aktifkan ekstensi wajib, dan ubah `upload_max_filesize = 16M` serta `post_max_size = 20M`.
4. Buat database MySQL dan pengguna database di cPanel, lalu salin `.env.production.example` (template produksi dengan `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan `MAIL_MAILER=smtp`) menjadi `.env` dan isi kredensial database serta `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, dan `BACKUP_ARCHIVE_PASSWORD`.
5. Jalankan migrasi dan symlink storage melalui Terminal SSH cPanel (atau Cron Job satu kali jika tanpa SSH):

   ```bash
   php artisan key:generate --force
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   ```

#### Opsi B: Hosting Wajib Memakai Folder `public_html`

1. Ekstrak seluruh kode inti proyek (kecuali isi folder `public/`) ke luar `public_html`, misalnya `/home/<user>/satkercms_core`.
2. Salin seluruh isi folder `public/` (termasuk `.htaccess`, `index.php`, `build/`, `vendor/tinymce/`) ke `/home/<user>/public_html/`.
3. Ubah berkas `/home/<user>/public_html/index.php` pada baris loader dan aplikasi agar mengarah ke `satkercms_core`:

   ```php
   require __DIR__.'/../satkercms_core/vendor/autoload.php';
   $app = require_once __DIR__.'/../satkercms_core/bootstrap/app.php';
   $app->usePublicPath(__DIR__);
   ```

4. Buat symlink penyimpanan agar dokumen PDF dan gambar WebP dapat diakses:

   ```bash
   ln -s /home/<user>/satkercms_core/storage/app/public /home/<user>/public_html/storage
   ```

5. Pasang Cron Job di cPanel (frekuensi setiap menit `* * * * *`) untuk Laravel Scheduler:

   ```cron
   * * * * * cd /home/<user>/satkercms_core && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

### 2.4 Instalasi di VPS / Server Mandiri (Ubuntu + Nginx + PHP 8.5-FPM)

1. **Pasang Nginx, PHP 8.5-FPM, MySQL/MariaDB, Composer, dan Certbot:**

   ```bash
   sudo apt update && sudo apt install -y nginx software-properties-common unzip git curl certbot python3-certbot-nginx
   sudo add-apt-repository -y ppa:ondrej/php
   sudo apt update && sudo apt install -y php8.5-fpm php8.5-mysql php8.5-xml php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl php8.5-bcmath
   ```

2. **Kloning dan bangun aplikasi di `/var/www/satkercms`:**

   ```bash
   sudo mkdir -p /var/www/satkercms
   sudo chown -R $USER:www-data /var/www/satkercms
   git clone https://github.com/rtriadi/cms-laravel-template-badilag.git /var/www/satkercms
   cd /var/www/satkercms
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   cp .env.production.example .env && php artisan key:generate
   ```

3. **Konfigurasi `.env` produksi:**

   ```env
   APP_NAME="Pengadilan Agama Contoh"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://pa-contoh.go.id
   APP_LOCALE=id
   APP_FALLBACK_LOCALE=id
   APP_TIMEZONE=Asia/Makassar

   ADMIN_PATH=panel-admin
   SESSION_ENCRYPT=true
   SESSION_SECURE_COOKIE=true
   LOG_LEVEL=error
   TRUSTED_PROXIES=127.0.0.1
   TRUSTED_HOSTS=^(?:www\.)?pa-contoh\.go\.id$
   BACKUP_ARCHIVE_PASSWORD=GantiDenganSandiArsipKuat123!
   ```

4. **Migrasi, storage link, optimasi, dan izin folder:**

   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   sudo chown -R www-data:www-data /var/www/satkercms/storage /var/www/satkercms/bootstrap/cache
   sudo chmod -R 775 /var/www/satkercms/storage /var/www/satkercms/bootstrap/cache
   ```

5. **Pengerasan PHP-FPM (`/etc/php/8.5/fpm/php.ini`):**
   Atur `expose_php = Off`, `upload_max_filesize = 16M`, `post_max_size = 20M`, `memory_limit = 256M`, dan aktifkan OPcache (`opcache.enable=1`), lalu restart `php8.5-fpm`.
6. **Virtual Host Nginx & SSL HTTPS:**
   Konfigurasikan `root /var/www/satkercms/public;`, `server_tokens off;`, `fastcgi_hide_header X-Powered-By;`, `client_max_body_size 16M;`, aktifkan kompresi Gzip dan cache header aset (`/build/assets/` dan `/storage/`), blokir akses berkas tersembunyi (`location ~ /\.(?!well-known).* { deny all; }`), lalu pasang sertifikat SSL Let's Encrypt:

   ```bash
   sudo nginx -t && sudo systemctl reload nginx
   sudo certbot --nginx -d pa-contoh.go.id -d www.pa-contoh.go.id
   ```

7. **Pasang Cron Job Scheduler (`sudo crontab -u www-data -e`):**

   ```cron
   * * * * * cd /var/www/satkercms && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

---

## Bab 3. Setup Awal Aplikasi dan Keamanan Login Admin

### 3.1 Menjalankan Setup Wizard (`/setup`)

Saat aplikasi diakses pertama kali setelah instalasi baru, sistem secara otomatis mengarahkan browser ke halaman **`/setup`**. Wizard ini terdiri dari **4 langkah terpandu**:

1. **Langkah 1 - Identitas & Tingkat Peradilan:**
   - Masukkan **Nama Satuan Kerja** (contoh: *Pengadilan Agama Gorontalo*).
   - Pilih **Jenis Badan Peradilan**: `PA` (Pengadilan Agama), `PTA` (Pengadilan Tinggi Agama), `MS` (Mahkamah Syar'iyah), atau `MS_ACEH` (Mahkamah Syar'iyah Aceh).
   - Pilih **Tingkat Peradilan**: `Tingkat Pertama` atau `Tingkat Banding`. Pilihan ini secara otomatis menyesuaikan template menu, halaman wajib, dan kriteria SINOVA (misalnya kriteria `1.a.2.f` Pengembalian Sisa Panjar hanya aktif di Tingkat Pertama).
   - Isi **Kode Satker** (6 digit) dan **Tahun Berdiri**.
2. **Langkah 2 - Alamat & Kontak Resmi:**
   - Lengkapi jalan, desa/kelurahan, kecamatan, kabupaten/kota, provinsi, kode pos, nomor telepon kantor, alamat surat elektronik resmi (`.go.id`), dan URL website utama.
3. **Langkah 3 - Tautan Integrasi Nasional MARI:**
   - Isi tautan **SIPP** (`https://sipp.pa-satker.go.id/`), **e-Court**, **Direktori Putusan**, **SIWAS**, **URL atau Token 32 karakter SIMTEPA**, serta tautan **Survei Pelayanan (SKM/SPAK/SPKP)**.
4. **Langkah 4 - Pembuatan Akun Super Admin:**
   - Buat akun `super_admin` pertama dengan alamat email resmi dan kata sandi kuat.

Saat tombol **Selesai & Inisialisasi SatkerCMS** diklik, sistem secara otomatis:
- Menyimpan identitas satuan kerja dan menandai status setup selesai (rute `/setup` terkunci otomatis setelah selesai).
- Menanamkan **6 peran RBAC** beserta **12 izin** standar.
- Mengimpor **40 kriteria evaluasi SINOVA** terbaru (`juknis-2026.json`).
- Membuat **36 halaman standar**, **3 slide awal beranda**, dan **6 menu navigasi utama** sesuai tingkat peradilan.

### 3.2 Login Admin, CAPTCHA Lokal, dan Wajib 2FA TOTP

Untuk memenuhi Kriteria `3.a.1` Keamanan Website SINOVA, akses panel admin menerapkan pertahanan berlapis:

1. Buka alamat login **`/login`** (atau langsung **`/panel-admin`** yang dapat diubah jalurnya lewat variabel `ADMIN_PATH` di `.env`).
2. Masukkan email, kata sandi, dan jawaban **CAPTCHA matematika/kode lokal** (diproses sepenuhnya di server sendiri tanpa ketergantungan layanan eksternal).
3. **Aktivasi Wajib 2FA TOTP (Multi-Factor Authentication):**
   - Pada login pertama kali, akun pengelola akan otomatis diarahkan ke **`/panel-admin/keamanan/2fa`**.
   - Pindai **QR Code** menggunakan aplikasi autentikator di ponsel (**Google Authenticator**, **Microsoft Authenticator**, **Authy**, atau **FreeOTP**).
   - Masukkan **6 digit kode OTP** untuk konfirmasi.
   - **Simpan 8 Kode Pemulihan (Recovery Codes)** di tempat aman atau cetak secara luring. Kode ini dipakai bila perangkat autentikator hilang atau diganti.
   - Setelah 2FA aktif, seluruh menu Dashboard Admin terbuka penuh sesuai hak akses peran.

### 3.3 Lapisan Keamanan Tambahan Bawaan

- **Security Headers (`SecurityHeadersMiddleware`):** Mengirimkan `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, dan `Permissions-Policy`.
- **Penyembunyian Versi di HTTP Header:** Header `X-Powered-By` dan `Server` disembunyikan pada level server/aplikasi, sementara informasi versi PHP dan CMS ditampilkan secara transparan hanya di halaman **`/status-sistem`**.
- **Sanitasi HTML (`SafeHtml`):** Seluruh masukan konten HTML dari editor disanitasi terhadap celah XSS sebelum disimpan dan ditampilkan ke pengunjung publik.
- **Rate Limiting:** Pembatasan percobaan login dan pengiriman komentar publik guna mencegah serangan *brute force* dan spam.

---

## Bab 4. Manajemen Pengguna dan Hak Akses (6 Peran RBAC)

SatkerCMS menyediakan **6 peran standar** berbasis `spatie/laravel-permission` yang dikelola melalui menu **`/panel-admin/pengguna`**. Pembagian peran ini selaras dengan struktur organisasi Pengadilan Agama dan Mahkamah Syar'iyah agar tidak terjadi penumpukan kewenangan pada satu akun.

### 4.1 Matriks Peran dan Hak Akses

| Peran (`Role`) | Deskripsi Tugas | Hak Akses Utama (`Permissions`) |
| :--- | :--- | :--- |
| **`super_admin`** | Administrator TI / Penanggung Jawab Sistem | Akses penuh seluruh 12 izin, termasuk pengelolaan pengguna, reset 2FA, impor kriteria SINOVA, backup sistem, dan **ubah tingkat peradilan**. |
| **`admin_konten`** | Pengelola Utama Website / Koordinator Tim TI | Kelola pengaturan satker (kecuali ubah tingkat), menu navigasi, halaman & Page Builder, slider beranda, publikasi, komentar, dokumen, Link SINOVA, dan lihat dashboard kepatuhan. |
| **`operator_kepaniteraan`** | Staf/Operator Bagian Kepaniteraan | Kelola data kepaniteraan (jadwal sidang cadangan, statistik perkara, panggilan ghaib, laporan biaya perkara, pengembalian sisa panjar) dan dokumen kepaniteraan. |
| **`operator_kesekretariatan`** | Staf/Operator Bagian Kesekretariatan / PTIP / Keuangan / Kepegawaian | Kelola data pegawai cadangan (tanpa NIP/tanggal lahir), arsip dokumen SAKIP (Renstra, RKT, Renaksi, PK, IKU, LKjIP), DIPA, LRA, CALK, LHKPN, dan PBJ. |
| **`operator_ppid_humas`** | Petugas PPID / Kehumasan | Kelola berita, pengumuman resmi, inovasi satker, artikel Prodeo/Posbakum, informasi serta merta, moderasi komentar publik, dan dokumen PPID/SOP. |
| **`penilai_internal`** | Hakim Pengawas Bidang TI / Tim Evaluasi Internal | Akses **baca-saja** ke Dashboard Kepatuhan SINOVA, Hub Link SINOVA, laporan audit tautan rusak, dan log aktivitas. |

### 4.2 Praktik Baik Pengelolaan Akun di Satuan Kerja

1. Setiap operator wajib menggunakan **akun pribadi masing-masing** (tidak berbagi satu akun bersama) agar jejak perubahan pada **Audit Aktivitas** tercatat akurat berdasarkan penanggung jawab.
2. Aktifkan minimal dua akun berstatus `super_admin` (misalnya Kasubbag PTIP dan Pranata Komputer) sebagai cadangan operasional.
3. Apabila seorang operator mutasi atau pensiun, segera nonaktifkan atau hapus perannya melalui **`/panel-admin/pengguna`**.
4. Apabila seorang pengguna kehilangan perangkat autentikator 2FA, `super_admin` dapat menekan tombol **Reset 2FA** pada baris akun terkait agar pengguna dapat menautkan ulang QR Code pada login berikutnya.

---

## Bab 5. Panduan Pengaturan Satuan Kerja dan Integrasi Nasional

Menu **`/panel-admin/pengaturan`** merupakan pusat konfigurasi identitas dan integrasi nasional. Perubahan yang disimpan di sini secara otomatis menyegarkan cache beranda, footer, halaman kontak, dan blok integrasi di seluruh portal publik.

### 5.1 Seksi 1: Identitas Satuan Kerja, Logo, dan Foto Gedung

- **Nama Satker, Jenis Badan, Kode Satker, dan Tahun Berdiri:** Ditampilkan pada kop portal, footer, metadata SEO, dan halaman `/status-sistem`.
- **Logo Resmi Satker:** Unggah berkas PNG/JPG/WebP. Sistem secara otomatis mengoptimalkannya ke format WebP ringan.
- **Foto Gedung Kantor:** Wajib diisi untuk memenuhi Kriteria `1.b.p.d` (Alamat dan Kontak Pengadilan). Sistem otomatis melakukan *resize* dan kompresi ke WebP lebar maksimal `1280px` agar tetap ringan saat dimuat.

### 5.2 Seksi 2: Alamat, Kontak Resmi, dan Jam Layanan

- Isi alamat lengkap secara terstruktur: **Jalan**, **Desa/Kelurahan**, **Kecamatan**, **Kabupaten/Kota**, **Provinsi**, dan **Kode Pos** (seluruh komponen alamat ini disyaratkan secara eksplisit oleh Kriteria `1.b.p.d`).
- Isi **Nomor Telepon Kantor**, **Surat Elektronik Resmi**, dan **URL Website**.
- **Jam Layanan PTSP:** Dapat dikonfigurasi melalui variabel `.env` (`SATKER_HOURS_MON_THU_OPEN=08:00`, `SATKER_HOURS_MON_THU_CLOSE=16:30`, `SATKER_HOURS_FRI_OPEN=08:00`, `SATKER_HOURS_FRI_CLOSE=17:00`, dan `SATKER_TIMEZONE_LABEL=WITA`/`WIB`/`WIT`) sehingga seragam tampil di Beranda, Footer, dan Halaman PTSP.

### 5.3 Seksi 3: Integrasi SIPP, SIMTEPA, dan Layanan Nasional MARI

1. **URL SIPP Satuan Kerja (`sipp_url`):**
   - Cukup masukkan URL dasar SIPP satker, contoh: `https://sipp.pa-gorontalo.go.id/`.
   - SatkerCMS secara otomatis memetakan tiga tampilan layanan pada blok halaman:
     - Daftar / Penelusuran Perkara (`/`)
     - Agenda / Jadwal Persidangan Harian (`/list_jadwal_sidang`)
     - Statistik Perkara Bulanan (`/statistik_perkara`)
2. **Integrasi SIMTEPA (`simtepa_pegawai_url` dan `simtepa_struktur_url`):**
   - **Token / URL Profil Pegawai (`simtepa_pegawai_url`):** Cukup tempelkan URL share SIMTEPA atau **token 32 karakter hex** milik satuan kerja. Sistem otomatis membentuk tampilan **7 Tab Kategori Profil** (Ketua, Wakil Ketua, Hakim, Kepaniteraan, Kesekretariatan, Fungsional, dan Pelaksana) pada halaman Profil Pejabat & Pegawai (`1.b.3.a`), tanpa menampilkan data pribadi (NIP/tanggal lahir).
   - **URL Bagan Struktur Organisasi (`simtepa_struktur_url`):** Tempelkan tautan langsung bagan struktur organisasi SIMTEPA, misalnya `https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/{token}`. Pada halaman Struktur Organisasi (`1.b.p.c`), pilih sumber blok **`SIMTEPA Bagan Struktur Organisasi Langsung`** agar diagram struktur resmi langsung tampil interaktif. Selain itu tersedia pula opsi **`SIMTEPA Struktur Pejabat (6 Tab)`** maupun mode **Cadangan Lokal** apabila SIMTEPA sedang dalam pemeliharaan.
3. **e-Court MARI (`ecourt_url`), Direktori Putusan (`direktori_putusan_url`), dan SIWAS MARI (`siwas_url`):**
   - Isi tautan resmi satuan kerja masing-masing. Tautan ini otomatis terhubung ke tombol akses cepat header, kartu layanan beranda, dan blok Portal Eksternal pada halaman terkait.

### 5.4 Seksi 4: Pengawasan dan Indikator Halaman Depan

Bagian ini disiapkan khusus untuk memenuhi instrumen **Pedoman Pengawasan Bagian A-6**:

- **Pengadilan Tingkat Banding (PTA / MS Aceh):** Isi **Nama PTA** dan **URL Website PTA** agar tampil pada kartu tautan resmi halaman depan.
- **Indeks Survei Pelayanan (SKM, SPAK, SPKP):**
  - Isi tautan survei **SKM** (`skm_url`) dan **SPAK** (`spak_url`).
  - Isi **Periode Survei** (misal: *Triwulan I 2026*), **Nilai IKM/SKM**, **Nilai SPAK**, dan **Nilai SPKP** agar kartu capaian mutu pelayanan tampil transparan di beranda.
- **Tautan Video Informasi Resmi (Ringan / Non-Embed Berat):**
  - Isi URL **Video Profil Satker**, **Video Layanan PTSP**, dan **Video Kebijakan Mahkamah Agung RI** (YouTube atau media resmi lainnya).
  - Sesuai larangan video/streaming tertanam di beranda (Bab III.A.5 Juknis Badilag), SatkerCMS menampilkannya sebagai **kartu tautan eksternal ringan** sehingga skor kecepatan muat beranda tetap di bawah 5 detik.

### 5.5 Seksi 5: Tautan Instansi Terkait, Sosial Media Resmi, dan Access CCTV Online (ACO)

- **Tautan Instansi Terkait (`related_links`):**
  - Pengelola dapat menambah, mengubah, mengurutkan, atau menghapus hingga **20 tautan instansi terkait** secara dinamis (contoh: Mahkamah Agung RI, Ditjen Badilag, PTA/MS Aceh, PA se-Wilayah, Pemerintah Daerah).
  - Setiap entri memiliki kolom: **Kode Singkat** (contoh `MA`, `BADILAG`, `PTA`), **Judul Instansi**, **Subjudul / Keterangan**, dan **URL Resmi**.
- **Sosial Media Resmi Satuan Kerja:**
  - Isi URL resmi **Facebook**, **Instagram**, **TikTok**, **YouTube**, dan **X (Twitter)**. Hanya platform yang URL-nya diisi yang akan ditampilkan di halaman beranda dan footer publik.
- **Access CCTV Online (ACO) Badilag (`cctv_aco_url` & `cctv_aco_label`):**
  - Masukkan URL portal pemantauan **ACO Badilag** milik satuan kerja beserta label singkatnya (contoh: *Access CCTV Online - PA Gorontalo*).
  - Ditampilkan sebagai kartu akses cepat berkinerja ringan di beranda yang membuka portal ACO di tab baru tanpa membebani kecepatan muat beranda dengan aliran video langsung.

### 5.6 Seksi 6: Pengubahan Tingkat Peradilan (Khusus Super Admin)

Apabila satuan kerja mengalami perubahan status atau koreksi tingkat peradilan (`Tingkat Pertama` <-> `Tingkat Banding`), hanya `super_admin` yang berwenang mengakses tombol **Ubah Tingkat Peradilan**. Sistem mewajibkan **konfirmasi ganda** dengan mengetik frasa verifikasi di modal dialog sebelum menyelaraskan ulang visibilitas kriteria SINOVA dan struktur halaman bawaan.

---

## Bab 6. Panduan Operasional Pengelolaan Konten CMS

### 6.1 Dashboard Kepatuhan dan Skor SINOVA (`/panel-admin`)

Dashboard utama menampilkan ringkasan *real-time*:
- **Persentase Kesiapan Link SINOVA** (jumlah kriteria URL terisi dibanding total kriteria aktif sesuai tingkat peradilan).
- **Jumlah Halaman Tayang, Publikasi, Arsip Dokumen PDF, dan Komentar Menunggu Moderasi**.
- **Indikator Kesehatan Sistem** (versi PHP, status SSL, status 2FA, dan jumlah tautan rusak dari pemindaian terakhir).
- **Tombol Aksi Cepat** menuju pengisian Link SINOVA, pembuatan berita baru, unggah dokumen PDF, dan pengaturan satker.

### 6.2 Manajemen Halaman dan Page Builder (`/panel-admin/halaman`)

Halaman statis digunakan untuk profil pengadilan, layanan hukum, transparansi SAKIP, dan informasi PPID. Setiap halaman disusun menggunakan **Page Builder modular** dengan **7 jenis blok** yang dapat ditambah, diduplikasi, dihapus, dan diatur urutannya:

| Jenis Blok | Fungsi dan Penggunaan |
| :--- | :--- |
| **`richtext` (Teks Kaya)** | Konten narasi, daftar, tabel, gambar, dan tautan PDF menggunakan **3-Tab Studio TinyMCE 8** (*Editor Visual*, *Kode HTML*, dan *Pratinjau*). |
| **`document_list` (Daftar Dokumen PDF)** | Menampilkan tabel arsip PDF otomatis berdasarkan kategori koleksi (misal `lkjip`, `renstra`, `dipa`, `lra`, `calk`, `sop_ppid`, `biaya_perkara`, `sisa_panjar`). |
| **`article_list` (Daftar Publikasi)** | Menampilkan daftar otomatis berita/artikel berdasarkan kategori publikasi tertentu (misal artikel Prodeo/Posbakum atau Informasi Serta Merta). |
| **`embed_fallback` (Integrasi SIPP / SIMTEPA)** | Menampilkan *iframe* SIPP (Daftar Perkara, Jadwal Sidang, Statistik) atau SIMTEPA (**Bagan Struktur Organisasi Langsung**, **Profil Pegawai 7 Tab**, **Struktur Pejabat 6 Tab**, atau **7 Kategori Satuan**) lengkap dengan tombol fallback ke **Data Cadangan Lokal**. |
| **`external_portal` (Kartu Portal Eksternal)** | Kartu rujukan resmi dengan deskripsi dan tombol akses menuju SIPP, e-Court MARI, Direktori Putusan, atau SIWAS. |
| **`map_yurisdiksi` (Peta Wilayah Yurisdiksi)** | Peta interaktif lokasi dan wilayah hukum satuan kerja berdasarkan koordinat `latitude`, `longitude`, dan tingkat `zoom` (Kriteria `1.b.p.b`). |
| **`satker_contact` (Alamat & Foto Gedung)** | Menarik otomatis alamat terstruktur, telepon, email, jam kerja PTSP, dan foto gedung dari Pengaturan Satker (Kriteria `1.b.p.d`). |

**Fitur SEO & Redirect Otomatis pada Halaman:**
- Panel kanan editor menyediakan tab **SEO & Slug**, **Publikasi**, dan **Pemetaan Kriteria SINOVA**.
- Setiap kali **Slug URL** halaman diubah, sistem secara otomatis membuat aturan **Redirect 301** dari alamat lama ke alamat baru sehingga tidak menimbulkan tautan rusak (`404`).

### 6.3 Berita, Pengumuman, dan Publikasi (`/panel-admin/publikasi`)

Digunakan untuk menerbitkan Berita Pelayanan, Pengumuman Resmi, Artikel Layanan & Bantuan Hukum (Prodeo/Posbakum), Inovasi Satker, Opini & Gagasan, Galeri Infografis, serta Informasi Serta Merta.

- **Studio Editor 3-Tab (TinyMCE 8 GPL Self-Hosted):**
  - **Tab Editor:** Penyuntingan visual lengkap (judul, daftar, tabel, kutipan, unggah gambar langsung ke WebP, dan penyisipan tautan dokumen PDF).
  - **Tab Code:** Penyuntingan langsung kode sumber HTML bagi admin yang ingin menyempurnakan struktur tabel atau tata letak khusus.
  - **Tab Preview:** Pratinjau tampilan akhir artikel sebagaimana akan terlihat oleh pengunjung sebelum diterbitkan.
- **Gambar Sampul (Featured Image):** Mendukung unggahan gambar yang otomatis dikonversi dan dikompresi ke format WebP.
- **Status Publikasi & Statistik:** Mendukung status *Draft* / *Tayang*, pencatatan tanggal unggah, penghitung jumlah dilihat (`views_count`), serta fitur komentar pembaca yang dapat dimoderasi.

### 6.4 Moderasi Komentar Publik (`/panel-admin/komentar`)

Pengunjung portal dapat menyampaikan pertanyaan atau tanggapan pada halaman detail publikasi. Demi menjaga etika informasi dan mencegah spam:
- Seluruh komentar baru masuk dengan status **Menunggu Moderasi** (tidak langsung tampil di publik).
- Pengelola (`admin_konten` atau `operator_ppid_humas`) dapat **Menyetujui**, **Menolak / Menandai Spam**, **Menghapus**, serta menulis **Balasan Resmi Satuan Kerja** yang tampil mencolok di bawah komentar warga.

### 6.5 Arsip Dokumen PDF (`/panel-admin/dokumen`)

Modul terpusat untuk mengelola dokumen transparansi dan laporan berkala (batas ukuran unggahan hingga **10 MB per berkas PDF**):

- **Kategori Koleksi Standar:** Laporan Tahunan, Renstra, RKT, Renaksi, Perjanjian Kinerja (PK), IKU, LKjIP, DIPA, LRA, CALK, LHKPN, Pengadaan Barang & Jasa (PBJ), Laporan Biaya Perkara, Pengembalian Sisa Panjar, Panggilan Ghaib, SK Radius & Biaya Perkara, Laporan Akses Informasi PPID, Rekapitulasi Pengaduan, Hukuman Disiplin, dan 7 SOP PPID.
- Setiap dokumen mencatat **Tahun Anggaran**, **Judul Dokumen**, **Nomor/Keterangan**, **Kategori Koleksi**, dan **Berkas PDF**.
- Dokumen yang diunggah otomatis tampil berurutan (terbaru di atas) pada seluruh halaman yang memakai blok `document_list` dengan kategori koleksi yang sesuai.

### 6.6 Manajemen Menu Navigasi (`/panel-admin/menu`)

Struktur menu portal publik mengikuti standar konsolidasi **maksimal 6 menu utama** (agar navigasi tidak membingungkan masyarakat awam sesuai Kriteria `2.c.1`):

1. **Beranda**
2. **Profil Pengadilan**
3. **Layanan Hukum**
4. **Transparansi**
5. **Layanan Publik**
6. **Berita & Publikasi**

Pengelola dapat menyusun submenu hingga **3 tingkat**, menautkannya ke Halaman CMS, Rute Internal (seperti `/publikasi`, `/statistik-pengunjung`), atau URL Eksternal (seperti SIPP, e-Court, Direktori Putusan), serta mengatur urutan dan status aktif/nonaktif.

### 6.7 Slider Beranda (`/panel-admin/slider`)

Sesuai Bab III.A.5 Juknis Badilag, slider beranda memiliki aturan ketat yang dijaga langsung oleh sistem:
- **Jumlah Slide Aktif:** Dianjurkan **3 sampai 5 slide** (maksimal 5 slide aktif agar waktu muat beranda tetap di bawah 5 detik).
- **Format & Rasio:** Hanya menerima gambar statis (tanpa video atau *live streaming*). Gunakan rasio **16:9 HD (`1280 x 720` piksel)** agar tidak terpotong di desktop maupun ponsel. Gambar yang diunggah otomatis dikonversi ke format WebP.
- **Konten Setiap Slide:** Judul singkat, subjudul/keterangan, label tombol aksi (CTA), URL tujuan, dan urutan tampil.

### 6.8 Data Cadangan Kepaniteraan dan Pegawai (`/panel-admin/kepaniteraan`)

Modul ini menyediakan mekanisme *graceful fallback* (cadangan lokal) apabila layanan SIPP atau SIMTEPA sedang mengalami gangguan jaringan:
- **Tab Jadwal Sidang Cadangan:** Pencatatan tanggal sidang, nomor perkara, ruang sidang, agenda, dan majelis.
- **Tab Statistik Perkara Bulanan:** Pencatatan tahun, bulan, sisa bulan lalu, perkara masuk, perkara putus, dan sisa akhir.
- **Tab Panggilan Ghaib:** Pencatatan nama pihak, nomor perkara, tanggal sidang, dan tautan/berkas relaas panggilan.
- **Tab Pegawai / Struktur Cadangan:** Pencatatan nama, jabatan, dan kategori unit kerja (**tanpa kolom NIP maupun tanggal lahir** demi perlindungan data pribadi sesuai SK KMA 2-144/2022).

---

## Bab 7. Panduan Evaluasi SINOVA dan Pedoman Pengawasan

### 7.1 Menggunakan Hub Link SINOVA (`/panel-admin/link-sinova`)

Menu **Link SINOVA** merupakan pusat kendali utama saat satuan kerja menghadapi evaluasi berkala dari Ditjen Badilag maupun PTA:

1. **Ringkasan Kepatuhan Otomatis:** Menampilkan jumlah Link Terisi (`35/35` untuk Tingkat Pertama atau `34/34` untuk Tingkat Banding), Link Kosong, Kategori Lengkap, dan skor kesiapan.
2. **Pemetaan Otomatis & Penyesuaian URL:** Setiap kriteria memiliki URL bawaan ke halaman CMS yang sudah disiapkan saat setup. Pengelola tetap dapat mengganti tautan dengan URL kustom (misalnya tautan SIPP atau halaman khusus) dan menyimpannya per baris.
3. **Penguncian Link (Lock):** Link yang sudah diverifikasi kebenarannya oleh pimpinan atau penilai internal dapat ditandai terverifikasi agar tidak berubah tanpa sengaja.
4. **Cek Kesehatan Tautan:** Menjalankan pemeriksaan HTTP pada seluruh link terdaftar untuk memastikan status `200 OK` (bebas 404/500).
5. **Ekspor CSV Siap Lapor:** Tombol **Ekspor CSV** menghasilkan berkas rekap 40 kriteria lengkap dengan kode resmi, nama kriteria, status, dan URL aktif untuk dilampirkan pada laporan evaluasi atau pengisian aplikasi SINOVA Badilag.

### 7.2 Daftar Lengkap 40 Kriteria Resmi SINOVA (`juknis-2026.json`)

Tabel berikut memuat seluruh **18 grup** dan **40 kriteria resmi** (35 URL + 5 non-URL) yang tertanam pada SatkerCMS sesuai **SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026**:

#### Aspek 1: Konten dan Informasi (34 Kriteria URL)

| Kode | Grup | Nama Kriteria | Tipe | Cakupan | Rute Default SatkerCMS |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `1.a.1.a` | 1.a.1 Informasi Umum | Rencana Aksi Kinerja (5 tahun) | URL | Semua | `/halaman/transparansi/rencana-aksi-kinerja` |
| `1.a.1.b` | 1.a.1 Informasi Umum | Laporan Pelaksanaan Kegiatan (Laporan Tahunan 5 tahun) | URL | Semua | `/halaman/transparansi/laporan-tahunan` |
| `1.a.2.a` | 1.a.2 Kepaniteraan | Sistem Informasi Penelusuran Perkara (SIPP) | URL | Semua | `/halaman/layanan-hukum/sipp` |
| `1.a.2.b` | 1.a.2 Kepaniteraan | Agenda/Jadwal Persidangan | URL | Semua | `/halaman/layanan-hukum/jadwal-sidang` |
| `1.a.2.c` | 1.a.2 Kepaniteraan | Statistik Perkara | URL | Semua | `/halaman/layanan-hukum/statistik-perkara` |
| `1.a.2.d` | 1.a.2 Kepaniteraan | Panggilan Ghaib | URL | Semua | `/halaman/layanan-hukum/panggilan-ghaib` |
| `1.a.2.e` | 1.a.2 Kepaniteraan | Laporan Penggunaan Biaya Perkara | URL | Semua | `/halaman/layanan-hukum/laporan-biaya-perkara` |
| `1.a.2.f` | 1.a.2 Kepaniteraan | Laporan Pengembalian Sisa Panjar Perkara | URL | Tk. Pertama | `/halaman/layanan-hukum/sisa-panjar` |
| `1.a.3.a` | 1.a.3 Kesekretariatan | Laporan Harta Kekayaan Penyelenggara Negara (LHKP) | URL | Semua | `/halaman/transparansi/lhkpn` |
| `1.a.3.b` | 1.a.3 Kesekretariatan | Daftar Isian Pelaksanaan Anggaran (DIPA) | URL | Semua | `/halaman/transparansi/dipa` |
| `1.a.3.c` | 1.a.3 Kesekretariatan | Laporan Realisasi Anggaran (LRA) | URL | Semua | `/halaman/transparansi/lra` |
| `1.a.3.d` | 1.a.3 Kesekretariatan | Catatan Atas Laporan Keuangan (CALK) | URL | Semua | `/halaman/transparansi/calk` |
| `1.a.3.e` | 1.a.3 Kesekretariatan | Pengadaan Barang dan Jasa | URL | Semua | `/halaman/transparansi/pengadaan-barang-jasa` |
| `1.a.3.f` | 1.a.3 Kesekretariatan | Rencana Strategis (Renstra) | URL | Semua | `/halaman/transparansi/renstra` |
| `1.a.3.g` | 1.a.3 Kesekretariatan | Rencana Kinerja Tahunan (RKT) | URL | Semua | `/halaman/transparansi/rkt` |
| `1.a.3.h` | 1.a.3 Kesekretariatan | Rencana Aksi Kinerja (Tahun Berjalan) | URL | Semua | `/halaman/transparansi/renaksi-berjalan` |
| `1.a.3.i` | 1.a.3 Kesekretariatan | Perjanjian Kinerja (PK) | URL | Semua | `/halaman/transparansi/perjanjian-kinerja` |
| `1.a.3.j` | 1.a.3 Kesekretariatan | Indikator Kinerja Utama (IKU) dan Reviu IKU | URL | Semua | `/halaman/transparansi/iku` |
| `1.a.3.k` | 1.a.3 Kesekretariatan | Laporan Kinerja Instansi Pemerintah (LKjIP) | URL | Semua | `/halaman/transparansi/lkjip` |
| `1.a.4.1.a` | 1.a.4.1 Permintaan Informasi | Laporan Akses Informasi | URL | Semua | `/halaman/layanan-publik/laporan-akses-informasi` |
| `1.a.4.2.a` | 1.a.4.2 Layanan Pengaduan | Laporan Rekapitulasi Pengaduan | URL | Semua | `/halaman/layanan-publik/rekapitulasi-pengaduan` |
| `1.a.4.3.a` | 1.a.4.3 Pengawasan | Hukuman Disiplin Hasil Pengawasan | URL | Semua | `/halaman/layanan-publik/hukuman-disiplin` |
| `1.a.4.4.a` | 1.a.4.4 Informasi Lainnya | Statistik Kunjungan Website | URL | Semua | `/statistik-pengunjung` |
| `1.b.1` | 1.b Informasi Wajib | Visi dan Misi Pengadilan | URL | Semua | `/halaman/profil/visi-misi` |
| `1.b.2.a` | 1.b.2 Kepaniteraan | e-Court | URL | Semua | `/halaman/layanan-hukum/ecourt` |
| `1.b.2.b` | 1.b.2 Kepaniteraan | Direktori Putusan | URL | Semua | `/halaman/layanan-hukum/direktori-putusan` |
| `1.b.2.c` | 1.b.2 Kepaniteraan | Prosedur Berperkara | URL | Semua | `/halaman/layanan-hukum/prosedur-berperkara` |
| `1.b.2.d` | 1.b.2 Kepaniteraan | Biaya Proses Berperkara | URL | Semua | `/halaman/layanan-hukum/biaya-berperkara` |
| `1.b.3.a` | 1.b.3 Kesekretariatan | Profil Pejabat dan Pegawai | URL | Semua | `/halaman/profil/pejabat-pegawai` |
| `1.b.4.a` | 1.b.4 Layanan Publik | Standar dan Maklumat Pelayanan Pengadilan | URL | Semua | `/halaman/layanan-publik/standar-maklumat-pelayanan` |
| `1.b.p.a` | 1.b.p Profil Pengadilan | Tugas Pokok dan Fungsi Pengadilan | URL | Semua | `/halaman/profil/tupoksi` |
| `1.b.p.b` | 1.b.p Profil Pengadilan | Wilayah Yurisdiksi | URL | Semua | `/halaman/profil/wilayah-yurisdiksi` |
| `1.b.p.c` | 1.b.p Profil Pengadilan | Struktur Organisasi | URL | Semua | `/halaman/profil/struktur-organisasi` |
| `1.b.p.d` | 1.b.p Profil Pengadilan | Alamat dan Kontak Pengadilan | URL | Semua | `/halaman/profil/alamat-kontak` |

#### Aspek 2 (UI/UX & Aksesibilitas) dan Aspek 3 (Performa & Keamanan) (1 URL + 5 Item Non-URL)

| Kode | Grup | Nama Kriteria | Tipe | Bobot Aspek | Rujukan Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `2.a.1` | 2.a Mobile Responsiveness | Mobile Responsiveness | URL | 40% UI/UX | `/` (uji tampilan ponsel & tablet) |
| `2.b.1` | 2.b Aksesibilitas (WCAG) | Aksesibilitas Ramah Disabilitas / WCAG Compliance | Item | 30% UI/UX | `/aksesibilitas` & widget disabilitas |
| `2.c.1` | 2.c Navigasi & Pencarian | Navigasi & Pencarian (Searchability) | Item | 30% UI/UX | `/cari` & 6 menu hierarkis |
| `3.a.1` | 3.a Keamanan Website | Keamanan Website (SSL, MFA 2FA, versi PHP & CMS) | Item | 40% Performa | `/status-sistem` |
| `3.b.1` | 3.b Kecepatan Akses | Kecepatan Akses / Page Load Speed (< 5 detik = 10) | Item | 30% Performa | `/` (uji PageSpeed / Lighthouse) |
| `3.c.1` | 3.c Validitas Tautan | Validitas Tautan / Bebas Broken Links (404) | Item | 30% Performa | `/peta-situs` & Audit Broken Link |

### 7.3 Manajemen dan Impor Juknis Masa Depan (`/panel-admin/kriteria`)

Apabila di masa mendatang Ditjen Badilag menerbitkan pembaruan Juknis Evaluasi Website (misalnya Juknis 2027 atau perubahan bobot/kode kriteria), `super_admin` tidak perlu mengubah kode program:
1. Buka menu **`/panel-admin/kriteria`**.
2. Tambah/ubah grup atau butir kriteria secara langsung, **atau** unggah berkas **JSON Juknis** baru menggunakan fitur **Impor Kriteria JSON**.
3. Sistem secara otomatis menyelaraskan daftar kriteria di Dashboard dan Hub Link SINOVA tanpa menghapus tautan yang sudah terisi sebelumnya.

### 7.4 Checklist Halaman Depan Pedoman Pengawasan (Bagian A-6)

Sebelum jadwal pengawasan reguler PTA atau Badan Pengawasan MA RI, pastikan seluruh item berikut telah terisi pada **`/panel-admin/pengaturan`**:
- Logo resmi dan nama satuan kerja tampil jelas pada kop website.
- Slider beranda aktif 3–5 gambar informatif beresolusi `1280 x 720`.
- Tautan **SIPP**, **e-Court**, **Direktori Putusan**, **SIWAS**, dan **PTA/Tingkat Banding** terisi dan dapat dibuka.
- Nilai dan tautan survei **SKM (IKM)**, **SPAK**, dan **SPKP** diperbarui sesuai periode berjalan.
- Tautan **Video Profil**, **Video PTSP**, dan **Video Kebijakan MA** terisi.
- Daftar **Tautan Instansi Terkait**, **Sosial Media Resmi**, dan **Access CCTV Online (ACO)** terisi dan aktif.
- Jam layanan PTSP (Senin–Kamis & Jumat) sesuai ketentuan resmi satuan kerja.

---

## Bab 8. Portal Publik, Aksesibilitas WCAG, dan SEO

### 8.1 Peta Rute Portal Publik

| Rute Publik | Fungsi Halaman |
| :--- | :--- |
| `/` | Beranda portal utama (Hero Slider, Pencarian Cepat, Layanan Unggulan, Pengawasan/Survei/Video, Berita Terbaru, Tautan Instansi, Medsos, dan ACO CCTV) |
| `/halaman/{slug}` | Halaman konten dinamis dari Page Builder (Profil, Layanan Hukum, Transparansi SAKIP, PPID) |
| `/publikasi` | Direktori Berita, Pengumuman Resmi, Inovasi Satker, Artikel Prodeo/Posbakum, dan Informasi Serta Merta |
| `/publikasi/{slug}` | Halaman detail artikel dengan pembacaan nyaman (`max-w-7xl`), statistik dilihat, tombol salin tautan, dan kolom komentar termoderasi |
| `/cari` | Pencarian internal lintas Halaman, Publikasi, dan Dokumen PDF (didukung pintasan keyboard `Ctrl + K`) |
| `/aksesibilitas` | Pernyataan kepatuhan dan panduan lengkap fitur ramah disabilitas WCAG 2.2 AA |
| `/status-sistem` | Informasi transparan versi PHP, Laravel, SatkerCMS, keamanan HTTPS, dan status 2FA (Kriteria `3.a.1`) |
| `/statistik-pengunjung` | Grafik dan rekapitulasi kunjungan harian/bulanan/tahunan portal (Kriteria `1.a.4.4.a`) |
| `/peta-situs` | Peta situs HTML terstruktur untuk pengunjung awam dan penilai tautan |
| `/sitemap.xml` | Peta situs XML dinamis otomatis bagi Google Search Console dan mesin pencari |
| `/robots.txt` | Aturan perayapan bot mesin pencari dinamis yang melindungi jalur `/panel-admin` |

### 8.2 Fitur Widget Aksesibilitas Ramah Disabilitas

Setiap halaman publik dilengkapi tombol mengambang **Aksesibilitas** di sisi layar (serta tautan *Lewati ke Konten Utama* bagi pengguna keyboard) yang menyediakan:
1. **Pembaca Layar / Text-to-Speech (TTS `id-ID`):** Membacakan isi halaman atau teks yang disorot menggunakan Web Speech API berbahasa Indonesia.
2. **Perbesar / Perkecil Teks:** Penyesuaian skala huruf bertahap hingga **200%** tanpa merusak tata letak halaman.
3. **Mode Kontras Tinggi:** Meningkatkan rasio kontras teks dan latar belakang bagi penyandang *low vision*.
4. **Mode Skala Abu-abu (Grayscale) & Garis Bawah Tautan:** Memudahkan identifikasi seluruh elemen interaktif.
5. **Reset Pengaturan:** Mengembalikan seluruh preferensi visual ke kondisi semula dalam satu klik (preferensi tersimpan di `localStorage` browser pengguna).

---

## Bab 9. Audit Aktivitas, Redirect 301, Backup, dan Kesiapan Instalasi Baru

### 9.1 Modul Audit, Kesehatan Tautan, Redirect, dan Backup (`/panel-admin/audit`)

Halaman **`/panel-admin/audit`** merangkum empat instrumen tata kelola penting:

1. **Log Aktivitas (`spatie/laravel-activitylog`):** Mencatat setiap pembuatan, perubahan, dan penghapusan pada Halaman, Publikasi, Dokumen, Menu, Slider, Pengaturan Satker, dan Pengguna lengkap dengan stempel waktu dan pelaku.
2. **Pemindai Tautan Rusak (Broken Link Audit):** Memindai seluruh tautan menu navigasi dan kriteria SINOVA serta menampilkan status HTTP masing-masing untuk menjaga nilai sempurna Kriteria `3.c.1`. Setiap pemeriksaan mengganti catatan pemeriksaan sebelumnya agar hasil tidak menumpuk duplikat, dilengkapi ringkasan jumlah tautan bermasalah/sehat serta filter `Bermasalah Saja`.
3. **Manajemen Redirect 301:** Menampilkan daftar pengalihan permanen (`301`) yang terbuat otomatis saat slug berubah maupun aturan pengalihan URL lama agar tautan yang sudah terindeks Google atau terdaftar di SINOVA tidak pernah menghasilkan `404 Not Found`. Pengelola juga dapat menambah aturan Redirect 301 manual.
4. **Backup Terenkripsi (`spatie/laravel-backup`):** Menjalankan pencadangan database dan berkas penting menjadi arsip `.zip` terenkripsi kata sandi (`BACKUP_ARCHIVE_PASSWORD` di `.env`) serta menyediakan daftar unduhan arsip cadangan. SatkerCMS mendeteksi lokasi binary `mysqldump` secara otomatis (termasuk instalasi Laragon/XAMPP/Linux standar), dan mendukung penentuan manual lewat `MYSQL_DUMP_BINARY_PATH` di `.env` jika diperlukan.

### 9.2 Kesiapan Instalasi Baru (*Fresh Installation*)

Repositori SatkerCMS disiapkan bersih untuk **instalasi baru (*fresh installation*)** bagi satuan kerja Pengadilan Agama maupun Mahkamah Syar'iyah (Tingkat Pertama maupun Tingkat Banding) tanpa menyertakan data atau *seeder* migrasi milik satuan kerja tertentu:

1. Perintah `php artisan migrate --force && php artisan db:seed --force` hanya menginisialisasi 6 peran dan hak akses standar (`RoleAndPermissionSeeder`).
2. Kunjungan pertama ke portal otomatis mengarahkan pengelola ke **Setup Wizard (`/setup`)** untuk mengisi identitas satuan kerja, mengunci tingkat pengadilan, menanamkan 40 kriteria SINOVA (`SinovaCriteriaSeeder`), serta membuat halaman profil, menu navigasi, dan slider bawaan (`SatkerDefaultContentSeeder`) yang seluruhnya otomatis menyesuaikan nama satuan kerja.
3. Seluruh data migrasi khusus maupun *seeder* data lama dikecualikan melalui `.gitignore` agar repositori tetap ringan, bersih, dan siap pakai untuk satuan kerja baru.

---

## Bab 10. Pemeliharaan Berkala, Pembaruan CMS, dan Troubleshooting

### 10.1 Daftar Perintah Artisan Pemeliharaan

| Perintah | Kegunaan |
| :--- | :--- |
| `php artisan cms:check-update --force` | Memeriksa ketersediaan rilis terbaru SatkerCMS langsung ke GitHub Releases dan memperbarui status Dashboard Admin. |
| `php artisan cms:update --dry-run` | Memeriksa versi CMS, mode peladen (`atomic_release` / `staged_inplace`), status rilis GitHub, dan kesiapan link SINOVA tanpa mengubah berkas atau database. |
| `php artisan cms:update` | Mengunduh paket rilis terbaru dari GitHub Releases, memvalidasi checksum `SHA-256`, mencadangkan database otomatis, menerapkan pembaruan secara *zero-downtime* (tanpa perlu Node.js/Composer/Git di server), dan mereset OPcache web. Pada VPS Ubuntu disarankan dijalankan dengan `sudo -u www-data php artisan cms:update --yes`. |
| `php artisan cms:update --local-only` | Menjalankan penemuan paket, migrasi database, pemanasan cache, dan pemeriksaan kesehatan SINOVA setelah penarikan kode manual (`git pull`). |
| `php artisan cms:rollback` | Mengembalikan aplikasi ke rilis sebelumnya di folder `releases/` (atau snapshot cadangan lokal `prev-*`) secara cepat apabila ditemukan kendala pasca-pembaruan. |
| `php artisan cms:process-update-queue` | Memproses antrean tiket pembaruan/rollback bertanda tangan HMAC dari tombol 1-klik Dashboard Admin (`everyMinute`), atau pembaruan otomatis pukul `02:30` bila `CMS_UPDATE_AUTO=true` (`--auto`). |
| `php artisan satker:optimize-media` | Mengonversi dan mengecilkan seluruh gambar eksisting (slider, logo, foto gedung, gambar berita) ke WebP terkompresi. |
| `php artisan backup:run` | Menjalankan pencadangan penuh database dan berkas ke arsip ZIP terenkripsi. Backup juga dapat dijalankan dan diunduh langsung dari tab **Backup & Pembaruan Sistem** di `/panel-admin/audit`. |
| `php artisan schedule:list` | Menampilkan jadwal pemeliharaan otomatis: backup harian pukul 02:00, auto-update opsional pukul 02:30 (`CMS_UPDATE_AUTO=true`), pembersihan arsip lama pukul 03:00, pemangkasan log aktivitas setiap Senin pukul 03:30, pengecekan rilis GitHub pukul 04:00 & 16:00, serta pemroses antrean update 1-klik setiap menit (dijalankan cron `schedule:run`). |
| `php artisan optimize` | Membangun ulang cache konfigurasi, rute, dan tampilan Blade untuk performa produksi maksimal. |
| `php artisan optimize:clear` | Membersihkan seluruh cache Laravel apabila terjadi kendala tampilan atau perubahan `.env`. |
| `php artisan test` | Menjalankan seluruh rangkaian pengujian otomatis Pest guna memastikan integritas aplikasi setelah pembaruan. |

#### Prosedur Pembaruan Zero-Downtime, Tombol 1-Klik, & Struktur `releases/current`
1. **Deteksi Otomatis & Pemicu 1-Klik via Antrean Cron (`schedule:run`):** Pengguna `super_admin` dan `admin_konten` menerima banner pemberitahuan di Dashboard `/panel-admin` setiap kali rilis baru terbit di GitHub Releases. Khusus `super_admin` (dengan 2FA TOTP aktif), tersedia tombol **Perbarui ke vX.Y.Z** (serta tombol **Rollback** bila cadangan tersedia) yang menulis tiket bertanda tangan HMAC `APP_KEY` untuk dieksekusi oleh cron permanen `schedule:run` dalam <= 1 menit tanpa batas timeout browser dan tanpa perlu pasang-hapus Cron Job di cPanel. Fitur tombol web ini dapat dimatikan via `.env` (`CMS_UPDATE_WEB_TRIGGER=false`) untuk peladen VPS ketat, dan tersedia pula flag opsional `CMS_UPDATE_AUTO=true` bagi satker yang menginginkan pembaruan otomatis penuh setiap pukul `02:30` dini hari setelah backup harian selesai.
2. **Mode Atomic Symlink (`releases/current`):** Untuk VPS Linux maupun Shared Hosting Opsi A yang dapat mengatur Document Root ke `{root}/current/public` (dengan `{root}/shared/.env` dan `{root}/shared/storage`), `php artisan cms:update` mengekstrak rilis baru ke `{root}/releases/vX.Y.Z` lalu menukar symlink `current` secara atomik dalam < 1 milidetik tanpa waktu henti.
3. **Mode Staged Swap (`public_html`):** Untuk instalasi satu folder maupun Shared Hosting Opsi B (`satkercms_core` + `public_html`, opsional didukung variabel `.env` `CMS_PUBLIC_PATH=/home/<user>/public_html`), `php artisan cms:update` otomatis mempertahankan aset CSS/JS Vite versi sebelumnya selama transisi, mencadangkan versi lama ke snapshot lokal untuk *auto-revert* / `cms:rollback`, dan mereset OPcache web secara otomatis.

### 10.2 Jadwal Pemeliharaan Berkala yang Disarankan

- **Harian / Otomatis (Cron Scheduler):** Backup database otomatis pukul 02:00, pembersihan arsip backup lama pukul 03:00, pemangkasan log aktivitas setiap Senin pukul 03:30, serta agregasi statistik pengunjung.
- **Mingguan:** Moderasi komentar publik, publikasi berita kegiatan, dan pengecekan agenda jadwal sidang/SIPP.
- **Bulanan:** Unggah laporan bulanan Kepaniteraan (biaya perkara, sisa panjar, statistik perkara), jalankan Audit Broken Link di `/panel-admin/audit`, dan simpan salinan backup terenkripsi di media penyimpanan eksternal (luring).
- **Triwulanan / Tahunan:** Perbarui nilai Indeks Survei (SKM, SPAK, SPKP), unggah dokumen SAKIP tahunan (LKjIP, PK, RKT, Renaksi, DIPA, LRA, CALK, LHKPN, Laporan Tahunan), dan ekspor laporan CSV dari **Link SINOVA**.

### 10.3 Pemecahan Masalah Umum (Troubleshooting)

1. **Pesan Galat `Unable to locate file in Vite manifest`:**
   - Pastikan folder `public/build/` (berisi `manifest.json` dan `assets/`) sudah ikut terunggah ke server.
   - Jika belum ada, jalankan `npm run build` di komputer lokal lalu unggah folder `public/build/`.
   - Pada Shared Hosting Opsi B (`public_html`), pastikan baris `$app->usePublicPath(__DIR__);` terpasang di `public_html/index.php`.

2. **Logo, Foto Gedung, Slider, atau Dokumen PDF Mengembalikan Error `404`:**
   - Tautan simbolis (`symlink`) `public/storage` belum dibuat atau masih mengarah ke jalur komputer lokal.
   - Hapus folder/symlink `public/storage` yang lama, lalu jalankan `php artisan storage:link` (atau buat ulang dengan perintah `ln -s` pada cPanel).

3. **Error `500 Internal Server Error` atau `Permission denied` pada `storage/logs`:**
   - Pastikan kepemilikan dan izin tulis pada folder `storage/` dan `bootstrap/cache/` sesuai user web server (`www-data` pada Ubuntu):
     ```bash
     sudo chown -R www-data:www-data storage bootstrap/cache
     sudo chmod -R 775 storage bootstrap/cache
     ```
   - Periksa rincian galat terakhir pada berkas `storage/logs/laravel.log`.

4. **Gagal Mengunggah Dokumen PDF Berukuran Besar (`413 Request Entity Too Large`):**
   - SatkerCMS menerima PDF hingga **10 MB**. Pastikan konfigurasi server mencukupi:
     - Nginx: `client_max_body_size 16M;`
     - PHP (`php.ini` / MultiPHP INI Editor cPanel): `upload_max_filesize = 16M` dan `post_max_size = 20M`.
   - Restart layanan `nginx` dan `php8.5-fpm` setelah perubahan.

5. **Super Admin Kehilangan Perangkat Autentikator 2FA:**
   - Gunakan salah satu **Recovery Code** pada layar tantangan 2FA, atau minta `super_admin` lain melakukan reset dari menu **Pengguna & Peran**.
   - Apabila hanya terdapat satu akun `super_admin` dan seluruh Recovery Code hilang, reset 2FA melalui SSH/Terminal server:
     ```bash
     php artisan tinker --execute "App\Models\User::where('email', 'admin@pa-satker.go.id')->update(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);"
     ```
   - Login kembali untuk memindai QR Code 2FA baru.

6. **Bagan Struktur Organisasi SIMTEPA Tidak Muncul di Halaman Publik:**
   - Pastikan kolom **URL SIMTEPA Struktur** di `/panel-admin/pengaturan` telah diisi URL share SIMTEPA (`https://simtepa.mahkamahagung.go.id/share/struktur_organisasi/{token}`).
   - Buka halaman **Struktur Organisasi** di `/panel-admin/halaman`, lalu pastikan blok `embed_fallback` memilih sumber **`SIMTEPA Bagan Struktur Organisasi Langsung`**.
   - Jika server SIMTEPA pusat sedang dalam pemeliharaan, lengkapi data di `/panel-admin/kepaniteraan` (tab Pegawai/Struktur Cadangan) agar halaman tetap menampilkan informasi struktur cadangan.

---

## Penutup dan Kredit Pengembangan

SatkerCMS dikembangkan untuk mendukung transformasi digital, transparansi peradilan, dan peningkatan kualitas layanan publik berbasis elektronik pada seluruh satuan kerja di lingkungan Peradilan Agama dan Mahkamah Syar'iyah.

| Peran | Informasi |
| :--- | :--- |
| **Pembuat Aplikasi** | **Rahmat Triadi, S.Kom.** |
| **Nama Proyek** | SatkerCMS (CMS Website Profil Satuan Kerja Ditjen Badilag) |
| **Repositori Kode Sumber** | `https://github.com/rtriadi/cms-laravel-template-badilag` |
| **Lisensi** | MIT License |
