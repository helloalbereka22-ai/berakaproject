<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_number'  => $this->invoice_number,
            'order_id'        => $this->order_id,
            'customer_id'     => $this->customer_id,
            'subtotal'        => (float) $this->subtotal,
            'tax_amount'      => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount'    => (float) $this->total_amount,
            'status'          => $this->status,
            'issued_at'       => $this->issued_at?->toISOString(),
            'notes'           => $this->notes,
            'created_at'      => $this->created_at?->toISOString(),

            'balance_due'  => $this->getBalanceDue(),
            'total_paid'   => $this->getTotalPaid(),

            'order'    => $this->whenLoaded('order',   fn() => new OrderResource($this->order)),
            'customer' => $this->whenLoaded('customer',fn() => new CustomerResource($this->customer)),
            'payments' => $this->whenLoaded('payments',fn() => PaymentResource::collection($this->payments)),
        ];
    }
}
