<?php

namespace Tests\Feature;

use App\Models\PendingSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contract guard (staged-registration spec §12.7): the fixtures under
 * tests/fixtures/signup-intake exist IDENTICALLY in this repo and in
 * api.binnii.com. The api asserts its endpoint speaks them; this side
 * asserts the worker does. Changing a field on one side without syncing
 * the fixture turns exactly one suite red — the only drift protection
 * once the hosts are split.
 */
class SignupIntakeContractTest extends TestCase
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

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/fixtures/signup-intake/{$name}.json")), true);
    }

    private function makeRowFromRequestFixture(): PendingSignup
    {
        $request = $this->fixture('request');

        return PendingSignup::create([
            'uuid' => $request['uuid'],
            'name' => $request['name'],
            'email' => $request['email'],
            'password_hash' => $request['password_hash'],
            'country_code' => $request['country_code'],
            'organization_name' => $request['organization_name'],
            'billing_timezone' => $request['billing_timezone'],
            'status' => PendingSignup::STATUS_VERIFIED,
            'verified_at' => $request['verified_at'],
            'next_push_at' => now()->subMinute(),
        ]);
    }

    public function test_the_pushed_payload_matches_the_request_fixture(): void
    {
        $this->makeRowFromRequestFixture();
        Http::fake(['*' => Http::response($this->fixture('response-201'), 201)]);

        $this->artisan('signup:push', ['--once' => true]);

        $fixture = $this->fixture('request');
        Http::assertSent(function (Request $request) use ($fixture) {
            $sent = json_decode($request->body(), true);

            return array_keys($sent) === array_keys($fixture)
                && $sent['uuid'] === $fixture['uuid']
                && $sent['email'] === $fixture['email']
                && $sent['verified_at'] === '2026-08-16T03:12:00+00:00'; // ISO-8601 of the fixture instant
        });
    }

    public function test_the_201_fixture_is_understood_by_the_worker(): void
    {
        $row = $this->makeRowFromRequestFixture();
        Http::fake(['*' => Http::response($this->fixture('response-201'), 201)]);

        $this->artisan('signup:push', ['--once' => true]);

        $fresh = $row->fresh();
        $this->assertSame(PendingSignup::STATUS_SYNCED, $fresh->status);
        $this->assertSame(567, $fresh->mysql_organization_id);
        $this->assertSame(1234, $fresh->mysql_user_id);
        $this->assertSame($this->fixture('response-201')['handoff_token'], $fresh->handoff_token);
    }

    public function test_the_409_fixture_is_understood_by_the_worker(): void
    {
        $row = $this->makeRowFromRequestFixture();
        Http::fake(['*' => Http::response($this->fixture('response-409'), 409)]);

        $this->artisan('signup:push', ['--once' => true]);

        $this->assertSame(PendingSignup::FAILURE_EMAIL_TAKEN, $row->fresh()->failure_reason);
    }

    public function test_the_422_fixture_is_understood_by_the_worker(): void
    {
        $row = $this->makeRowFromRequestFixture();
        Http::fake(['*' => Http::response($this->fixture('response-422'), 422)]);

        $this->artisan('signup:push', ['--once' => true]);

        $this->assertSame(PendingSignup::FAILURE_VALIDATION, $row->fresh()->failure_reason);
    }

    public function test_the_503_fixture_backs_off_instead_of_failing(): void
    {
        $row = $this->makeRowFromRequestFixture();
        Http::fake(['*' => Http::response($this->fixture('response-503'), 503)]);

        $this->artisan('signup:push', ['--once' => true]);

        $this->assertSame(PendingSignup::STATUS_VERIFIED, $row->fresh()->status);
    }
}
