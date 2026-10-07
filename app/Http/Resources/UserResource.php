<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'status'     => $this->status ?? 'active',
            'is_super_admin' => $this->isSuperAdmin(),
            'roles'      => $this->whenLoaded('roles', fn() =>
                $this->roles->map(fn($r) => [
                    'id'          => $r->id,
                    'name'        => $r->name,
                    'business_id' => $r->pivot->business_id,
                    'branch_id'   => $r->pivot->branch_id,
                ])
            ),
            'assigned_business_ids' => $this->assignedBusinessIds(),
            'created_at' => $this->created_at?->toISOString(),
            // Never expose: password, remember_token, tokens
        ];
    }
}
