<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'book_id' => $this->book_id,
            'price' => (float) $this->price,
            'quantity' => (int) $this->quantity,
            'status' => $this->status,
            'seller' => new UserResource($this->whenLoaded('seller')),
            'book' => new BookResource($this->whenLoaded('book')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
