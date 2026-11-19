@if (! empty($years))
    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-hairline bg-mist p-3 text-xs">
        <span class="font-semibold text-ink">Filter Tahun:</span>
        <a
            href="{{ request()->url() }}"
            @class([
                'inline-flex min-h-11 items-center rounded px-3 font-semibold',
                'bg-primary text-white' => $selectedYear <= 0,
                'border border-hairline bg-white text-ink hover:bg-gray-50' => $selectedYear > 0,
            ])
        >
            Semua Tahun
        </a>
        @foreach ($years as $yr)
            <a
                href="{{ request()->fullUrlWithQuery(['tahun' => $yr]) }}"
                @class([
                    'inline-flex min-h-11 items-center rounded px-3 font-mono font-semibold',
                    'bg-primary text-white' => $selectedYear === (int) $yr,
                    'border border-hairline bg-white text-ink hover:bg-gray-50' => $selectedYear !== (int) $yr,
                ])
            >
                {{ $yr }}
            </a>
        @endforeach
    </div>
@endif

@if ($documents->isEmpty())
    <div class="rounded-lg border border-hairline bg-mist p-6 text-center text-sm text-ink-muted">
        Belum ada dokumen yang dipublikasikan pada bagian ini.
    </div>
@else
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
                    @if ($doc->external_url)
                        <a
                            href="{{ $doc->external_url }}"
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
@endif

