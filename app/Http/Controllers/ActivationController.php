<?php

namespace App\Http\Controllers;

use App\Models\PendingSignup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * The "Activating your account…" waiting page (§5.4). Signed URLs only —
 * a bare uuid must never let anyone enumerate other people's status. No
 * JavaScript: polling is a <meta http-equiv="refresh"> tag. This page
 * never shows any MySQL-side data.
 */
class ActivationController extends Controller
{
    public function show(string $uuid): View|RedirectResponse
    {
        $pending = PendingSignup::where('uuid', $uuid)->firstOrFail();

        if (in_array($pending->status, [PendingSignup::STATUS_VERIFIED, PendingSignup::STATUS_PUSHING], true)) {
            $stalled = $pending->verified_at !== null && $pending->verified_at->lt(now()->subMinutes(5));
            if ($stalled) {
                Log::warning("Signup {$pending->uuid} still unprovisioned after 5 minutes.");
            }

            return view('signup.activating', ['state' => 'working', 'stalled' => $stalled]);
        }

        if ($pending->status === PendingSignup::STATUS_SYNCED) {
            if ($pending->handoff_token !== null
                && $pending->handoff_expires_at !== null
                && $pending->handoff_expires_at->isFuture()) {
                $token = $pending->handoff_token;
                // The plaintext leaves this store exactly once.
                $pending->update(['handoff_token' => null]);

                return redirect()->away(config('app.console_url').'/auth/handoff?token='.$token);
            }

            return view('signup.activating', ['state' => 'ready']);
        }

        if ($pending->status === PendingSignup::STATUS_FAILED
            && $pending->failure_reason === PendingSignup::FAILURE_EMAIL_TAKEN) {
            return view('signup.activating', ['state' => 'email_taken']);
        }

        return view('signup.activating', [
            'state' => 'failed',
            'reference' => strtoupper(substr($pending->uuid, 0, 8)),
        ]);
    }
}
