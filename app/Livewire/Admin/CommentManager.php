<?php

namespace App\Livewire\Admin;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\SatkerSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class CommentManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';

    public string $categoryFilter = '';

    public string $search = '';

    /** @var array<int, int|string> */
    public array $selectedIds = [];

    public string $bulkAction = '';

    #[Locked]
    public ?int $replyingToId = null;

    public string $replyBody = '';

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();

        $reqStatus = (string) request()->query('status', '');
        if ($reqStatus === 'semua' || array_key_exists($reqStatus, PostComment::statusLabels())) {
            $this->statusFilter = $reqStatus === 'semua' ? '' : $reqStatus;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $this->moderateStatus($id, PostComment::STATUS_APPROVED, 'Komentar disetujui untuk tayang di portal publik.');
    }

    public function reject(int $id): void
    {
        $this->moderateStatus($id, PostComment::STATUS_REJECTED, 'Komentar ditolak dan disembunyikan dari publik.');
    }

    public function markSpam(int $id): void
    {
        $this->moderateStatus($id, PostComment::STATUS_SPAM, 'Komentar ditandai sebagai spam.');
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        $comment = PostComment::query()->findOrFail($id);
        abort_unless(auth()->user()?->can('delete', $comment), 403);
        $comment->delete();

        if ($this->replyingToId === $id) {
            $this->cancelReply();
        }

        $this->statusNotice = 'Komentar berhasil dihapus permanen.';
    }

    public function applyBulkAction(): void
    {
        $this->authorizeAccess();

        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selectedIds), static fn (int $id): bool => $id > 0)));
        if ($ids === [] || $this->bulkAction === '') {
            return;
        }

        $this->validate([
            'bulkAction' => ['required', Rule::in(['approve', 'reject', 'spam', 'delete'])],
        ]);

        $comments = PostComment::query()->whereIn('id', $ids)->get();
        $actor = auth()->user();
        $actorId = $actor?->id;

        foreach ($comments as $comment) {
            $ability = $this->bulkAction === 'delete' ? 'delete' : 'moderate';
            abort_unless($actor !== null && $actor->can($ability, $comment), 403);

            match ($this->bulkAction) {
                'approve' => $comment->update([
                    'status' => PostComment::STATUS_APPROVED,
                    'moderated_by' => $actorId,
                    'moderated_at' => now(),
                ]),
                'reject' => $comment->update([
                    'status' => PostComment::STATUS_REJECTED,
                    'moderated_by' => $actorId,
                    'moderated_at' => now(),
                ]),
                'spam' => $comment->update([
                    'status' => PostComment::STATUS_SPAM,
                    'moderated_by' => $actorId,
                    'moderated_at' => now(),
                ]),
                'delete' => $comment->delete(),
            };
        }

        $count = $comments->count();
        $this->selectedIds = [];
        $this->bulkAction = '';
        $this->statusNotice = "Aksi massal berhasil diterapkan pada {$count} komentar.";
    }

    public function openReply(int $id): void
    {
        $this->authorizeAccess();
        $comment = PostComment::query()->findOrFail($id);
        abort_unless(auth()->user()?->can('reply', $comment), 403);
        abort_if($comment->parent_id !== null || $comment->is_official_reply, 422, 'Tanggapan resmi hanya dapat diberikan pada komentar utama warga.');
        $this->replyingToId = $id;
        $this->replyBody = '';
        $this->resetValidation('replyBody');
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyBody = '';
        $this->resetValidation('replyBody');
    }

    public function saveReply(): void
    {
        $this->authorizeAccess();

        if ($this->replyingToId === null) {
            return;
        }

        $this->validate([
            'replyBody' => ['required', 'string', 'min:2', 'max:1500'],
        ], [
            'replyBody.required' => 'Isi tanggapan resmi wajib diisi.',
            'replyBody.min' => 'Isi tanggapan resmi minimal 2 karakter.',
            'replyBody.max' => 'Isi tanggapan resmi maksimal 1.500 karakter.',
        ]);

        $parent = PostComment::query()->findOrFail($this->replyingToId);
        $actor = auth()->user();
        abort_unless($actor !== null && $actor->can('reply', $parent), 403);
        abort_if($parent->parent_id !== null || $parent->is_official_reply, 422, 'Tanggapan resmi hanya dapat diberikan pada komentar utama warga.');
        $satkerName = SatkerSetting::current()?->nama_satker ?: 'Admin Satker';

        if ($parent->status !== PostComment::STATUS_APPROVED) {
            $parent->update([
                'status' => PostComment::STATUS_APPROVED,
                'moderated_by' => $actor->id,
                'moderated_at' => now(),
            ]);
        }

        $cleanBody = trim((string) preg_replace('/\n{3,}/', "\n\n", str_replace(["\r\n", "\r"], "\n", strip_tags($this->replyBody))));

        PostComment::query()->create([
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
            'author_name' => "Tanggapan Resmi {$satkerName}",
            'author_email' => null,
            'body' => $cleanBody,
            'status' => PostComment::STATUS_APPROVED,
            'is_official_reply' => true,
            'ip_hash' => null,
            'moderated_by' => $actor->id,
            'moderated_at' => now(),
        ]);

        $this->cancelReply();
        $this->statusNotice = 'Tanggapan resmi satuan kerja berhasil dikirim dan ditayangkan.';
    }

    private function moderateStatus(int $id, string $status, string $message): void
    {
        $this->authorizeAccess();
        $comment = PostComment::query()->findOrFail($id);
        abort_unless(auth()->user()?->can('moderate', $comment), 403);
        $comment->update([
            'status' => $status,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        $this->statusNotice = $message;
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && $user->can('viewAny', PostComment::class),
            403
        );
    }

    public function render(): View
    {
        $this->authorizeAccess();

        $counts = [
            'all' => PostComment::query()->count(),
            'pending' => PostComment::query()->where('status', PostComment::STATUS_PENDING)->count(),
            'approved' => PostComment::query()->where('status', PostComment::STATUS_APPROVED)->count(),
            'rejected' => PostComment::query()->where('status', PostComment::STATUS_REJECTED)->count(),
            'spam' => PostComment::query()->where('status', PostComment::STATUS_SPAM)->count(),
        ];

        $comments = PostComment::query()
            ->with([
                'post:id,title,slug,category',
                'parent:id,author_name,body',
                'replies' => fn ($q) => $q->orderBy('created_at'),
                'moderator:id,name',
            ])
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== '', function ($q): void {
                $q->whereHas('post', fn ($pq) => $pq->where('category', $this->categoryFilter));
            })
            ->when(trim($this->search) !== '', function ($q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term): void {
                    $sub->where('author_name', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhereHas('post', fn ($pq) => $pq->where('title', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.comment-manager', [
            'comments' => $comments,
            'counts' => $counts,
            'statusLabels' => PostComment::statusLabels(),
            'categories' => Post::categoryLabels(),
        ])->title('Moderasi Komentar Masyarakat');
    }
}
