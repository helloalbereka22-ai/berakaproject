<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_id'         => $this->order_id,
            'product_id'       => $this->product_id,
            'business_id'      => $this->business_id,
            'branch_id'        => $this->fulfilling_branch_id,
            'quantity'         => (float) $this->quantity,
            'unit_price'       => (float) $this->unit_price,
            'original_price'   => (float) $this->original_price,
            'discount_amount'  => (float) $this->discount_amount,
            'discount_type'    => $this->discount_type,
            'total_amount'     => (float) $this->total_amount,
            'product'          => $this->whenLoaded('product',
                fn() => new ProductResource($this->product)
            ),
        ];
    }
}
