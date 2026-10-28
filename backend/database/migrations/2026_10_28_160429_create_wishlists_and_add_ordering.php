<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('wishes', function (Blueprint $table): void {
            $table->unsignedBigInteger('wishlist_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        $now = now();

        $defaultWishlistId = DB::table('wishlists')->insertGetId([
            'name' => 'My Wishlist',
            'description' => 'My personal wishlist',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $position = 0;

        foreach (
            DB::table('wishes')->orderBy('id')->cursor() as $wish
        ) {
            DB::table('wishes')
                ->where('id', $wish->id)
                ->update([
                    'wishlist_id' => $defaultWishlistId,
                    'sort_order' => $position,
                ]);

            $position++;
        }

        Schema::table('wishes', function (Blueprint $table): void {
            $table->unsignedBigInteger('wishlist_id')
                ->nullable(false)
                ->change();

            $table->foreign('wishlist_id')
                ->references('id')
                ->on('wishlists')
                ->restrictOnDelete();

            $table->index(
                ['wishlist_id', 'sort_order', 'id'],
                'wishes_list_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table): void {
            $table->dropIndex('wishes_list_order_idx');
            $table->dropForeign(['wishlist_id']);
            $table->dropColumn(['wishlist_id', 'sort_order']);
        });

        Schema::dropIfExists('wishlists');
    }
};