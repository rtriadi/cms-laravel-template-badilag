<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Moderasi Komentar</h1>
        <p class="mt-1 text-sm text-ink-body">
            Komentar masyarakat baru tidak langsung tayang sebelum disetujui admin humas. Anda dapat membalas sebagai tanggapan resmi satker. Alamat surel pengirim tetap privat.
        </p>
    </div>

    <div class="studio-card overflow-hidden">
        <!-- Underline Status Tabs -->
        <div class="studio-tabs px-4 pt-2 sm:px-6" role="tablist" aria-label="Filter status komentar">
            @foreach ([
                'pending' => ['Menunggu', $counts['pending']],
                'approved' => ['Disetujui', $counts['approved']],
                'rejected' => ['Ditolak', $counts['rejected']],
                'spam' => ['Spam', $counts['spam']],
                '' => ['Semua', $counts['all']],
            ] as $stKey => [$stName, $stCount])
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $statusFilter === $stKey ? 'true' : 'false' }}"
                    wire:click="$set('statusFilter', '{{ $stKey }}')"
                    @class([
                        'inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 border-b-2 px-3 text-sm transition-colors',
                        'border-primary font-bold text-primary' => $statusFilter === $stKey,
                        'border-transparent font-medium text-ink-body hover:text-ink' => $statusFilter !== $stKey,
                    ])
                >
                    <span>{{ $stName }}</span>
                    <span @class([
                        'rounded-md px-2 py-0.5 font-mono text-xs font-semibold',
                        'bg-primary-soft text-primary' => $statusFilter === $stKey,
                        'bg-mist text-ink-muted' => $statusFilter !== $stKey,
                    ])>{{ $stCount }}</span>
                </button>
            @endforeach
        </div>

        <!-- Search & Category Toolbar -->
        <div class="flex flex-col gap-3 border-b border-hairline/70 p-4 sm:flex-row sm:items-center sm:px-6">
            <div class="flex-1 sm:max-w-sm">
                <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari komentar atau penulis" placeholder="Cari isi komentar atau nama pengirim..." />
            </div>
            <div class="w-full sm:w-56">
                <label for="comment_cat_filter" class="sr-only">Filter kategori publikasi</label>
                <x-admin.select id="comment_cat_filter" wire:model.live="categoryFilter" search-placeholder="Cari kategori berita...">
                    <option value="">Semua kategori berita</option>
                    @foreach ($categories as $catKey => $catLabel)
                        <option value="{{ $catKey }}">{{ $catLabel }}</option>
                    @endforeach
                </x-admin.select>
            </div>
            <div wire:loading wire:target="search,categoryFilter,statusFilter" role="status" aria-live="polite" class="text-xs font-medium text-primary">
                Memuat komentar...
            </div>
        </div>

        <!-- Contextual Obsidian Bulk Action Bar -->
        @if (! empty($selectedIds))
            <form wire:submit="applyBulkAction" class="flex flex-col gap-3 bg-obsidian px-4 py-3 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <span class="text-sm font-semibold">{{ count($selectedIds) }} komentar dipilih</span>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-48">
                        <x-admin.select wire:model="bulkAction" aria-label="Pilih aksi massal komentar" :compact="true" search-placeholder="Cari aksi...">
                            <option value="">Pilih aksi massal...</option>
                            <option value="approve">Setujui &amp; Tayangkan</option>
                            <option value="reject">Tolak Komentar</option>
                            <option value="spam">Tandai sebagai Spam</option>
                            <option value="delete">Hapus Permanen</option>
                        </x-admin.select>
                    </div>
                    <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-gold px-4 text-xs font-bold text-ink hover:opacity-90 sm:min-h-9">
                        Terapkan
                    </button>
                    <button type="button" wire:click="$set('selectedIds', [])" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-[#1a4d3e] bg-[#0d4434] px-3 text-xs font-semibold text-white hover:bg-primary sm:min-h-9">
                        Batal
                    </button>
                </div>
            </form>
        @endif

        @if ($comments->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="text-base font-bold text-ink">Tidak ada komentar pada filter ini</p>
                <p class="mt-1 text-sm text-ink-muted">Komentar masyarakat baru yang masuk akan muncul di tab Menunggu.</p>
            </div>
        @else
            <ul class="divide-y divide-hairline">
                @foreach ($comments as $comment)
                    @php
                        $authorInitials = collect(preg_split('/\s+/', trim((string) $comment->author_name)) ?: [])
                            ->filter()
                            ->take(2)
                            ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('') ?: 'WG';
                    @endphp
                    <li wire:key="cm-{{ $comment->id }}" @class([
                        'p-4 sm:px-6 sm:py-5',
                        'bg-[#fafdfb]' => $replyingToId === $comment->id,
                    ])>
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <input
                                    type="checkbox"
                                    value="{{ $comment->id }}"
                                    wire:model.live="selectedIds"
                                    aria-label="Pilih komentar dari {{ $comment->author_name }}"
                                    class="mt-2 size-4.5 shrink-0 rounded border-[#768780] text-primary"
                                >
                                <span class="hidden size-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary sm:flex" aria-hidden="true">
                                    {{ $authorInitials }}
                                </span>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-ink">{{ $comment->author_name }}</span>
                                        @if ($comment->is_official_reply)
                                            <span class="rounded-md bg-gold px-2 py-0.5 text-[11px] font-bold text-ink">Resmi Satker</span>
                                        @endif
                                        @if ($comment->author_email)
                                            <span class="rounded-md border border-hairline bg-mist px-2 py-0.5 font-mono text-[11px] text-ink-muted">Surel privat</span>
                                        @endif
                                        <span @class([
                                            'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold',
                                            'bg-amber-50 text-amber-900' => $comment->status === 'pending',
                                            'bg-emerald-50 text-emerald-800' => $comment->status === 'approved',
                                            'bg-mist text-ink-body' => $comment->status === 'rejected',
                                            'bg-red-50 text-red-700' => $comment->status === 'spam',
                                        ])>
                                            {{ $statusLabels[$comment->status] ?? $comment->status }}
                                        </span>
                                        <span class="font-mono text-xs text-ink-muted">
                                            {{ \App\Support\Tanggal::format($comment->created_at, 'd M Y, H:i') }}
                                        </span>
                                    </div>

                                    @if ($comment->post)
                                        <p class="text-xs text-ink-muted">
                                            Pada:
                                            <a href="{{ route('public.posts.show', ['slug' => $comment->post->slug]) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary hover:underline">
                                                {{ $comment->post->title }}
                                            </a>
                                        </p>
                                    @endif

                                    <p class="rounded-xl border border-hairline bg-mist px-4 py-3 text-sm leading-relaxed text-ink">
                                        {!! nl2br(e($comment->body)) !!}
                                    </p>

                                    @if ($comment->replies->isNotEmpty())
                                        <ul class="space-y-2 border-s-2 border-primary ps-3.5">
                                            @foreach ($comment->replies as $rep)
                                                <li class="rounded-lg bg-primary-soft px-3.5 py-2.5 text-xs leading-relaxed text-ink-body">
                                                    <span class="font-bold text-primary-deep">{{ $rep->author_name }}:</span>
                                                    {{ $rep->body }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($replyingToId === $comment->id)
                                        <form wire:submit="saveReply" class="mt-3 space-y-3 rounded-xl border border-primary bg-white p-4">
                                            <flux:field>
                                                <flux:label for="reply_body_{{ $comment->id }}">Tulis tanggapan resmi satuan kerja</flux:label>
                                                <flux:textarea id="reply_body_{{ $comment->id }}" wire:model="replyBody" rows="3" required placeholder="Tanggapan resmi otomatis disetujui dan tampil berlabel Resmi Satker..." />
                                                <flux:error name="replyBody" />
                                            </flux:field>
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button type="button" wire:click="cancelReply" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-4 text-xs font-semibold text-ink hover:bg-mist sm:min-h-9">
                                                    Batal
                                                </button>
                                                <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover sm:min-h-9">
                                                    Setujui &amp; Kirim Tanggapan
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs lg:shrink-0">
                                @if ($comment->status !== 'approved')
                                    <button type="button" wire:click="approve({{ $comment->id }})" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-primary px-3.5 font-semibold text-white hover:bg-primary-hover sm:min-h-9">
                                        Setujui
                                    </button>
                                @endif
                                @if (! $comment->is_official_reply && $comment->parent_id === null)
                                    <button type="button" wire:click="openReply({{ $comment->id }})" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-primary bg-white px-3.5 font-semibold text-primary hover:bg-primary-soft sm:min-h-9">
                                        Balas Resmi
                                    </button>
                                @endif
                                @if ($comment->status !== 'rejected')
                                    <button type="button" wire:click="reject({{ $comment->id }})" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist sm:min-h-9">
                                        Tolak
                                    </button>
                                @endif
                                @if ($comment->status !== 'spam')
                                    <button type="button" wire:click="markSpam({{ $comment->id }})" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-amber-200 bg-amber-50 px-3 font-semibold text-amber-900 hover:bg-amber-100 sm:min-h-9">
                                        Spam
                                    </button>
                                @endif
                                <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="Hapus komentar ini secara permanen?" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700 hover:bg-red-50 sm:min-h-9">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-hairline px-4 py-3.5 sm:px-6">
                {{ $comments->links() }}
            </div>
        @endif
    </div>
</div>
