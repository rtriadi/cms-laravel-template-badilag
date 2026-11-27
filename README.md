# SatkerCMS

**CMS Website Profil Resmi Satuan Kerja Peradilan Agama dan Mahkamah Syar'iyah** (Tingkat Pertama dan Tingkat Banding) yang dirancang selaras dengan **SK Dirjen Badilag Nomor 2722/DJA/SK.TI1/X/2026** (Penilaian **SINOVA**: 18 grup, 40 kriteria), **SK KMA Nomor 2-144/KMA/SK/VIII/2022** (Standar Pelayanan Informasi Publik / PPID), serta **Pedoman Pengawasan PTA/Bawas**.

![Versi](https://img.shields.io/badge/Versi-v1.0.0--dev-056e4f?style=flat-square)
![Pembuat](https://img.shields.io/badge/Pembuat-Rahmat%20Triadi%2C%20S.Kom.-063b2b?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4.x%20%2B%20Flux%20UI-FB70A9?style=flat-square)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![Lisensi](https://img.shields.io/badge/Lisensi-MIT-111827?style=flat-square)

> **Buku Panduan Lengkap SatkerCMS (Instalasi, Pengelolaan Konten, Evaluasi SINOVA 40 Kriteria, & Pemeliharaan):**
> - Baca langsung di GitHub: **[panduan/PANDUAN-LENGKAP-SATKERCMS.md](panduan/PANDUAN-LENGKAP-SATKERCMS.md)**
> - Unduh versi siap cetak (PDF): **[panduan/PANDUAN-LENGKAP-SATKERCMS.pdf](panduan/PANDUAN-LENGKAP-SATKERCMS.pdf)**

---

## Daftar Isi

- [1. Tentang Aplikasi & Versi](#1-tentang-aplikasi--versi)
- [2. Fitur Utama](#2-fitur-utama)
- [3. Persyaratan Server](#3-persyaratan-server)
- [4. Instalasi Lokal (Pengembangan)](#4-instalasi-lokal-pengembangan)
- [5. Cara Deploy di Shared Hosting (cPanel / DirectAdmin)](#5-cara-deploy-di-shared-hosting-cpanel--directadmin)
- [6. Cara Deploy di VPS (Ubuntu + Nginx + PHP-FPM + SSL)](#6-cara-deploy-di-vps-ubuntu--nginx--php-fpm--ssl)
- [7. Setup Awal Aplikasi & Login Admin](#7-setup-awal-aplikasi--login-admin)
- [8. Perintah Pemeliharaan & Pembaruan](#8-perintah-pemeliharaan--pembaruan)
- [9. Troubleshooting Umum](#9-troubleshooting-umum)

---

## 1. Tentang Aplikasi & Versi

| Informasi | Detail |
| :--- | :--- |
| **Nama Aplikasi** | SatkerCMS |
| **Pembuat Aplikasi** | **Rahmat Triadi, S.Kom.** |
| **Versi Saat Ini** | `v1.0.0-dev` (tercatat pada `config/satker.php` dan halaman `/status-sistem`) |
| **Dasar Regulasi** | SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 (SINOVA 40 kriteria), SK KMA No. 2-144/KMA/SK/VIII/2022 (PPID), dan Pedoman Pengawasan PTA/Bawas |
| **Target Instansi** | Pengadilan Agama (`PA`), Pengadilan Tinggi Agama (`PTA`), Mahkamah Syar'iyah (`MS`), dan Mahkamah Syar'iyah Aceh (`MS_ACEH`) |
| **Identitas Warna** | Hijau Utama `#056e4f`, Obsidian `#063b2b`, & Aksen Emas `#ff9900` (teks gelap demi kontras WCAG 2.2 AA) |
| **Stack Utama** | PHP 8.5 (minimal 8.3, disarankan 8.4/8.5 non-EOL), Laravel 13, Livewire 4, Flux UI Free, Tailwind CSS v4, TinyMCE 8 GPL (self-hosted), Laravel Fortify (wajib 2FA TOTP), Pest 4 (`110` pengujian otomatis) |

---

## 2. Fitur Utama

### Portal Publik (Masyarakat)
- **Beranda Clean Civic Editorial v4 & Tata Letak Seragam (`max-w-7xl`):** Utility bar tipis dengan pita emas 3px dan **1 baris sticky navbar putih bersih** (logo, 6 menu utama, pencarian cepat `Ctrl+K`, tombol `e-Court MARI`), **Full-Width Visual Banner Slider** (3–5 slide HD `1280x720` ber-scrim kontras AAA) dengan **Integrated Citizen Action Dock** tepat menempel di bawahnya (SIPP, Jadwal Sidang, e-Court, Prodeo/Posbakum, dan Cari), lalu disusun ringkas dalam **5 seksi editorial lapang**: Layanan Hukum `01-06` + Jam PTSP & Agenda Sidang/Survei, Berita Majalah Asimetris & Pengumuman, Pita Gelap Transparansi SAKIP (8 dokumen utama), serta Penutup Terpadu (Tautan Instansi, Access CCTV Online / ACO, Sosial Media, dan Aksesibilitas WCAG).
- **Kelengkapan Halaman Depan Pedoman Pengawasan (Bagian A-6):** Kartu capaian **Indeks Survei Pelayanan (SKM, SPAK, SPKP)**, tautan resmi **PTA / Tingkat Banding**, kartu tautan ringan **Video Profil, Video PTSP, dan Video Kebijakan MA** (non-embed agar muat halaman tetap di bawah 5 detik), **Tautan Instansi Terkait** dinamis, **Sosial Media Resmi** (Facebook, Instagram, TikTok, YouTube, X), serta akses cepat ringan **Access CCTV Online (ACO) Badilag**.
- **Halaman Dalam Editorial & Direktori Publikasi:** Hero banner seragam, Daftar Isi otomatis, menu navigasi halaman serumpun, kartu jam layanan PTSP, paginasi ringkas berbahasa Indonesia, penghitung jumlah dilihat, komentar publik termoderasi beserta balasan resmi, serta keterangan waktu publik (`Diunggah:` dan `Terakhir diperbarui:`).
- **Integrasi Aplikasi MARI + Mode Cadangan Lokal:**
  - **SIPP:** Otomatis memetakan jadwal sidang (`/list_jadwal_sidang`), daftar perkara (`/`), dan statistik perkara (`/statistik_perkara`).
  - **SIMTEPA:** Mendukung **Bagan Struktur Organisasi Langsung** (`/share/struktur_organisasi/{token}`), **Profil Pegawai 7 Tab** (Ketua, Wakil Ketua, Hakim, Kepaniteraan, Kesekretariatan, Fungsional, Pelaksana), **Struktur Pejabat 6 Tab**, dan 7 kategori satuan lengkap dengan mode cadangan tabel lokal tanpa ekspos data pribadi (NIP/tanggal lahir/kontak pribadi).
- **Aksesibilitas WCAG 2.2 AA:** Widget aksesibilitas bawaan (pembaca suara TTS Bahasa Indonesia `id-ID`, kontras tinggi, perbesar teks hingga 200%, skala abu-abu, garis bawah tautan, navigasi keyboard).
- **SEO Publik Siap Indeks Google & WhatsApp:** Canonical URL, Open Graph (`og:*`), Twitter Card, Favicon dinamis, Structured Data JSON-LD (`Courthouse` + `GovernmentOrganization`, `WebSite` + `SearchAction`, `BreadcrumbList`, `NewsArticle`), `/sitemap.xml` sesuai tingkat peradilan, `/robots.txt` dinamis, dan Redirect 301 otomatis saat slug halaman diubah.
- **Optimasi Kecepatan:** Bundel CSS publik terpisah, konversi gambar upload otomatis ke **WebP** via PHP GD, deduplikasi DOM menu navigasi mobile, caching menu & beranda otomatis.

### Panel Admin CMS (`/panel-admin`)
- **Studio Konten 2-Mode & 7 Blok Page Builder:** Pemisahan tampilan *Direktori* dan *Studio Editor* untuk Halaman Website (blok Teks Kaya, Daftar Dokumen PDF, Daftar Publikasi, Embed SIPP/SIMTEPA + Cadangan, Portal Eksternal, Peta Yurisdiksi, dan Alamat & Foto Gedung), Publikasi (termasuk **tanggal kedaluwarsa tayang** otomatis), Moderasi Komentar, Arsip Dokumen PDF, Slider Beranda, Menu Navigasi 3 tingkat, serta Data Cadangan Kepaniteraan/Pegawai.
- **3-Tab Studio TinyMCE 8 Self-Hosted (`Editor` | `Code` | `Preview`):** Mendukung penyuntingan visual, kode HTML langsung, pratinjau instan, tabel, serta unggah gambar (otomatis WebP) dan dokumen PDF.
- **Pengaturan Satker 6 Seksi:** Identitas & Logo/Gedung, Alamat & Kontak, Integrasi SIPP & SIMTEPA (termasuk URL Bagan Struktur Organisasi), Pengawasan & Beranda (PTA, Indeks SKM/SPAK/SPKP, Video), Tautan Instansi Dinamis (maks. 20) / Sosial Media Resmi / Access CCTV Online (ACO), dan Ubah Tingkat Peradilan dengan konfirmasi ganda Super Admin.
- **Hub Link SINOVA 2026 & Audit Sistem:** Pemetaan resmi **18 grup & 40 kriteria SINOVA** (`1.a.1.a` s.d. `3.c.1`: 35 URL + 5 non-URL), ekspor CSV siap lapor, manajemen/impor JSON kriteria juknis masa depan (`/panel-admin/kriteria`), audit tautan rusak (*broken link*), daftar Redirect 301, backup ZIP terenkripsi (`spatie/laravel-backup`) dengan tombol **Jalankan Backup Database** dan **Unduh** arsip di panel, serta log aktivitas (`spatie/laravel-activitylog`).
- **Deteksi Pembaruan GitHub & Zero-Downtime Updater:** Pengecekan rilis baru otomatis ke GitHub Releases dua kali sehari (`cms:check-update`), banner notifikasi versi baru di Dashboard Admin khusus `super_admin` dan `admin_konten`, pemicu pembaruan & *rollback* 1-klik khusus `super_admin` melalui antrean tiket bertanda tangan `APP_KEY` yang dieksekusi oleh cron permanen `schedule:run` (`CMS_UPDATE_WEB_TRIGGER=true`, plus opsi auto-update dini hari `CMS_UPDATE_AUTO=true` pukul 02:30), serta pembaruan terminal satu perintah `php artisan cms:update` berarsitektur *zero-downtime* (mendukung pertukaran symlink atomik `releases/current` di VPS maupun *staged swap* di Shared Hosting tanpa butuh Node.js/Composer/Git di peladen, lengkap dengan verifikasi `SHA-256`, backup database otomatis, reset OPcache web bertanda tangan, dan `php artisan cms:rollback`).
- **Keamanan Berlapis:** Login admin dengan CAPTCHA lokal + wajib **2FA TOTP** (Google Authenticator/Authy), reset 2FA akun lain oleh **Super Admin** langsung dari menu Pengguna & Peran, 6 peran RBAC standar (`super_admin`, `admin_konten`, `operator_kepaniteraan`, `operator_kesekretariatan`, `operator_ppid_humas`, `penilai_internal`), sanitasi HTML (`SafeHtml`), dan header keamanan HTTP (`SecurityHeadersMiddleware`).

---

## 3. Persyaratan Server

| Komponen | Minimum | Rekomendasi Produksi |
| :--- | :--- | :--- |
| **PHP** | `8.3` | **`8.4` atau `8.5`** (non-EOL, wajib hijau di `/status-sistem`) |
| **Ekstensi PHP** | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `intl`, `curl`, `zip` | Sama + **`gd` dengan dukungan WebP** dan **`opcache`** aktif |
| **Database** | MySQL `8.0` / MariaDB `10.6` | MySQL `8.0+` / MariaDB `10.11+` |
| **Web Server** | Apache 2.4 (`mod_rewrite`) atau Nginx 1.22+ | Nginx + PHP-FPM dengan Gzip/Brotli & HTTPS SSL |
| **Composer & Node.js** | Composer `2.x`, Node.js `20+` (hanya untuk proses build aset) | Composer `2.x`, Node.js `22 / 24` |

---

## 4. Instalasi Lokal (Pengembangan)

```bash
# 1. Clone repositori
git clone https://github.com/rtriadi/cms-laravel-template-badilag.git satkercms
cd satkercms

# 2. Instal dependensi PHP & Frontend
composer install
npm install
npm run build

# 3. Salin konfigurasi environment dan buat APP_KEY
cp .env.example .env
php artisan key:generate

# 4. Atur koneksi database di file .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD), lalu migrasi
php artisan migrate
php artisan storage:link

# 5. Jalankan server lokal (pilih salah satu)
php artisan serve
# atau jalankan Laravel + Vite bersamaan:
composer run dev
# atau khusus Windows, jalankan ganda run-app.bat / .\run-app.ps1
```

Buka `http://127.0.0.1:8000` di browser. Aplikasi otomatis mengarahkan ke halaman **Setup Wizard (`/setup`)**.

---

## 5. Cara Deploy di Shared Hosting (cPanel / DirectAdmin)

Shared hosting umumnya tidak menyediakan Node.js atau akses root. Karena itu, **build aset frontend dan paket `vendor/` disiapkan di komputer lokal terlebih dahulu**, lalu diunggah ke server.

### Langkah 1: Siapkan Paket Produksi di Komputer Lokal

```bash
# Instal dependensi PHP khusus produksi (tanpa paket dev)
composer install --no-dev --optimize-autoloader

# Build aset CSS & JS produksi (menghasilkan folder public/build)
npm ci
npm run build
```

Kompres seluruh isi folder proyek ke dalam satu arsip `satkercms-prod.zip` (**kecuali** folder `.git/`, `node_modules/`, `tests/`, dan file `.env` lokal). Pastikan folder **`vendor/`** dan **`public/build/`** ikut masuk ke dalam file ZIP.

### Langkah 2: Buat Database MySQL di cPanel
1. Masuk ke **cPanel -> MySQL Databases** (atau **Database Wizard**).
2. Buat database baru (contoh: `cpuser_satkercms`).
3. Buat user database baru beserta kata sandi kuat.
4. Hubungkan user ke database dengan mencentang **ALL PRIVILEGES**.

### Langkah 3: Unggah Berkas & Pilih Struktur Folder

Terdapat **2 cara** penempatan folder tergantung pengaturan domain di shared hosting Anda:

#### Opsi A (Paling Mudah & Direkomendasikan, untuk Addon Domain / Subdomain / Hosting yang Bisa Ubah Document Root)
1. Buat folder `/home/<user>/satkercms` di File Manager, unggah `satkercms-prod.zip`, lalu klik **Extract**.
2. Masuk ke menu **Domains** di cPanel, lalu ubah **Document Root** domain ke:
   ```text
   /home/<user>/satkercms/public
   ```
   Dengan cara ini file `.env` berada di luar folder publik dan aman dari akses langsung browser.

#### Opsi B (Untuk Domain Utama `public_html` yang Document Root-nya Tidak Bisa Diubah)
1. Buat folder baru `/home/<user>/satkercms_core` (sejajar dengan `public_html`, BUKAN di dalam `public_html`).
2. Unggah dan ekstrak `satkercms-prod.zip` ke dalam `/home/<user>/satkercms_core`.
3. Pindahkan **seluruh isi** folder `/home/<user>/satkercms_core/public/*` (termasuk file tersembunyi `.htaccess`, `index.php`, folder `build/`, folder `vendor/tinymce/`) ke dalam `/home/<user>/public_html/`.
4. Edit file `/home/<user>/public_html/index.php` menjadi seperti berikut:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../satkercms_core/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../satkercms_core/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../satkercms_core/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

> **Penting:** Baris `$app->usePublicPath(__DIR__);` wajib ada pada Opsi B agar Laravel membaca `public_html/build/manifest.json` dan `public_html/storage` dengan benar.

### Langkah 4: Konfigurasi File `.env` Produksi
Salin `.env.production.example` (template produksi yang sudah `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan `MAIL_MAILER=smtp`) menjadi `.env` di folder inti Laravel (`/home/<user>/satkercms/.env` atau `/home/<user>/satkercms_core/.env`), lalu sesuaikan nilai berikut:

```env
APP_NAME="Pengadilan Agama Contoh"
APP_ENV=production
APP_KEY=base64:ISI_KUNCI_DARI_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://pa-contoh.go.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpuser_satkercms
DB_USERNAME=cpuser_dbuser
DB_PASSWORD=KATA_SANDI_DATABASE_ANDA

ADMIN_PATH=panel-admin

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
TRUSTED_PROXIES=127.0.0.1
TRUSTED_HOSTS=^(?:www\.)?pa-contoh\.go\.id$
BACKUP_ARCHIVE_PASSWORD=GantiPasswordArsipBackupKuat!
# Opsional: direktori bin MySQL jika mysqldump tidak berada di PATH peladen
# MYSQL_DUMP_BINARY_PATH=/usr/bin
```

> **Catatan:** Jika Anda belum memiliki `APP_KEY`, Anda dapat menjalankan `php artisan key:generate --show` di komputer lokal lalu menyalin string `base64:...` tersebut ke `.env` server.

### Langkah 5: Atur Versi PHP & Ekstensi di cPanel
1. Buka menu **Select PHP Version** / **MultiPHP Manager** di cPanel.
2. Pilih **PHP 8.4** atau **PHP 8.5** untuk domain tersebut.
3. Pada tab **Extensions**, pastikan ekstensi berikut aktif: `gd`, `intl`, `fileinfo`, `mbstring`, `pdo_mysql`, `bcmath`, `zip`, `opcache`.
4. Pada menu **SSL/TLS Status**, jalankan **AutoSSL** agar situs berjalan penuh di **HTTPS**.

### Langkah 6: Migrasi Database, Symlink Storage, & Cache

#### Jika Shared Hosting Memiliki Menu Terminal / SSH:
```bash
cd /home/<user>/satkercms   # atau /home/<user>/satkercms_core
chmod -R 775 storage bootstrap/cache
php artisan migrate --force
php artisan storage:link
php artisan optimize
```
*(Untuk **Opsi B**, jika `storage:link` membuat link di folder lama `satkercms_core/public`, buat symlink langsung ke `public_html` dengan perintah: `ln -s /home/<user>/satkercms_core/storage/app/public /home/<user>/public_html/storage`.)*

#### Jika Shared Hosting TIDAK Memiliki Terminal / SSH:
1. **Migrasi Database:** Di komputer lokal, jalankan `php artisan migrate:fresh` pada database kosong, lalu ekspor SQL dari phpMyAdmin / `mysqldump` dan impor file `.sql` tersebut melalui **phpMyAdmin** di cPanel.
2. **Symlink Storage:** Masuk ke menu **Cron Jobs** di cPanel, jalankan perintah sekali saja (lalu hapus cron setelah symlink terbentuk):
   - Untuk Opsi A:
     ```bash
     ln -s /home/<user>/satkercms/storage/app/public /home/<user>/satkercms/public/storage
     ```
   - Untuk Opsi B:
     ```bash
     ln -s /home/<user>/satkercms_core/storage/app/public /home/<user>/public_html/storage
     ```
3. Pastikan izin folder `storage/` dan `bootstrap/cache/` bernilai `775` (atau `755` sesuai konfigurasi suPHP/LiteSpeed hosting).

### Langkah 7: Verifikasi `.htaccess` (Kompresi Gzip & Cache Aset Statis)
File `public/.htaccess` bawaan repositori sudah menyertakan aturan kompresi Gzip (`mod_deflate`), cache aset jangka panjang (`mod_expires`), dan header `Cache-Control: public, max-age=31536000, immutable` untuk aset statis, serta pemblokiran eksekusi PHP di dalam `storage/`. Pastikan file `public/.htaccess` (atau `public_html/.htaccess`) ikut terunggah utuh tanpa tertimpa bawaan hosting.

Buka `https://pa-contoh.go.id` di browser dan lanjutkan ke bagian [7. Setup Awal Aplikasi](#7-setup-awal-aplikasi--login-admin).

---

## 6. Cara Deploy di VPS (Ubuntu 22.04 / 24.04 + Nginx + PHP-FPM + SSL)

Panduan ini menyiapkan server produksi berkinerja tinggi (Nginx + PHP 8.5-FPM + OPcache + Gzip + HTTPS Let's Encrypt).

### Langkah 1: Instalasi Paket Server (Nginx, MySQL, PHP 8.5, Composer, Node.js)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip nginx mysql-server certbot python3-certbot-nginx

# Tambah PPA PHP (Ondrej Sury) & instal PHP 8.5 + ekstensi wajib
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.5-fpm php8.5-cli php8.5-mysql php8.5-xml php8.5-mbstring \
  php8.5-curl php8.5-zip php8.5-bcmath php8.5-gd php8.5-intl php8.5-opcache

# Instal Composer 2
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instal Node.js 22 LTS (untuk build aset Vite)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

*(Catatan: Jika ingin memakai PHP 8.4, cukup ganti `php8.5-*` menjadi `php8.4-*` pada perintah di atas dan di blok Nginx.)*

### Langkah 2: Buat Database & User MySQL

```bash
sudo mysql -e "CREATE DATABASE satker_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'satker_user'@'localhost' IDENTIFIED BY 'GantiDenganPasswordKuat123!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON satker_cms.* TO 'satker_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### Langkah 3: Clone Aplikasi & Build Aset

```bash
sudo mkdir -p /var/www/satkercms
sudo chown -R $USER:www-data /var/www/satkercms

git clone https://github.com/rtriadi/cms-laravel-template-badilag.git /var/www/satkercms
cd /var/www/satkercms

composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### Langkah 4: Konfigurasi `.env`, Migrasi, & Hak Akses

```bash
cp .env.production.example .env
php artisan key:generate
nano .env
```

Pastikan pengaturan kunci di `.env` produksi:

```env
APP_NAME="Pengadilan Agama Contoh"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pa-contoh.go.id
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=satker_cms
DB_USERNAME=satker_user
DB_PASSWORD=GantiDenganPasswordKuat123!

ADMIN_PATH=panel-admin
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
LOG_LEVEL=error
MAIL_MAILER=smtp
TRUSTED_PROXIES=127.0.0.1
TRUSTED_HOSTS=^(?:www\.)?pa-contoh\.go\.id$
BACKUP_ARCHIVE_PASSWORD=GantiPasswordArsipBackupKuat!
# Opsional: direktori bin MySQL jika mysqldump tidak berada di PATH peladen
# MYSQL_DUMP_BINARY_PATH=/usr/bin
```

Lalu jalankan migrasi, symlink storage, cache produksi, dan set permission:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize

sudo chown -R www-data:www-data /var/www/satkercms/storage /var/www/satkercms/bootstrap/cache
sudo chmod -R 775 /var/www/satkercms/storage /var/www/satkercms/bootstrap/cache
```

### Langkah 5: Konfigurasi PHP-FPM (`upload_max_filesize` & OPcache)

Edit `/etc/php/8.5/fpm/php.ini`:

```ini
expose_php = Off
upload_max_filesize = 16M
post_max_size = 20M
memory_limit = 256M

opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.5-fpm
```

### Langkah 6: Konfigurasi Virtual Host Nginx

Buat file `/etc/nginx/sites-available/satkercms`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name pa-contoh.go.id www.pa-contoh.go.id;
    root /var/www/satkercms/public;

    index index.php;
    charset utf-8;
    server_tokens off;
    client_max_body_size 16M;

    # Kompresi Gzip untuk HTML, CSS, JS, JSON, XML, SVG
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;

    # Cache jangka panjang untuk aset build Vite & media WebP
    location ^~ /build/assets/ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    location ^~ /storage/ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
        access_log off;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Blokir file sensitif (.env, .git, .htaccess)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi & pasang SSL HTTPS gratis (Let's Encrypt):

```bash
sudo ln -s /etc/nginx/sites-available/satkercms /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Pasang SSL HTTPS otomatis
sudo certbot --nginx -d pa-contoh.go.id -d www.pa-contoh.go.id
```

### Langkah 7: Pasang Cron Job Laravel Scheduler

```bash
sudo crontab -u www-data -e
```

Tambahkan baris berikut:

```cron
* * * * * cd /var/www/satkercms && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Langkah 8 (Opsional Direkomendasikan): Tata Letak `releases/current` untuk Zero-Downtime 0ms
Agar pergantian versi melalui `php artisan cms:update` berjalan secara atomik tanpa jeda seperseribu detik pun (serta bisa *rollback* instan):
```text
/var/www/satkercms/
├── current -> releases/v1.0.0   (arahkan root Nginx ke /var/www/satkercms/current/public)
├── releases/
│   └── v1.0.0/
└── shared/
    ├── .env
    └── storage/
```
Saat berada di struktur ini, jalankan pembaruan sebagai user web server:
```bash
sudo -u www-data php /var/www/satkercms/current/artisan cms:update --yes
```
*(Catatan: Instalasi satu folder biasa maupun Shared Hosting `public_html` tetap didukung otomatis melalui mode `staged_inplace` tanpa wajib mengubah struktur folder.)*

---

## 7. Setup Awal Aplikasi & Login Admin

Setelah instalasi di Shared Hosting atau VPS berhasil, buka domain Anda (`https://pa-contoh.go.id`).

### A. Menjalankan Setup Wizard (`/setup`)
Saat pertama kali dibuka, aplikasi otomatis mengarahkan ke **`/setup`** yang terdiri dari **4 langkah**:
1. **Langkah 1 (Identitas & Tingkat Peradilan):** Isi Nama Satker, Jenis Badan (`PA`, `PTA`, `MS`, `MS_ACEH`), Tingkat (`Tingkat Pertama` / `Tingkat Banding`), Kode Satker, dan Tahun Berdiri.
2. **Langkah 2 (Alamat & Kontak Resmi):** Isi alamat lengkap, desa/kelurahan, kecamatan, kabupaten/kota, provinsi, kode pos, telepon, email, dan URL website resmi.
3. **Langkah 3 (Tautan Integrasi MARI):** Masukkan URL **SIPP** (`https://sipp.pa-satker.go.id/`), e-Court, Direktori Putusan, SIWAS, **URL atau Token 32 karakter SIMTEPA** (otomatis membentuk 7 tab profil Ketua s.d. Pelaksana), serta tautan Survei Pelayanan (SKM/SPAK/SPKP).
4. **Langkah 4 (Akun Super Admin):** Buat akun `super_admin` pertama.

Ketika tombol **Selesai** ditekan, sistem otomatis:
- Mengunci tingkat peradilan satker dan menandai setup selesai.
- Mengisi 6 peran RBAC + hak akses standar.
- Mengisi daftar kriteria kepatuhan SINOVA.
- Membuat 36 halaman standar, 3 slide beranda, dan struktur 6 menu navigasi utama sesuai tingkat peradilan.

### B. Login & Aktivasi Wajib 2FA TOTP
1. Buka `https://pa-contoh.go.id/login`, masukkan email, kata sandi, dan kode **CAPTCHA lokal**.
2. Pada login pertama ke `/panel-admin`, sistem otomatis mengarahkan ke **`/panel-admin/keamanan/2fa`**.
3. Pindai kode QR menggunakan aplikasi autentikator (**Google Authenticator**, **Microsoft Authenticator**, **Authy**, atau **FreeOTP**), masukkan 6 digit kode OTP, lalu simpan **Recovery Codes** di tempat aman.
4. Setelah 2FA aktif, seluruh menu Dashboard Admin terbuka penuh.

### C. Pengisian Konten Awal yang Disarankan
1. **Pengaturan Satker (`/panel-admin/pengaturan`):** Unggah Logo Resmi Satker dan Foto Gedung (otomatis dikonversi ke WebP ringan), lengkapi URL SIPP & SIMTEPA (termasuk URL Bagan Struktur Organisasi), isi data Pengawasan (PTA, Nilai SKM/SPAK/SPKP, Video Profil/PTSP/Kebijakan MA), serta atur Tautan Instansi Terkait, Sosial Media Resmi, dan Access CCTV Online (ACO).
2. **Slider Beranda (`/panel-admin/slider`):** Sesuaikan 3 sampai 5 slide utama rasio 16:9 (`1280x720`, dilarang memakai video/streaming).
3. **Halaman Website (`/panel-admin/halaman`):** Lengkapi konten teks menggunakan 3-Tab Studio TinyMCE (`Editor` | `Code` | `Preview`) dan Deskripsi SEO Google.
4. **Arsip Dokumen PDF (`/panel-admin/dokumen`):** Unggah dokumen SAKIP (LKjIP, Renstra, RKT, Renaksi, PK, IKU, DIPA, LRA, CALK, LHKPN, PBJ), 7 SOP PPID, dan laporan kepaniteraan.
5. **Verifikasi Kepatuhan:** Periksa **`/panel-admin/link-sinova`** (40 kriteria resmi SINOVA 2026: 35 URL + 5 non-URL) dan halaman publik **`/status-sistem`**.

### D. Daftar Rute & Peran RBAC Standar

| Rute | Keterangan |
| :--- | :--- |
| `/` | Beranda portal publik (Slider, Layanan, Survei Pengawasan, Berita, Tautan Instansi, Medsos, ACO CCTV) |
| `/halaman/{slug}` | Halaman profil, layanan hukum, transparansi SAKIP, dan PPID |
| `/publikasi` | Direktori berita, pengumuman, inovasi, dan artikel bantuan hukum |
| `/cari` | Pencarian internal lintas halaman, publikasi, dan dokumen PDF (`Ctrl+K`) |
| `/panel-admin` | Panel Admin CMS (dapat diubah melalui `ADMIN_PATH` di `.env`) |
| `/status-sistem` | Status versi PHP, Laravel, CMS, dan kepatuhan keamanan publik |
| `/statistik-pengunjung` | Statistik kunjungan website publik dengan rentang waktu |
| `/aksesibilitas` | Pernyataan dan panduan fitur aksesibilitas WCAG 2.2 AA |
| `/peta-situs` & `/sitemap.xml` | Peta situs HTML untuk pengunjung & XML untuk mesin pencari |
| `/robots.txt` | Aturan perayapan bot mesin pencari dinamis |

**6 Peran Standar (`spatie/laravel-permission`):**
- `super_admin` (akses penuh + perubahan tingkat peradilan + manajemen pengguna + impor kriteria)
- `admin_konten` (kelola halaman, menu, slider, publikasi, komentar, pengaturan satker, audit)
- `operator_kepaniteraan` (kelola jadwal sidang cadangan, statistik perkara, panggilan ghaib, dokumen kepaniteraan)
- `operator_kesekretariatan` (kelola data pegawai cadangan, dokumen SAKIP, DIPA, LRA, CALK, LHKPN, PBJ)
- `operator_ppid_humas` (kelola berita, pengumuman, informasi serta merta, moderasi komentar, dokumen PPID)
- `penilai_internal` (akses baca dashboard kepatuhan, Link SINOVA, dan audit tautan)

---

## 8. Perintah Pemeliharaan & Pembaruan

```bash
# Periksa ketersediaan rilis baru SatkerCMS dari GitHub sekarang
php artisan cms:check-update --force

# Cek status versi, mode peladen, status rilis GitHub, & kesiapan SINOVA tanpa mengubah database
php artisan cms:update --dry-run

# Unduh rilis terbaru dari GitHub Releases (terkompilasi penuh + verifikasi SHA-256),
# jalankan backup DB otomatis, terapkan secara zero-downtime, dan reset OPcache web
php artisan cms:update

# Khusus VPS Ubuntu: jalankan sebagai user www-data agar izin cache berkas tetap selaras
sudo -u www-data php artisan cms:update --yes

# Jalankan pembaruan pasca-tarik lokal saja (misalnya setelah git pull pada kanal dev)
php artisan cms:update --local-only

# Kembalikan aplikasi ke versi sebelumnya secara cepat bila terjadi kendala pasca-update
php artisan cms:rollback

# Konversi & perkecil seluruh gambar eksisting (slider, logo, gedung, berita) ke WebP
php artisan satker:optimize-media

# Jalankan backup database & berkas terenkripsi (spatie/laravel-backup)
php artisan backup:run

# Tampilkan jadwal pemeliharaan otomatis (backup harian 02:00, auto-update opsional 02:30,
# pembersihan arsip 03:00, pemangkasan log aktivitas mingguan, cek update GitHub 2x sehari,
# & pemroses antrean 1-klik tiap menit) yang dijalankan cron `schedule:run`
php artisan schedule:list

# Memproses antrean pembaruan 1-klik dari Dashboard Admin (dijalankan otomatis oleh cron)
php artisan cms:process-update-queue

# Bangun ulang cache produksi setelah mengubah .env atau update kode
php artisan optimize

# Bersihkan seluruh cache jika terjadi kendala tampilan/konfigurasi
php artisan optimize:clear

# Jalankan seluruh pengujian otomatis Pest
php artisan test
```

> **Cara Menerbitkan Rilis Baru di GitHub (Untuk Pengembang / Maintainer):**
> Dorong tag SemVer baru (contoh: `git tag v1.0.1 && git push origin v1.0.1`). Workflow `.github/workflows/release.yml` otomatis menjalankan pengujian Pest, mengompilasi `vendor/` produksi dan aset Vite `public/build/`, membuat `satkercms-v1.0.1.zip` beserta `.sha256`, lalu menerbitkannya ke **GitHub Releases** sehingga seluruh server satker langsung mendeteksi pembaruan.

---

## 9. Troubleshooting Umum

<details>
<summary><strong>1. Error "Unable to locate file in Vite manifest"</strong></summary>

- Pastikan folder `public/build/` (berisi `manifest.json` dan folder `assets/`) sudah ikut terunggah ke server.
- Jika belum ada, jalankan `npm run build` di komputer lokal lalu unggah folder `public/build/` ke server.
- Pada **Shared Hosting Opsi B (`public_html`)**, pastikan baris `$app->usePublicPath(__DIR__);` sudah ditambahkan di `public_html/index.php`.

</details>

<details>
<summary><strong>2. Gambar Logo, Slider, atau Dokumen PDF Muncul 404</strong></summary>

- Symlink `public/storage` belum dibuat atau masih mengarah ke path komputer lokal.
- Hapus folder/symlink `public/storage` lama jika ada, lalu jalankan `php artisan storage:link` (atau buat ulang symlink lewat perintah `ln -s` di Cron Job cPanel sesuai Langkah 6 Shared Hosting).

</details>

<details>
<summary><strong>3. Error 500 atau "Permission denied" pada <code>storage/logs</code></strong></summary>

- Pastikan izin folder `storage/` dan `bootstrap/cache/` dapat ditulis oleh user web server:
  ```bash
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
  ```
- Jika masih error 500, cek rincian pesan galat di file `storage/logs/laravel.log`.

</details>

<details>
<summary><strong>4. Gagal Unggah PDF Besar (413 Request Entity Too Large)</strong></summary>

- Aplikasi mendukung unggahan PDF hingga **10 MB**.
- Di **VPS Nginx**, pastikan `client_max_body_size 16M;` di konfigurasi Nginx serta `upload_max_filesize = 16M` dan `post_max_size = 20M` di `php.ini` sudah diterapkan, lalu restart `nginx` dan `php8.5-fpm`.
- Di **Shared Hosting cPanel**, ubah `upload_max_filesize` dan `post_max_size` menjadi minimal `16M` - `20M` pada menu **Select PHP Version -> Options** atau **MultiPHP INI Editor**.

</details>

<details>
<summary><strong>5. Admin Kehilangan Perangkat Authenticator (Reset 2FA)</strong></summary>

- Gunakan salah satu **Recovery Code** saat login 2FA, atau minta **Super Admin** mereset 2FA melalui menu **Pengguna & Peran**.
- Jika akun `super_admin` satu-satunya kehilangan akses 2FA, reset melalui SSH/Terminal server:
  ```bash
  php artisan tinker --execute "App\Models\User::where('email', 'admin@pa-satker.go.id')->update(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);"
  ```

</details>

---

## Pembuat Aplikasi & Lisensi

- **Pembuat Aplikasi:** **Rahmat Triadi, S.Kom.**
- **Dokumentasi Lengkap:** [Panduan Markdown](panduan/PANDUAN-LENGKAP-SATKERCMS.md) | [Panduan PDF Siap Cetak](panduan/PANDUAN-LENGKAP-SATKERCMS.pdf)
- **Lisensi:** SatkerCMS dirilis di bawah lisensi [MIT](https://opensource.org/licenses/MIT).
