<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'listing_id' => $this->listing_id,
            'quantity' => (int) $this->quantity,
            'listing' => new ListingResource($this->whenLoaded('listing')),
            'subtotal' => $this->whenLoaded('listing', fn () => (float) $this->listing->price * $this->quantity),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
