<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wish extends Model
{
    protected $fillable = [
        'wishlist_id',
        'sort_order',
        'title',
        'description',
        'brand',
        'product_url',
        'image_url',
        'price',
        'original_price',
        'currency',
        'is_purchased',
    ];

    protected function casts(): array
    {
        return [
            'wishlist_id' => 'integer',
            'sort_order' => 'integer',
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_purchased' => 'boolean',
        ];
    }

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Wish $wish): void {
            $wish->product_url_hash = $wish->product_url
                ? hash('sha256', $wish->product_url)
                : null;
        });
    }
}
