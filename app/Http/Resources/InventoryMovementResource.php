<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'previous_quantity' => $this->previous_quantity,
            'quantity_change' => $this->quantity_change,
            'new_quantity' => $this->new_quantity,
            'reason' => $this->reason,
            'reference_number' => $this->reference_number,
            'created_at' => $this->created_at,
        ];
    }
}
