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
     * This repo carries NO migrations at all — app.binnii.com owns every
     * schema definition, including this host's private SQLite staging
     * store (app.binnii.com/database/migrations/signup-sqlite). The local
     * schema arrives as a generated snapshot,
     * database/schema/signup-schema.sql, which `php artisan migrate` loads
     * onto a fresh SQLite; regenerate it in the app repo (recipe in the
     * pending_signups migration header) whenever the staging schema
     * changes, then rebuild the staging file (its data is 30-day
     * transient).
     */
    public function boot(): void
    {
        //
    }
}
