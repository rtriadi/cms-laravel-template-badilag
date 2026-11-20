<?php

namespace App\Actions\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InvalidateUserSessions
{
    /**
     * Invalidate active database sessions and rotate remember_token for a user.
     */
    public function execute(User $user, ?string $exceptSessionId = null): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') !== 'database') {
            return;
        }

        $table = (string) config('session.table', 'sessions');
        $connection = config('session.connection');

        if (! Schema::connection($connection)->hasTable($table)) {
            return;
        }

        DB::connection($connection)
            ->table($table)
            ->where('user_id', $user->getKey())
            ->when(
                $exceptSessionId !== null && $exceptSessionId !== '',
                fn ($query) => $query->where('id', '!=', $exceptSessionId),
            )
            ->delete();
    }
}
