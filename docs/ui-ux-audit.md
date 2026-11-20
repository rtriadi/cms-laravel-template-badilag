# Audit UI/UX SatkerCMS — Baseline Perbaikan

Tanggal: 19 November 2026
Cakupan: portal publik, panel admin SINOVA, halaman autentikasi, widget aksesibilitas, stylesheet.
Metode: audit statis kode (Blade, CSS, JS inline) dibandingkan `DESIGN.md` + WCAG 2.2 AA. **Tidak** ada pengujian runtime, Lighthouse, atau screen reader nyata.

## 1. Ringkasan

| Severity | Jumlah | Inti masalah |
|---|---|---|
| Kritis | 2 | Slider autoplay tanpa kontrol; tiga overlay tanpa manajemen fokus |
| Tinggi | 8 | Pesan validasi Inggris; panel a11y terpotong; captcha hanya gambar; command palette tanpa navigasi keyboard; state submit tanpa umpan balik; high-contrast merusak keterbacaan; tipografi di bawah spesifikasi; semantik carousel belum lengkap |
| Sedang | 12 | Pola tab tidak lengkap, tombol < 44px, label aksi ambigu, tanggal non-locale, dsb. |
| Rendah | 5 | Duplikasi CSS/komponen, token menyimpang, header mobile tidak sticky |

Yang sudah kuat dan harus dipertahankan: skip-to-content, `:focus-visible` global, `min-h-11` pada mayoritas kontrol, dukungan `prefers-reduced-motion`, `aria-pressed` pada widget a11y, status selalu teks + warna, `aria-current="page"` pada sidebar halaman, token warna & pasangan font sesuai `DESIGN.md`, kontras primary `#056e4f` di atas putih ≈ 6,3:1.

Skor kelayakan: **belum siap rilis publik** sampai K-01, K-02, dan T-01 selesai.

## 2. Legenda

- Severity: **Kritis** = memblokir pengguna tertentu atau melanggar syarat wajib; **Tinggi** = gangguan besar pada tugas utama; **Sedang** = friksi/inkonsistensi; **Rendah** = kebersihan kode/token.
- Effort: **S** ≤ 0,5 hari, **M** 0,5–1,5 hari, **L** > 1,5 hari.

---

## 3. Kritis

### K-01 — Slider hero berputar otomatis tanpa kontrol jeda
- Lokasi: `resources/views/public/home.blade.php:435-449` (interval 7000 ms), kontrol hanya `Sebelumnya`/`Berikutnya`/dot di `:104-136`.
- Dampak: melanggar WCAG 2.2.2 (Pause, Stop, Hide). Pembaca lambat, pengguna disleksia, dan pengguna keyboard kehilangan konten yang berganti saat sedang membaca; fokus tidak pernah berhenti karena tidak ada tombol jeda. `prefers-reduced-motion` hanya mematikan autoplay, tidak memberi kendali bagi yang tetap ingin membaca.
- Perbaikan: tambah tombol `Jeda`/`Lanjutkan` (`aria-pressed`) di toolbar slider, hentikan timer saat hover/focus-within dan saat tab tidak aktif (`document.hidden`), lanjutkan hanya atas aksi pengguna. Simpan preferensi di `localStorage`. Beri `aria-live="polite"` pada wrapper slide + `aria-current="true"` pada dot aktif.
- Effort: M.

### K-02 — Tiga overlay tanpa manajemen fokus
- Lokasi:
  - Drawer mobile `resources/views/components/layouts/public.blade.php:221-229` (dibuka `:429-450`).
  - Command palette `resources/views/components/layouts/admin.blade.php:498-537` (input `:514` memakai `focus:outline-none` tanpa pengganti).
  - Modal konfirmasi tingkat `resources/views/livewire/admin/satker-settings-manager.blade.php:217-224`.
- Dampak: fokus keyboard tetap berada di halaman di belakang overlay, Tab dapat keluar dari dialog, fokus tidak dipindahkan saat buka dan tidak dikembalikan saat tutup. Pengguna screen reader dapat membaca konten yang seharusnya tidak aktif. `aria-modal="true"` tanpa trap = klaim palsu.
- Perbaikan: ganti ketiganya ke `flux:modal` (tersedia di Flux Free, sudah memberi trap + Escape + restore fokus) atau tambahkan `x-trap`/util fokus minimal. Untuk palette, tambahkan `role="listbox"` + `role="option"`, `aria-activedescendant`, navigasi panah/Enter, dan fokus terlihat pada input.
- Effort: M.

