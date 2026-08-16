<?php

namespace App\Http\Requests;

use App\Models\MarketMirror;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SignupAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Duplicate-email checking is NOT a rule here: it runs in the
     * controller through EmailAvailability so that a mysql_ro outage can
     * refuse the registration with a reference instead of silently passing
     * validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc,dns', 'max:190'],
            'password' => ['required', Password::min(12)->letters()->numbers()],
            // Options come from the market mirror (['CA'] until the first
            // successful pull) — authoritative resolution stays on the api.
            'country_code' => ['required', Rule::in(MarketMirror::activeCountryCodes())],
        ];
    }
}
