<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Staged-registration schedule
|--------------------------------------------------------------------------
|
| These three commands are the moving parts of the signup flow; without
| them a registration stops dead at the "Activating your account…" page.
| The host needs exactly one cron line (see docs/deploy-cron.md):
|
|   * * * * * www-data cd /var/www/html/signup.binnii.com && php artisan schedule:run
|
| withoutOverlapping() is deliberately NOT used: it takes a cache lock, and
| this app has no cache table. `signup:push --once` is already safe to run
| concurrently — pushable() only picks up `verified` rows and flips them to
| `pushing` before the HTTP call, and the api intake is idempotent on uuid.
|
*/

// --once, not the default daemon loop: the scheduler owns the cadence.
Schedule::command('signup:push --once')
    ->everyMinute()
    ->runInBackground();

// The mirror only warns once it is 24h stale, so hourly has plenty of slack.
Schedule::command('signup:pull-markets')->hourly();

Schedule::command('signup:purge')->dailyAt('04:10');
