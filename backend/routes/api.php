<?php

declare(strict_types=1);

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

Route::get(
    'wishlists/{wishlist}/wishes',
    [WishlistController::class, 'wishes']
);

Route::apiResource('wishlists', WishlistController::class);

Route::apiResource('wishes', WishController::class);