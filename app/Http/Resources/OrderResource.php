<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'business_id'     => $this->business_id,
            'status'          => $this->status,
            'status_label'    => $this->getStatusLabel(),
            'subtotal'        => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'delivery_fee'    => (float) $this->delivery_fee,
            'tax_amount'      => (float) $this->tax_amount,
            'total_amount'    => (float) $this->total_amount,
            'currency'        => $this->currency ?? 'USD',
            'notes'           => $this->notes,
            'placed_at'       => $this->placed_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),

            // Relationships — only loaded when requested
            'customer'  => $this->whenLoaded('customer',
                fn() => new CustomerResource($this->customer)
            ),
            'items'     => $this->whenLoaded('items',
                fn() => OrderItemResource::collection($this->items)
            ),
            'invoice'   => $this->whenLoaded('invoice',
                fn() => $this->invoice ? new InvoiceResource($this->invoice) : null
            ),
            'payments'  => $this->whenLoaded('payments',
                fn() => PaymentResource::collection($this->payments)
            ),
            'deliveries'=> $this->whenLoaded('deliveries',
                fn() => DeliveryResource::collection($this->deliveries)
            ),

            // Computed
            'balance_due'  => $this->whenLoaded('invoice',
                fn() => $this->invoice?->getBalanceDue() ?? (float) $this->total_amount
            ),
            'is_fully_paid'=> $this->whenLoaded('invoice',
                fn() => $this->invoice ? $this->invoice->getBalanceDue() <= 0 : false
            ),
        ];
    }
}
