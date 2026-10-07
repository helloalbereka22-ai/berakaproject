<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'branch_id'           => $this->branch_id,
            'location_id'         => $this->location_id,
            'production_batch_id' => $this->production_batch_id,
            'product_id'          => $this->product_id,
            'quantity'            => (float) $this->quantity,
            'unit_price'          => (float) $this->unit_price,
            'cost_price'          => (float) $this->cost_price,
            'total_amount'        => (float) $this->total_amount,
            'sale_date'           => $this->sale_date?->toDateString(),
            'recorded_by'         => $this->recorded_by,
            'created_at'          => $this->created_at?->toISOString(),

            // Computed
            'gross_profit'        => round(((float)$this->unit_price - (float)$this->cost_price) * (float)$this->quantity, 4),

            'branch'   => $this->whenLoaded('branch',  fn() => new BranchResource($this->branch)),
            'product'  => $this->whenLoaded('product', fn() => new ProductResource($this->product)),
            'batch'    => $this->whenLoaded('batch',   fn() => $this->batch ? [
                'id'           => $this->batch->id,
                'batch_number' => $this->batch->batch_number,
            ] : null),
            'recorder' => $this->whenLoaded('recorder', fn() => [
                'id'   => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),
        ];
    }
}
