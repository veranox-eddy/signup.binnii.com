<?php

namespace App\Console\Commands;

use App\Models\PendingSignup;
use App\Models\PushRun;
use Illuminate\Console\Command;

/** Daily retention policy (staged-registration spec §11.1). */
class SignupPurgeCommand extends Command
{
    protected $signature = 'signup:purge';

    protected $description = 'Apply the pending-signup retention policy';

    public function handle(): int
    {
        // Delivered rows are done after 30 days.
        PendingSignup::where('status', PendingSignup::STATUS_SYNCED)
            ->where('synced_at', '<', now()->subDays(30))
            ->delete();

        // Never-verified rows expire after 30 days, scrubbed of PII.
        PendingSignup::where('status', PendingSignup::STATUS_PENDING_VERIFICATION)
            ->where('created_at', '<', now()->subDays(30))
            ->update([
                'status' => PendingSignup::STATUS_EXPIRED,
                'name' => '', 'email' => '', 'password_hash' => null,
                'organization_name' => null,
                'verification_token_hash' => null,
            ]);

        // Failed rows keep their diagnostics for 90 days.
        PendingSignup::where('status', PendingSignup::STATUS_FAILED)
            ->where('updated_at', '<', now()->subDays(90))
            ->delete();

        PushRun::where('started_at', '<', now()->subDays(30))->delete();

        // Unclaimed tickets die with their expiry.
        PendingSignup::whereNotNull('handoff_token')
            ->where('handoff_expires_at', '<', now())
            ->update(['handoff_token' => null]);

        $this->info('Purge complete.');

        return 0;
    }
}
