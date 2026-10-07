<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchReturn extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'production_batch_id',
        'product_id',
        'quantity',
        'reason',
        'status',
        'returned_by',
        'returned_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity'    => 'decimal:4',
        'returned_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
