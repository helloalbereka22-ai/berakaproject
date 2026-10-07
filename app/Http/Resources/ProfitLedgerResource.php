<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'revenue' => $this->revenue,
            'cost' => $this->cost,
            'profit' => $this->profit,
            'recorded_at' => $this->recorded_at,
            'created_at' => $this->created_at,
        ];
    }
}
