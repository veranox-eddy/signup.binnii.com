<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Schema owner is the app.binnii.com repo: this project has NO
     * migrations of its own and deployment MUST NOT run migrate. The test
     * suite builds its throwaway sqlite schema from
     * database/schema/sqlite-schema.sql — a dump of the console repo's
     * migrations. Regenerate it there when the schema changes:
     *
     *   cd ../app.binnii.com
     *   DB_CONNECTION=sqlite DB_DATABASE=/tmp/schema.sqlite php artisan migrate --force
     *   DB_CONNECTION=sqlite DB_DATABASE=/tmp/schema.sqlite php artisan schema:dump
     *   mv database/schema/sqlite-schema.sql ../signup.binnii.com/database/schema/
     */
    public function boot(): void
    {
        //
    }
}
