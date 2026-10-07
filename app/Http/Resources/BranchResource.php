<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'business_id'     => $this->business_id,
            'name'            => $this->name,
            'code'            => $this->code,
            'description'     => $this->description,
            'manager_id'      => $this->manager_id,
            'status'          => $this->status,
            'locations_count' => $this->whenCounted('locations'),
            'business'        => new BusinessResource($this->whenLoaded('business')),
            'manager'         => $this->whenLoaded('manager', fn() => [
                'id'   => $this->manager?->id,
                'name' => $this->manager?->name,
            ]),
            'locations'       => $this->whenLoaded('locations'),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
