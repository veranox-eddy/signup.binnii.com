<?php

namespace Tests\Feature;

use App\Mail\VerifyEmail;
use App\Models\PendingSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignupStagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(); // ANY outbound call from the web flow is a failure
        // Stand-in for the two GRANTed columns of daycare.users (raw PDO —
        // the connection guard blocks schema writes through the query API).
        DB::connection('mysql_ro')->getPdo()->exec(
            'create table if not exists users (id integer primary key autoincrement, email varchar(190), deleted_at datetime null)'
        );
        DB::connection('mysql_ro')->getPdo()->exec('delete from users');
    }

    private function postStepOne(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/signup', [
            'name' => 'Alex Chen',
            'email' => 'alex@gmail.com',
            'password' => 'correct-horse-42-battery',
            'country_code' => 'CA',
            ...$overrides,
        ]);
    }

    public function test_step_one_stages_a_draft_row_with_no_outbound_calls(): void
    {
        $this->postStepOne()->assertRedirect('/signup/organization');

        $pending = PendingSignup::sole();
        $this->assertSame(PendingSignup::STATUS_DRAFT, $pending->status);
        $this->assertSame('alex@gmail.com', $pending->email);
        Http::assertNothingSent();
    }

    public function test_step_two_moves_the_row_to_pending_verification_and_mails_the_link(): void
    {
        Mail::fake();
        $this->postStepOne();

        $this->post('/signup/organization', [
            'organization_name' => 'Cedar Way',
            'billing_timezone' => 'America/Vancouver',
        ])->assertRedirect('/signup/check-email');

        $pending = PendingSignup::sole();
        $this->assertSame(PendingSignup::STATUS_PENDING_VERIFICATION, $pending->status);
        $this->assertSame('Cedar Way', $pending->organization_name);
        $this->assertNotNull($pending->verification_token_hash);
        $this->assertTrue($pending->verification_expires_at->between(now()->addHours(23), now()->addHours(25)));
        Mail::assertSent(VerifyEmail::class, fn (VerifyEmail $mail) => $mail->hasTo('alex@gmail.com'));
        Http::assertNothingSent();
    }

    public function test_email_existing_in_mysql_is_rejected_without_a_row(): void
    {
        DB::connection('mysql_ro')->getPdo()->exec("insert into users (email) values ('alex@gmail.com')");

        $this->from('/signup')->postStepOne()
            ->assertSessionHasErrors(['email' => 'This email is already registered. Log in instead.']);
        $this->assertSame(0, PendingSignup::count());
    }

    public function test_email_with_an_active_staged_row_is_rejected(): void
    {
        PendingSignup::create([
            'uuid' => 'b0000000-0000-0000-0000-000000000001',
            'name' => 'First Comer', 'email' => 'alex@gmail.com',
            'country_code' => 'CA', 'status' => PendingSignup::STATUS_PENDING_VERIFICATION,
        ]);

        $this->postStepOne()->assertSessionHasErrors('email');
        $this->assertSame(1, PendingSignup::count());
    }

    public function test_a_failed_old_row_never_blocks_a_fresh_registration(): void
    {
        PendingSignup::create([
            'uuid' => 'b0000000-0000-0000-0000-000000000002',
            'name' => 'Old Failure', 'email' => 'alex@gmail.com',
            'country_code' => 'CA', 'status' => PendingSignup::STATUS_FAILED,
        ]);

        $this->postStepOne()->assertRedirect('/signup/organization');
        // Partial unique index: one finished + one active row coexist.
        $this->assertSame(2, PendingSignup::where('email', 'alex@gmail.com')->count());
    }

    public function test_mysql_ro_outage_refuses_the_registration_with_a_reference(): void
    {
        DB::connection('mysql_ro')->getPdo()->exec('drop table users'); // simulate the outage

        $response = $this->from('/signup')->postStepOne();

        $response->assertRedirect('/signup');
        $this->assertStringContainsString('SGN-', session('signup_error'));
        $this->assertSame(0, PendingSignup::count());
    }

    public function test_only_a_bcrypt_hash_is_stored_and_the_session_holds_no_password(): void
    {
        $this->postStepOne(['password' => 'correct-horse-42-battery']);

        $pending = PendingSignup::sole();
        $this->assertNotSame('correct-horse-42-battery', $pending->password_hash);
        $this->assertSame('bcrypt', password_get_info($pending->password_hash)['algoName']);
        $this->assertTrue(password_verify('correct-horse-42-battery', $pending->password_hash));

        $this->assertStringNotContainsString('correct-horse-42-battery', json_encode(session()->all()));
    }
}
