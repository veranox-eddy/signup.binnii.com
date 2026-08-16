<?php

// Mirrors app.binnii.com/app/Enums/PaymentMethodReadiness.php — keep both in sync when the enum changes.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** Trial-expiry routing gate (Platform PRD F-49). */
enum PaymentMethodReadiness: string
{
    use HasValues;

    case NotSetUp = 'not_set_up';
    case Pending = 'pending';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::NotSetUp => 'Not set up',
            self::Pending => 'Pending',
            self::Verified => 'Verified',
        };
    }
}
