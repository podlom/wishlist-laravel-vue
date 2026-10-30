<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Wish;
use App\Models\Wishlist;
use Database\Seeders\WishSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_five_wishes_in_default_wishlist(): void
    {
        $this->seed(WishSeeder::class);

        $wishlist = Wishlist::query()->firstOrFail();

        $this->assertDatabaseCount('wishes', 5);

        $this->assertSame(
            5,
            Wish::query()
                ->where('wishlist_id', $wishlist->id)
                ->count()
        );

        $this->assertSame(
            [0, 1, 2, 3, 4],
            Wish::query()
                ->where('wishlist_id', $wishlist->id)
                ->orderBy('sort_order')
                ->pluck('sort_order')
                ->map(fn ($value): int => (int) $value)
                ->all()
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(WishSeeder::class);
        $this->seed(WishSeeder::class);

        $this->assertDatabaseCount('wishes', 5);

        $this->assertSame(
            5,
            Wish::query()
                ->distinct()
                ->count('product_url_hash')
        );
    }
}
