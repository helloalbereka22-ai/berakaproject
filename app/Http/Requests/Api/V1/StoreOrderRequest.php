<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'            => 'required|integer|exists:customers,id',
            'delivery_address_id'    => 'nullable|integer|exists:addresses,id',
            'notes'                  => 'nullable|string|max:1000',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|integer|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.branch_id'      => 'nullable|integer|exists:branches,id',
        ];
    }
}
