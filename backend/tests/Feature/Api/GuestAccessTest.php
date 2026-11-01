<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_public_wishlist_listing_exposes_no_private_data(): void
    {
        $this->getJson('/api/wishlists')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_guests_cannot_read_private_wishes(): void
    {
        $this->getJson('/api/wishes')
            ->assertUnauthorized();
    }

    public function test_guests_cannot_create_wishlists(): void
    {
        $this->postJson('/api/wishlists', [
            'name' => 'Private Wishlist',
        ])->assertUnauthorized();
    }

    public function test_guests_cannot_access_wishlist_details(): void
    {
        $this->getJson('/api/wishlists/1')
            ->assertUnauthorized();
    }

    public function test_guests_cannot_reorder_wishes(): void
    {
        $this->patchJson('/api/wishlists/1/reorder', [
            'wish_ids' => [],
        ])->assertUnauthorized();
    }
}
