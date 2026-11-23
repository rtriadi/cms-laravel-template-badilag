<?php

namespace App\Policies;

use App\Models\PostComment;
use App\Models\User;

class PostCommentPolicy
{
    private function canManageComments(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_konten', 'operator_ppid_humas'])
            || $user->can('manage-ppid-humas');
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageComments($user);
    }

    public function view(User $user, PostComment $postComment): bool
    {
        return $this->canManageComments($user);
    }

    public function moderate(User $user, PostComment $postComment): bool
    {
        return $this->canManageComments($user);
    }

    public function reply(User $user, PostComment $postComment): bool
    {
        return $this->canManageComments($user);
    }

    public function delete(User $user, PostComment $postComment): bool
    {
        return $this->canManageComments($user);
    }
}
