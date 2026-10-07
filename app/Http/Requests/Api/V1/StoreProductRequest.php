<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'business_id'  => 'required|integer|exists:businesses,id',
            'category_id'  => 'required|integer|exists:categories,id',
            'name'         => 'required|string|max:255',
            'type'         => 'required|in:finished_oil,raw_material,packing_material,book,bundle,general',
            'sku'          => 'nullable|string|max:100|unique:products,sku',
            'description'  => 'nullable|string',
            'selling_price'=> 'required|numeric|min:0',
            'cost_price'   => 'required|numeric|min:0',
            'unit'         => 'nullable|string|max:30',
            'package_size' => 'nullable|string|max:50',
            'barcode'      => 'nullable|string|max:100',
            'status'       => 'nullable|in:active,inactive,draft',
            'is_featured'  => 'nullable|boolean',
            'is_marketplace_visible' => 'nullable|boolean',
        ];
    }
}
