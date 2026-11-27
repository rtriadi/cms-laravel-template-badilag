<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Dokumen PDF &amp; SAKIP</h1>
        <p class="mt-1 text-sm text-ink-body">
            Unggah berkas resmi (LKjIP, DIPA, Renstra, SK, DIP PPID) satu kali di sini. Dokumen otomatis muncul di halaman Transparansi dan tautan SINOVA.
        </p>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Daftar Dokumen (7 cols, tampil pertama di desktop, bawah/utama di mobile) -->
        <section class="studio-card overflow-hidden lg:col-span-7">
            <div class="space-y-3 border-b border-hairline p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-bold text-ink">Daftar Dokumen ({{ number_format($documents->total()) }})</h2>
                    <div class="w-full sm:w-72">
                        <label for="doc_filter" class="sr-only">Pilih koleksi dokumen</label>
                        <x-admin.select id="doc_filter" wire:model.live="collectionFilter" search-placeholder="Cari koleksi SAKIP / DIPA / SK...">
                            @foreach ($collections as $colKey => $colLabel)
                                <option value="{{ $colKey }}">{{ $colLabel }}</option>
                            @endforeach
                        </x-admin.select>
                    </div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari judul dokumen" placeholder="Cari judul atau status dokumen..." />
                    </div>
                    <div class="w-full sm:w-40 shrink-0">
                        <label for="doc_year_filter" class="sr-only">Filter tahun dokumen</label>
                        <x-admin.select id="doc_year_filter" wire:model.live="yearFilter" search-placeholder="Cari tahun...">
                            <option value="">Semua Tahun</option>
                            @foreach ($availableYears as $yr)
                                <option value="{{ $yr }}">{{ $yr }}</option>
                            @endforeach
                        </x-admin.select>
                    </div>
                </div>
            </div>

            @if ($documents->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-base font-bold text-ink">Belum ada dokumen di koleksi ini</p>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ trim($search) !== '' || $yearFilter !== '' ? 'Tidak ada dokumen yang cocok dengan filter pencarian.' : 'Unggah berkas PDF pertama melalui panel di samping agar otomatis tampil di portal.' }}
                    </p>
                </div>
            @else
                <ul class="divide-y divide-hairline/70">
                    @foreach ($documents as $doc)
                        <li wire:key="doc-{{ $doc->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 font-mono text-[11px] font-bold text-red-700" aria-hidden="true">
                                    PDF
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink">{{ $doc->title }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                        <span class="rounded bg-primary-soft px-2 py-0.5 font-mono font-semibold text-primary">
                                            {{ $doc->year }}@if($doc->month)/{{ str_pad((string) $doc->month, 2, '0', STR_PAD_LEFT) }}@endif
                                        </span>
                                        @if ($doc->status_label)
                                            <span class="rounded bg-[#fff4e0] px-2 py-0.5 font-semibold text-[#8a4b00]">{{ $doc->status_label }}</span>
                                        @endif
                                        @if ($doc->file_path)
                                            <span class="rounded bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-800">PDF Valid</span>
                                        @endif
                                        <span class="font-mono text-[11px] text-ink-muted">
                                            {{ \App\Support\Tanggal::format($doc->created_at, 'd M Y') }}
                                        </span>
                                    </div>
                                    @if ($doc->external_url)
                                        <p class="mt-0.5 truncate font-mono text-xs text-ink-muted">{{ $doc->external_url }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-xs sm:shrink-0">
                                <button type="button" wire:click="edit({{ $doc->id }})" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white px-3.5 font-semibold text-ink hover:border-primary hover:bg-primary-soft hover:text-primary sm:min-h-8 sm:flex-none">
                                    Ubah
                                </button>
                                <button type="button" wire:click="delete({{ $doc->id }})" wire:confirm="Hapus dokumen ini?" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700 hover:bg-red-50 sm:min-h-8 sm:flex-none">
                                    Hapus
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-hairline px-4 py-3.5 sm:px-5">
                    {{ $documents->links() }}
                </div>
            @endif
        </section>

        <!-- Panel Unggah Cepat (5 cols) -->
        <aside class="studio-card p-5 sm:p-6 lg:sticky lg:top-20 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3.5">
                <div>
                    <h2 class="text-base font-bold text-ink">
                        {{ $editingId ? 'Ubah Dokumen' : 'Unggah dokumen baru' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-ink-muted">File PDF divalidasi otomatis (maks. 10 MB).</p>
                </div>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="min-h-10 cursor-pointer text-xs font-semibold text-primary hover:underline">
                        + Dokumen Baru
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="doc_pdf">Berkas PDF (Maks. 10 MB)</flux:label>
                    <label for="doc_pdf" class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-[#768780] bg-mist px-4 py-6 text-center transition-colors hover:border-primary hover:bg-primary-soft/50">
                        <span class="text-sm font-semibold text-ink">Seret berkas PDF ke sini atau pilih file</span>
                        <span class="mt-1 text-xs text-ink-muted">Hanya berkas .PDF resmi, maks. 10 MB.</span>
                        <input id="doc_pdf" type="file" wire:model="pdf_file" accept="application/pdf,.pdf" class="sr-only" />
                    </label>
                    <div wire:loading wire:target="pdf_file" role="status" aria-live="polite" class="mt-1.5 text-xs font-semibold text-primary">Mengunggah berkas PDF...</div>
                    @if ($pdf_file)
                        <p class="mt-1 font-mono text-xs font-semibold text-emerald-800">Berkas siap diunggah: {{ method_exists($pdf_file, 'getClientOriginalName') ? $pdf_file->getClientOriginalName() : 'PDF terpilih' }}</p>
                    @endif
                    <flux:error name="pdf_file" />
                </flux:field>

                <flux:field>
                    <flux:label for="doc_title">Judul dokumen <span class="text-red-700">*</span></flux:label>
                    <flux:input id="doc_title" wire:model="title" required placeholder="Contoh: Perjanjian Kinerja Tahun 2026" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="doc_collection">Koleksi dokumen</flux:label>
                    <x-admin.select id="doc_collection" wire:model="collection_type" search-placeholder="Cari koleksi dokumen...">
                        @foreach ($collections as $colKey => $colLabel)
                            <option value="{{ $colKey }}">{{ $colLabel }}</option>
                        @endforeach
                    </x-admin.select>
                    <flux:error name="collection_type" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="doc_year">Tahun <span class="text-red-700">*</span></flux:label>
                        <flux:input id="doc_year" type="number" wire:model="year" min="1980" max="2100" required />
                        <flux:error name="year" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="doc_month">Bulan (1-12)</flux:label>
                        <flux:input id="doc_month" type="number" wire:model="month" min="1" max="12" placeholder="Opsional" />
                        <flux:error name="month" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="doc_status">Label status</flux:label>
                        <flux:input id="doc_status" wire:model="status_label" placeholder="Final / Revisi" />
                        <flux:error name="status_label" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="doc_url">Atau tautan eksternal (opsional)</flux:label>
                    <flux:input id="doc_url" type="url" wire:model="external_url" placeholder="https://drive.google.com/..." />
                    <flux:error name="external_url" />
                </flux:field>

                <div class="grid gap-3 rounded-xl border border-hairline bg-mist p-3.5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="doc_meta_nomor">No. Dokumen</flux:label>
                        <flux:input id="doc_meta_nomor" wire:model="meta.nomor" placeholder="No. Surat / DIPA" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="doc_meta_dipa">Jenis DIPA</flux:label>
                        <flux:input id="doc_meta_dipa" wire:model="meta.jenis_dipa" placeholder="DIPA 01 / DIPA 04" />
                    </flux:field>
                </div>

                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 text-sm font-medium text-ink">
                    <input type="checkbox" wire:model="is_published" class="size-4.5 rounded border-[#768780] text-primary">
                    <span>Langsung tayangkan di portal</span>
                </label>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Dokumen' : 'Simpan & Unggah PDF' }}
                </flux:button>
            </form>
        </aside>
    </div>
</div>
