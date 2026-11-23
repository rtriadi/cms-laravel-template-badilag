# SatkerCMS Design System (`DESIGN.md`) v3

**Acuan Regulasi:** SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 (Evaluasi Website Satker Peradilan Agama melalui Aplikasi SINOVA).

**Design Read:**
`Reading this as: civic public-service portal (Public Portal v3) and WordPress/Joomla-grade editorial compliance studio (Backend CMS Studio v2) for Indonesian Religious Court citizens and staff, in a dignified Indonesian civic-editorial visual language, dial ENERGY 2 / RHYTHM 3 / MOTION 2.`

---

## 1. Dials & Visual Direction

| Dial | Level | Implementasi Nyata |
|---|---|---|
| **ENERGY** | `2` (Dignified Authority + Interactive Clarity) | Hirarki editorial berwibawa untuk publik dengan panggung hero Three.js progresif dan alur layanan cepat 2-klik, dipadukan studio admin gelap-terang yang produktif |
| **RHYTHM** | `3` (Deliberate Asymmetric Cadence) | Variasi terencana: obsidian top bar + header terpadu, hero split 7/5, 3-channel citizen strip, hub operasional asimetris 5/7, timeline majalah 7/5, showcase SAKIP gelap, dan spotlight aksesibilitas ivory |
| **MOTION** | `2` (GSAP Scroll Choreography + Three.js Progressive Hero + Motion Transitions) | Animasi terukur berbasis `--ease-judicial` (`cubic-bezier(0.22, 1, 0.36, 1)`), dinamika GSAP ScrollTrigger untuk band di bawah fold, WebGL Three.js progresif khusus desktop idle, dan kepatuhan penuh pada `prefers-reduced-motion` serta toggle `.a11y-no-motion` |

**Identity Motif:** Pita garis emas kelembagaan 3px (`#FF9900`) yang menempel tepat di bawah bilah utilitas Obsidian Emerald (`#022C20`), membingkai emblem Timbangan Keadilan 3D/SVG serta indeks layanan bernomor (`01-06`).

---

## 2. Color Palette & Verified WCAG AA/AAA Contrast

Sesuai SK Dirjen Badilag Bab III.B.1, warna utama (`#056E4F`) dan sekunder (`#FF9900`) dikunci. Setiap pasangan teks telah diverifikasi:

