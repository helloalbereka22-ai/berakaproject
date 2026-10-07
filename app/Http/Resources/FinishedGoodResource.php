<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinishedGoodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'production_batch_id'  => $this->production_batch_id,
            'product_id'           => $this->product_id,
            'warehouse_location_id'=> $this->warehouse_location_id,

            // Quantity accounting
            'quantity'             => (float) $this->quantity,
            'quantity_available'   => (float) $this->quantity_available,
            'quantity_distributed' => (float) $this->quantity_distributed,
            'quantity_sold'        => (float) $this->quantity_sold,
            'quantity_returned'    => (float) $this->quantity_returned,
            'quantity_remaining'   => (float) $this->getRemaining(),

            // Costing
            'unit_cost'            => (float) $this->unit_cost,
            'total_value'          => round((float) $this->quantity_available * (float) $this->unit_cost, 2),

            // Status
            'status'               => $this->status,
            'status_label'         => ucfirst($this->status),

            // Relationships
            'product'              => new ProductResource($this->whenLoaded('product')),
            'production_batch'     => new ProductionBatchResource($this->whenLoaded('productionBatch')),
            'warehouse_location'   => new LocationResource($this->whenLoaded('warehouseLocation')),

            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}
