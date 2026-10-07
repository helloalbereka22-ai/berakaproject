<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_batch_id' => $this->production_batch_id,
            'produced_quantity' => $this->produced_quantity,
            'distributed_quantity' => $this->distributed_quantity,
            'sold_quantity' => $this->sold_quantity,
            'returned_quantity' => $this->returned_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'reconciled_at' => $this->reconciled_at,
        ];
    }
}
