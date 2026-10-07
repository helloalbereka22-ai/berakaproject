<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'location_id'  => 'required|integer|exists:locations,id',
            'item_type'    => 'required|string|max:100',
            'item_id'      => 'required|integer|min:1',
            'direction'    => 'required|in:in,out',
            'quantity'     => 'required|numeric|min:0.0001',
            'unit_cost'    => 'nullable|numeric|min:0',
            'reason'       => 'required|string|max:500',
            'notes'        => 'nullable|string|max:1000',
        ];
    }
}
