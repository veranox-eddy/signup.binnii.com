<?php

namespace App\Services;

use App\Enums\AccessLevel;
use App\Enums\OrganizationLifecycleStatus;
use App\Enums\PaymentMethodReadiness;
use App\Enums\UserType;
use App\Models\EmailVerificationToken;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Creates the whole tenant in ONE transaction:
 * Organization (market-bound, lifecycle Active) + User (org admin,
 * unverified) + Subscription (14-day trial, entitlement snapshot, no
 * charges) + the email-verification token. The verification mail is sent by
 * the caller AFTER the transaction — a mail failure must never roll back an
 * already-provisioned tenant.
 */
class TenantProvisioner
{
    public function __construct(private MarketResolver $markets) {}

    /**
     * @param  array{name: string, email: string, password: string, country_code: string}  $account  password already hashed
     * @param  array{organization_name: string, billing_timezone: string}  $organization
     */
    public function provision(array $account, array $organization): ProvisionedTenant
    {
        return DB::transaction(function () use ($account, $organization) {
            $resolved = $this->markets->resolve($account['country_code']);
            $settings = PlatformSetting::current();

            $org = Organization::create([
                'name' => $organization['organization_name'],
                'status' => true,
                'market_id' => $resolved->market->id,
                // P-F48: trialing organizations stay Active.
                'lifecycle_status' => OrganizationLifecycleStatus::Active,
                // Transitional raw value: the onboarding_status column is
                // being removed (spec v2 dropped the F-42 gate); this write
                // disappears together with the column.
                'onboarding_status' => 'pending_first_center',
                'billing_timezone' => $organization['billing_timezone'],
                'is_test_account' => false,
            ]);

            // type/access_level are not mass-assignable in the console repo;
            // forceFill keeps the two models behaviorally identical.
            $user = new User;
            $user->forceFill([
                'organization_id' => $org->id,
                'name' => $account['name'],
                'email' => Str::lower($account['email']),
                'password' => $account['password'],
                'type' => UserType::Admin,
                'access_level' => AccessLevel::Organization,
                'is_active' => true,
                'email_verified_at' => null,
            ])->save();

            // The role must already exist (console RoleSeeder). findByName
            // throws when missing, failing the whole transaction — never
            // silently skip.
            $user->assignRole(Role::findByName('Org Admin'));

            $trialing = $settings->free_trial_enabled;

            Subscription::create([
                'organization_id' => $org->id,
                // P-F47: no forced plan selection — plan_key stays null for
                // the whole trial; limits come from the snapshot below.
                'plan_key' => null,
                'billing_cycle' => null,
                'is_trialing' => $trialing,
                'trial_started_at' => $trialing ? now() : null,
                // End of the Nth CALENDAR day in the organization's billing
                // timezone, stored UTC (C-F31).
                'trial_ends_at' => $trialing
                    ? now($organization['billing_timezone'])
                        ->addDays($settings->default_trial_length_days)
                        ->endOfDay()
                        ->utc()
                    : null,
                // Snapshots (AC-F-45): later changes to the global defaults
                // never affect already-granted trials.
                'trial_plan_key' => $trialing ? $settings->trial_plan_entitlement : null,
                'trial_days_granted' => $trialing ? $settings->default_trial_length_days : null,
                'payment_method_readiness' => PaymentMethodReadiness::NotSetUp,
            ]);

            $plainToken = bin2hex(random_bytes(32));
            EmailVerificationToken::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addHours(24),
            ]);

            return new ProvisionedTenant($org, $user, $plainToken);
        });
    }
}
