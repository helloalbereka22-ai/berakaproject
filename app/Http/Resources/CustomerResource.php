<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'created_at' => $this->created_at?->toISOString(),

            // Never expose password, remember_token, or tokens
            'addresses' => $this->whenLoaded('addresses', fn() =>
                AddressResource::collection($this->addresses)
            ),
            'orders_count' => $this->whenCounted('orders'),
        ];
    }
}
