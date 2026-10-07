<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_batch_id' => $this->production_batch_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'returned_by' => $this->returned_by,
            'returned_at' => $this->returned_at,
            'created_at' => $this->created_at,
        ];
    }
}
