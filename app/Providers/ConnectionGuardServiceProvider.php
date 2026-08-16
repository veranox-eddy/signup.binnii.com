<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Code-layer read-only enforcement for `mysql_ro` (staged-registration
 * spec §3.1): even if the DB GRANT is ever misconfigured, no write
 * statement leaves this process. beforeExecuting (not DB::listen) so the
 * statement is blocked BEFORE it reaches MySQL, not logged after.
 */
class ConnectionGuardServiceProvider extends ServiceProvider
{
    private const string FORBIDDEN = '/^\s*(insert|update|delete|replace|alter|drop|create|truncate|grant|set)\b/i';

    public function boot(): void
    {
        DB::connection('mysql_ro')->beforeExecuting(function (string $query) {
            if (preg_match(self::FORBIDDEN, $query)) {
                throw new RuntimeException('mysql_ro is read-only: refusing to execute a write statement.');
            }
        });
    }
}
