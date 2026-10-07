<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'business_id'            => $this->business_id,
            'branch_id'              => $this->branch_id,
            'manufacturing_order_id' => $this->manufacturing_order_id,
            'batch_number'           => $this->batch_number,
            'status'                 => $this->status,
            'status_label'           => $this->getStatusLabel(),

            // Production quantities
            'planned_quantity'       => (float) $this->planned_quantity,
            'produced_quantity'      => (float) $this->produced_quantity,
            'oil_quantity_produced'  => (float) $this->oil_quantity_produced,
            'oil_unit'               => $this->oil_unit,
            'oil_ml'                 => $this->getOilInMl(),
            'oil_litres'             => $this->getOilInLitres(),
            'yield_percentage'       => $this->getYieldPercentage(),

            // Costing
            'raw_material_cost'      => (float) $this->raw_material_cost,
            'packing_cost'           => (float) $this->packing_cost,
            'total_cost'             => (float) $this->raw_material_cost + (float) $this->packing_cost,
            'revenue'                => (float) $this->revenue,
            'profit'                 => (float) $this->profit,

            // Relationships
            'supervisor'             => $this->whenLoaded('supervisor', fn() => [
                'id'   => $this->supervisor?->id,
                'name' => $this->supervisor?->name,
            ]),
            'manufacturing_order'    => $this->whenLoaded('manufacturingOrder', fn() => [
                'id'          => $this->manufacturingOrder->id,
                'product'     => optional($this->manufacturingOrder->product)->name,
                'package_size'=> $this->manufacturingOrder->package_size,
            ]),
            'quality_check'          => $this->whenLoaded('qualityCheck', fn() =>
                $this->qualityCheck ? [
                    'status'         => $this->qualityCheck->status,
                    'inspection_date'=> $this->qualityCheck->inspection_date?->toDateString(),
                    'remarks'        => $this->qualityCheck->remarks,
                ] : null
            ),
            'finished_good'          => $this->whenLoaded('finishedGood', fn() =>
                $this->finishedGood ? [
                    'quantity'           => (float) $this->finishedGood->quantity,
                    'quantity_available' => (float) $this->finishedGood->quantity_available,
                    'remaining'          => $this->finishedGood->getRemaining(),
                ] : null
            ),

            'notes'        => $this->notes,
            'started_at'   => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
