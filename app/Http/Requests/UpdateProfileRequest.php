<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            // Flutter sends these four fields from the edit form
            'name'             => ['sometimes', 'string', 'max:255'],
            'phone'            => ['sometimes', 'string', 'max:20'],
            'business_name'    => ['sometimes', 'string', 'max:255'],
            'business_address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.max'          => 'Phone number must not exceed 20 characters.',
            'business_name.max'  => 'Business name must not exceed 255 characters.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
