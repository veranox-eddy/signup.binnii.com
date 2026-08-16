<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use App\Enums\MarketSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'code', 'name', 'country_code', 'currency', 'annual_discount_rate',
        'tax_name', 'tax_rate', 'tax_confirmed_at', 'tax_notice', 'is_active',
        'is_fallback', 'source', 'contract_version',
    ];

    protected function casts(): array
    {
        return [
            'annual_discount_rate' => 'decimal:3',
            'tax_rate' => 'decimal:4',
            'tax_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'source' => MarketSource::class,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
