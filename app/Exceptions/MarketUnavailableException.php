<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Str;

/**
 * No market could be resolved for a signup. Carries a trackable reference
 * (SGN-XXXXXXXX) shown to the customer and written to the log — we MUST NOT
 * silently fall back to a hard-coded Canada/CAD (C-F33, F-34).
 */
class MarketUnavailableException extends Exception
{
    public readonly string $reference;

    public function __construct(string $countryCode)
    {
        $this->reference = 'SGN-'.Str::upper(Str::random(8));

        parent::__construct("No active market available for signup hint [{$countryCode}] and no valid fallback (reference {$this->reference}).");
    }
}
