<?php

namespace Tests\Feature;

use App\Models\PendingSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ActivationPageTest extends TestCase
{
    use RefreshDatabase;

    private PendingSignup $pending;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pending = PendingSignup::create([
            'uuid' => 'd0000000-0000-0000-0000-000000000001',
            'name' => 'Alex Chen', 'email' => 'alex@gmail.com',
            'country_code' => 'CA', 'organization_name' => 'Cedar Way',
            'billing_timezone' => 'America/Vancouver',
            'status' => PendingSignup::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);
    }

    private function signedUrl(): string
    {
        return URL::temporarySignedRoute('signup.activating', now()->addMinutes(30), ['uuid' => $this->pending->uuid]);
    }

    public function test_unsigned_or_expired_signatures_are_403(): void
    {
        $this->get('/signup/activating/'.$this->pending->uuid)->assertForbidden();

        $expired = URL::temporarySignedRoute('signup.activating', now()->addMinutes(30), ['uuid' => $this->pending->uuid]);
        $this->travel(31)->minutes();
        $this->get($expired)->assertForbidden();
    }

    public function test_verified_state_polls_with_a_meta_refresh(): void
    {
        $this->get($this->signedUrl())
            ->assertOk()
            ->assertSee('Activating your account', false)
            ->assertSee('<meta http-equiv="refresh"', false)
            ->assertSee('Refresh now');
    }

    public function test_stalled_provisioning_adds_the_still_working_note(): void
    {
        $this->pending->update(['verified_at' => now()->subMinutes(6)]);

        $this->get($this->signedUrl())
            ->assertOk()
            ->assertSee('Still working on it');
    }

    public function test_synced_with_a_live_ticket_redirects_once_and_burns_the_token(): void
    {
        $this->pending->update([
            'status' => PendingSignup::STATUS_SYNCED,
            'handoff_token' => str_repeat('ab', 32),
            'handoff_expires_at' => now()->addMinutes(9),
        ]);

        $this->get($this->signedUrl())
            ->assertRedirect(config('app.console_url').'/auth/handoff?token='.str_repeat('ab', 32));

        $this->assertNull($this->pending->fresh()->handoff_token);

        // Second visit: the ticket is gone — offer login, never a new ticket.
        $this->get($this->signedUrl())
            ->assertOk()
            ->assertSee('Your account is ready.')
            ->assertSee('Log in');
    }

    public function test_expired_ticket_offers_login_instead(): void
    {
        $this->pending->update([
            'status' => PendingSignup::STATUS_SYNCED,
            'handoff_token' => str_repeat('cd', 32),
            'handoff_expires_at' => now()->subMinute(),
        ]);

        $this->get($this->signedUrl())
            ->assertOk()
            ->assertSee('Your account is ready.');
        // The stale plaintext is not handed out.
        $this->assertStringNotContainsString(str_repeat('cd', 32), $this->get($this->signedUrl())->getContent());
    }

    public function test_email_taken_failure_shows_the_already_registered_message(): void
    {
        $this->pending->update([
            'status' => PendingSignup::STATUS_FAILED,
            'failure_reason' => PendingSignup::FAILURE_EMAIL_TAKEN,
        ]);

        $this->get($this->signedUrl())
            ->assertOk()
            ->assertSee('This email is already registered.');
    }

    public function test_other_failures_show_a_customer_safe_reference(): void
    {
        $this->pending->update([
            'status' => PendingSignup::STATUS_FAILED,
            'failure_reason' => PendingSignup::FAILURE_PUSH_ERROR,
        ]);

        $this->get($this->signedUrl())
            ->assertOk()
            ->assertSee('reference D0000000');
    }
}
