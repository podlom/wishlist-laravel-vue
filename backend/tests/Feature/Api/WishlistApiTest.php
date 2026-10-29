<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistApiTest extends TestCase
{
    use RefreshDatabase;

    private function createWishlist(string $name = 'Test Wishlist'): Wishlist
    {
        return Wishlist::create([
            'name' => $name,
            'sort_order' => 0,
        ]);
    }

    private function createWish(
        Wishlist $wishlist,
        string $title = 'Test Product',
        int $position = 0
    ): Wish {
        return Wish::create([
            'wishlist_id' => $wishlist->id,
            'title' => $title,
            'sort_order' => $position,
            'price' => 100,
            'currency' => 'UAH',
        ]);
    }

    public function test_can_create_a_wishlist(): void
    {
        $this->postJson('/api/wishlists', [
            'name' => 'Books',
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Books');

        $this->assertDatabaseHas('wishlists', [
            'name' => 'Books',
        ]);
    }

    public function test_can_create_a_wish_in_a_specific_list(): void
    {
        $wishlist = $this->createWishlist();

        $this->postJson('/api/wishes', [
            'wishlist_id' => $wishlist->id,
            'title' => 'New Book',
            'price' => 250,
        ])
            ->assertCreated()
            ->assertJsonPath('wishlist_id', $wishlist->id)
            ->assertJsonPath('sort_order', 0)
            ->assertJsonMissingPath('product_url_hash');

        $this->assertDatabaseHas('wishes', [
            'wishlist_id' => $wishlist->id,
            'title' => 'New Book',
        ]);
    }

    public function test_rejects_nonexistent_wishlist(): void
    {
        $this->postJson('/api/wishes', [
            'wishlist_id' => 999999,
            'title' => 'Invalid Wish',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wishlist_id');

        $this->assertDatabaseCount('wishes', 0);
    }

    public function test_can_move_a_wish_to_another_list(): void
    {
        $source = $this->createWishlist('Source');
        $target = $this->createWishlist('Target');
        $wish = $this->createWish($source);

        $this->patchJson("/api/wishes/{$wish->id}", [
            'wishlist_id' => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('wishlist_id', $target->id)
            ->assertJsonPath('sort_order', 0)
            ->assertJsonMissingPath('product_url_hash');

        $this->assertDatabaseHas('wishes', [
            'id' => $wish->id,
            'wishlist_id' => $target->id,
        ]);
    }

    public function test_can_reorder_wishes(): void
    {
        $wishlist = $this->createWishlist();

        $first = $this->createWish($wishlist, 'First', 0);
        $second = $this->createWish($wishlist, 'Second', 1);
        $third = $this->createWish($wishlist, 'Third', 2);

        $this->patchJson("/api/wishlists/{$wishlist->id}/reorder", [
            'wish_ids' => [$third->id, $first->id, $second->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.0.id', $third->id)
            ->assertJsonPath('data.0.sort_order', 0)
            ->assertJsonMissingPath('data.0.product_url_hash');

        $this->assertSame(
            [$third->id, $first->id, $second->id],
            Wish::query()
                ->where('wishlist_id', $wishlist->id)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all()
        );
    }

    public function test_rejects_duplicate_ids_when_reordering(): void
    {
        $wishlist = $this->createWishlist();
        $first = $this->createWish($wishlist, 'First', 0);
        $second = $this->createWish($wishlist, 'Second', 1);

        $this->patchJson("/api/wishlists/{$wishlist->id}/reorder", [
            'wish_ids' => [$first->id, $first->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wish_ids.0');

        $this->assertDatabaseHas('wishes', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
    }

    public function test_rejects_incomplete_reorder_payload(): void
    {
        $wishlist = $this->createWishlist();
        $first = $this->createWish($wishlist, 'First', 0);
        $second = $this->createWish($wishlist, 'Second', 1);

        $this->patchJson("/api/wishlists/{$wishlist->id}/reorder", [
            'wish_ids' => [$first->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wish_ids');

        $this->assertDatabaseHas('wishes', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
    }

    public function test_cannot_delete_nonempty_wishlist(): void
    {
        $wishlist = $this->createWishlist();
        $this->createWish($wishlist);

        $this->deleteJson("/api/wishlists/{$wishlist->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    public function test_can_delete_empty_wishlist(): void
    {
        $wishlist = $this->createWishlist();

        $this->deleteJson("/api/wishlists/{$wishlist->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }
}
