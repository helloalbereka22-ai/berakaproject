<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        return [
            'category_id'  => 'sometimes|integer|exists:categories,id',
            'name'         => 'sometimes|string|max:255',
            'type'         => 'sometimes|in:finished_oil,raw_material,packing_material,book,bundle,general',
            'sku'          => "sometimes|string|max:100|unique:products,sku,{$productId}",
            'description'  => 'nullable|string',
            'selling_price'=> 'sometimes|numeric|min:0',
            'cost_price'   => 'sometimes|numeric|min:0',
            'unit'         => 'nullable|string|max:30',
            'package_size' => 'nullable|string|max:50',
            'barcode'      => 'nullable|string|max:100',
            'status'       => 'nullable|in:active,inactive,draft',
            'is_featured'  => 'nullable|boolean',
            'is_marketplace_visible' => 'nullable|boolean',
        ];
    }
}
