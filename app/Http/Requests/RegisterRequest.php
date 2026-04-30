<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:vendors',
            'phone_number' => 'required|string|max:20',
            'business_name' => 'required|string|max:255',
            'nin' => 'nullable|string|size:11',
            'business_address' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'nin.size' => 'The NIN must be exactly 11 characters.',
        ];
    }
}