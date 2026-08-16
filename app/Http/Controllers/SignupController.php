<?php

namespace App\Http\Controllers;

use App\Exceptions\MarketUnavailableException;
use App\Http\Requests\SignupAccountRequest;
use App\Http\Requests\SignupOrganizationRequest;
use App\Mail\VerifyEmail;
use App\Models\EmailVerificationToken;
use App\Models\LoginHandoff;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class SignupController extends Controller
{
    public function account(): View
    {
        return view('signup.account');
    }

    public function storeAccount(SignupAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Only validated fields reach the session, and the password is
        // hashed first — the plaintext must never be stored.
        $request->session()->put('signup.account', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'country_code' => $validated['country_code'],
        ]);

        return redirect()->route('signup.organization');
    }

    public function organization(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('signup.account')) {
            return redirect()->route('signup.account')
                ->with('status', 'Start by creating your account.');
        }

        return view('signup.organization');
    }

    public function store(SignupOrganizationRequest $request, TenantProvisioner $provisioner): RedirectResponse
    {
        $account = $request->session()->get('signup.account');

        if (! $account) {
            return redirect()->route('signup.account')
                ->with('status', 'Start by creating your account.');
        }

        try {
            $tenant = $provisioner->provision($account, $request->validated());
        } catch (MarketUnavailableException $e) {
            // Customer-safe message + trackable reference; never fall back
            // to a hard-coded market (C-F33/F-34).
            return back()->with('signup_error',
                "We can't open signups for your region right now. Please contact support with reference {$e->reference}.");
        }

        $request->session()->forget('signup.account');
        $request->session()->put('signup.pending_email', $tenant->user->email);

        // Outside the transaction — a mail failure must not roll back the
        // freshly provisioned tenant.
        Mail::to($tenant->user->email)->send(new VerifyEmail($tenant->user, $tenant->plainVerificationToken));

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
        $email = strtolower((string) $request->input('email'));
        // One send per address per 60 seconds, and one identical response
        // whether or not the address exists (no enumeration).
        $allowed = $email !== '' && RateLimiter::attempt('signup-resend:'.$email, 1, fn () => true, 60);

        if ($allowed) {
            $user = User::where('email', $email)->whereNull('email_verified_at')->first();

            if ($user) {
                $plain = bin2hex(random_bytes(32));
                EmailVerificationToken::create([
                    'user_id' => $user->id,
                    'token_hash' => hash('sha256', $plain),
                    'expires_at' => now()->addHours(24),
                ]);
                Mail::to($user->email)->send(new VerifyEmail($user, $plain));
            }
        }

        return back()->with('status', "If that address needs verification, we've sent a new link.");
    }

    public function verify(Request $request, string $token): View|RedirectResponse
    {
        $row = EmailVerificationToken::where('token_hash', hash('sha256', $token))->first();

        if (! $row || $row->expires_at->isPast()) {
            return view('signup.verified-error', [
                'title' => 'Verification link is invalid or has expired.',
                'showResend' => true,
            ]);
        }

        if ($row->consumed_at !== null) {
            // Never issue a second handoff for a spent link.
            return view('signup.verified-error', [
                'title' => 'This link has already been used. Log in to continue.',
                'showResend' => false,
            ]);
        }

        $plainHandoff = bin2hex(random_bytes(32));

        DB::transaction(function () use ($row, $request, $plainHandoff) {
            $row->forceFill(['consumed_at' => now()])->save();

            $user = $row->user;
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            LoginHandoff::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plainHandoff),
                'expires_at' => now()->addSeconds(60),
                'issued_ip' => $request->ip(),
                'redirect_to' => '/organizations',
            ]);
        });

        return redirect()->away(config('app.console_url').'/auth/handoff?token='.$plainHandoff);
    }
}
