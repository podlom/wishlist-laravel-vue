<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wishes', function (Blueprint $table): void {
            $table->unique(
                ['wishlist_id', 'product_url_hash'],
                'wishes_wishlist_product_hash_unique'
            );
        });

        Schema::table('wishes', function (Blueprint $table): void {
            $table->dropUnique('wishes_product_url_hash_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table): void {
            $table->unique(
                'product_url_hash',
                'wishes_product_url_hash_unique'
            );
        });

        Schema::table('wishes', function (Blueprint $table): void {
            $table->dropUnique('wishes_wishlist_product_hash_unique');
        });
    }
};
