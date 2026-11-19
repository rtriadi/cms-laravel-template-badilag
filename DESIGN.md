# SatkerCMS Design System (`DESIGN.md`)

**Acuan Regulasi:** SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 (Evaluasi Website Satker Peradilan Agama melalui Aplikasi SINOVA).

**Design Read:**
`Reading this as: civic public-service portal and SINOVA compliance CMS for Indonesian Religious Court citizens and staff, in a dignified Indonesian civic-editorial visual language, dial ENERGY 1 / RHYTHM 2 / MOTION 1.`

---

## 1. Dials & Visual Direction

| Dial | Level | Implementasi Nyata |
|---|---|---|
| **ENERGY** | `1` (Calm & Authoritative) | Tampilan bersih, berwibawa, mudah dipindai pencari keadilan di berbagai tingkat literasi digital |
| **RHYTHM** | `2` (Consistent with deliberate breaks) | Variasi komposisi antar bagian: split asimetris hero 8/4, berita 7/5, direktori transparansi terstruktur, tabel akuntabilitas, serta footer 3 kolom |
| **MOTION** | `1` (Hover & subtle focus states only) | Transisi hover 150ms, indikator fokus jelas, tanpa loop animasi, tanpa parallax, tunduk penuh pada `prefers-reduced-motion: reduce` |

**Identity Motif:** Pita garis emas kelembagaan 3px (`#FF9900`) yang menempel tepat di bawah bilah utilitas hijau zamrud gelap (`#033D2C`), dipadukan dengan direktori layanan publik bergaya tabel/indeks yang rapi.

---

## 2. Color Palette & Verified WCAG AA Contrast

Sesuai SK Dirjen Badilag Bab III.B.1, warna utama (`#056E4F`) dan sekunder (`#FF9900`) dikunci. Setiap pasangan teks telah diverifikasi menggunakan `contrast-check.py`:

| Token | Nilai Light | Nilai High Contrast | Peran | Rasio Terukur |
|---|---|---|---|---|
| `brand/primary` | `#056E4F` | `#000000` | Warna utama merek, CTA utama, aksen fokus aktif | `#FFFFFF` di `#056E4F`: **6.27:1** (PASS AA) |
| `brand/primary-deep` | `#033D2C` | `#000000` | Bilah utilitas atas, footer kelembagaan, scrim hero | `#F9FAFB` di `#033D2C`: **11.77:1** (PASS AAA) |
| `brand/primary-hover` | `#04543C` | `#1F2937` | Status hover tombol utama | `#FFFFFF` di `#04543C`: **8.98:1** (PASS AAA) |
| `brand/primary-soft` | `#F0F7F4` | `#111827` | Latar item aktif sidebar, sorotan halus | `#056E4F` di `#F0F7F4`: **5.77:1** (PASS AA) |
| `brand/accent` | `#FF9900` | `#FFFF00` | Aksen emas terukur: garis identitas 3px, badge penting, tombol aksen gelap | `#111827` di `#FF9900`: **8.29:1** (PASS AAA). **LARANGAN:** Teks putih di `#FF9900` hanya **2.14:1** (FAIL) |
| `surface/canvas` | `#FFFFFF` | `#000000` | Latar utama halaman dan kartu | `#111827` di `#FFFFFF`: **17.74:1** (PASS AAA) |
| `surface/mist` | `#F8FAF9` | `#000000` | Pemisah band konten beranda dan latar workspace admin | `#111827` di `#F8FAF9`: **16.92:1** (PASS AAA) |
| `surface/border` | `#D8E2DD` | `#FFFF00` | Garis tepi 1px pada kartu, tabel, input, dan sidebar | Memenuhi pemisahan visual jelas |
| `ink/primary` | `#111827` | `#FFFFFF` | Judul utama, label penting, teks di atas aksen `#FF9900` | 17.74:1 (Light) / 21.00:1 (HC) |
| `ink/body` | `#374151` | `#FFFFFF` | Paragraf isi, deskripsi layanan, sel tabel | `#374151` di `#FFFFFF`: **10.31:1** (PASS AAA) |
| `ink/muted` | `#52525B` | `#FFFF00` | Metadata tanggal, keterangan breadcrumb, sublabel | `#52525B` di `#FFFFFF`: **7.73:1** (PASS AAA) |
| `status/complete` | `#047857` | `#00FF00` | Status "Lengkap / Siap" (selalu disertai ikon + label teks) | Latar soft `#ECFDF5` dengan teks `#047857` |
| `status/warning` | `#B45309` | `#FFFF00` | Status "Perlu Perbaikan" & banner Informasi Serta Merta | Latar `#FFFBEB` dengan teks `#92400E` |
| `status/danger` | `#B91C1C` | `#FF6B6B` | Status "Kosong / Tautan Rusak" & error validasi | Latar `#FEF2F2` dengan teks `#B91C1C` |

