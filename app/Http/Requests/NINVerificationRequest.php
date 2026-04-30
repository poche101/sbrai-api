<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NINVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nin' => 'required|string|size:11',
        ];
    }

    public function messages(): array
    {
        return [
            'nin.required' => 'NIN number is required',
            'nin.size' => 'NIN must be exactly 11 characters',
        ];
    }
}