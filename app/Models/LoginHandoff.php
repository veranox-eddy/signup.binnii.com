<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Issued here after email verification; consumed by app.binnii.com. */
class LoginHandoff extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'token_hash', 'expires_at', 'issued_ip', 'redirect_to'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
