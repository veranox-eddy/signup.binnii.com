<?php

// Mirrors app.binnii.com/app/Enums/AccessLevel.php — keep both in sync when the enum changes.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AccessLevel: string
{
    use HasValues;

    case Organization = 'organization';
    case Center = 'center';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
