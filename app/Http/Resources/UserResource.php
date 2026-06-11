<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canSeePrivateFields = $request->user()?->id === $this->id
            || $request->user()?->is_admin
            || str_starts_with($request->path(), 'api/auth/');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($canSeePrivateFields, $this->email),
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'rating' => (float) $this->rating,
            'is_admin' => $this->when($request->user()?->is_admin, (bool) $this->is_admin),
            'stripe_account_ready' => $this->when($canSeePrivateFields, (bool) $this->stripe_account_ready),
            'email_verified_at' => $this->when($canSeePrivateFields, $this->email_verified_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
