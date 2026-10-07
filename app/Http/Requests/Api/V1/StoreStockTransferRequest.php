<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_location_id' => 'required|integer|exists:locations,id|different:to_location_id',
            'to_location_id'   => 'required|integer|exists:locations,id',
            'item_type'        => 'required|string|max:100',
            'item_id'          => 'required|integer|min:1',
            'quantity'         => 'required|numeric|min:0.0001',
            'notes'            => 'nullable|string|max:1000',
        ];
    }
}
