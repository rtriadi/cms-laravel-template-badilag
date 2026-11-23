@php
    $a11yDesc = 'Pernyataan dan panduan aksesibilitas portal ' . ($satker?->nama_satker ?? 'Peradilan Agama') . ': pembaca suara id-ID, kontras tinggi, perbesar teks 200%, dan navigasi keyboard.';
@endphp
<x-layouts.public title="Pernyataan & Panduan Aksesibilitas" :description="$a11yDesc" :canonical="route('public.accessibility')">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Standar WCAG 2.2 AA/AAA</span>
            <h1 class="mt-2 font-serif text-2xl font-bold text-white sm:text-3xl">
                Pernyataan &amp; Panduan Aksesibilitas Portal
            </h1>
            <p class="mt-2 text-sm leading-relaxed text-emerald-100">
                {{ $satker?->nama_satker ?: 'Satuan Kerja Peradilan Agama' }} berkomitmen menyediakan layanan informasi digital yang ramah bagi penyandang disabilitas dan seluruh pencari keadilan.
            </p>
        </header>

        <article class="rounded-xl border border-hairline bg-white p-6 shadow-2xs sm:p-8">
            <section class="space-y-3">
                <h2 class="font-serif text-xl font-bold text-primary-deep">Fitur Bantuan pada Widget Aksesibilitas</h2>
                <ul class="list-disc space-y-2.5 pl-5 text-sm text-ink-body">
                    <li><strong>Pembaca Halaman (Text-to-Speech Bahasa Indonesia):</strong> Membacakan isi utama halaman atau teks yang dipilih menggunakan Web Speech API suara bahasa Indonesia (<code>id-ID</code>).</li>
                    <li><strong>Perbesar Teks hingga 200%:</strong> Memperbesar ukuran seluruh huruf secara proporsional tanpa merusak tata letak.</li>
                    <li><strong>Mode Kontras Tinggi:</strong> Mengaktifkan latar hitam pekat dengan teks putih serta sorotan tautan kuning demi kejelasan baca.</li>
                    <li><strong>Spasi Ramah Disleksia &amp; Sorot Tautan:</strong> Menambah jarak antar kata/baris dan menandai seluruh tautan yang dapat diklik.</li>
                    <li><strong>Navigasi Papan Ketik (Keyboard Friendly):</strong> Mendukung tombol <code>Tab</code>, <code>Shift+Tab</code>, dan <code>Esc</code> lengkap dengan tautan "Lewati ke konten utama" di bagian teratas halaman.</li>
                </ul>
            </section>
        </article>
    </div>
</x-layouts.public>
