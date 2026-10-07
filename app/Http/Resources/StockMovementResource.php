<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'business_id'    => $this->business_id,
            'branch_id'      => $this->branch_id,
            'location_id'    => $this->location_id,
            'item_type'      => $this->item_type,
            'item_id'        => $this->item_id,
            'movement_type'  => $this->movement_type,
            'type_label'     => $this->getTypeLabel(),
            'direction'      => $this->direction,
            'quantity'       => (float) $this->quantity,
            'unit_cost'      => (float) $this->unit_cost,
            'total_cost'     => (float) $this->total_cost,
            'balance_after'  => (float) $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'notes'          => $this->notes,
            'created_by'     => $this->created_by,
            'creator_name'   => $this->whenLoaded('creator', fn() => $this->creator?->name),
            'moved_at'       => $this->moved_at?->toISOString(),
        ];
    }
}
