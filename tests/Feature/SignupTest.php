<?php

namespace Tests\Feature;

use App\Enums\AccessLevel;
use App\Enums\MarketSource;
use App\Enums\PlanKey;
use App\Mail\VerifyEmail;
use App\Models\EmailVerificationToken;
use App\Models\LoginHandoff;
use App\Models\Market;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Org Admin', 'guard_name' => 'web']);
        PlatformSetting::forceCreate([
            'free_trial_enabled' => true,
            'default_trial_length_days' => 14,
            'trial_plan_entitlement' => PlanKey::Pro,
        ]);
        Market::create([
            'code' => 'CA', 'name' => 'Canada', 'country_code' => 'CA',
            'currency' => 'CAD', 'annual_discount_rate' => 0.800,
            'tax_rate' => 0.0500, 'tax_confirmed_at' => now(),
            'is_active' => true, 'is_fallback' => 1,
            'source' => MarketSource::LocalStub,
            'contract_version' => 'v1-cad-2026-07-23',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function postAccount(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/signup', [
            'name' => 'Alex Chen',
            'email' => 'alex@gmail.com',
            'password' => 'correct-horse-42-battery',
            'country_code' => 'CA',
            ...$overrides,
        ]);
    }

    private function completeSignup(): \Illuminate\Testing\TestResponse
    {
        $this->postAccount();

        return $this->post('/signup/organization', [
            'organization_name' => 'Sunrise Childcare Inc.',
            'billing_timezone' => 'America/Vancouver',
        ]);
    }

    public function test_signup_page_shows_two_steps_only(): void
    {
        $this->get('/signup')
            ->assertOk()
            ->assertSee('1 · Account')
            ->assertSee('2 · Organization')
            ->assertDontSee('First center');
    }

    public function test_step_one_validation(): void
    {
        $this->from('/signup')->post('/signup', [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'country_code']);

        $this->postAccount(['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->postAccount(['password' => 'short1a'])->assertSessionHasErrors('password');
        $this->postAccount(['country_code' => 'US'])->assertSessionHasErrors('country_code');

        // Duplicate email — including soft-deleted accounts.
        $this->makeExistingUser('taken@gmail.com');
        $this->postAccount(['email' => 'taken@gmail.com'])
            ->assertSessionHasErrors(['email' => 'This email is already registered. Log in instead.']);
    }

    public function test_session_never_holds_the_plaintext_password(): void
    {
        $this->postAccount(['password' => 'correct-horse-42-battery'])->assertRedirect('/signup/organization');

        $stored = session('signup.account');
        $this->assertNotSame('correct-horse-42-battery', $stored['password']);
        $this->assertTrue(password_verify('correct-horse-42-battery', $stored['password']));
    }

    public function test_step_two_requires_step_one_first(): void
    {
        $this->get('/signup/organization')->assertRedirect('/signup');
    }

    public function test_completed_signup_provisions_the_whole_tenant(): void
    {
        Mail::fake();

        $this->completeSignup()->assertRedirect('/signup/check-email');

        $organization = Organization::where('name', 'Sunrise Childcare Inc.')->sole();
        $this->assertSame('active', $organization->lifecycle_status->value);
        $this->assertSame('CA', $organization->market->code);

        $user = User::where('email', 'alex@gmail.com')->sole();
        $this->assertSame(AccessLevel::Organization, $user->access_level);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Org Admin'));

        $this->assertSame(1, EmailVerificationToken::where('user_id', $user->id)->count());
        $this->assertFalse(session()->has('signup.account'));
    }

    public function test_trial_subscription_snapshot_and_local_end_of_day(): void
    {
        $this->freezeTime();
        Mail::fake();

        $this->completeSignup();

        $subscription = Subscription::sole();
        $this->assertTrue($subscription->is_trialing);
        $this->assertNull($subscription->plan_key);
        $this->assertNull($subscription->billing_cycle);
        $this->assertSame(PlanKey::Pro, $subscription->trial_plan_key);
        $this->assertSame(14, $subscription->trial_days_granted);

        // DB timestamps carry second precision — compare to the second.
        $expected = now('America/Vancouver')->addDays(14)->endOfDay()->utc();
        $this->assertSame(
            $expected->format('Y-m-d H:i:s'),
            $subscription->trial_ends_at->utc()->format('Y-m-d H:i:s')
        );
    }

    public function test_changing_global_defaults_never_touches_existing_trials(): void
    {
        Mail::fake();
        $this->completeSignup();

        PlatformSetting::query()->update(['default_trial_length_days' => 30, 'trial_plan_entitlement' => 'go']);

        $subscription = Subscription::sole()->fresh();
        $this->assertSame(14, $subscription->trial_days_granted);
        $this->assertSame(PlanKey::Pro, $subscription->trial_plan_key);
    }

    public function test_disabled_free_trial_provisions_a_non_trial_subscription(): void
    {
        Mail::fake();
        PlatformSetting::query()->update(['free_trial_enabled' => false]);

        $this->completeSignup();

        $subscription = Subscription::sole();
        $this->assertFalse($subscription->is_trialing);
        $this->assertNull($subscription->trial_ends_at);
    }

    public function test_no_qualifying_market_rolls_back_everything_with_a_reference(): void
    {
        Mail::fake();
        Market::query()->update(['is_active' => false]);

        $this->postAccount();
        $response = $this->from('/signup/organization')->post('/signup/organization', [
            'organization_name' => 'Sunrise Childcare Inc.',
            'billing_timezone' => 'America/Vancouver',
        ]);

        $response->assertRedirect('/signup/organization');
        $this->assertStringContainsString('SGN-', session('signup_error'));
        $this->assertSame(0, Organization::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Subscription::count());
        Mail::assertNothingSent();
    }

    public function test_verification_mail_is_sent_to_the_new_admin(): void
    {
        Mail::fake();

        $this->completeSignup();

        Mail::assertSent(VerifyEmail::class, fn (VerifyEmail $mail) => $mail->hasTo('alex@gmail.com'));
    }

    public function test_verify_marks_the_email_and_issues_one_handoff(): void
    {
        Mail::fake();
        $this->completeSignup();
        $plain = $this->sentPlainToken();

        $response = $this->get('/signup/verify/'.$plain);

        $user = User::sole();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNotNull(EmailVerificationToken::sole()->consumed_at);

        $handoff = LoginHandoff::sole();
        $this->assertSame('/organizations', $handoff->redirect_to);
        $response->assertRedirect(config('app.console_url').'/auth/handoff?token='.$this->lastHandoffPlain($response));
    }

    public function test_a_used_verification_link_never_issues_a_second_handoff(): void
    {
        Mail::fake();
        $this->completeSignup();
        $plain = $this->sentPlainToken();

        $this->get('/signup/verify/'.$plain);
        $this->get('/signup/verify/'.$plain)
            ->assertOk()
            ->assertSee('This link has already been used. Log in to continue.');

        $this->assertSame(1, LoginHandoff::count());
    }

    public function test_expired_verification_links_show_the_error_page(): void
    {
        Mail::fake();
        $this->completeSignup();
        $plain = $this->sentPlainToken();
        EmailVerificationToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->get('/signup/verify/'.$plain)
            ->assertOk()
            ->assertSee('Verification link is invalid or has expired.');

        $this->assertSame(0, LoginHandoff::count());
    }

    public function test_resend_is_throttled_to_once_per_minute(): void
    {
        Mail::fake();
        $this->completeSignup();
        Mail::fake(); // reset the signup mail

        $this->post('/signup/resend', ['email' => 'alex@gmail.com'])
            ->assertSessionHas('status', "If that address needs verification, we've sent a new link.");
        $this->post('/signup/resend', ['email' => 'alex@gmail.com'])
            ->assertSessionHas('status', "If that address needs verification, we've sent a new link.");

        Mail::assertSentCount(1);
    }

    private function sentPlainToken(): string
    {
        $plain = null;
        Mail::assertSent(VerifyEmail::class, function (VerifyEmail $mail) use (&$plain) {
            $plain = $mail->plainToken;

            return true;
        });

        return $plain;
    }

    private function lastHandoffPlain($response): string
    {
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        return $query['token'];
    }

    private function makeExistingUser(string $email): User
    {
        $organization = Organization::create([
            'name' => 'Existing Org', 'status' => true,
            'market_id' => Market::sole()->id,
            'billing_timezone' => 'America/Vancouver',
        ]);

        $user = new User;
        $user->forceFill([
            'organization_id' => $organization->id,
            'name' => 'Existing Admin',
            'email' => $email,
            'password' => bcrypt('irrelevant-password-1'),
            'type' => 'admin',
            'access_level' => 'organization',
            'is_active' => true,
        ])->save();

        return $user;
    }
}
