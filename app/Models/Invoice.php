<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    const STATUS_PENDING   = 'pending';
    const STATUS_ISSUED    = 'issued';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'customer_id',
        'invoice_number',
        'issued_at',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'issued_at'       => 'datetime',
        'subtotal'        => 'decimal:4',
        'tax_amount'      => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount'    => 'decimal:4',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaid(): float
    {
        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getBalanceDue(): float
    {
        return max(0, (float) $this->total_amount - $this->getTotalPaid());
    }

    public static function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
