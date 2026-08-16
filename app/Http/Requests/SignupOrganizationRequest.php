<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignupOrganizationRequest extends FormRequest
{
    /** The Canadian timezones offered on the form. */
    public const array TIMEZONES = [
        'America/Vancouver',
        'America/Edmonton',
        'America/Winnipeg',
        'America/Toronto',
        'America/Halifax',
        'America/St_Johns',
    ];

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
            'organization_name' => ['required', 'string', 'max:150'],
            'billing_timezone' => ['required', Rule::in(self::TIMEZONES)],
        ];
    }
}
