@php
    $lat = (float) ($block['latitude'] ?? -6.9175);
    $lng = (float) ($block['longitude'] ?? 107.6191);
    $bbox = sprintf('%F,%F,%F,%F', $lng - 0.08, $lat - 0.06, $lng + 0.08, $lat + 0.06);
    $osmEmbedUrl = "https://www.openstreetmap.org/export/embed.html?bbox={$bbox}&layer=mapnik&marker={$lat},{$lng}";
    $osmLinkUrl = "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lng}#map=12/{$lat}/{$lng}";
@endphp

<div class="space-y-3 rounded-lg border border-hairline bg-mist p-4">
    <iframe
        src="{{ $osmEmbedUrl }}"
        title="{{ $block['heading'] ?? 'Peta Wilayah Yurisdiksi Satuan Kerja' }}"
        loading="lazy"
        sandbox="allow-scripts allow-same-origin allow-popups"
        referrerpolicy="no-referrer-when-downgrade"
        width="100%"
        height="380"
        class="h-96 w-full rounded border border-hairline bg-white"
    ></iframe>

    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
        <span class="font-mono text-ink-muted">Koordinat Referensi: {{ $lat }}, {{ $lng }}</span>
        <a href="{{ $osmLinkUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary underline">
            Buka Peta OpenStreetMap
        </a>
    </div>
</div>

