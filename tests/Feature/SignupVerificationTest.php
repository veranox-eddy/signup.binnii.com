<?php

namespace Tests\Feature;

use App\Models\PendingSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SignupVerificationTest extends TestCase
{
    use RefreshDatabase;

    private string $plain;

    private PendingSignup $pending;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        $this->plain = bin2hex(random_bytes(32));
        $this->pending = PendingSignup::create([
            'uuid' => 'c0000000-0000-0000-0000-000000000001',
            'name' => 'Alex Chen', 'email' => 'alex@gmail.com',
            'country_code' => 'CA', 'organization_name' => 'Cedar Way',
            'billing_timezone' => 'America/Vancouver',
            'status' => PendingSignup::STATUS_PENDING_VERIFICATION,
            'verification_token_hash' => hash('sha256', $this->plain),
            'verification_expires_at' => now()->addHours(24),
        ]);
    }

    public function test_verifying_marks_the_row_and_redirects_to_a_signed_activating_url(): void
    {
        $response = $this->get('/signup/verify/'.$this->plain);

        $pending = $this->pending->fresh();
        $this->assertSame(PendingSignup::STATUS_VERIFIED, $pending->status);
        $this->assertNotNull($pending->verified_at);
        $this->assertNotNull($pending->next_push_at);
        $this->assertNull($pending->verification_token_hash);
        Http::assertNothingSent(); // provisioning belongs to the worker only

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/signup/activating/'.$pending->uuid, $location);
        $this->assertStringContainsString('signature=', $location);

        // The signed URL works; the same URL without its signature is 403.
        $this->get($location)->assertOk();
        $this->get('/signup/activating/'.$pending->uuid)->assertForbidden();
    }

    public function test_the_same_token_twice_shows_the_link_error_and_changes_nothing(): void
    {
        $this->get('/signup/verify/'.$this->plain);
        $verifiedAt = $this->pending->fresh()->verified_at;

        $this->get('/signup/verify/'.$this->plain)
            ->assertOk()
            ->assertSee('Verification link is invalid or has expired.');

        $this->assertSame(PendingSignup::STATUS_VERIFIED, $this->pending->fresh()->status);
        $this->assertTrue($verifiedAt->equalTo($this->pending->fresh()->verified_at));
    }

    public function test_expired_tokens_show_the_link_error(): void
    {
        $this->pending->update(['verification_expires_at' => now()->subMinute()]);

        $this->get('/signup/verify/'.$this->plain)
            ->assertOk()
            ->assertSee('Verification link is invalid or has expired.');

        $this->assertSame(PendingSignup::STATUS_PENDING_VERIFICATION, $this->pending->fresh()->status);
    }
}
