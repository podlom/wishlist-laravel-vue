<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wishlist_id' => $this->wishlist_id,
            'sort_order' => $this->sort_order,
            'title' => $this->title,
            'description' => $this->description,
            'brand' => $this->brand,
            'product_url' => $this->product_url,
            'image_url' => $this->image_url,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'currency' => $this->currency,
            'is_purchased' => $this->is_purchased,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
