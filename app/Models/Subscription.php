<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethodReadiness;
use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id', 'plan_key', 'billing_cycle', 'is_trialing',
        'trial_started_at', 'trial_ends_at', 'trial_plan_key',
        'trial_days_granted', 'payment_method_readiness',
    ];

    protected function casts(): array
    {
        return [
            'plan_key' => PlanKey::class,
            'billing_cycle' => BillingCycle::class,
            'is_trialing' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'trial_plan_key' => PlanKey::class,
            'trial_days_granted' => 'integer',
            'payment_method_readiness' => PaymentMethodReadiness::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
