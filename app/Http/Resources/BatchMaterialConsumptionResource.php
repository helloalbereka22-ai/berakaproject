<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchMaterialConsumptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_batch_id' => $this->production_batch_id,
            'raw_material_id' => $this->raw_material_id,
            'quantity_used' => $this->quantity_used,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'lot_number' => $this->lot_number,
            'warehouse_id' => $this->warehouse_id,
            'created_at' => $this->created_at,
        ];
    }
}
