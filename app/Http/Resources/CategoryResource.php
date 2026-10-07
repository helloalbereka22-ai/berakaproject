<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'business_id' => $this->business_id,
            'parent_id'   => $this->parent_id,
            'type'        => $this->type,
            'type_label'  => $this->getTypeLabel(),
            'name'        => $this->name,
            'full_path'   => $this->getFullPath(),
            'slug'        => $this->slug,
            'description' => $this->description,
            'icon'        => $this->icon,
            'sort_order'  => $this->sort_order,
            'is_active'   => (bool) $this->is_active,
            'parent'      => new CategoryResource($this->whenLoaded('parent')),
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
            'products_count' => $this->whenCounted('products'),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
