<?php

namespace App\Console\Commands;

use App\Models\PendingSignup;
use App\Models\PushRun;
use App\Services\SignupIntakeClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers verified signups to api.binnii.com (staged-registration spec
 * §7). The ONLY provisioning trigger in the system: the web flow never
 * calls the api. Idempotency-Key = pending_signups.uuid, so a retry after
 * a timeout whose first attempt actually succeeded re-issues a handoff on
 * the api side instead of creating a second tenant.
 */
class SignupPushCommand extends Command
{
    protected $signature = 'signup:push {--once} {--limit=25} {--loop-seconds=10}';

    protected $description = 'Push verified signups to the api.binnii.com intake endpoint';

    /** Exponential backoff per attempt: 10s 30s 2m 10m 30m 2h. */
    private const array BACKOFF_SECONDS = [10, 30, 120, 600, 1800, 7200];

    public function handle(SignupIntakeClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('SIGNUP_INTAKE_URL / SIGNUP_INTAKE_SECRET missing — refusing to run.');

            return 1;
        }

        do {
            $this->runOnce($client, (int) $this->option('limit'));

            if (! $this->option('once')) {
                sleep((int) $this->option('loop-seconds'));
            }
        } while (! $this->option('once'));

        return 0;
    }

    private function runOnce(SignupIntakeClient $client, int $limit): void
    {
        $run = PushRun::create(['started_at' => now()]);
        $attempted = $succeeded = $failed = 0;
        $roundError = null;

        foreach (PendingSignup::pushable()->limit($limit)->get() as $pending) {
            $attempted++;
            $pending->update([
                'status' => PendingSignup::STATUS_PUSHING,
                'push_attempts' => $pending->push_attempts + 1,
                'pushed_at' => now(),
            ]);

            try {
                $response = $client->postSignup($pending);
            } catch (Throwable $e) {
                // A timeout does NOT mean the tenant was not created — the
                // Idempotency-Key makes the retry safe on the api side.
                $this->retryLater($pending, 'connection_error '.class_basename($e));
                $failed++;

                continue;
            }

            if ($this->dispatch($pending, $response)) {
                $succeeded++;
            } else {
                $failed++;
            }

            if ($response->status() === 401) {
                // Broken secret or clock — a human problem; pushing more
                // rows this round would only burn attempts.
                $roundError = 'aborted: 401 from intake endpoint';
                break;
            }
        }

        $run->update([
            'finished_at' => now(),
            'attempted' => $attempted,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'error' => $roundError,
        ]);
    }

    /** @return bool true when the row reached a successful terminal state */
    private function dispatch(PendingSignup $pending, Response $response): bool
    {
        $status = $response->status();

        if ($status === 201) {
            $pending->update([
                'status' => PendingSignup::STATUS_SYNCED,
                'synced_at' => now(),
                'mysql_user_id' => $response->json('user_id'),
                'mysql_organization_id' => $response->json('organization_id'),
                'handoff_token' => $response->json('handoff_token'),
                'handoff_expires_at' => $response->json('handoff_expires_at'),
                'password_hash' => null,
                'last_push_error' => null,
            ]);

            return true;
        }

        if ($status === 409) {
            $this->fail_($pending, PendingSignup::FAILURE_EMAIL_TAKEN, 'http_409');

            return false;
        }

        if ($status === 422) {
            // The two sides disagree on validation rules — that is a bug.
            Log::error("Signup push {$pending->uuid} rejected as validation_failed — signup and api rules have drifted.");
            $this->fail_($pending, PendingSignup::FAILURE_VALIDATION, 'http_422');

            return false;
        }

        if ($status === 401) {
            Log::critical('Signup push got 401 from the intake endpoint — secret or clock is broken, human needed.');
            $pending->update(['status' => PendingSignup::STATUS_VERIFIED, 'last_push_error' => 'http_401']);

            return false;
        }

        if ($status === 429) {
            Log::warning('Signup push rate-limited by the intake endpoint.');
            $pending->update([
                'status' => PendingSignup::STATUS_VERIFIED,
                'next_push_at' => now()->addMinutes(5),
                'last_push_error' => 'http_429',
            ]);

            return false;
        }

        if ($status === 503) {
            $this->retryLater($pending, 'http_503', PendingSignup::FAILURE_MARKET_UNAVAILABLE);

            return false;
        }

        // 5xx and anything unexpected.
        $this->retryLater($pending, 'http_'.$status);

        return false;
    }

    /** Backoff, or the terminal failure once the 6th attempt is spent. */
    private function retryLater(PendingSignup $pending, string $error, string $terminalReason = PendingSignup::FAILURE_PUSH_ERROR): void
    {
        if ($pending->push_attempts >= 6) {
            $this->fail_($pending, $terminalReason, $error);

            return;
        }

        $delay = self::BACKOFF_SECONDS[min($pending->push_attempts - 1, 5)];
        $pending->update([
            'status' => PendingSignup::STATUS_VERIFIED,
            'next_push_at' => now()->addSeconds($delay),
            // Category + HTTP status + exception class only — never SQL,
            // credentials or response bodies.
            'last_push_error' => $error,
        ]);
    }

    private function fail_(PendingSignup $pending, string $reason, string $error): void
    {
        $pending->update([
            'status' => PendingSignup::STATUS_FAILED,
            'failure_reason' => $reason,
            'last_push_error' => $error,
            'password_hash' => null,
        ]);
    }
}
