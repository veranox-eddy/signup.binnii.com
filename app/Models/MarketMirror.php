<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Local copy of api.binnii.com's market list (form options and
 * pre-validation only — the authoritative resolution happens on the api).
 */
class MarketMirror extends Model
{
    protected $table = 'market_mirror';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_fallback' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Country codes the form accepts right now; ['CA'] before the first
     * successful mirror pull so the form never bricks itself.
     *
     * @return array<int, string>
     */
    public static function activeCountryCodes(): array
    {
        $codes = self::active()->pluck('country_code')->unique()->values()->all();

        return $codes === [] ? ['CA'] : $codes;
    }
}
