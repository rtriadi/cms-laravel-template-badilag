<div class="rounded-lg border border-hairline bg-mist p-5">
    @if (! empty($block['description']))
        <p class="text-sm leading-relaxed text-ink-body">{{ $block['description'] }}</p>
    @endif

    @if (! empty($block['resolved_portal_url']))
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <span class="truncate font-mono text-xs text-ink-muted">{{ $block['resolved_portal_url'] }}</span>
            <a
                href="{{ $block['resolved_portal_url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-11 items-center rounded-md bg-primary px-5 py-2.5 text-xs font-semibold text-white hover:bg-primary-hover"
            >
                Buka Portal Layanan
            </a>
        </div>
    @else
        <p class="mt-3 text-xs text-amber-800">Tautan portal eksternal belum dikonfigurasi oleh admin satuan kerja.</p>
    @endif
</div>

