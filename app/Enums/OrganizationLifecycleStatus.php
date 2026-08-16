<?php

// Mirrors app.binnii.com/app/Enums/OrganizationLifecycleStatus.php — keep both in sync when the enum changes.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Subscription lifecycle (Platform PRD F-03). Distinct from the boolean
 * organizations.status, which is the manual SaaS enable/disable flag.
 */
enum OrganizationLifecycleStatus: string
{
    use HasValues;

    case Active = 'active';
    case PaymentIssue = 'payment_issue';
    case GracePeriod = 'grace_period';
    case ReadOnly = 'read_only';
    case Suspended = 'suspended';
    case Unsubscribed = 'unsubscribed';

    /** PRD-verbatim labels — note `Read-only` (hyphen, lowercase o). */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PaymentIssue => 'Payment Issue',
            self::GracePeriod => 'Grace Period',
            self::ReadOnly => 'Read-only',
            self::Suspended => 'Suspended',
            self::Unsubscribed => 'Unsubscribed',
        };
    }
}