---

## 4. Tinggi

### T-01 — Pesan validasi dan teks framework berbahasa Inggris
- Lokasi: `config/app.php:81` (`'locale' => 'en'`), `lang/` hanya berisi `vendor/backup/{en,id}` — tidak ada `lang/id`. Pagination vendor memakai `__('Showing')`, `__('results')` (`vendor/laravel/framework/.../pagination/tailwind.blade.php:32-42`). `.env.example:7-8` sudah `id`, tetapi tidak ada berkas terjemahan sehingga fallback ke kunci Inggris.
- Dampak: melanggar aturan proyek "UI dan pesan validasi Bahasa Indonesia". Pengguna melihat `The email field is required.`, `Showing 1 to 10 of 42 results`, dan pesan auth Fortify berbahasa Inggris.
- Perbaikan: set `APP_LOCALE=id` + `config/app.php` `'id'`, tambah `lang/id/{validation,auth,passwords,pagination}.php` (bisa dari `laravel-lang/lang`, tetapi salin berkasnya saja agar tanpa dependensi baru). Tambah feature test yang memastikan pesan validasi berbahasa Indonesia.
- Effort: M.

### T-02 — Panel aksesibilitas dapat terpotong di layar pendek
- Lokasi: `resources/views/components/accessibility-widget.blade.php:16-22` (panel `w-80`, `fixed bottom-4`, tanpa `max-height`/`overflow`).
- Dampak: pada viewport tinggi ≤ 600 px (lanskap ponsel, jendela kecil), isi panel memanjang ke atas melewati batas layar; tombol TTS/kontras di bagian atas tidak dapat dijangkau dan tidak ada scroll internal. Panel juga tidak menutup saat pengguna pindah halaman.
- Perbaikan: `max-h-[calc(100vh-2rem)] overflow-y-auto overscroll-contain`, posisi `bottom-4` + `max-h` aman terhadap safe area; pindahkan fokus ke tombol Tutup saat buka dan kembalikan ke tombol pemicu saat tutup.
- Effort: S.

### T-03 — Captcha hanya berupa gambar
- Lokasi: `resources/views/auth/login.blade.php:32-54`. `alt` hanya "Gambar kode keamanan captcha"; tidak ada alternatif audio/teks, tidak ada petunjuk format selain placeholder "Ketik 5 karakter".
- Dampak: pengguna tunanetra tidak dapat menyelesaikan login. WCAG 1.1.1 (konten non-teks) gagal. Karena login wajib bagi seluruh admin, ini memblokir peran tertentu sepenuhnya.
- Perbaikan: sediakan mode audio (`GET /captcha/audio` memakai Web Speech API atau berkas WAV) atau — lebih murah — captcha berbasis teks/logika sederhana yang terbaca screen reader, plus tombol "Ubah ke mode audio" dengan `aria-pressed`. Pastikan `aria-describedby` menghubungkan input ke petunjuk.
- Effort: M.

### T-04 — Command palette tidak dapat dioperasikan dengan keyboard
- Lokasi: `resources/views/components/layouts/admin.blade.php:498-537`.
- Dampak: setelah Ctrl+K, pengguna harus Tab melewati setiap tautan; tidak ada panah atas/bawah, tidak ada penanda item aktif, tidak ada `role="combobox"`/`listbox`/`option`, dan input menghapus outline tanpa pengganti sehingga fokus tidak terlihat. Fitur kunci produktivitas admin hanya nyaman untuk pengguna mouse.
- Perbaikan: implementasikan pola combobox APG (atau `flux:command` bila tersedia di versi terpasang) dengan `aria-activedescendant`, panah, Home/End, Enter, Escape, dan `focus:ring-2`.
- Effort: M.

