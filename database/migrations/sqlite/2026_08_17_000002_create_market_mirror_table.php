<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Form options + pre-validation ONLY — the authoritative market
     * resolution happens on api.binnii.com. Refreshed by
     * signup:pull-markets; a stale (>24h) mirror still serves the form but
     * logs a warning.
     */
    public function up(): void
    {
        Schema::create('market_mirror', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name', 100);
            $table->char('country_code', 2);
            $table->char('currency', 3);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_fallback')->default(false);
            $table->dateTime('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_mirror');
    }
};
