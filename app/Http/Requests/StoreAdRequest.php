<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Core fields ────────────────────────────────────────────────
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'type'         => ['required', 'in:product,service,property'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:5000'],
            'price'        => ['required', 'numeric', 'min:0'],
            'price_unit'   => ['required', 'string', 'max:50'],
            'location'     => ['required', 'string', 'max:255'],

            // ── Images — optional, max 5 ───────────────────────────────────
            // 'nullable' allows the field to be absent entirely.
            // Without this guard, foreach($request->file('images')) throws
            // when no images are sent.
            'images'       => ['nullable', 'array', 'max:5'],
            'images.*'     => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            // ── Property-specific (only required when type = property) ──────
            'property_status' => ['nullable', 'in:for_rent,for_sale'],
            'bedrooms'        => ['nullable', 'integer', 'min:0'],
            'sqft'            => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists'  => 'The selected category does not exist.',
            'type.in'             => 'Type must be product, service, or property.',
            'images.max'          => 'You may upload a maximum of 5 images.',
            'images.*.image'      => 'Each file must be a valid image.',
            'images.*.mimes'      => 'Images must be jpeg, jpg, png, or webp.',
            'images.*.max'        => 'Each image must be under 5MB.',
        ];
    }
}
