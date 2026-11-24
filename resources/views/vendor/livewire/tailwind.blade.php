@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $pageName = $paginator->getPageName();

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
            <p class="text-xs text-[#64748b]">
                Menampilkan
                <span class="font-mono font-semibold text-[#0f172a]">{{ number_format((int) $paginator->firstItem(), 0, ',', '.') }}-{{ number_format((int) $paginator->lastItem(), 0, ',', '.') }}</span>
                dari
                <span class="font-mono font-semibold text-[#0f172a]">{{ number_format((int) $paginator->total(), 0, ',', '.') }}</span>
                data
            </p>

            <div class="flex max-w-full flex-wrap items-center gap-1">
                {{-- Tombol Sebelumnya --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-[#e2e8e5] bg-[#f8faf9] px-2 text-[#94a3b8]">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $pageName }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        dusk="previousPage{{ $pageName == 'page' ? '' : '.' . $pageName }}.after"
                        aria-label="Halaman sebelumnya"
                        class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-md border border-[#e2e8e5] bg-white px-2 text-[#334155] transition-colors hover:border-primary/50 hover:bg-[#edf7f3] hover:text-primary"
                    >
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

                {{-- Nomor Halaman Ringkas --}}
                @foreach ($window as $item)
                    @if ($item === '...')
                        <span aria-hidden="true" class="inline-flex h-9 min-w-7 items-center justify-center px-1 font-mono text-xs text-[#94a3b8]">&hellip;</span>
                    @elseif ($item === $currentPage)
                        <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-primary bg-primary px-2.5 font-mono text-xs font-bold text-white shadow-2xs">
                            {{ $item }}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:key="paginator-{{ $pageName }}-page{{ $item }}"
                            wire:click="gotoPage({{ $item }}, '{{ $pageName }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            aria-label="Ke halaman {{ $item }}"
                            class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-md border border-[#e2e8e5] bg-white px-2.5 font-mono text-xs font-medium text-[#334155] transition-colors hover:border-primary/50 hover:bg-[#edf7f3] hover:text-primary"
                        >
                            {{ $item }}
                        </button>
                    @endif
                @endforeach

                {{-- Tombol Berikutnya --}}
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $pageName }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        dusk="nextPage{{ $pageName == 'page' ? '' : '.' . $pageName }}.after"
                        aria-label="Halaman berikutnya"
                        class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-md border border-[#e2e8e5] bg-white px-2 text-[#334155] transition-colors hover:border-primary/50 hover:bg-[#edf7f3] hover:text-primary"
                    >
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @else
                    <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-[#e2e8e5] bg-[#f8faf9] px-2 text-[#94a3b8]">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>

