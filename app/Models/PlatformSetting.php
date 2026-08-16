<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected function casts(): array
    {
        return [
            'free_trial_enabled' => 'boolean',
            'default_trial_length_days' => 'integer',
            'trial_plan_entitlement' => PlanKey::class,
        ];
    }

    /** The one row, memoized per request. */
    public static function current(): self
    {
        return once(fn () => self::firstOrFail());
    }
}
