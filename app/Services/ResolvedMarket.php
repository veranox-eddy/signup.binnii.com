<?php

namespace App\Services;

use App\Models\Market;

/**
 * `resolution` uses the Platform contract's literal values
 * (organization | handoff | hint | fallback, P-§10.1).
 */
readonly class ResolvedMarket
{
    public function __construct(
        public Market $market,
        public string $resolution,
    ) {}
}
