<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'invoice_id'            => $this->invoice_id,
            'order_id'              => $this->order_id,
            'payment_method'        => $this->payment_method,
            'amount'                => (float) $this->amount,
            'status'                => $this->status,
            'transaction_reference' => $this->transaction_reference,
            'notes'                 => $this->notes,
            'payment_date'          => $this->payment_date?->toDateString(),
            'recorded_by'           => $this->recorded_by,
            'recorded_at'           => $this->recorded_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),

            'invoice'  => $this->whenLoaded('invoice',  fn() => new InvoiceResource($this->invoice)),
            'recorder' => $this->whenLoaded('recorder', fn() => [
                'id'   => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),
        ];
    }
}
