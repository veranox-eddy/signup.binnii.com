<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * SQLite-only staging store (staged-registration spec §4). These
 * migrations run on the `signup` connection exclusively:
 *   php artisan migrate --database=signup --path=database/migrations/sqlite
 * Running migrations against MySQL remains absolutely forbidden — the
 * schema owner is app.binnii.com and this repo has no writable MySQL
 * connection anyway.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_signups', function (Blueprint $table) {
            $table->increments('id');
            // Public reference AND the push Idempotency-Key — never serial.
            $table->char('uuid', 36)->unique();
            $table->string('name', 150);
            $table->string('email', 190); // stored lowercase
            // bcrypt only, never plaintext; nulled the moment the push has
            // a verdict (success or terminal failure).
            $table->string('password_hash', 255)->nullable();
            $table->char('country_code', 2);
            $table->string('organization_name', 150)->nullable(); // step 2
            $table->string('billing_timezone', 64)->nullable();   // step 2
            $table->string('status', 20)->default('draft');
            $table->string('failure_reason', 40)->nullable();
            $table->char('verification_token_hash', 64)->nullable();
            $table->dateTime('verification_expires_at')->nullable();
            $table->dateTime('verification_sent_at')->nullable();
            $table->dateTime('last_resend_at')->nullable();
            $table->smallInteger('resend_count')->default(0);
            $table->dateTime('verified_at')->nullable();
            $table->smallInteger('push_attempts')->default(0);
            $table->dateTime('next_push_at')->nullable();
            // Category code + HTTP status + exception class name ONLY —
            // never SQL, credentials or response bodies.
            $table->string('last_push_error', 255)->nullable();
            $table->dateTime('pushed_at')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->unsignedBigInteger('mysql_user_id')->nullable();
            $table->unsignedBigInteger('mysql_organization_id')->nullable();
            // Plaintext from the api response; nulled as soon as the
            // activating page hands it to the browser (§5.4).
            $table->char('handoff_token', 64)->nullable();
            $table->dateTime('handoff_expires_at')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });

        // One ACTIVE row per email — finished rows (synced/failed/expired)
        // never block a fresh registration.
        DB::statement("CREATE UNIQUE INDEX pending_signups_email_active ON pending_signups(email)
            WHERE status IN ('draft','pending_verification','verified','pushing')");
        DB::statement('CREATE INDEX pending_signups_pushable ON pending_signups(status, next_push_at)');
        DB::statement('CREATE INDEX pending_signups_token ON pending_signups(verification_token_hash)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_signups');
    }
};
