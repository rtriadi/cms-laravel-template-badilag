<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;

class SubmitPostCommentAction
{
    /**
     * Persist a sanitized public comment in pending moderation status without storing raw IP PII.
     *
     * @param  array{author_name: string, author_email?: ?string, body: string}  $validated
     */
    public function execute(Post $post, array $validated, Request $request): PostComment
    {
        $cleanName = $this->sanitizePlainText((string) $validated['author_name'], singleLine: true);
        $cleanBody = $this->sanitizePlainText((string) $validated['body'], singleLine: false);
        $rawEmail = trim((string) ($validated['author_email'] ?? ''));

        $appKey = (string) config('app.key');
        if ($appKey === '') {
            throw new \RuntimeException('APP_KEY wajib dikonfigurasi.');
        }

        $ipHash = hash_hmac(
            'sha256',
            (string) $request->ip(),
            $appKey
        );

        return PostComment::query()->create([
            'post_id' => $post->id,
            'parent_id' => null,
            'author_name' => $cleanName,
            'author_email' => $rawEmail !== '' ? mb_strtolower($rawEmail) : null,
            'body' => $cleanBody,
            'status' => PostComment::STATUS_PENDING,
            'is_official_reply' => false,
            'ip_hash' => $ipHash,
        ]);
    }

    private function sanitizePlainText(string $input, bool $singleLine): string
    {
        $stripped = strip_tags($input);
        $normalized = str_replace(["\r\n", "\r"], "\n", $stripped);

        if ($singleLine) {
            return trim((string) preg_replace('/\s+/', ' ', $normalized));
        }

        $collapsed = (string) preg_replace('/\n{3,}/', "\n\n", $normalized);

        return trim($collapsed);
    }
}
