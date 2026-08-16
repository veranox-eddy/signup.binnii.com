<?php

namespace App\Console\Commands;

use App\Models\MarketMirror;
use App\Services\SignupIntakeClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refreshes the local market mirror from GET /internal/v1/markets — form
 * options and pre-validation only; the authoritative market resolution
 * stays on the api. On failure the last copy keeps serving.
 */
class SignupPullMarketsCommand extends Command
{
    protected $signature = 'signup:pull-markets';

    protected $description = 'Refresh the market mirror from the api intake endpoint';

    public function handle(SignupIntakeClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('SIGNUP_INTAKE_URL / SIGNUP_INTAKE_SECRET missing — refusing to run.');

            return 1;
        }

        try {
            $response = $client->getMarkets();
        } catch (Throwable $e) {
            Log::warning('Market mirror pull failed: '.class_basename($e));

            return 1;
        }

        if (! $response->successful()) {
            Log::warning('Market mirror pull failed: http_'.$response->status());

            return 1;
        }

        $markets = $response->json('markets', []);

        DB::transaction(function () use ($markets) {
            MarketMirror::query()->delete(); // whole-batch overwrite
            foreach ($markets as $market) {
                MarketMirror::create([
                    'code' => $market['code'],
                    'name' => $market['name'],
                    'country_code' => $market['country_code'],
                    'currency' => $market['currency'],
                    'is_active' => (bool) $market['is_active'],
                    'is_fallback' => (bool) $market['is_fallback'],
                    'synced_at' => now(),
                ]);
            }
        });

        $this->info(count($markets).' markets mirrored.');

        return 0;
    }
}
