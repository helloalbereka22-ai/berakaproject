<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'code'            => $this->code,
            'description'     => $this->description,
            'status'          => $this->status,
            'branches_count'  => $this->whenCounted('branches'),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
            'branches'        => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}