| Token | Nilai Light | Nilai High Contrast | Peran | Rasio Terukur |
|---|---|---|---|---|
| `brand/obsidian` | `#022C20` | `#000000` | Bilah utilitas publik, latar hero panggung utama, sidebar Studio Admin v2, showcase SAKIP | `#FFFFFF` di `#022C20`: **15.18:1** (PASS AAA) |
| `brand/primary` | `#056E4F` | `#000000` | Warna utama merek, CTA utama, aksen fokus aktif | `#FFFFFF` di `#056E4F`: **6.27:1** (PASS AA) |
| `brand/primary-deep` | `#033D2C` | `#000000` | Footer kelembagaan, gradasi hero, sub-panel gelap | `#FFFFFF` di `#033D2C`: **12.30:1** (PASS AAA) |
| `brand/emerald-glow` | `#0A946B` | `#00FF00` | Aksen grafis terang di atas latar obsidian atau grafik wireframe 3D | Dekoratif / batas highlight |
| `brand/primary-hover` | `#04543C` | `#1F2937` | Status hover tombol utama | `#FFFFFF` di `#04543C`: **8.98:1** (PASS AAA) |
| `brand/primary-soft` | `#F0F7F4` | `#111827` | Latar item aktif, sorotan halus, kartu PTSP | `#056E4F` di `#F0F7F4`: **5.77:1** (PASS AA) |
| `brand/accent` | `#FF9900` | `#FFFF00` | Aksen emas terukur: garis identitas 3px, badge penting, CTA hero dengan tinta gelap | `#111827` di `#FF9900`: **8.29:1** (PASS AAA) / `#022C20` di `#FF9900`: **7.09:1** (PASS AAA). **LARANGAN:** Teks putih di `#FF9900` hanya **2.14:1** (FAIL) |
| `surface/canvas` | `#FFFFFF` | `#000000` | Latar kartu konten dan panel utama | `#111827` di `#FFFFFF`: **17.74:1** (PASS AAA) |
| `surface/ivory` | `#F7F8F5` | `#000000` | Kanvas hangat portal publik v3 agar kartu putih memiliki separasi tenang | `#111827` di `#F7F8F5`: **16.64:1** (PASS AAA) |
| `surface/mist` | `#F8FAF9` | `#000000` | Latar workspace admin dan sel tabel sekunder | `#111827` di `#F8FAF9`: **16.92:1** (PASS AAA) |
| `surface/border` | `#D8E2DD` | `#FFFF00` | Garis tepi 1px pada kartu, tabel, input, dan sidebar | Memenuhi pemisahan visual jelas |
| `ink/primary` | `#111827` | `#FFFFFF` | Judul utama, label penting, teks di atas aksen `#FF9900` | 17.74:1 (Light) / 21.00:1 (HC) |
| `ink/body` | `#374151` | `#FFFFFF` | Paragraf isi, deskripsi layanan, sel tabel | `#374151` di `#FFFFFF`: **10.31:1** (PASS AAA) |
| `ink/muted` | `#475569` / `#52525B` | `#FFFF00` | Metadata tanggal, jumlah dilihat/komentar, breadcrumb | `#475569` di `#F7F8F5`: **7.11:1** (PASS AAA) |
| `status/complete` | `#047857` | `#00FF00` | Status "Siap / Disetujui" | Latar `#ECFDF5` dengan teks `#047857` |
| `status/warning` | `#B45309` | `#FFFF00` | Status "Menunggu Moderasi / Revisi" & banner Serta Merta | Latar `#FFFBEB` dengan teks `#92400E` (**6.84:1**) |
| `status/danger` | `#B91C1C` | `#FF6B6B` | Status "Kosong / Rusak / Ditolak" & error validasi | Latar `#FEF2F2` dengan teks `#B91C1C` |

---

## 3. Typography & Alasan Pemilihan

- **Display & Headings (`Source Serif 4`):** Memberi wibawa institusi peradilan dan keterbacaan editorial pada H1/H2 publik serta judul Studio CMS.
- **Body, UI, Form & Tabel (`Plus Jakarta Sans`):** Huruf bersih dengan angka tabular yang jelas untuk formulir layanan, direktori perkara, dan tabel manajemen admin.
- **Technical Identifiers (`IBM Plex Mono`):** Untuk kode kriteria SINOVA (`1.a.2`), URL target, jumlah kunjungan/komentar (`1.240 dilihat`), serta status sistem (`PHP 8.5`, `Laravel 13.x`).

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

## 4. Motion, GSAP, Three.js & Performance Budget

Untuk menjaga waktu muat beranda **jauh di bawah 5 detik** (pedoman SINOVA) dan melindungi Core Web Vitals (`LCP`, `CLS`, `INP`):

1. **Zero Render-Blocking Bundle:** `three` dan `gsap` dipecah ke Vite dynamic chunks terpisah (`three-hero` dan `motion-gsap`). Entry `public.js` tidak mengimpor Three.js maupun GSAP secara statis.
2. **Progressive Three.js Stage (Khusus Desktop Idle di `/`):**
   - Fallback **inline SVG Timbangan Keadilan** langsung tampil pada `0ms` pertama.
   - `hero-3d.js` hanya dimuat lewat `dynamic import()` setelah `requestIdleCallback` pada viewport `>= 1024px`, `prefers-reduced-motion: no-preference`, dan mode `.a11y-no-motion` tidak aktif.
   - WebGLRenderer dibatasi `DPR <= 1.5`, `powerPreference: 'low-power'`, geometri low-poly (`<= 32` segmen), `InstancedMesh` untuk partikel debu emas, pause otomatis saat tab tersembunyi atau hero keluar viewport via `IntersectionObserver`, dan `dispose()` penuh saat animasi dimatikan.
