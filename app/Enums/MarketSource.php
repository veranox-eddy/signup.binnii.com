<?php

// Mirrors app.binnii.com/app/Enums/MarketSource.php — keep both in sync when the enum changes.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Local stub of the Platform market contract (admin.binnii.com is not built
 * yet); see specs/saas/build-signup-free-trial.md §2.2. Once the Platform
 * exists, rows switch to `platform` and only the data source changes.
 */
enum MarketSource: string
{
    use HasValues;

    case LocalStub = 'local_stub';
    case Platform = 'platform';
}
