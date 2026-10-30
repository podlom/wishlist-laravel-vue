<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Http\Resources\WishResource;
use App\Models\Wish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

        return response()->json(
            WishResource::collection($wishes)->resolve()
        );
    }

    public function store(StoreWishRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->validateProductUrl(
            $data['product_url'] ?? null
        );

        $wish = DB::transaction(function () use ($data): Wish {
            $data['sort_order'] = $this->nextPosition(
                (int) $data['wishlist_id']
            );

            return Wish::create($data);
        });

        return response()->json(
            (new WishResource($wish))->resolve(),
            201
        );
    }

    public function show(Wish $wish): JsonResponse
    {
        return response()->json(
            (new WishResource($wish))->resolve()
        );
    }

    public function update(
        UpdateWishRequest $request,
        Wish $wish
    ): JsonResponse {
        $data = $request->validated();

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

        return response()->json(
            (new WishResource($wish))->resolve()
        );
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
}
