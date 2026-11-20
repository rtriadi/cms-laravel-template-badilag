<div class="grid gap-6 rounded-lg border border-hairline bg-mist p-5 md:grid-cols-12">
    <div class="space-y-3 md:col-span-7">
        <h3 class="font-serif text-lg font-bold text-ink">
            {{ $satker?->nama_satker ?: 'Identitas Satuan Kerja' }}
        </h3>

        <dl class="space-y-2 text-sm text-ink-body">
            <div>
                <dt class="text-xs font-semibold text-ink-muted uppercase">Alamat Kantor</dt>
                <dd class="mt-0.5">{{ $satker?->formattedAddress() ?: 'Belum dikonfigurasi.' }}</dd>
            </div>
            @if ($satker?->telepon)
                <div>
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Telepon Resmi</dt>
                    <dd class="mt-0.5 font-mono">{{ $satker->telepon }}</dd>
                </div>
            @endif
            @if ($satker?->email)
                <div>
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Surel Resmi</dt>
                    <dd class="mt-0.5 font-mono">{{ $satker->email }}</dd>
                </div>
            @endif
            @if ($satker?->kode_satker)
                <div>
                    <dt class="text-xs font-semibold text-ink-muted uppercase">Kode Satuan Kerja</dt>
                    <dd class="mt-0.5 font-mono">{{ $satker->kode_satker }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="flex flex-col items-center justify-center rounded-lg border border-hairline bg-white p-4 md:col-span-5">
        @if ($satker?->foto_gedung_path)
            @php
                $gedungImg = \App\Services\ImageOptimizerService::responsiveAttributes($satker->foto_gedung_path, [480, 800], '(max-width: 768px) calc(100vw - 64px), 420px', null, 1280);
            @endphp
            <img
                src="{{ $gedungImg['src'] }}"
                @if ($gedungImg['srcset']) srcset="{{ $gedungImg['srcset'] }}" sizes="{{ $gedungImg['sizes'] }}" @endif
                alt="Foto Gedung Kantor {{ $satker->nama_satker }}"
                width="420"
                height="260"
                loading="lazy"
                decoding="async"
                class="h-52 w-full rounded object-cover"
            />
        @else
            <p class="py-10 text-center text-xs text-ink-muted">
                Foto gedung kantor belum diunggah melalui Pengaturan Satker.
            </p>
        @endif
    </div>
</div>

