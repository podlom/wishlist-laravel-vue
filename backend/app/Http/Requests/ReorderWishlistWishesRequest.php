<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderWishlistWishesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wish_ids' => ['required', 'array'],
            'wish_ids.*' => [
                'required',
                'integer',
                'distinct',
                'min:1',
            ],
        ];
    }
}
