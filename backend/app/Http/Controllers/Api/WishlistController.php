<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Http\Requests\UpdateWishlistRequest;
use App\Http\Resources\WishResource;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $data = $request->validated();

        $wishlist = DB::transaction(function () use ($request, $data): Wishlist {
            $user = $request->user();

            $maxOrder = $user->wishlists()
                ->max('sort_order');

            $nextOrder = $maxOrder === null
                ? 0
                : (int) $maxOrder + 1;

            return $user->wishlists()->create([
                ...$data,
                'sort_order' => $nextOrder,
            ]);
        });

        return response()->json($wishlist, 201);
    }

    public function show(Wishlist $wishlist, Request $request): JsonResponse
    {
        $this->ensureOwner($wishlist, $request);

        $wishlist->load('wishes');

        return response()->json([
            'id' => $wishlist->id,
            'name' => $wishlist->name,
            'description' => $wishlist->description,
            'sort_order' => $wishlist->sort_order,
            'wishes' => WishResource::collection(
                $wishlist->wishes
            )->resolve(),
            'created_at' => $wishlist->created_at?->toISOString(),
            'updated_at' => $wishlist->updated_at?->toISOString(),
        ]);
    }

    public function update(
        UpdateWishlistRequest $request,
        Wishlist $wishlist
    ): JsonResponse {
        $this->ensureOwner($wishlist, $request);

        $data = $request->validated();

        $wishlist->update($data);

        return response()->json($wishlist->refresh());
    }

    public function destroy(Wishlist $wishlist, Request $request): Response
    {
        $this->ensureOwner($wishlist, $request);

        if ($wishlist->wishes()->exists()) {
            throw ValidationException::withMessages([
                'wishlist' => [
                    'Move or delete all wishes before deleting this wishlist.',
                ],
            ]);
        }

        $wishlist->delete();

        return response()->noContent();
    }

    public function wishes(Wishlist $wishlist, Request $request): JsonResponse
    {
        $this->ensureOwner($wishlist, $request);

        return response()->json(
            WishResource::collection($wishlist->wishes)->resolve()
        );
    }

    private function ensureOwner(Wishlist $wishlist, Request $request): void
    {
        abort_unless(
            (int) $wishlist->user_id === (int) $request->user()->id,
            404
        );
    }
}