---

## 3. Typography & Alasan Pemilihan

- **Display & Headings (`Source Serif 4`):** Memberi wibawa institusi peradilan dan keterbacaan editorial yang resmi pada judul halaman, H1, dan H2 publik.
- **Body, UI, Form & Tabel (`Plus Jakarta Sans`):** Dirancang dengan metrik huruf yang jelas untuk teks Bahasa Indonesia panjang, kontrol formulir, navigasi, dan angka tabular pada statistik perkara maupun anggaran.
- **Technical Identifiers (`IBM Plex Mono`):** Khusus untuk kode kriteria SINOVA (`1.a.2`), URL target, serta informasi versi sistem (`PHP 8.5`, `Laravel 13.x`) agar mudah dipindai dan disalin tanpa salah baca karakter.

| Token Skala | Font Family | Ukuran (Desktop / Mobile) | Weight | Line Height |
|---|---|---|---|---|
| `display-h1` | Source Serif 4 | `36px` / `26px` | 700 | 1.2 |
| `heading-h2` | Source Serif 4 | `26px` / `22px` | 600 | 1.25 |
| `heading-h3` | Plus Jakarta Sans | `18px` / `17px` | 600 | 1.35 |
| `body-base` | Plus Jakarta Sans | `16px` / `16px` | 400 | 1.6 |
| `ui-label` | Plus Jakarta Sans | `14px` / `14px` | 500 | 1.4 |
| `caption-meta` | Plus Jakarta Sans | `13px` / `13px` | 400 | 1.4 |
| `mono-code` | IBM Plex Mono | `13px` / `13px` | 500 | 1.45 |

---

## 4. Spacing, Radius & Elevation Hierarchy

- **Spacing Scale:** `4px`, `8px` (`xs`), `12px` (`sm`), `16px` (`md`), `24px` (`lg`), `32px`, `40px` (`xl`), `64px` (antar bagian beranda desktop, turun ke `32px` di mobile).
- **Border Radius Hierarchy (dilarang membuat semua elemen berbentuk kapsul):**
  - Status Badges: `4px`
  - Buttons, Inputs & Select: `6px`
  - Content Cards, Table Containers & Panels: `8px`
  - Modals, Drawers & Floating Accessibility Widget: `12px`
- **Elevation Rules:**
  - Permukaan standar menggunakan latar solid (`#FFFFFF` atau `#F8FAF9`) dengan border `1px solid #D8E2DD` (flat, tanpa bayangan mengambang massal).
  - Shadow terukur (`0 4px 16px rgba(3, 61, 44, 0.08)`) hanya dipakai pada: Floating Accessibility Widget, dropdown menu aktif, dan modal konfirmasi.

---

## 5. Written Design Decisions (R-31)

