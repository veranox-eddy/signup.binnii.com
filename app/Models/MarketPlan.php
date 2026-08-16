<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPlan extends Model
{
    protected function casts(): array
    {
        return [
            'plan_key' => PlanKey::class,
            'monthly_base_fee' => 'decimal:2',
            'annual_base_fee' => 'decimal:2',
            'included_active_children' => 'integer',
            'monthly_overage_rate' => 'decimal:4',
            'annual_overage_rate' => 'decimal:4',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
