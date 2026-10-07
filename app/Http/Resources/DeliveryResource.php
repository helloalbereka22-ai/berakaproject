<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'order_id'              => $this->order_id,
            'fulfilling_branch_id'  => $this->fulfilling_branch_id,
            'delivery_address_id'   => $this->delivery_address_id,
            'status'                => $this->status,
            'delivery_date'         => $this->delivery_date?->toDateString(),
            'tracking_number'       => $this->tracking_number,
            'notes'                 => $this->notes,
            'completed_at'          => $this->completed_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),

            'order'             => $this->whenLoaded('order',          fn() => new OrderResource($this->order)),
            'fulfilling_branch' => $this->whenLoaded('fulfillingBranch',fn() => new BranchResource($this->fulfillingBranch)),
            'address'           => $this->whenLoaded('address',        fn() => new AddressResource($this->address)),
        ];
    }
}
