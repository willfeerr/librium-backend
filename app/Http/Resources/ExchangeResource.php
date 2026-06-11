<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_id' => $this->requester_id,
            'recipient_id' => $this->recipient_id,
            'offered_book_id' => $this->offered_book_id,
            'requested_book_id' => $this->requested_book_id,
            'status' => $this->status,
            'message' => $this->message,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'recipient' => new UserResource($this->whenLoaded('recipient')),
            'offered_book' => new BookResource($this->whenLoaded('offeredBook')),
            'requested_book' => new BookResource($this->whenLoaded('requestedBook')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
