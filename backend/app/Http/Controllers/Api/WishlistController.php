<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $wishlists = Wishlist::query()
            ->withCount('wishes')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($wishlists);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $wishlist = DB::transaction(function () use ($data): Wishlist {
            $nextOrder = ((int) Wishlist::query()
                ->lockForUpdate()
                ->max('sort_order')) + 1;

            return Wishlist::create([
                ...$data,
                'sort_order' => $nextOrder,
            ]);
        });

        return response()->json($wishlist, 201);
    }

    public function show(Wishlist $wishlist): JsonResponse
    {
        $wishlist->load('wishes');

        return response()->json($wishlist);
    }

    public function update(
        Request $request,
        Wishlist $wishlist
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $wishlist->update($data);

        return response()->json($wishlist->refresh());
    }

    public function destroy(Wishlist $wishlist): Response
    {
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

    public function wishes(Wishlist $wishlist): JsonResponse
    {
        return response()->json($wishlist->wishes);
    }
}
