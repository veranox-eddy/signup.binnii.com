<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A staged registration living in the local SQLite store — the ONLY place
 * this application writes. MySQL rows are created later by
 * api.binnii.com's intake endpoint once the email is verified and the push
 * worker delivers the row.
 */
class PendingSignup extends Model
{
    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const string STATUS_VERIFIED = 'verified';

    public const string STATUS_PUSHING = 'pushing';

    public const string STATUS_SYNCED = 'synced';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_EXPIRED = 'expired';

    /** Statuses covered by the partial unique email index. */
    public const array ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_VERIFICATION,
        self::STATUS_VERIFIED,
        self::STATUS_PUSHING,
    ];

    public const string FAILURE_EMAIL_TAKEN = 'email_taken';

    public const string FAILURE_MARKET_UNAVAILABLE = 'market_unavailable';

    public const string FAILURE_VALIDATION = 'validation_failed';

    public const string FAILURE_PUSH_ERROR = 'push_error';

    /** Internal staging model — never filled from raw request input. */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'verification_expires_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'last_resend_at' => 'datetime',
            'verified_at' => 'datetime',
            'next_push_at' => 'datetime',
            'pushed_at' => 'datetime',
            'synced_at' => 'datetime',
            'handoff_expires_at' => 'datetime',
            'resend_count' => 'integer',
            'push_attempts' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /** Rows the push worker should deliver this round. */
    public function scopePushable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where(fn ($q) => $q->whereNull('next_push_at')->orWhere('next_push_at', '<=', now()))
            ->where('push_attempts', '<', 6)
            ->orderBy('verified_at');
    }
}
