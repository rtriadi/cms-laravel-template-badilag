@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        if ($lastPage <= 7) {
            $window = range(1, $lastPage);
        } else {
            $pages = array_unique(array_filter([
                1,
                $currentPage > 2 ? $currentPage - 1 : null,
                $currentPage,
                $currentPage < $lastPage - 1 ? $currentPage + 1 : null,
                $lastPage,
            ]));
            sort($pages);

            $window = [];
            $prev = null;
            foreach ($pages as $p) {
                if ($prev !== null && $p - $prev > 1) {
                    $window[] = '...';
                }
                $window[] = $p;
                $prev = $p;
            }
        }
    @endphp

    <nav role="navigation" aria-label="Navigasi Halaman" class="flex w-full max-w-full flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-ink-muted">
            Menampilkan
            <span class="font-mono font-semibold text-ink">{{ number_format((int) $paginator->firstItem(), 0, ',', '.') }}-{{ number_format((int) $paginator->lastItem(), 0, ',', '.') }}</span>
            dari
            <span class="font-mono font-semibold text-ink">{{ number_format((int) $paginator->total(), 0, ',', '.') }}</span>
            data
        </p>

        <div class="flex max-w-full flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-hairline bg-mist px-2 text-ink-muted/50">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    aria-label="Halaman sebelumnya"
                    class="pub-btn inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-hairline bg-white px-2 text-ink hover:border-primary/50 hover:bg-primary-soft hover:text-primary"
                >
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @endif

            @foreach ($window as $item)
                @if ($item === '...')
                    <span aria-hidden="true" class="inline-flex h-10 min-w-7 items-center justify-center px-1 font-mono text-xs text-ink-muted">&hellip;</span>
                @elseif ($item === $currentPage)
                    <span aria-current="page" class="inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-primary bg-primary px-3 font-mono text-xs font-bold text-white shadow-2xs">
                        {{ $item }}
                    </span>
                @else
                    <a
                        href="{{ $paginator->url($item) }}"
                        aria-label="Ke halaman {{ $item }}"
                        class="pub-btn inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-hairline bg-white px-3 font-mono text-xs font-medium text-ink hover:border-primary/50 hover:bg-primary-soft hover:text-primary"
                    >
                        {{ $item }}
                    </a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    aria-label="Halaman berikutnya"
                    class="pub-btn inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-hairline bg-white px-2 text-ink hover:border-primary/50 hover:bg-primary-soft hover:text-primary"
                >
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-hairline bg-mist px-2 text-ink-muted/50">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif

