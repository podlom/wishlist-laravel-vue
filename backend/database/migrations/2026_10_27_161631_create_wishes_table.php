<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishes', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('brand', 100)->nullable();

            $table->string('product_url', 2048)->nullable();
            $table->char('product_url_hash', 64)->nullable()->unique();

            $table->text('image_url')->nullable();

            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('UAH');

            $table->boolean('is_purchased')->default(false);

            $table->timestamps();

            $table->index('is_purchased');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
