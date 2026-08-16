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
     * The SQLite staging schema is this repo's own (database/migrations/
     * sqlite, running on the default `signup` connection). MySQL schema
     * stays owned by app.binnii.com — this repo cannot even connect to
     * MySQL with write permissions.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/sqlite'));
    }
}
