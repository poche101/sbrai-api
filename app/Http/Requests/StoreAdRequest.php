<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // swap to auth()->check() once auth is wired up
    }

    public function rules(): array
    {
        $base = [
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'type'         => ['required', 'string', 'in:product,service,property'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:2000'],
            'price'        => ['nullable', 'numeric', 'min:0'],
            'price_unit'   => ['nullable', 'string', 'max:50'],
            'location'     => ['required', 'string', 'max:255'],
            'images'       => ['required', 'array', 'min:1', 'max:5'],
            'images.*'     => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];

        // Extra rules for property listings
        if ($this->input('type') === 'property') {
            $base['property_status'] = ['required', 'string', 'in:for_rent,for_sale'];
            $base['bedrooms']        = ['nullable', 'integer', 'min:0'];
            $base['sqft']            = ['nullable', 'numeric', 'min:0'];
        }

        return $base;
    }

    public function messages(): array
    {
        return [
            'images.required'    => 'At least one image is required.',
            'images.*.image'     => 'Each file must be a valid image.',
            'images.*.max'       => 'Each image must be under 10 MB.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }

    /**
     * Return JSON errors instead of redirecting (API context).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
