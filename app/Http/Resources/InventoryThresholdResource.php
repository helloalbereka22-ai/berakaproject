<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryThresholdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'reorder_point' => $this->reorder_point,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