### T-05 — Semantik carousel belum lengkap
- Lokasi: `resources/views/public/home.blade.php:30-45, 104-136, 424-433`.
- Dampak: dot aktif hanya dibedakan lewat kelas warna (`bg-gold`), tanpa `aria-current`/`aria-selected`; perubahan slide tidak diumumkan (`aria-live` tidak ada); tidak ada label "slide X dari N" pada tiap slide; tombol Sebelumnya/Berikutnya tidak menunjukkan status nonaktif (tidak relevan karena loop, tetapi perlu label yang jelas). Pengguna screen reader tidak tahu slide mana yang tampil.
- Perbaikan: `aria-current="true"` pada dot aktif, `aria-live="polite"` + `aria-atomic="false"` pada wrapper slide, label `role="group" aria-roledescription="slide" aria-label="Slide {{ n }} dari {{ total }}"` per slide, dan `aria-hidden` eksplisit untuk slide non-aktif (atribut `hidden` sudah membantu).
- Effort: S.

### T-06 — Mode kontras tinggi menghapus latar semua `div`
- Lokasi: `resources/css/app.css:272-306` dan duplikat `resources/css/public.css:249-283` (`html.a11y-hc div { background: #000 !important; ... }`).
- Dampak: semua `div` berlatar hitam, termasuk panel di atas gambar hero dan badge berwarna; teks putih di atas gambar terang menjadi tidak terbaca, dan pembeda visual (status, badge) hilang. Mode yang seharusnya meningkatkan keterbacaan justru menurunkannya.
- Perbaikan: batasi override ke `background-color` permukaan netral (`body`, `header`, `nav`, `main`, `footer`, kartu berkelas tertentu), jangan semua `div`. Untuk elemen di atas gambar, beri `text-shadow`/scrim hitam. Uji dengan 5 halaman: beranda, halaman statis, berita, login, dashboard.
- Effort: M.

### T-07 — Skala tipografi menyimpang dari DESIGN.md
- Lokasi (contoh): `text-[10px]` di `public/home.blade.php:161,302,347`, `public/page.blade.php:134`, `menu-manager.blade.php:172,192`, `criteria-manager.blade.php:137`; `text-[11px]` 66 kemunculan termasuk `accessibility-widget.blade.php:41`; `text-xs` 260 kemunculan sebagai teks isi.
- Dampak: teks 10 px di bawah ambang baca nyaman dan bertentangan dengan skala minimum; hirarki visual datar karena hampir semua label memakai `text-xs`; pengguna low-vision harus memperbesar halaman sejak awal.
- Perbaikan: definisikan util token (`text-2xs` 12 px khusus badge/mono, `text-sm` 14 px untuk isi), larang `< 12px` untuk teks yang harus dibaca, sisakan 10 px hanya untuk metadata dekoratif dengan kontras cukup. Ganti bertahap per komponen; mulai dari halaman publik.
- Effort: L (mekanis, banyak berkas).

### T-08 — Aksi simpan tanpa umpan balik dan tanpa perlindungan perubahan
- Lokasi: hanya 3 komponen dari 12 pemakai `wire:submit` memiliki `wire:loading` (contoh: `slider-manager.blade.php:52`). Tidak ada tombol `disabled` saat proses pada `satker-settings-manager`, `page-manager`, `post-manager`, `criteria-manager`, `menu-manager`, `user-role-manager`, `document-manager`, `kepaniteraan-manager`. Form panjang (`satker-settings-manager`, editor halaman/berita) tidak memperingatkan perubahan belum tersimpan.
- Dampak: klik ganda memicu submit ganda; pengguna tidak tahu aksi berjalan pada koneksi lambat; menutup tab/editor menghilangkan isi tanpa peringatan. Pada form setelan satker, kehilangan isi berarti mengulang puluhan field.
- Perbaikan: pola seragam `<button wire:loading.attr="disabled" wire:target="simpan">` + spinner + label tetap, `wire:dirty`/`beforeunload` untuk form panjang, dan `role="status"` untuk pesan hasil. Sediakan komponen Blade kecil `x-submit-button` agar tidak menduplikasi markup di 12 tempat.
- Effort: M.

---

## 5. Sedang

### S-01 — Pola tab tidak sesuai APG
- Lokasi: `criteria-manager.blade.php:9-24`, `audit-and-backup-manager.blade.php:8-23`, `kepaniteraan-manager.blade.php:14-25`.
- Masalah: `role="tab"` + `aria-selected` ada, tetapi tanpa `aria-controls`, panel tanpa `role="tabpanel"`/`aria-labelledby`, tanpa roving `tabindex`, tanpa navigasi panah/Home/End. `aria-selected` di dalam `tablist` tanpa panel yang terhubung tidak bermakna bagi AT.
- Perbaikan: satu partial `x-tab-list` + `x-tab-panel` (atau `flux:tabs` bila tersedia), lengkapi atribut dan handler panah. Effort: M.

