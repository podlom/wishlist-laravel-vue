<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderWishController extends Controller
{
    public function __invoke(
        Request $request,
        Wishlist $wishlist
    ): JsonResponse {
        $validated = $request->validate([
            'wish_ids' => ['required', 'array'],
            'wish_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);

        $orderedWishes = DB::transaction(
            function () use ($wishlist, $validated) {
                // Serialize reorder requests for this wishlist.
                Wishlist::query()
                    ->whereKey($wishlist->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $wishes = Wish::query()
                    ->where('wishlist_id', $wishlist->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);

                $existingIds = $wishes
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                $requestedIds = array_map(
                    'intval',
                    $validated['wish_ids']
                );

                $sortedExisting = $existingIds;
                $sortedRequested = $requestedIds;

                sort($sortedExisting, SORT_NUMERIC);
                sort($sortedRequested, SORT_NUMERIC);

                if ($sortedExisting !== $sortedRequested) {
                    throw ValidationException::withMessages([
                        'wish_ids' => [
                            'Provide every wish ID from this wishlist exactly once.',
                        ],
                    ]);
                }

                foreach ($requestedIds as $position => $id) {
                    Wish::query()
                        ->whereKey($id)
                        ->where('wishlist_id', $wishlist->id)
                        ->update(['sort_order' => $position]);
                }

                return Wish::query()
                    ->where('wishlist_id', $wishlist->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            },
            3
        );

        return response()->json([
            'message' => 'Wishlist reordered successfully.',
            'data' => $orderedWishes,
        ]);
    }
}
