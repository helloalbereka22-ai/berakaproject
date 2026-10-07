<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'business_id' => 'required|integer|exists:businesses,id',
            'branch_id'   => 'required|integer|exists:branches,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'status'      => 'nullable|in:active,inactive',
        ];
    }
}
