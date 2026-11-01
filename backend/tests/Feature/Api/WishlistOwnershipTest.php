<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    private Wishlist $foreignWishlist;

    private Wish $foreignWish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->foreignWishlist = $this->otherUser
            ->wishlists()
            ->create([
                'name' => 'Private Wishlist',
                'sort_order' => 0,
            ]);

        $this->foreignWish = Wish::create([
            'wishlist_id' => $this->foreignWishlist->id,
            'title' => 'Private Product',
            'sort_order' => 0,
            'currency' => 'UAH',
        ]);

        $this->actingAs($this->owner, 'web');
    }

    public function test_cannot_read_another_users_wishlist(): void
    {
        $this->getJson(
            "/api/wishlists/{$this->foreignWishlist->id}"
        )->assertNotFound();
    }

    public function test_cannot_read_another_users_wish(): void
    {
        $this->getJson(
            "/api/wishes/{$this->foreignWish->id}"
        )->assertNotFound();
    }

    public function test_cannot_modify_another_users_wish(): void
    {
        $this->patchJson(
            "/api/wishes/{$this->foreignWish->id}",
            ['title' => 'Changed']
        )->assertNotFound();

        $this->assertDatabaseHas('wishes', [
            'id' => $this->foreignWish->id,
            'title' => 'Private Product',
        ]);
    }

    public function test_cannot_delete_another_users_wish(): void
    {
        $this->deleteJson(
            "/api/wishes/{$this->foreignWish->id}"
        )->assertNotFound();

        $this->assertDatabaseHas('wishes', [
            'id' => $this->foreignWish->id,
        ]);
    }

    public function test_cannot_create_wish_in_another_users_list(): void
    {
        $this->postJson('/api/wishes', [
            'wishlist_id' => $this->foreignWishlist->id,
            'title' => 'Unauthorized Product',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wishlist_id');

        $this->assertDatabaseCount('wishes', 1);
    }

    public function test_cannot_reorder_another_users_list(): void
    {
        $this->patchJson(
            "/api/wishlists/{$this->foreignWishlist->id}/reorder",
            ['wish_ids' => [$this->foreignWish->id]]
        )->assertNotFound();
    }

    public function test_cannot_update_another_users_wishlist(): void
    {
        $this->patchJson(
            "/api/wishlists/{$this->foreignWishlist->id}",
            ['name' => 'Hijacked Wishlist']
        )->assertNotFound();

        $this->assertDatabaseHas('wishlists', [
            'id' => $this->foreignWishlist->id,
            'name' => 'Private Wishlist',
        ]);
    }

    public function test_cannot_delete_another_users_wishlist(): void
    {
        $this->deleteJson(
            "/api/wishlists/{$this->foreignWishlist->id}"
        )->assertNotFound();

        $this->assertDatabaseHas('wishlists', [
            'id' => $this->foreignWishlist->id,
        ]);
    }

    public function test_cannot_list_another_users_wishes(): void
    {
        $this->getJson(
            "/api/wishlists/{$this->foreignWishlist->id}/wishes"
        )->assertNotFound();
    }

    public function test_cannot_move_own_wish_into_another_users_list(): void
    {
        $ownWishlist = $this->owner->wishlists()->create([
            'name' => 'My Private Wishlist',
            'sort_order' => 0,
        ]);

        $ownWish = Wish::create([
            'wishlist_id' => $ownWishlist->id,
            'title' => 'My Product',
            'sort_order' => 0,
            'currency' => 'UAH',
        ]);

        $this->patchJson("/api/wishes/{$ownWish->id}", [
            'wishlist_id' => $this->foreignWishlist->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wishlist_id');

        $this->assertDatabaseHas('wishes', [
            'id' => $ownWish->id,
            'wishlist_id' => $ownWishlist->id,
        ]);
    }
}
