<?php

namespace App\Services;

use App\Exceptions\MarketUnavailableException;
use App\Models\Market;
use Illuminate\Support\Facades\Log;

/**
 * Market resolution per Platform PRD F-58. Signup only ever exercises the
 * last two priority levels — an existing organization's market (1) and a
 * verified pre-provisioned first-Center handoff (2) don't apply here.
 */
class MarketResolver
{
    public function resolve(string $countryCode): ResolvedMarket
    {
        // 3. Valid landing/signup hint.
        $market = Market::active()->where('country_code', $countryCode)->first();
        if ($market) {
            return $this->log(new ResolvedMarket($market, 'hint'), $countryCode);
        }

        // 4. Fallback market — must be active AND tax-confirmed (P-F54/F-58).
        $market = Market::active()->where('is_fallback', 1)->whereNotNull('tax_confirmed_at')->first();
        if ($market) {
            return $this->log(new ResolvedMarket($market, 'fallback'), $countryCode);
        }

        $exception = new MarketUnavailableException($countryCode);
        Log::error($exception->getMessage());

        throw $exception;
    }

    private function log(ResolvedMarket $resolved, string $countryCode): ResolvedMarket
    {
        Log::info('Signup market resolved', [
            'country_code' => $countryCode,
            'market_code' => $resolved->market->code,
            'market_resolution' => $resolved->resolution,
        ]);

        return $resolved;
    }
}
