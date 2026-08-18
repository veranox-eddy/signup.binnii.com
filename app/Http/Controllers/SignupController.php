<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignupAccountRequest;
use App\Http\Requests\SignupOrganizationRequest;
use App\Mail\VerifyEmail;
use App\Models\MarketMirror;
use App\Models\PendingSignup;
use App\Services\EmailAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Staged registration (specs/saas/build-signup-staged-registration.md):
 * the form only ever writes the LOCAL SQLite staging store. MySQL rows are
 * created later by api.binnii.com's intake endpoint, after the email is
 * verified and the push worker delivers the row. This controller never
 * calls the api and never touches MySQL beyond the read-only email check.
 */
class SignupController extends Controller
{
    public function account(): View
    {
        return view('signup.account', ['countries' => $this->countryOptions()]);
    }

    public function storeAccount(SignupAccountRequest $request, EmailAvailability $availability): RedirectResponse
    {
        $validated = $request->validated();
        $email = Str::lower($validated['email']);

        // A mysql_ro outage must refuse the registration — never skip the
        // duplicate check and let the push fail later (§5.2).
        try {
            $taken = $availability->isTaken($email);
        } catch (Throwable $e) {
            $reference = 'SGN-'.Str::upper(Str::random(8));
            Log::error("Signup email check unavailable ({$reference}): ".$e->getMessage());

            return back()->withInput()->with('signup_error',
                "We can't take registrations right now. Please try again shortly or contact support with reference {$reference}.");
        }

        if ($taken) {
            return back()->withInput()->withErrors([
                'email' => 'This email is already registered. Log in instead.',
            ]);
        }

        // An abandoned step-1 draft for this email is overwritten, not
        // refused: the partial unique index allows exactly one active row
        // per address, and the person retrying IS the owner of that draft.
        // A fresh uuid keeps every attempt its own push Idempotency-Key.
        $attributes = [
            'uuid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'email' => $email,
            // Hashed immediately — the plaintext never reaches the session
            // or the database.
            'password_hash' => Hash::make($validated['password']),
            'country_code' => $validated['country_code'],
            'status' => PendingSignup::STATUS_DRAFT,
            'request_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250),
            // Step 2 has not run yet — clear anything the previous attempt left.
            'organization_name' => null,
            'billing_timezone' => null,
            'failure_reason' => null,
        ];

        $pending = PendingSignup::where('email', $email)
            ->where('status', PendingSignup::STATUS_DRAFT)
            ->first();

        if ($pending) {
            $pending->update($attributes);
        } else {
            $pending = PendingSignup::create($attributes);
        }

        $request->session()->put('signup.uuid', $pending->uuid);

        return redirect()->route('signup.organization');
    }

    public function organization(Request $request): View|RedirectResponse
    {
        if (! $this->draft($request)) {
            return redirect()->route('signup.account')->with('status', 'Start by creating your account.');
        }

        return view('signup.organization');
    }

    public function store(SignupOrganizationRequest $request): RedirectResponse
    {
        $pending = $this->draft($request);

        if (! $pending) {
            return redirect()->route('signup.account')->with('status', 'Start by creating your account.');
        }

        $plain = bin2hex(random_bytes(32));
        $pending->update([
            'organization_name' => $request->validated()['organization_name'],
            'billing_timezone' => $request->validated()['billing_timezone'],
            'status' => PendingSignup::STATUS_PENDING_VERIFICATION,
            'verification_token_hash' => hash('sha256', $plain),
            'verification_expires_at' => now()->addHours(24),
            'verification_sent_at' => now(),
        ]);

        $request->session()->forget('signup.uuid');
        $request->session()->put('signup.pending_email', $pending->email);

        Mail::to($pending->email)->send(new VerifyEmail($pending, $plain));

        return redirect()->route('signup.check-email');
    }

    public function checkEmail(Request $request): View
    {
        return view('signup.check-email', [
            'email' => $request->session()->get('signup.pending_email', $request->query('email')),
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = Str::lower((string) $request->input('email'));
        $pending = $email === '' ? null : PendingSignup::where('email', $email)
            ->where('status', PendingSignup::STATUS_PENDING_VERIFICATION)
            ->first();

        // One resend per address per 60 seconds; the response is identical
        // whether or not the address exists (no enumeration).
        if ($pending && ($pending->last_resend_at === null || $pending->last_resend_at->lte(now()->subSeconds(60)))) {
            $plain = bin2hex(random_bytes(32));
            $pending->update([
                'verification_token_hash' => hash('sha256', $plain),
                'verification_expires_at' => now()->addHours(24),
                'verification_sent_at' => now(),
                'last_resend_at' => now(),
                'resend_count' => $pending->resend_count + 1,
            ]);
            Mail::to($pending->email)->send(new VerifyEmail($pending, $plain));
        }

        return back()->with('status', "If that address needs verification, we've sent a new link.");
    }

    /**
     * Marks the row verified and hands over to the ACTIVATING page — this
     * step never touches MySQL and never calls the api: provisioning is the
     * worker's single code path (§5.3).
     */
    public function verify(Request $request, string $token): View|RedirectResponse
    {
        $pending = PendingSignup::where('verification_token_hash', hash('sha256', $token))->first();

        if (! $pending
            || $pending->status !== PendingSignup::STATUS_PENDING_VERIFICATION
            || $pending->verification_expires_at === null
            || $pending->verification_expires_at->isPast()) {
            return view('signup.verified-error', [
                'title' => 'Verification link is invalid or has expired.',
                'showResend' => true,
            ]);
        }

        $pending->update([
            'status' => PendingSignup::STATUS_VERIFIED,
            'verified_at' => now(),
            'next_push_at' => now(),
            'verification_token_hash' => null,
        ]);

        return redirect()->to(URL::temporarySignedRoute(
            'signup.activating', now()->addMinutes(30), ['uuid' => $pending->uuid]
        ));
    }

    private function draft(Request $request): ?PendingSignup
    {
        $uuid = $request->session()->get('signup.uuid');

        return $uuid
            ? PendingSignup::where('uuid', $uuid)->where('status', PendingSignup::STATUS_DRAFT)->first()
            : null;
    }

    /** @return array<int, array{code: string, name: string}> */
    private function countryOptions(): array
    {
        $markets = MarketMirror::active()->orderBy('name')->get();

        if ($markets->isEmpty()) {
            // Before the first mirror pull the form must still work.
            return [['code' => 'CA', 'name' => 'Canada']];
        }

        if ($markets->min('synced_at')?->lt(now()->subDay())) {
            Log::warning('Signup market mirror is older than 24h — still serving the last copy.');
        }

        return $markets->map(fn ($m) => ['code' => $m->country_code, 'name' => $m->name])->unique('code')->values()->all();
    }
}