### S-02 — Modal konfirmasi tingkat: detail implementasi
- Lokasi: `satker-settings-manager.blade.php:217-224`.
- Masalah tambahan: tidak ada `x-cloak` (risiko kilasan), klik backdrop tidak menutup, tidak ada tombol Batal yang terlihat (hanya Escape), tidak ada fokus awal ke tombol aman, dan tidak ada indikator proses saat `confirmChangeTingkat` berjalan.
- Perbaikan: bungkus dengan `flux:modal`; tambahkan tombol Batal + state loading. Effort: S (setelah K-02).

### S-03 — Tombol Edit/Hapus butir kriteria di bawah target sentuh
- Lokasi: `criteria-manager.blade.php:140-141` (`px-2 py-1`, tanpa `min-h-11`) → tinggi ± 26 px.
- Dampak: sulit ditekan di perangkat sentuh; inkonsisten dengan tombol lain di berkas yang sama (`:209-210` sudah `min-h-11`).
- Perbaikan: tambahkan `min-h-11 px-3`. Effort: S.

### S-04 — Menu manager: status, label aksi, dan filter
- Lokasi: `menu-manager.blade.php`.
- Masalah: `statusNotice` tanpa `role="status"` (`:2-5`) sehingga tidak diumumkan; tombol berulang "Naik/Turun/Sembunyi/Edit/Hapus" tanpa `aria-label` berisi nama menu (`:152-153, 176-177, 196-197`) — screen reader hanya mendengar "Naik, tombol" lima kali; filter lokasi tanpa `aria-pressed` (`:121-133`); `bg-gray-200` di luar token (`:148,172,192`). Tree 3 level juga tidak memakai `role="tree"`/`aria-level`, tetapi itu opsional.
- Perbaikan: `role="status"`, `aria-label="{{ $menu->title }}"` pada tiap aksi, `aria-pressed` pada filter, ganti `bg-gray-200` → `bg-mist`/`bg-hairline/40`. Effort: S.

### S-05 — Manajemen pengguna: kata sandi dan kondisi kosong
- Lokasi: `user-role-manager.blade.php:51` (input password tanpa toggle tampil), `:65+` (tabel tanpa empty state).
- Dampak: salah ketik kata sandi baru tidak terdeteksi; tabel kosong tampil sebagai header tanpa penjelasan.
- Perbaikan: tombol tampil/sembunyikan dengan `aria-pressed`, dan baris empty state "Belum ada pengguna lain…". Effort: S.

### S-06 — Tanggal tidak locale-aware dan memakai singkatan Inggris
- Lokasi: `public/page.blade.php:47,49` (`format('d M Y, H:i')`), `public/home.blade.php:347` (`format('M Y')`), daftar berita/dokumen lain.
- Dampak: "19 Nov 2026" konsisten dengan bahasa Inggris, bukan "19 November 2026"; tidak mengikuti locale pengguna. Melanggar aturan locale-aware.
- Perbaikan: helper tunggal `App\Support\Tanggal::format()` memakai `translatedFormat('d F Y, H:i')` setelah locale `id` aktif (lihat T-01), atau `IntlDateFormatter`. Effort: S.

### S-07 — Section publik tanpa accessible name
- Lokasi: `public/page.blade.php:61` — `<section>` dirender walau blok tidak punya judul, tanpa `aria-label`/heading.
- Dampak: landmark section tanpa nama membingungkan navigasi screen reader.
- Perbaikan: render `<section>` hanya bila ada judul; jika tidak, gunakan `<div>`; atau `aria-label` dari tipe blok. Effort: S.

### S-08 — Progress bar tanpa peran dan nilai
- Lokasi: `dashboard.blade.php:47-49`.
- Dampak: pembaca layar tidak tahu ada progres maupun nilainya; hanya teks "Skor: x%" di dekatnya.
- Perbaikan: `role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $aspect['score_pct'] }}" aria-label="{{ $aspect['label'] }}"`. Effort: S.

