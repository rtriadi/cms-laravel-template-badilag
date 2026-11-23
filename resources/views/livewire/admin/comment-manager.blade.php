<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <!-- Status Filter Tabs & Counters -->
    <div class="rounded-xl border border-hairline bg-white p-5 shadow-2xs">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline pb-4">
            <div>
                <h2 class="font-serif text-lg font-bold text-ink">Antrean Moderasi Komentar Masyarakat</h2>
                <p class="text-xs text-ink-muted">
                    Periksa komentar warga sebelum ditayangkan, tandai spam, dan berikan Tanggapan Resmi Satuan Kerja. Alamat surel pengirim tetap privat.
                </p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach ([
                    'pending' => 'Menunggu (' . $counts['pending'] . ')',
                    'approved' => 'Disetujui (' . $counts['approved'] . ')',
                    'rejected' => 'Ditolak (' . $counts['rejected'] . ')',
                    'spam' => 'Spam (' . $counts['spam'] . ')',
                    '' => 'Semua (' . $counts['all'] . ')',
                ] as $stKey => $stLabel)
                    <button
                        type="button"
                        wire:click="$set('statusFilter', '{{ $stKey }}')"
                        @class([
                            'min-h-10 cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                            'bg-primary text-white shadow-2xs' => $statusFilter === $stKey,
                            'border border-hairline bg-mist text-ink hover:bg-primary-soft hover:text-primary' => $statusFilter !== $stKey,
                        ])
                    >
                        {{ $stLabel }}
                    </button>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="w-48">
                    <label for="comment_cat_filter" class="sr-only">Filter kategori publikasi</label>
                    <flux:select id="comment_cat_filter" wire:model.live="categoryFilter">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-56">
                    <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari komentar atau penulis" placeholder="Cari isi komentar atau nama..." />
                </div>
            </div>
        </div>

        @if (! empty($selectedIds))
            <form wire:submit="applyBulkAction" class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary/40 bg-primary-soft/70 px-4 py-2.5 text-xs">
                <span class="font-bold text-primary-deep">{{ count($selectedIds) }} komentar dipilih</span>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model="bulkAction" aria-label="Pilih aksi massal komentar" class="min-h-10 rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink">
                        <option value="">-- Pilih Aksi Massal --</option>
                        <option value="approve">Setujui &amp; Tayangkan</option>
                        <option value="reject">Tolak Komentar</option>
                        <option value="spam">Tandai sebagai Spam</option>
                        <option value="delete">Hapus Permanen</option>
                    </select>
                    <button type="submit" class="min-h-10 cursor-pointer rounded-md bg-primary px-4 font-bold text-white hover:bg-primary-hover">
                        Terapkan
                    </button>
                </div>
            </form>
        @endif

        @if ($comments->isEmpty())
            <p class="py-12 text-center text-sm text-ink-muted">Tidak ada komentar pada filter status ini.</p>
        @else
            <ul class="mt-4 divide-y divide-hairline">
                @foreach ($comments as $comment)
                    <li wire:key="cm-{{ $comment->id }}" class="py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <input
                                    type="checkbox"
                                    value="{{ $comment->id }}"
                                    wire:model.live="selectedIds"
                                    aria-label="Pilih komentar dari {{ $comment->author_name }}"
                                    class="mt-1 size-4 shrink-0 rounded border-hairline text-primary"
                                >
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-ink">{{ $comment->author_name }}</span>
                                        @if ($comment->is_official_reply)
                                            <span class="rounded bg-gold px-2 py-0.5 font-mono text-2xs font-bold text-ink uppercase">Tanggapan Resmi</span>
                                        @endif
                                        @if ($comment->author_email)
                                            <span class="rounded border border-hairline bg-mist px-2 py-0.5 font-mono text-2xs text-ink-muted">Surel terlampir (privat)</span>
                                        @endif
                                        <span @class([
                                            'rounded px-2 py-0.5 text-2xs font-semibold',
                                            'bg-amber-100 text-ink' => $comment->status === 'pending',
                                            'bg-emerald-50 text-emerald-800' => $comment->status === 'approved',
                                            'bg-slate-100 text-ink-muted' => $comment->status === 'rejected',
                                            'bg-red-50 text-red-700' => $comment->status === 'spam',
                                        ])>
                                            {{ $statusLabels[$comment->status] ?? $comment->status }}
                                        </span>
                                        <span class="font-mono text-2xs text-ink-muted">
                                            {{ \App\Support\Tanggal::format($comment->created_at, 'd M Y, H:i') }}
                                        </span>
                                    </div>

                                    @if ($comment->post)
                                        <p class="text-xs text-ink-muted">
                                            Pada publikasi:
                                            <a href="{{ route('public.posts.show', ['slug' => $comment->post->slug]) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary hover:underline">
                                                {{ $comment->post->title }}
                                            </a>
                                        </p>
                                    @endif

                                    <p class="rounded-lg border border-hairline bg-mist p-3 text-sm leading-relaxed text-ink-body">
                                        {!! nl2br(e($comment->body)) !!}
                                    </p>

                                    @if ($comment->replies->isNotEmpty())
                                        <ul class="mt-2 space-y-1.5 border-s-2 border-primary ps-3">
                                            @foreach ($comment->replies as $rep)
                                                <li class="rounded bg-primary-soft/60 px-3 py-2 text-xs text-ink-body">
                                                    <span class="font-bold text-primary-deep">{{ $rep->author_name }}:</span>
                                                    {{ $rep->body }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($replyingToId === $comment->id)
                                        <form wire:submit="saveReply" class="mt-3 space-y-2 rounded-lg border border-primary/40 bg-primary-soft/40 p-3">
                                            <flux:field>
                                                <flux:label for="reply_body_{{ $comment->id }}">Tulis Tanggapan Resmi Satuan Kerja</flux:label>
                                                <flux:textarea id="reply_body_{{ $comment->id }}" wire:model="replyBody" rows="3" required placeholder="Tanggapan resmi akan otomatis disetujui dan tampil di bawah komentar..." />
                                                <flux:error name="replyBody" />
                                            </flux:field>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="submit" class="min-h-10 cursor-pointer rounded-md bg-primary px-4 text-xs font-bold text-white hover:bg-primary-hover">
                                                    Kirim Tanggapan Resmi
                                                </button>
                                                <button type="button" wire:click="cancelReply" class="min-h-10 cursor-pointer rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist">
                                                    Batal
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 text-xs lg:shrink-0">
                                @if ($comment->status !== 'approved')
                                    <button type="button" wire:click="approve({{ $comment->id }})" class="min-h-10 cursor-pointer rounded-md bg-primary px-3 font-semibold text-white hover:bg-primary-hover">
                                        Setujui
                                    </button>
                                @endif
                                @if (! $comment->is_official_reply && $comment->parent_id === null)
                                    <button type="button" wire:click="openReply({{ $comment->id }})" class="min-h-10 cursor-pointer rounded-md border border-primary bg-white px-3 font-semibold text-primary hover:bg-primary-soft">
                                        Balas Resmi
                                    </button>
                                @endif
                                @if ($comment->status !== 'rejected')
                                    <button type="button" wire:click="reject({{ $comment->id }})" class="min-h-10 cursor-pointer rounded-md border border-hairline bg-white px-3 font-semibold text-ink hover:bg-mist">
                                        Tolak
                                    </button>
                                @endif
                                @if ($comment->status !== 'spam')
                                    <button type="button" wire:click="markSpam({{ $comment->id }})" class="min-h-10 cursor-pointer rounded-md border border-amber-300 bg-amber-50 px-3 font-semibold text-ink hover:bg-amber-100">
                                        Spam
                                    </button>
                                @endif
                                <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="Hapus komentar ini secara permanen?" class="min-h-10 cursor-pointer rounded-md border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-red-50">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4">
                {{ $comments->links() }}
            </div>
        @endif
    </div>
</div>

