<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'business_id'            => $this->business_id,
            'parent_id'              => $this->parent_id,
            'category_id'            => $this->category_id,
            'type'                   => $this->type,
            'type_label'             => $this->getTypeLabel(),
            'name'                   => $this->name,
            'short_name'             => $this->short_name,
            'sku'                    => $this->sku,
            'barcode'                => $this->barcode,
            'unit'                   => $this->unit,
            'package_size'           => $this->package_size,
            'description'            => $this->description,
            'selling_price'          => (float) $this->selling_price,
            'discount_price'         => $this->discount_price ? (float) $this->discount_price : null,
            'discount_amount'        => (float) $this->discount_amount,
            'discount_percentage'    => (float) $this->discount_percentage,
            'cost_price'             => (float) $this->cost_price,
            'reorder_point'          => (float) $this->reorder_point,
            'min_stock'              => (float) $this->min_stock,
            'max_stock'              => (float) $this->max_stock,
            'status'                 => $this->status,
            'status_label'           => $this->getStatusLabel(),
            'is_featured'            => (bool) $this->is_featured,
            'is_marketplace_visible' => (bool) $this->is_marketplace_visible,
            'is_sellable'            => $this->isSellable(),
            'sort_order'             => $this->sort_order,

            // ── Images (stored as metadata attribute — no separate table) ──────
            // primary_image: the first image flagged is_primary (or first overall)
            'primary_image'  => $this->primary_image,
            // images: full ordered list of all images with path, url, alt, sort_order
            'images'         => $this->images,

            // ── Relations ────────────────────────────────────────────────────
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'variants'       => ProductResource::collection($this->whenLoaded('variants')),

            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
