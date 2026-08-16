<?php

namespace App\Services;

use App\Models\PendingSignup;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HMAC-signed HTTP client for api.binnii.com's internal intake endpoints
 * (staged-registration spec §6.2). The signature covers
 * METHOD \n PATH \n TIMESTAMP \n NONCE \n sha256(raw body), so the raw
 * body string sent MUST be exactly the string that was hashed.
 */
class SignupIntakeClient
{
    public function postSignup(PendingSignup $pending): Response
    {
        // Exactly the §6.3 contract — the api decides everything privileged
        // (plan, lifecycle, flags, ids) itself and ignores anything extra.
        $body = json_encode([
            'uuid' => $pending->uuid,
            'name' => $pending->name,
            'email' => $pending->email,
            'password_hash' => $pending->password_hash,
            'country_code' => $pending->country_code,
            'organization_name' => $pending->organization_name,
            'billing_timezone' => $pending->billing_timezone,
            'verified_at' => $pending->verified_at->utc()->toIso8601String(),
        ]);

        $url = $this->baseUrl().'/signups';

        return Http::withHeaders([
            ...$this->authHeaders('POST', $this->pathOf($url), $body),
            'Idempotency-Key' => $pending->uuid,
        ])->withBody($body, 'application/json')
            ->timeout(10)
            ->post($url);
    }

    public function getMarkets(): Response
    {
        $url = $this->baseUrl().'/markets';

        return Http::withHeaders($this->authHeaders('GET', $this->pathOf($url), ''))
            ->timeout(10)
            ->get($url);
    }

    /**
     * @return array<string, string>
     */
    public function authHeaders(string $method, string $path, string $body): array
    {
        $timestamp = (string) now()->timestamp;
        $nonce = bin2hex(random_bytes(16));

        $signature = hash_hmac('sha256', implode("\n", [
            $method, $path, $timestamp, $nonce, hash('sha256', $body),
        ]), (string) config('services.signup_intake.secret'));

        return [
            'X-Binnii-Client' => (string) config('services.signup_intake.client'),
            'X-Binnii-Timestamp' => $timestamp,
            'X-Binnii-Nonce' => $nonce,
            'X-Binnii-Signature' => $signature,
        ];
    }

    public function isConfigured(): bool
    {
        return config('services.signup_intake.url') !== ''
            && filled(config('services.signup_intake.secret'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.signup_intake.url'), '/');
    }

    private function pathOf(string $url): string
    {
        return (string) parse_url($url, PHP_URL_PATH);
    }
}
