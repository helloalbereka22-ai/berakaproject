<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'requestable_type' => $this->requestable_type,
            'requestable_id' => $this->requestable_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'requested_by' => $this->requested_by,
            'created_at' => $this->created_at,
        ];
    }
}
