<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_attribute_id' => $this->product_attribute_id,
            'value' => $this->value,
            'created_at' => $this->created_at,
        ];
    }
}
