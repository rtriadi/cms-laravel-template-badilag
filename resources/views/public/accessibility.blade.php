<x-layouts.public title="Pernyataan & Panduan Aksesibilitas">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <article class="rounded-xl border border-hairline bg-white p-6 sm:p-8">
            <span class="rounded bg-primary-soft px-2.5 py-0.5 font-mono text-xs font-semibold text-primary">
                Kriteria SINOVA 2.b | WCAG 2.2 AA
            </span>
            <h1 class="mt-3 font-serif text-2xl font-bold text-ink sm:text-3xl">
                Pernyataan &amp; Panduan Aksesibilitas Portal
            </h1>
            <p class="mt-3 text-sm leading-relaxed text-ink-body">
                {{ $satker?->nama_satker ?: 'Satuan Kerja Peradilan Agama' }} berkomitmen menyediakan layanan informasi digital yang ramah bagi penyandang disabilitas dan seluruh pencari keadilan sesuai pedoman WCAG 2.2 tingkat AA serta SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026.
            </p>

            <section class="mt-6 space-y-3 border-t border-hairline pt-6">
                <h2 class="font-serif text-xl font-bold text-primary-deep">Fitur Bantuan pada Widget Aksesibilitas</h2>
                <ul class="list-disc space-y-2 pl-5 text-sm text-ink-body">
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

