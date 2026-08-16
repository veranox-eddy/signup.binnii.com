<?php

namespace Tests\Feature;

use App\Models\MarketMirror;
use App\Models\PendingSignup;
use App\Services\SignupIntakeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SignupPushWorkerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.signup_intake.url' => 'https://api.binnii.test/api/internal/v1',
            'services.signup_intake.secret' => str_repeat('s', 64),
            'services.signup_intake.client' => 'signup-1',
        ]);
    }

    private function makeVerified(array $overrides = []): PendingSignup
    {
        return PendingSignup::create([
            'uuid' => $overrides['uuid'] ?? fake()->uuid(),
            'name' => 'Alex Chen',
            'email' => $overrides['email'] ?? fake()->unique()->safeEmail(),
            'password_hash' => password_hash('trial-password-2026', PASSWORD_BCRYPT, ['cost' => 4]),
            'country_code' => 'CA',
            'organization_name' => 'Cedar Way',
            'billing_timezone' => 'America/Vancouver',
            'status' => PendingSignup::STATUS_VERIFIED,
            'verified_at' => now()->subMinute(),
            'next_push_at' => now()->subSecond(),
            ...$overrides,
        ]);
    }

    private function fakeStatus(int $status, array $body = []): void
    {
        Http::fake(['*' => Http::response($body, $status)]);
    }

    public function test_201_marks_synced_stores_ids_and_scrubs_the_password_hash(): void
    {
        $pending = $this->makeVerified();
        $this->fakeStatus(201, [
            'organization_id' => 567, 'user_id' => 1234,
            'handoff_token' => str_repeat('ab', 32),
            'handoff_expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        $this->artisan('signup:push', ['--once' => true])->assertExitCode(0);

        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::STATUS_SYNCED, $fresh->status);
        $this->assertSame(567, $fresh->mysql_organization_id);
        $this->assertSame(1234, $fresh->mysql_user_id);
        $this->assertSame(str_repeat('ab', 32), $fresh->handoff_token);
        $this->assertNull($fresh->password_hash);
    }

    public function test_409_fails_as_email_taken_and_scrubs_the_password_hash(): void
    {
        $pending = $this->makeVerified();
        $this->fakeStatus(409, ['error' => 'email_taken']);

        $this->artisan('signup:push', ['--once' => true]);

        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::STATUS_FAILED, $fresh->status);
        $this->assertSame(PendingSignup::FAILURE_EMAIL_TAKEN, $fresh->failure_reason);
        $this->assertNull($fresh->password_hash);
    }

    public function test_422_fails_as_validation_drift_and_logs_error(): void
    {
        $pending = $this->makeVerified();
        $this->fakeStatus(422, ['error' => 'validation_failed']);
        Log::spy();

        $this->artisan('signup:push', ['--once' => true]);

        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::FAILURE_VALIDATION, $fresh->failure_reason);
        $this->assertNull($fresh->password_hash);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_503_backs_off_then_terminally_fails_on_the_sixth_attempt(): void
    {
        $pending = $this->makeVerified();
        $this->fakeStatus(503, ['error' => 'market_unavailable']);

        $this->artisan('signup:push', ['--once' => true]);
        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::STATUS_VERIFIED, $fresh->status);
        $this->assertTrue($fresh->next_push_at->isFuture());
        $this->assertSame(1, $fresh->push_attempts);

        $pending->update(['push_attempts' => 5, 'next_push_at' => now()->subMinutes(2)]);
        $this->artisan('signup:push', ['--once' => true]);
        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::STATUS_FAILED, $fresh->status);
        $this->assertSame(PendingSignup::FAILURE_MARKET_UNAVAILABLE, $fresh->failure_reason);
    }

    public function test_401_reverts_logs_critical_and_stops_the_round(): void
    {
        $first = $this->makeVerified(['verified_at' => now()->subMinutes(2)]);
        $second = $this->makeVerified(['verified_at' => now()->subMinute()]);
        $this->fakeStatus(401, ['error' => 'unauthorized']);
        Log::spy();

        $this->artisan('signup:push', ['--once' => true]);

        $this->assertSame(PendingSignup::STATUS_VERIFIED, $first->fresh()->status);
        // The round stopped after the first 401: row two was never pushed.
        $this->assertSame(0, $second->fresh()->push_attempts);
        Log::shouldHaveReceived('critical')->once();
        Http::assertSentCount(1);
    }

    public function test_timeout_retries_reuse_the_same_idempotency_key(): void
    {
        $pending = $this->makeVerified(['uuid' => 'e0000000-0000-0000-0000-000000000001']);

        // One stateful fake: first delivery times out, the retry succeeds.
        // Idempotency-Key headers are captured on BOTH attempts.
        $keys = [];
        Http::fake(function (Request $request) use (&$keys) {
            $keys[] = $request->header('Idempotency-Key')[0] ?? null;
            if (count($keys) === 1) {
                throw new ConnectionException('timed out');
            }

            return Http::response([
                'organization_id' => 1, 'user_id' => 1,
                'handoff_token' => str_repeat('cd', 32),
                'handoff_expires_at' => now()->addMinutes(10)->toIso8601String(),
            ], 201);
        });

        $this->artisan('signup:push', ['--once' => true]);
        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::STATUS_VERIFIED, $fresh->status);
        $this->assertStringContainsString('ConnectionException', $fresh->last_push_error);

        // Second attempt after the backoff: SAME Idempotency-Key, so the
        // api's signup_reference hit path re-issues a handoff instead of
        // creating a second tenant.
        $pending->update(['next_push_at' => now()->subMinutes(2)]);
        $this->artisan('signup:push', ['--once' => true]);

        $this->assertSame([
            'e0000000-0000-0000-0000-000000000001',
            'e0000000-0000-0000-0000-000000000001',
        ], $keys);
        $this->assertSame(PendingSignup::STATUS_SYNCED, $pending->fresh()->status);
    }

    public function test_429_defers_five_minutes(): void
    {
        $this->freezeTime();
        $pending = $this->makeVerified();
        $this->fakeStatus(429, ['error' => 'rate_limited']);

        $this->artisan('signup:push', ['--once' => true]);

        $this->assertSame(
            now()->addMinutes(5)->format('Y-m-d H:i:s'),
            $pending->fresh()->next_push_at->format('Y-m-d H:i:s')
        );
    }

    public function test_payload_contains_exactly_the_contract_fields(): void
    {
        $this->makeVerified();
        $this->fakeStatus(201, [
            'organization_id' => 1, 'user_id' => 1,
            'handoff_token' => str_repeat('ef', 32),
            'handoff_expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        $this->artisan('signup:push', ['--once' => true]);

        Http::assertSent(function (Request $request) {
            $keys = array_keys(json_decode($request->body(), true));
            sort($keys);

            return $keys === [
                'billing_timezone', 'country_code', 'email', 'name',
                'organization_name', 'password_hash', 'uuid', 'verified_at',
            ];
        });
    }

    public function test_hmac_signature_matches_the_contract_concatenation(): void
    {
        $this->freezeTime();
        $client = app(SignupIntakeClient::class);
        $headers = $client->authHeaders('POST', '/api/internal/v1/signups', '{"a":1}');

        $expected = hash_hmac('sha256',
            "POST\n/api/internal/v1/signups\n".$headers['X-Binnii-Timestamp']."\n".$headers['X-Binnii-Nonce']."\n".hash('sha256', '{"a":1}'),
            str_repeat('s', 64)
        );

        $this->assertSame($expected, $headers['X-Binnii-Signature']);
        $this->assertSame('signup-1', $headers['X-Binnii-Client']);
    }

    public function test_missing_secret_exits_1_and_touches_nothing(): void
    {
        config(['services.signup_intake.secret' => null]);
        $pending = $this->makeVerified();
        Http::fake();

        $this->artisan('signup:push', ['--once' => true])->assertExitCode(1);

        $this->assertSame(0, $pending->fresh()->push_attempts);
        Http::assertNothingSent();
    }

    public function test_sixth_5xx_attempt_becomes_a_terminal_push_error(): void
    {
        $pending = $this->makeVerified(['push_attempts' => 5]);
        $this->fakeStatus(500);

        $this->artisan('signup:push', ['--once' => true]);

        $fresh = $pending->fresh();
        $this->assertSame(PendingSignup::STATUS_FAILED, $fresh->status);
        $this->assertSame(PendingSignup::FAILURE_PUSH_ERROR, $fresh->failure_reason);
        $this->assertNull($fresh->password_hash);
    }

    public function test_pull_markets_overwrites_the_mirror(): void
    {
        MarketMirror::create([
            'code' => 'OLD', 'name' => 'Old', 'country_code' => 'OL',
            'currency' => 'OLD', 'is_active' => true, 'is_fallback' => false,
            'synced_at' => now()->subDay(),
        ]);
        Http::fake(['*' => Http::response(['markets' => [[
            'code' => 'CA', 'name' => 'Canada', 'country_code' => 'CA',
            'currency' => 'CAD', 'is_active' => true, 'is_fallback' => true,
        ]]], 200)]);

        $this->artisan('signup:pull-markets')->assertExitCode(0);

        $this->assertNull(MarketMirror::find('OLD'));
        $this->assertTrue(MarketMirror::find('CA')->is_active);
    }

    public function test_purge_applies_the_retention_policy(): void
    {
        $synced = $this->makeVerified(['status' => PendingSignup::STATUS_SYNCED, 'synced_at' => now()->subDays(31)]);
        $stale = $this->makeVerified(['status' => PendingSignup::STATUS_PENDING_VERIFICATION]);
        PendingSignup::whereKey($stale->id)->update(['created_at' => now()->subDays(31)]);
        $ticket = $this->makeVerified(['status' => PendingSignup::STATUS_SYNCED, 'synced_at' => now(),
            'handoff_token' => str_repeat('aa', 32), 'handoff_expires_at' => now()->subMinute()]);

        $this->artisan('signup:purge')->assertExitCode(0);

        $this->assertNull($synced->fresh());
        $expired = $stale->fresh();
        $this->assertSame(PendingSignup::STATUS_EXPIRED, $expired->status);
        $this->assertSame('', $expired->email);
        $this->assertNull($expired->password_hash);
        $this->assertNull($ticket->fresh()->handoff_token);
    }
}
