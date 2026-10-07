<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualityCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'production_batch_id' => $this->production_batch_id,
            'inspector_id' => $this->inspector_id,
            'inspection_date' => $this->inspection_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'approval_date' => $this->approval_date,
            'rejected_reason' => $this->rejected_reason,
            'created_at' => $this->created_at,
        ];
    }
}