3. **GSAP + ScrollTrigger Terjaga:**
   - Unsur hero above-the-fold tidak pernah disembunyikan (`opacity: 0`) sebelum JS dimuat agar LCP tetap instan.
   - Koreografi `gsap.matchMedia()` hanya menganimasikan kemunculan halus band di bawah fold (`ScrollTrigger.batch(..., { once: true })`) dan transisi teks slide beranda; langsung melakukan `revert()` jika pengguna mengaktifkan "Hentikan Animasi".

---

## 5. Arsitektur Layar (Public Portal v3 & Backend CMS Studio v2)

### A. Public Portal v3
1. **Header Terpadu & Pencarian Cepat (`Ctrl+K`):** Bilah utilitas Obsidian (`#022C20`), pita emas 3px (`#FF9900`), identitas satker, 6 menu utama konsolidasi (maksimal 3 level), modal pencarian cepat terpusat `#public-search-modal`, serta bilah aksi cepat bawah 4 tombol di mobile (`Beranda`, `Sidang`, `SIPP`, `Akses`).
2. **Beranda Publik v3:** Pita Informasi Serta Merta -> Hero Obsidian-Emerald 7/5 (dengan slide foto `fetchpriority="high"` atau panggung 3D/SVG) -> Strip 3-Channel Citizen Pathway & Pintasan Cepat -> Hub Operasional 5/7 (Agenda Sidang & Jam PTSP | Service Ledger `01-06`) -> Berita & Pengumuman Timeline (lengkap dengan jumlah dilihat & komentar) -> Showcase SAKIP Gelap -> Spotlight Aksesibilitas -> Footer 3 Kolom -> Widget Aksesibilitas Mengambang.
3. **Halaman Publikasi, Penghitung View & Komentar Termoderasi:** Setiap berita, pengumuman, dan artikel layanan hukum mencatat jumlah dilihat (`views_count`, terdeduplikasi per pengunjung per 30 menit agar tidak menggerus cache) serta menampilkan komentar disetujui berikut tanggapan resmi satker dengan formulir kirim komentar bercaptcha lokal, honeypot anti-spam, dan antrean moderasi.

### B. Backend CMS Studio v2 (Flux UI Free + Livewire 4)
1. **Obsidian Emerald Studio Shell:** Sidebar terkelompok gelap (`#022C20`) dengan status indikator aktif emas, header 64px (`+ Buat Konten`, `Ctrl+K` Command Palette, `Lihat Website`).
2. **Dashboard Studio & Kesiapan SINOVA:** Skor kesiapan 3 aspek, antrean perbaikan prioritas, **Widget Draf Cepat Publikasi** ala WordPress, konten terbaru beserta statistik baca/komentar, antrean komentar menunggu moderasi, dan status infrastruktur non-EOL.
3. **Page Builder 3-Pane (Gutenberg Style):** Panel kiri daftar blok + outline halaman, kanvas tengah editor blok dengan urutan Naik/Turun/Duplikat/Hapus, dan panel kanan Inspector + **Pratinjau Cuplikan Google SEO** live.
4. **Manajer Publikasi & Moderasi Komentar:** Filter tab status + pencarian, seleksi massal (*Bulk Actions*: Tayangkan, Draf, Sematkan, Hapus), jumlah dilihat/komentar per artikel, serta halaman khusus **Komentar & Moderasi** (`approve`, `reject`, `spam`, `delete`, dan **Balas Resmi sebagai Satker**).
5. **Pustaka Dokumen, Menu Builder Visual, Kokpit SINOVA & RBAC 6-Peran:** Zona unggah PDF interaktif, penjaga 6 menu utama SINOVA, filter kesehatan tautan SINOVA, dan ringkasan matriks 6 peran + status 2FA TOTP.
