<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturingOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'business_id'        => $this->business_id,
            'branch_id'          => $this->branch_id,
            'product_id'         => $this->product_id,
            'package_size'       => $this->package_size,
            'planned_quantity'   => (float) $this->planned_quantity,
            'status'             => $this->status,
            'supervisor_id'      => $this->supervisor_id,
            'notes'              => $this->notes,
            'planned_start_date' => $this->planned_start_date?->toDateString(),
            'planned_end_date'   => $this->planned_end_date?->toDateString(),
            'created_by'         => $this->created_by,
            'product'            => $this->whenLoaded('product', fn() => [
                'id'   => $this->product?->id,
                'name' => $this->product?->name,
                'sku'  => $this->product?->sku,
            ]),
            'business'           => $this->whenLoaded('business', fn() => [
                'id'   => $this->business?->id,
                'name' => $this->business?->name,
            ]),
            'branch'             => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'supervisor'         => $this->whenLoaded('supervisor', fn() => [
                'id'   => $this->supervisor?->id,
                'name' => $this->supervisor?->name,
            ]),
            'batches_count'      => $this->whenCounted('batches'),
            'batches'            => ProductionBatchResource::collection($this->whenLoaded('batches')),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
