<?php

namespace App\Services;

use App\Models\PendingSignup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Duplicate-email pre-check for the signup form. Reads MySQL through the
 * column-grant-limited `mysql_ro` connection — the SELECT must name its
 * columns explicitly (both `select *` and `count(*)` are refused by a
 * column-level GRANT) — plus the local SQLite staging rows.
 *
 * Soft-deleted accounts count as taken (conservative). A connection
 * failure is NOT swallowed: the caller must refuse the registration, never
 * skip the check.
 */
class EmailAvailability
{
    public function isTaken(string $email): bool
    {
        $email = Str::lower($email);

        $taken = DB::connection('mysql_ro')->table('users')
            ->select('email') // never *, never count(*)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->limit(1)
            ->get()
            ->isNotEmpty();

        return $taken || PendingSignup::active()->where('email', $email)->exists();
    }
}
