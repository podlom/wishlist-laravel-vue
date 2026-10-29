<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wishlist_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('wishlists', 'id'),
            ],
            'title' => [
                'sometimes', 'required', 'string', 'max:255',
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'product_url' => [
                'sometimes', 'nullable', 'url:http,https', 'max:2048',
            ],
            'image_url' => [
                'sometimes', 'nullable', 'url:http,https', 'max:2048',
            ],
            'price' => [
                'sometimes', 'nullable', 'numeric',
                'min:0', 'max:99999999.99', 'decimal:0,2',
            ],
            'original_price' => [
                'sometimes', 'nullable', 'numeric',
                'min:0', 'max:99999999.99', 'decimal:0,2',
            ],
            'currency' => [
                'sometimes', 'required', 'string', 'size:3',
            ],
            'is_purchased' => ['sometimes', 'boolean'],
        ];
    }
}
