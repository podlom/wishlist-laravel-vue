<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishProductUrlUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Wishlist $firstWishlist;

    private Wishlist $secondWishlist;

    private const PRODUCT_URL = 'https://example.com/products/necklace';

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->firstWishlist = $this->owner->wishlists()->create([
            'name' => 'Birthday',
            'sort_order' => 0,
        ]);

        $this->secondWishlist = $this->owner->wishlists()->create([
            'name' => 'Anniversary',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->owner, 'web');
    }

    private function createWish(Wishlist $wishlist): int
    {
        $response = $this->postJson('/api/wishes', [
            'wishlist_id' => $wishlist->id,
            'title' => 'Necklace',
            'product_url' => self::PRODUCT_URL,
        ])->assertCreated();

        return (int) $response->json('id');
    }

    public function test_rejects_duplicate_url_within_same_wishlist(): void
    {
        $this->createWish($this->firstWishlist);

        $this->postJson('/api/wishes', [
            'wishlist_id' => $this->firstWishlist->id,
            'title' => 'Another Necklace',
            'product_url' => self::PRODUCT_URL,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_url');

        $this->assertDatabaseCount('wishes', 1);
    }

    public function test_allows_same_url_in_different_users_wishlists(): void
    {
        Wish::create([
            'wishlist_id' => $this->firstWishlist->id,
            'title' => 'Owner Necklace',
            'product_url' => self::PRODUCT_URL,
            'sort_order' => 0,
            'currency' => 'UAH',
        ]);

        $otherUser = User::factory()->create();

        $otherWishlist = $otherUser->wishlists()->create([
            'name' => 'Other User Wishlist',
            'sort_order' => 0,
        ]);

        $this->actingAs($otherUser, 'web');

        $this->postJson('/api/wishes', [
            'wishlist_id' => $otherWishlist->id,
            'title' => 'Other User Necklace',
            'product_url' => self::PRODUCT_URL,
        ])
            ->assertCreated()
            ->assertJsonPath('wishlist_id', $otherWishlist->id);

        $this->assertDatabaseCount('wishes', 2);
    }

    public function test_rejects_move_when_destination_contains_same_url(): void
    {
        $sourceWishId = $this->createWish($this->firstWishlist);
        $this->createWish($this->secondWishlist);

        $this->patchJson("/api/wishes/{$sourceWishId}", [
            'wishlist_id' => $this->secondWishlist->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_url');

        $this->assertDatabaseHas('wishes', [
            'id' => $sourceWishId,
            'wishlist_id' => $this->firstWishlist->id,
        ]);
    }

    public function test_rejects_duplicate_url_during_update(): void
    {
        $this->createWish($this->firstWishlist);

        $secondWish = $this->postJson('/api/wishes', [
            'wishlist_id' => $this->firstWishlist->id,
            'title' => 'Another Product',
            'product_url' => 'https://example.com/products/another',
        ])->assertCreated();

        $secondWishId = (int) $secondWish->json('id');

        $this->patchJson("/api/wishes/{$secondWishId}", [
            'product_url' => self::PRODUCT_URL,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_url');

        $this->assertDatabaseHas('wishes', [
            'id' => $secondWishId,
            'product_url' => 'https://example.com/products/another',
        ]);
    }

    public function test_allows_same_url_in_two_wishlists_of_same_user(): void
    {
        $this->createWish($this->firstWishlist);
        $this->createWish($this->secondWishlist);

        $this->assertDatabaseCount('wishes', 2);
    }
}
