<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const METHOD_CASH         = 'cash';
    const METHOD_BANK         = 'bank';
    const METHOD_OTHER        = 'other';

    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'invoice_id',
        'order_id',
        'payment_method',
        'amount',
        'status',
        'transaction_reference',
        'notes',
        'payment_date',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:4',
        'recorded_at' => 'datetime',
        'payment_date'=> 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
