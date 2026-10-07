<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'invoice_id'     => 'required|integer|exists:invoices,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,other',
            'payment_date'   => 'required|date',
            'notes'          => 'nullable|string|max:1000',
        ];
    }
}
