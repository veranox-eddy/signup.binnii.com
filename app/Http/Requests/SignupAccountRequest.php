<?php

namespace App\Http\Requests;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            // Unique INCLUDING soft-deleted rows: the console's users.email
            // unique index covers them, so duplicates must fail here, not in
            // the DB.
            'email' => ['required', 'email:rfc,dns', 'max:190', Rule::unique('users', 'email')],
            // Aligned with the console project's password policy.
            'password' => ['required', Password::min(12)->letters()->numbers()],
            // Canada only this round: the US market has no approved
            // currency/tax/plan contract yet (P-F54); see spec §1.1.
            'country_code' => ['required', 'in:CA'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered. Log in instead.',
        ];
    }
}
