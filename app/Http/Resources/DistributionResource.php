<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_batch_id' => $this->production_batch_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'destination' => $this->destination,
            'distributed_by' => $this->distributed_by,
            'distributed_at' => $this->distributed_at,
            'created_at' => $this->created_at,
        ];
    }
}
