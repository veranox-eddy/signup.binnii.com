<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use App\Enums\OrganizationLifecycleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = [
        'name', 'status', 'market_id', 'lifecycle_status', 'onboarding_status',
        'billing_timezone', 'is_test_account',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'lifecycle_status' => OrganizationLifecycleStatus::class,
            'is_test_account' => 'boolean',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
