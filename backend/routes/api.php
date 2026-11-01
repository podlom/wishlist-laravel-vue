<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ReorderWishController;
use App\Http\Controllers\Api\WishController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'application' => 'Wishlist API',
        'version' => '1.0.0',
    ]);
});

Route::get('/wishlists', [WishlistController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get(
        '/wishlists/{wishlist}/wishes',
        [WishlistController::class, 'wishes']
    );

    Route::patch(
        '/wishlists/{wishlist}/reorder',
        ReorderWishController::class
    );

    Route::apiResource('wishlists', WishlistController::class)
        ->except(['index']);

    Route::apiResource('wishes', WishController::class);
});
