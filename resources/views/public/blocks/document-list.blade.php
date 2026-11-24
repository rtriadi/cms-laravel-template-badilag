@php
    $colKey = (string) ($block['collection_type'] ?? 'lkjip');
    $colLabel = \App\Models\Document::collectionTypes()[$colKey] ?? $colKey;
    $isPaginated = $documents instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $totalDocs = $isPaginated ? $documents->total() : $documents->count();
    $activeDocSearch = (string) ($docSearch ?? '');
    $baseFilterQuery = array_filter(['cari_dok' => $activeDocSearch !== '' ? $activeDocSearch : null]);
@endphp

@auth
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-primary-soft px-4 py-2.5 text-xs">
        <span class="font-medium text-ink">
            Koleksi Dokumen: <strong>{{ $colLabel }}</strong>
        </span>
        <a
            href="{{ route('admin.documents', ['koleksi' => $colKey]) }}"
            class="inline-flex min-h-11 items-center rounded-md bg-primary px-3.5 font-semibold text-white hover:bg-primary-hover"
        >
            + Unggah Dokumen PDF ke Bagian Ini
        </a>
    </div>
@endauth

@if (! empty($years) || $totalDocs > 10 || $activeDocSearch !== '')
    <div class="space-y-3 rounded-lg border border-hairline bg-mist p-3 text-xs">
        @if (! empty($years))
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold text-ink">Filter Tahun:</span>
                <a
                    href="{{ $baseFilterQuery !== [] ? request()->url() . '?' . http_build_query($baseFilterQuery) : request()->url() }}"
                    @if ($selectedYear <= 0) aria-current="true" @endif
                    @class([
                        'inline-flex min-h-11 items-center rounded px-3 font-semibold',
                        'bg-primary text-white' => $selectedYear <= 0,
                        'border border-hairline bg-white text-ink hover:bg-mist' => $selectedYear > 0,
                    ])
                >
                    Semua Tahun
                </a>
                @foreach ($years as $yr)
                    <a
                        href="{{ request()->url() . '?' . http_build_query([...$baseFilterQuery, 'tahun' => $yr]) }}"
                        @if ($selectedYear === (int) $yr) aria-current="true" @endif
                        @class([
                            'inline-flex min-h-11 items-center rounded px-3 font-mono font-semibold',
                            'bg-primary text-white' => $selectedYear === (int) $yr,
                            'border border-hairline bg-white text-ink hover:bg-mist' => $selectedYear !== (int) $yr,
                        ])
                    >
                        {{ $yr }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($totalDocs > 10 || $activeDocSearch !== '')
            <form action="{{ request()->url() }}" method="GET" role="search" class="flex flex-wrap items-center gap-2 border-t border-hairline/70 pt-3">
                @if ($selectedYear > 0)
                    <input type="hidden" name="tahun" value="{{ $selectedYear }}">
                @endif
                <label for="cari-dok-{{ $colKey }}" class="sr-only">Cari dokumen pada {{ $colLabel }}</label>
                <input
                    id="cari-dok-{{ $colKey }}"
                    type="search"
                    name="cari_dok"
                    value="{{ $activeDocSearch }}"
                    placeholder="Cari judul dokumen pada {{ $colLabel }}..."
                    class="min-h-11 min-w-0 flex-1 rounded-md border border-hairline bg-white px-3 text-xs text-ink placeholder:text-ink-muted focus:border-primary focus:outline-none"
                />
                <button type="submit" class="pub-btn min-h-11 cursor-pointer rounded-md bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover">
                    Cari
                </button>
                @if ($activeDocSearch !== '')
                    <a
                        href="{{ $selectedYear > 0 ? request()->url() . '?tahun=' . $selectedYear : request()->url() }}"
                        class="inline-flex min-h-11 items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                    >
                        Reset
                    </a>
                @endif
            </form>
        @endif
    </div>
@endif

@if ($documents->isEmpty())
    <div class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
        {{ $activeDocSearch !== '' ? 'Tidak ditemukan dokumen yang cocok dengan kata kunci tersebut.' : 'Belum ada dokumen yang dipublikasikan pada bagian ini.' }}
    </div>
@else
    @if ($isPaginated && $documents->total() > 0)
        <p class="font-mono text-2xs text-ink-muted">
            Menampilkan {{ number_format((int) $documents->firstItem()) }}-{{ number_format((int) $documents->lastItem()) }} dari {{ number_format($documents->total()) }} dokumen
        </p>
    @endif
    <ul class="divide-y divide-hairline rounded-lg border border-hairline bg-white">
        @foreach ($documents as $doc)
            <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded bg-primary-soft px-2 py-0.5 font-mono font-semibold text-primary">
                            {{ $doc->year }}@if($doc->month)/{{ str_pad((string) $doc->month, 2, '0', STR_PAD_LEFT) }}@endif
                        </span>
                        @if ($doc->status_label)
                            <span class="rounded bg-amber-100 px-2 py-0.5 font-semibold text-ink">
                                {{ $doc->status_label }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-semibold text-ink">{{ $doc->title }}</p>
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-2xs text-ink-muted">
                        <span>Diunggah: {{ \App\Support\Tanggal::format($doc->created_at, 'd M Y, H:i') }}</span>
                        @if ($doc->updated_at && $doc->created_at && $doc->updated_at->gt($doc->created_at))
                            <span>Diperbarui: {{ \App\Support\Tanggal::format($doc->updated_at, 'd M Y, H:i') }}</span>
                        @endif
                    </p>
                    @if (is_array($doc->metadata) && $doc->metadata !== [])
                        <p class="mt-0.5 text-xs text-ink-muted">
                            @foreach ($doc->metadata as $mKey => $mVal)
                                <span class="mr-3">{{ ucfirst(str_replace('_', ' ', (string) $mKey)) }}: {{ $mVal }}</span>
                            @endforeach
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if ($doc->file_path)
                        <a
                            href="{{ asset('storage/' . $doc->file_path) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 font-semibold text-white hover:bg-primary-hover"
                        >
                            Unduh PDF
                        </a>
                    @endif
                    @if ($doc->external_url && \App\Support\SafeHtml::cleanUrl($doc->external_url))
                        <a
                            href="{{ \App\Support\SafeHtml::cleanUrl($doc->external_url) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center rounded-md border border-primary bg-white px-4 font-semibold text-primary hover:bg-primary-soft"
                        >
                            Buka Tautan
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if ($isPaginated && $documents->hasPages())
        <div class="pt-3">
            {{ $documents->links() }}
        </div>
    @endif
@endif