### S-09 — Panel editor berkedip saat halaman dimuat
- Lokasi: `page-manager.blade.php:63`, `post-manager.blade.php:63` (`x-show="$wire.editorOpen"` tanpa `x-cloak`).
- Dampak: panel editor sempat tampil lalu hilang → layout shift dan kesan rusak pada koneksi lambat.
- Perbaikan: tambahkan `x-cloak` (sudah ada style global). Effort: S.

### S-10 — Umpan balik salin tautan dan akordeon kriteria
- Lokasi: `sinova-links.blade.php:57-58` (`copiedNotice` tanpa `role="status"`), `:34` (`confirm()` native, berbeda dari `wire:confirm` yang dipakai di tempat lain), `:118` (tombol akordeon tanpa `aria-expanded`/`aria-controls`).
- Dampak: konfirmasi salin tidak diumumkan; dialog native tidak bisa digayakan dan memblokir thread; status buka/tutup akordeon tidak terbaca.
- Perbaikan: `role="status"`, ganti `confirm()` dengan `wire:confirm`, lengkapi atribut akordeon. Effort: S.

### S-11 — Target sentuh kecil pada hasil pencarian dan tautan sekunder
- Lokasi: `public/search.blade.php:84` (`px-3 py-1.5`, tanpa `min-h-11`), `public/page.blade.php:132` (`min-h-9` untuk tautan sidebar), `criteria-manager.blade.php:39` (tautan "Butir Baru" `text-xs` tanpa tinggi minimum).
- Perbaikan: `min-h-11` (sidebar boleh `min-h-10` dengan padding vertikal cukup); pastikan area klik, bukan hanya teks. Effort: S.

### S-12 — Filter kategori/tahun tanpa penanda aktif programatik
- Lokasi: `public/posts/index.blade.php:18-29` (kategori aktif hanya `bg-primary`), `public/blocks/document-list.blade.php:20-43` (tahun aktif hanya warna).
- Dampak: pengguna screen reader tidak tahu filter mana yang sedang aktif.
- Perbaikan: `aria-current="true"` atau `aria-pressed` pada tautan filter aktif. Effort: S.

---

## 6. Rendah

### R-01 — Duplikasi blok CSS
- Lokasi: `app.css:257-327` vs `public.css:235-300` (motion + `a11y-hc`).
- Risiko: perbaikan dilakukan di satu berkas, yang lain tertinggal. Sudah terjadi pada T-06.
- Perbaikan: ekstrak ke `resources/css/shared.css` yang diimpor kedua entry. Effort: S.

### R-02 — Dua layout auth identik
- Lokasi: `resources/views/layouts/auth.blade.php` dan `resources/views/components/layouts/auth.blade.php` (identik).
- Perbaikan: hapus salah satu; komponen `x-layouts.auth` sudah dipakai semua view auth. Effort: S.

### R-03 — Navigasi utama hilang saat scroll di ponsel
- Lokasi: `public.blade.php:147` — bar nav `hidden lg:block`; header atas tidak sticky.
- Dampak: setelah menggulir jauh, satu-satunya jalan adalah menggulir ke atas.
- Perbaikan: buat header atas `sticky top-0` (dengan tinggi ringkas) atau tombol "kembali ke atas". Effort: S.

### R-04 — Dua tab stop untuk tujuan yang sama di slide
- Lokasi: `home.blade.php:84-96` — CTA utama dan "Jadwal Sidang Hari Ini" selalu tampil berpasangan; keduanya menuju halaman yang berdekatan.
- Perbaikan: tampilkan tautan sekunder hanya jika CTA tidak menuju halaman yang sama, atau turunkan menjadi tautan teks. Effort: S.

### R-05 — Warna di luar token desain
- Lokasi: `accessibility-widget.blade.php:60` (`hover:bg-gray-50`), `document-list.blade.php:28,39`, `search.blade.php` (`bg-gray-50`), `dashboard.blade.php:47` (`bg-gray-200`).
- Perbaikan: ganti ke `mist`, `hairline`, atau `primary-soft` sesuai makna. Effort: S.

---

## 7. Temuan positif (jangan diubah tanpa alasan)

