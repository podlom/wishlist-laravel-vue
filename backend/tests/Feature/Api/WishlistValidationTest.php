<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createWishlist(string $name): Wishlist
    {
        return Wishlist::create([
            'name' => $name,
            'sort_order' => 0,
        ]);
    }

    private function createWish(
        Wishlist $wishlist,
        string $title,
        int $sortOrder = 0
    ): Wish {
        return Wish::create([
            'wishlist_id' => $wishlist->id,
            'title' => $title,
            'sort_order' => $sortOrder,
            'price' => 100,
            'currency' => 'UAH',
        ]);
    }

    public function test_rejects_negative_price(): void
    {
        $wishlist = $this->createWishlist('Test');

        $this->postJson('/api/wishes', [
            'wishlist_id' => $wishlist->id,
            'title' => 'Invalid Price',
            'price' => -10,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');

        $this->assertDatabaseCount('wishes', 0);
    }

    public function test_rejects_invalid_currency(): void
    {
        $wishlist = $this->createWishlist('Test');

        $this->postJson('/api/wishes', [
            'wishlist_id' => $wishlist->id,
            'title' => 'Invalid Currency',
            'currency' => 'INVALID',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');

        $this->assertDatabaseCount('wishes', 0);
    }

    public function test_rejects_invalid_product_url(): void
    {
        $wishlist = $this->createWishlist('Test');

        $this->postJson('/api/wishes', [
            'wishlist_id' => $wishlist->id,
            'title' => 'Invalid URL',
            'product_url' => 'not-a-valid-url',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_url');

        $this->assertDatabaseCount('wishes', 0);
    }

    public function test_rejects_foreign_wish_ids_when_reordering(): void
    {
        $firstList = $this->createWishlist('First List');
        $secondList = $this->createWishlist('Second List');

        $firstWish = $this->createWish($firstList, 'First');
        $foreignWish = $this->createWish($secondList, 'Foreign');

        $this->patchJson(
            "/api/wishlists/{$firstList->id}/reorder",
            [
                'wish_ids' => [
                    $foreignWish->id,
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wish_ids');

        $this->assertDatabaseHas('wishes', [
            'id' => $firstWish->id,
            'wishlist_id' => $firstList->id,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('wishes', [
            'id' => $foreignWish->id,
            'wishlist_id' => $secondList->id,
            'sort_order' => 0,
        ]);
    }

    public function test_partial_update_preserves_other_fields(): void
    {
        $wishlist = $this->createWishlist('Test');

        $wish = $this->createWish($wishlist, 'Original Title');

        $this->patchJson("/api/wishes/{$wish->id}", [
            'title' => 'Updated Title',
        ])
            ->assertOk()
            ->assertJsonPath('title', 'Updated Title')
            ->assertJsonPath('wishlist_id', $wishlist->id)
            ->assertJsonMissingPath('product_url_hash');

        $this->assertDatabaseHas('wishes', [
            'id' => $wish->id,
            'title' => 'Updated Title',
            'wishlist_id' => $wishlist->id,
            'price' => 100,
            'currency' => 'UAH',
        ]);
    }

    public function test_nested_wishlist_response_hides_internal_hash(): void
    {
        $wishlist = $this->createWishlist('Test');
        $wish = $this->createWish($wishlist, 'Test Product');

        $this->getJson("/api/wishlists/{$wishlist->id}")
            ->assertOk()
            ->assertJsonPath('wishes.0.id', $wish->id)
            ->assertJsonMissingPath('wishes.0.product_url_hash');
    }
}
