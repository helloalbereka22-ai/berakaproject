<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_id'      => 'required|integer|exists:orders,id',
            'destination'   => 'required|string|max:500',
            'delivery_date' => 'nullable|date',
            'status'        => 'nullable|in:pending,in_transit,delivered,failed',
            'notes'         => 'nullable|string|max:1000',
        ];
    }
}