1. `skip to content` + `main id="main-content"` berfungsi di kedua shell.
2. `:focus-visible` global punya outline tebal yang jelas (`public.css`, `app.css`).
3. `min-h-11` dipakai konsisten pada mayoritas tombol dan item nav.
4. `prefers-reduced-motion` dihormati di CSS dan di slider JS.
5. Widget aksesibilitas memakai `aria-pressed` dan menyimpan preferensi di `localStorage`.
6. Status tidak pernah hanya warna: selalu ada teks "Siap/Revisi/Rusak".
7. `aria-current="page"` pada submenu halaman statis.
8. Kontras teks utama aman; gold hanya dipakai dengan `text-ink`.
9. Font dan token warna sesuai `DESIGN.md` (`vite.config.js:12-16`, `app.css:12-29`).
10. Label formulir memakai `flux:label`/`flux:error` sehingga terhubung ke input.
11. Tabel admin memakai `<table>` semantik, bukan grid div.
12. `loading="lazy"`, `width`, `height`, dan `fetchpriority` sudah dipakai pada gambar utama.

## 8. Area yang belum diverifikasi

- `kepaniteraan-manager`, `page-manager`, `slider-manager`: hanya bagian luar/permukaan; alur edit mendalam belum ditelusuri penuh.
- Blok publik `map-yurisdiksi` (iframe OSM: perlu uji fokus & alt), `satker-contact`, `external-portal`, `article-list` baru diperiksa markup dasar.
- `setup-wizard` dan `two-factor-setup`: alur multi-langkah belum diuji.
- Perilaku nyata Livewire (loading, validasi, modal) tidak dapat dijalankan: CLI PHP aktif 8.3.6 sedangkan proyek menuntut ≥ 8.4.1. Semua temuan bersifat statis.
- Kontras aktual mode kontras tinggi dan hasil TTS belum diuji di browser.

## 9. Rencana perbaikan

### Fase 0 — Prasyarat (0,5 hari)
- Siapkan PHP 8.4/8.5 agar `php artisan serve`, `npm run build`, dan Pest bisa dijalankan.
- Ambil baseline: tangkapan layar beranda, halaman statis, berita, dashboard, 3 modal, login (desktop + 390 px).
- Tambahkan uji regresi ringan: locale id pada pesan validasi, `aria-current` dot slider.

### Fase 1 — Quick wins, risiko rendah (1–1,5 hari)
K-01 (jeda slider), T-05 (aria carousel), S-03, S-04, S-05, S-06, S-07, S-08, S-09, S-10, S-12, R-01…R-05, plus T-02.
Kriteria selesai: tidak ada tombol aksi di bawah 44 px; tidak ada panel yang berkedip; semua status diumumkan; tanggal berbahasa Indonesia.

### Fase 2 — Interaksi & kepatuhan (2–3 hari)
K-02 (migrasi tiga overlay ke `flux:modal` + fokus), T-04 (combobox palette), S-01 (tab APG), S-02, T-03 (captcha alternatif), T-08 (pola submit + peringatan perubahan).
Kriteria selesai: navigasi keyboard penuh pada drawer, palette, modal, dan tab; tidak ada jebakan fokus; tidak ada submit ganda.

### Fase 3 — Sistemik (3–5 hari)
T-01 (i18n `lang/id` + test), T-06 (kontras tinggi teruji 5 halaman), T-07 (skala tipografi per komponen), audit manual screen reader (NVDA + VoiceOver) dan zoom 200% / 400%.
Kriteria selesai: pesan 100% Indonesia; WCAG 2.2 AA lolos untuk 1.1.1, 2.1.1, 2.2.2, 2.4.3, 2.4.7, 4.1.2; tidak ada konten terpotong pada 320 px lebar dan 200% zoom.

## 10. Checklist verifikasi akhir

- [ ] Tab melalui setiap halaman publik & admin: fokus selalu terlihat, urutan logis, tidak ada jebakan.
- [ ] Escape menutup setiap overlay dan fokus kembali ke pemicu.
- [ ] Semua slider dapat dijeda dan tetap berhenti setelah dinavigasi manual.
- [ ] Semua pesan validasi, pagination, dan error auth berbahasa Indonesia.
- [ ] Mode kontras tinggi: teks di atas gambar tetap terbaca; badge status tetap dibedakan.
- [ ] Zoom 200% pada 1280 px dan lebar 320 px: tidak ada scroll horizontal, tidak ada konten terpotong.
- [ ] Screen reader mengumumkan: perubahan slide, hasil simpan, salin tautan, dan status filter.
- [ ] Captcha dapat diselesaikan tanpa penglihatan.
