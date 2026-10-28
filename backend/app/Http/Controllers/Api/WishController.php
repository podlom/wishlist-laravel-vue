<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WishController extends Controller
{
    public function index(): JsonResponse
    {
        $wishes = Wish::query()
            ->orderBy('wishlist_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($wishes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $this->validateProductUrl(
            $data['product_url'] ?? null
        );

        $wish = DB::transaction(function () use ($data): Wish {
            $data['sort_order'] = $this->nextPosition(
                (int) $data['wishlist_id']
            );

            return Wish::create($data);
        });

        return response()->json($wish, 201);
    }

    public function show(Wish $wish): JsonResponse
    {
        return response()->json($wish);
    }

    public function update(
        Request $request,
        Wish $wish
    ): JsonResponse {
        $data = $request->validate(
            $this->rules(partial: true)
        );

        if (array_key_exists('product_url', $data)) {
            $this->validateProductUrl(
                $data['product_url'],
                $wish->id
            );
        }

        $wish = DB::transaction(function () use (
            $wish,
            $data
        ): Wish {
            $destinationId = (int) (
                $data['wishlist_id'] ?? $wish->wishlist_id
            );

            if ($destinationId !== (int) $wish->wishlist_id) {
                $data['sort_order'] = $this->nextPosition(
                    $destinationId
                );
            }

            $wish->update($data);

            return $wish->refresh();
        });

        return response()->json($wish);
    }

    public function destroy(Wish $wish): Response
    {
        $wish->delete();

        return response()->noContent();
    }

    private function nextPosition(int $wishlistId): int
    {
        $max = Wish::query()
            ->where('wishlist_id', $wishlistId)
            ->max('sort_order');

        return $max === null ? 0 : (int) $max + 1;
    }

    private function validateProductUrl(
        ?string $url,
        ?int $exceptId = null
    ): void {
        if ($url === null) {
            return;
        }

        $hash = hash('sha256', $url);

        $query = Wish::query()
            ->where('product_url_hash', $hash);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'product_url' => [
                    'This product URL already exists in a wishlist.',
                ],
            ]);
        }
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'wishlist_id' => [
                $required,
                'integer',
                Rule::exists('wishlists', 'id'),
            ],
            'title' => [
                $required,
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'brand' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'product_url' => [
                'sometimes',
                'nullable',
                'url:http,https',
                'max:2048',
            ],
            'image_url' => [
                'sometimes',
                'nullable',
                'url:http,https',
                'max:2048',
            ],
            'price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
            'original_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
            'currency' => [
                'sometimes',
                'required',
                'string',
                'size:3',
            ],
            'is_purchased' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
