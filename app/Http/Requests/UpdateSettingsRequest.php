<?php

namespace App\Http\Requests;

use App\Models\VendorSettings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Notifications — all optional so Flutter can send partial updates
            'new_listings'       => ['sometimes', 'boolean'],
            'price_drops'        => ['sometimes', 'boolean'],
            'messages'           => ['sometimes', 'boolean'],
            'promotions'         => ['sometimes', 'boolean'],

            // Privacy & Security
            'show_online_status' => ['sometimes', 'boolean'],
            'show_phone_number'  => ['sometimes', 'boolean'],
            'allow_messages'     => ['sometimes', 'boolean'],

            // Language & Region
            'language'  => ['sometimes', 'string', 'in:' . implode(',', VendorSettings::availableLanguages())],
            'currency'  => ['sometimes', 'string', 'in:' . implode(',', VendorSettings::availableCurrencies())],
        ];
    }

    public function messages(): array
    {
        return [
            'language.in' => 'Language must be one of: ' . implode(', ', VendorSettings::availableLanguages()),
            'currency.in' => 'Currency must be one of: ' . implode(', ', VendorSettings::availableCurrencies()),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
