<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Form Unggah Dokumen -->
        <div class="studio-card p-5 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">
                    {{ $editingId ? 'Edit Dokumen' : 'Unggah Dokumen Baru' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="cursor-pointer text-xs font-semibold text-primary hover:underline">
                        Dokumen Baru
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="doc_collection">Koleksi Dokumen</flux:label>
                    <flux:select id="doc_collection" wire:model="collection_type">
                        @foreach ($collections as $colKey => $colLabel)
                            <option value="{{ $colKey }}">{{ $colLabel }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="collection_type" />
                </flux:field>

                <flux:field>
                    <flux:label for="doc_title">Judul Dokumen *</flux:label>
                    <flux:input id="doc_title" wire:model="title" required placeholder="Contoh: Laporan Kinerja Instansi Pemerintah Tahun 2026" />
                    <flux:error name="title" />
                </flux:field>

                <div class="grid gap-3 sm:grid-cols-3">
                    <flux:field>
                        <flux:label for="doc_year">Tahun *</flux:label>
                        <flux:input id="doc_year" type="number" wire:model="year" min="1980" max="2100" required />
                        <flux:error name="year" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="doc_month">Bulan (1-12)</flux:label>
                        <flux:input id="doc_month" type="number" wire:model="month" min="1" max="12" placeholder="Opsional" />
                        <flux:error name="month" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="doc_status">Label Status</flux:label>
                        <flux:input id="doc_status" wire:model="status_label" placeholder="Final / Revisi 1" />
                        <flux:error name="status_label" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label for="doc_pdf">Berkas PDF (Maks. 10 MB)</flux:label>
                    <label for="doc_pdf" class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-primary/35 bg-mist px-4 py-5 text-center hover:border-primary hover:bg-primary-soft/50">
                        <span class="font-mono text-2xs font-bold text-primary uppercase">Klik atau Lepas Berkas PDF di Sini</span>
                        <span class="mt-1 text-xs text-ink-muted">Format PDF standar arsip peradilan (Maks. 10 MB)</span>
                        <input id="doc_pdf" type="file" wire:model="pdf_file" accept="application/pdf,.pdf" class="sr-only" />
                    </label>
                    <div wire:loading wire:target="pdf_file" class="mt-1.5 text-xs font-semibold text-primary">Mengunggah berkas PDF...</div>
                    @if ($pdf_file)
                        <p class="mt-1 font-mono text-2xs font-semibold text-emerald-800">Berkas siap diunggah: {{ method_exists($pdf_file, 'getClientOriginalName') ? $pdf_file->getClientOriginalName() : 'PDF terpilih' }}</p>
                    @endif
                    <flux:error name="pdf_file" />
                </flux:field>

                <flux:field>
                    <flux:label for="doc_url">Tautan Eksternal (Opsional)</flux:label>
                    <flux:input id="doc_url" type="url" wire:model="external_url" placeholder="https://..." />
                    <flux:error name="external_url" />
                </flux:field>

                <div class="grid gap-3 rounded-lg border border-hairline bg-mist p-3.5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="doc_meta_nomor">No. Dokumen (Opsional)</flux:label>
                        <flux:input id="doc_meta_nomor" wire:model="meta.nomor" placeholder="No. Surat / DIPA" />
                    </flux:field>
                    <flux:field>
                        <flux:label for="doc_meta_dipa">Jenis DIPA (Opsional)</flux:label>
                        <flux:input id="doc_meta_dipa" wire:model="meta.jenis_dipa" placeholder="DIPA 01 / DIPA 04" />
                    </flux:field>
                </div>

                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-xs font-medium text-ink">
                    <input type="checkbox" wire:model="is_published" class="size-4 rounded border-hairline text-primary">
                    <span>Publikasikan dokumen ini pada portal</span>
                </label>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Dokumen' : 'Simpan & Unggah Dokumen' }}
                </flux:button>
            </form>
        </div>

        <!-- Daftar Dokumen per Koleksi -->
        <div class="studio-card p-5 lg:col-span-7">
            <div class="space-y-3 border-b border-hairline pb-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-serif text-lg font-bold text-ink">Daftar Dokumen ({{ number_format($documents->total()) }})</h2>
                    <div class="w-full sm:w-64">
                        <label for="doc_filter" class="sr-only">Pilih koleksi dokumen</label>
                        <flux:select id="doc_filter" wire:model.live="collectionFilter">
                            @foreach ($collections as $colKey => $colLabel)
                                <option value="{{ $colKey }}">{{ $colLabel }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari judul dokumen" placeholder="Cari judul atau status dokumen..." />
                    </div>
                    <div class="w-36 shrink-0">
                        <label for="doc_year_filter" class="sr-only">Filter tahun dokumen</label>
                        <flux:select id="doc_year_filter" wire:model.live="yearFilter">
                            <option value="">Semua Tahun</option>
                            @foreach ($availableYears as $yr)
                                <option value="{{ $yr }}">{{ $yr }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>

            @if ($documents->isEmpty())
                <p class="py-10 text-center text-sm text-ink-muted">
                    {{ trim($search) !== '' || $yearFilter !== '' ? 'Tidak ada dokumen yang cocok dengan filter pencarian.' : 'Belum ada dokumen tersimpan untuk koleksi ini.' }}
                </p>
            @else
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($documents as $doc)
                        <li wire:key="doc-{{ $doc->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-primary-soft px-2 py-0.5 font-mono text-xs font-semibold text-primary">
                                        {{ $doc->year }}@if($doc->month)/{{ str_pad((string) $doc->month, 2, '0', STR_PAD_LEFT) }}@endif
                                    </span>
                                    @if ($doc->status_label)
                                        <span class="rounded bg-amber-100 px-2 py-0.5 text-2xs font-semibold text-ink">{{ $doc->status_label }}</span>
                                    @endif
                                    @if ($doc->file_path)
                                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-2xs font-semibold text-emerald-800">PDF Lokal</span>
                                    @endif
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-ink">{{ $doc->title }}</p>
                                <p class="mt-0.5 font-mono text-2xs text-ink-muted">
                                    Diunggah: {{ \App\Support\Tanggal::format($doc->created_at, 'd M Y, H:i') }}
                                    @if ($doc->updated_at && $doc->created_at && $doc->updated_at->gt($doc->created_at))
                                        · Diedit: {{ \App\Support\Tanggal::format($doc->updated_at, 'd M Y, H:i') }}
                                    @endif
                                </p>
                                @if ($doc->external_url)
                                    <p class="truncate font-mono text-xs text-ink-muted">{{ $doc->external_url }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 text-xs">
                                <button type="button" wire:click="edit({{ $doc->id }})" class="min-h-11 cursor-pointer rounded border border-primary px-3 py-1 font-semibold text-primary hover:bg-primary-soft">
                                    Edit
                                </button>
                                <button type="button" wire:click="delete({{ $doc->id }})" wire:confirm="Hapus dokumen ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-3 py-1 font-semibold text-red-700 hover:bg-red-50">
                                    Hapus
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 border-t border-hairline pt-3">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