1. **Warna:** Hijau `#056E4F` dan emas `#FF9900` mengikuti standar wajib SK Badilag, sementara hijau gelap `#033D2C` dipakai pada header/footer agar teks terang mendapat kontras > 11:1 dan emas dikunci berpasangan dengan teks gelap `#111827`.
2. **Tipografi:** Kombinasi `Source Serif 4` (judul resmi) dan `Plus Jakarta Sans` (antarmuka publik dan admin) menyeimbangkan wibawa lembaga hukum dengan keterbacaan layar modern.
3. **Layout Rhythm:** Halaman beranda membagi fokus melalui grid asimetris 8/4 di hero (slider + direktori 2-klik) dan 7/5 di berita/pengumuman agar tidak terjebak deretan kartu seragam.
4. **Spacing:** Kontainer publik maksimal `1280px` dengan pemisah band `#FFFFFF` dan `#F8FAF9` serta jarak vertikal `40px-64px` di desktop dan `24px-32px` di mobile.
5. **Kartu & Border:** Komponen duduk di atas garis tepi 1px `#D8E2DD` yang tenang sehingga perhatian pengguna langsung mengarah ke tombol aksi utama dan tautan perkara.
6. **Ikonografi:** Hanya memakai ikon fungsional yang relevan dengan tindakan pengguna (pencarian, tautan eksternal, unduh dokumen, aksesibilitas, status kesehatan link) tanpa ikon dekoratif AI dan tanpa emoji pada judul atau tombol.
7. **Motion:** Hanya transisi hover/focus 150ms dan perpindahan slide dengan tombol jeda eksplisit agar tetap ramah perangkat lambat dan mematuhi `prefers-reduced-motion`.

---

## 6. Responsive Breakpoints & Touch Standards

- **Mobile (`< 640px`, referensi 390px):** Reflow 1 kolom penuh; header ringkas 56px dengan tombol berlabel `Menu` (>= 44x44px) dan tombol `Cari` (>= 44x44px); grid pintasan layanan 2 kolom dengan sel minimal tinggi 48px dan jarak antar target >= 12px; nol horizontal overflow.
- **Tablet / Small Laptop (`640px - 1024px`):** Grid transisi 2 kolom untuk berita dan kartu direktori transparansi; sidebar admin menutup otomatis menjadi drawer dengan tombol pemicu dan dukungan tombol `Escape`.
- **Desktop (`> 1024px`, referensi 1440px):** Kontainer publik `1280px`; sidebar admin tetap `260px` dengan opsi collapse.

---

## 7. Arsitektur Layar (Figma & Implementasi)

1. **Halaman `01 · Design System & Tokens`:** Koleksi token Light & High Contrast, spesimen warna, skala tipografi, tombol, badge status berbasis teks, breadcrumb, banner Informasi Serta Merta, dan panel Widget Aksesibilitas.
2. **Halaman `02 · Website Publik (Desktop & Mobile)`:**
   - **Beranda Desktop (1440px):** Bilah utilitas atas, pita emas 3px, header 6 menu konsolidasi, banner Informasi Serta Merta, hero slider 3-5 slide + direktori layanan <= 2 klik, berita & pengumuman asimetris, indeks transparansi & layanan hukum, footer 3 kolom, serta preview Widget Aksesibilitas.
   - **Halaman Transparansi & Embed Fallback (1440px):** Breadcrumb, daftar dokumen 5 tahun per kategori, serta pola **Embed Eksternal (SIPP/SIMTEPA) + Cadangan Manual** saat sumber eksternal tidak terjangkau.
   - **Beranda Mobile (390px):** Reflow khusus mobile yang ringkas dan nyaman disentuh.
3. **Halaman `03 · Admin Panel (Flux UI)`:**
   - **Admin Shell & Link SINOVA (1440px):** Sidebar terkelompok dengan penanda aktif jelas, ringkasan kesiapan tautan nyata (bukan kartu metrik palsu), akordion seluruh kode kriteria SINOVA (`1.a.1` hingga `3.c`), dan baris tabel berisi URL auto/override, status kesehatan, tombol Salin URL, dan tombol Buka.
   - **Login Admin 2FA & Setup Wizard (1440px):** Alur masuk wajib captcha lokal + verifikasi TOTP 6 digit, serta wizard 4 langkah pengaturan awal satker termasuk penguncian Tingkat Pertama vs Tingkat Banding.
