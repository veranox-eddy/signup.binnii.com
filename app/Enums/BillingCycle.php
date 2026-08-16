<?php

// Mirrors app.binnii.com/app/Enums/BillingCycle.php — keep both in sync when the enum changes.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum BillingCycle: string
{
    use HasValues;

    case Monthly = 'monthly';
    case Annual = 'annual';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
