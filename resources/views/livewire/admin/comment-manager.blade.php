<div class="space-y-5">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-[#edf7f3] px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    <div>
        <h2 class="font-serif text-2xl font-bold tracking-tight text-[#0f172a]">Moderasi Komentar Masyarakat</h2>
        <p class="mt-0.5 text-xs text-[#64748b] sm:text-[13px]">
            Periksa komentar warga sebelum ditayangkan, tandai spam, dan berikan Tanggapan Resmi Satuan Kerja. Alamat surel pengirim tetap privat.
        </p>
    </div>

    <div class="studio-card overflow-hidden">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e2e8e5] p-4 sm:px-5">
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
                            'h-8 cursor-pointer rounded-md px-3 text-xs font-semibold transition-colors',
                            'bg-primary text-white' => $statusFilter === $stKey,
                            'border border-[#e2e8e5] bg-[#f5f7f6] text-[#334155] hover:bg-[#edf7f3] hover:text-primary' => $statusFilter !== $stKey,
                        ])
                    >
                        {{ $stLabel }}
                    </button>
                @endforeach
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <div class="w-full sm:w-44">
                    <label for="comment_cat_filter" class="sr-only">Filter kategori publikasi</label>
                    <flux:select id="comment_cat_filter" wire:model.live="categoryFilter">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full sm:w-52">
                    <flux:input wire:model.live.debounce.300ms="search" aria-label="Cari komentar atau penulis" placeholder="Cari isi komentar atau nama..." />
                </div>
            </div>
        </div>

        @if (! empty($selectedIds))
            <form wire:submit="applyBulkAction" class="flex flex-wrap items-center justify-between gap-3 border-b border-[#c7e6da] bg-[#edf7f3] px-5 py-2.5 text-xs">
                <span class="font-bold text-primary-deep">{{ count($selectedIds) }} komentar dipilih</span>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model="bulkAction" aria-label="Pilih aksi massal komentar" class="h-8 rounded-md border border-[#cbd5e1] bg-white px-2.5 text-xs font-semibold text-[#0f172a]">
                        <option value="">-- Pilih Aksi Massal --</option>
                        <option value="approve">Setujui &amp; Tayangkan</option>
                        <option value="reject">Tolak Komentar</option>
                        <option value="spam">Tandai sebagai Spam</option>
                        <option value="delete">Hapus Permanen</option>
                    </select>
                    <button type="submit" class="h-8 cursor-pointer rounded-md bg-primary px-3.5 font-semibold text-white hover:bg-primary-hover">
                        Terapkan
                    </button>
                </div>
            </form>
        @endif

        @if ($comments->isEmpty())
            <p class="py-12 text-center text-sm text-[#64748b]">Tidak ada komentar pada filter status ini.</p>
        @else
            <ul class="divide-y divide-[#eef2f0] px-5">
                @foreach ($comments as $comment)
                    <li wire:key="cm-{{ $comment->id }}" class="py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <input
                                    type="checkbox"
                                    value="{{ $comment->id }}"
                                    wire:model.live="selectedIds"
                                    aria-label="Pilih komentar dari {{ $comment->author_name }}"
                                    class="mt-1 size-4 shrink-0 rounded border-[#cbd5e1] text-primary"
                                >
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[13px] font-bold text-[#0f172a]">{{ $comment->author_name }}</span>
                                        @if ($comment->is_official_reply)
                                            <span class="rounded bg-gold px-2 py-0.5 font-mono text-[10px] font-bold text-[#0f172a] uppercase">Tanggapan Resmi</span>
                                        @endif
                                        @if ($comment->author_email)
                                            <span class="rounded border border-[#e2e8e5] bg-[#f9fbfa] px-2 py-0.5 font-mono text-[10.5px] text-[#64748b]">Surel privat</span>
                                        @endif
                                        <span @class([
                                            'rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                            'bg-[#fff7e6] text-[#92400e]' => $comment->status === 'pending',
                                            'bg-[#ecfdf5] text-[#047857]' => $comment->status === 'approved',
                                            'bg-[#f1f5f9] text-[#64748b]' => $comment->status === 'rejected',
                                            'bg-[#fef2f2] text-red-700' => $comment->status === 'spam',
                                        ])>
                                            {{ $statusLabels[$comment->status] ?? $comment->status }}
                                        </span>
                                        <span class="font-mono text-[11px] text-[#64748b]">
                                            {{ \App\Support\Tanggal::format($comment->created_at, 'd M Y, H:i') }}
                                        </span>
                                    </div>

                                    @if ($comment->post)
                                        <p class="text-xs text-[#64748b]">
                                            Pada publikasi:
                                            <a href="{{ route('public.posts.show', ['slug' => $comment->post->slug]) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary hover:underline">
                                                {{ $comment->post->title }}
                                            </a>
                                        </p>
                                    @endif

                                    <p class="rounded-lg border border-[#e2e8e5] bg-[#f9fbfa] p-3 text-[13px] leading-relaxed text-[#334155]">
                                        {!! nl2br(e($comment->body)) !!}
                                    </p>

                                    @if ($comment->replies->isNotEmpty())
                                        <ul class="mt-2 space-y-1.5 border-s-2 border-primary ps-3">
                                            @foreach ($comment->replies as $rep)
                                                <li class="rounded-md bg-[#edf7f3] px-3 py-2 text-xs text-[#334155]">
                                                    <span class="font-bold text-primary-deep">{{ $rep->author_name }}:</span>
                                                    {{ $rep->body }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($replyingToId === $comment->id)
                                        <form wire:submit="saveReply" class="mt-3 space-y-2.5 rounded-xl border border-[#c7e6da] bg-[#edf7f3]/60 p-3.5">
                                            <flux:field>
                                                <flux:label for="reply_body_{{ $comment->id }}">Tanggapan Resmi Satuan Kerja</flux:label>
                                                <flux:textarea id="reply_body_{{ $comment->id }}" wire:model="replyBody" rows="3" required placeholder="Tanggapan resmi akan otomatis disetujui dan tampil di bawah komentar..." />
                                                <flux:error name="replyBody" />
                                            </flux:field>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="submit" class="h-8 cursor-pointer rounded-md bg-primary px-3.5 text-xs font-semibold text-white hover:bg-primary-hover">
                                                    Kirim Tanggapan
                                                </button>
                                                <button type="button" wire:click="cancelReply" class="h-8 cursor-pointer rounded-md border border-[#e2e8e5] bg-white px-3 text-xs font-semibold text-[#334155] hover:bg-[#f5f7f6]">
                                                    Batal
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 text-xs lg:shrink-0">
                                @if ($comment->status !== 'approved')
                                    <button type="button" wire:click="approve({{ $comment->id }})" class="h-8 cursor-pointer rounded-md bg-primary px-3 font-semibold text-white hover:bg-primary-hover">
                                        Setujui
                                    </button>
                                @endif
                                @if (! $comment->is_official_reply && $comment->parent_id === null)
                                    <button type="button" wire:click="openReply({{ $comment->id }})" class="h-8 cursor-pointer rounded-md border border-primary bg-white px-3 font-semibold text-primary hover:bg-[#edf7f3]">
                                        Balas Resmi
                                    </button>
                                @endif
                                @if ($comment->status !== 'rejected')
                                    <button type="button" wire:click="reject({{ $comment->id }})" class="h-8 cursor-pointer rounded-md border border-[#e2e8e5] bg-white px-3 font-semibold text-[#334155] hover:bg-[#f5f7f6]">
                                        Tolak
                                    </button>
                                @endif
                                @if ($comment->status !== 'spam')
                                    <button type="button" wire:click="markSpam({{ $comment->id }})" class="h-8 cursor-pointer rounded-md border border-amber-200 bg-[#fff7e6] px-3 font-semibold text-[#92400e] hover:bg-amber-100">
                                        Spam
                                    </button>
                                @endif
                                <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="Hapus komentar ini secara permanen?" class="h-8 cursor-pointer rounded-md border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-[#fef2f2]">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-[#e2e8e5] px-5 py-3">
                {{ $comments->links() }}
            </div>
        @endif
    </div>
</div>
