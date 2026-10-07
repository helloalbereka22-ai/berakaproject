<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityCheck extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'production_batch_id',
        'inspected_by',
        'status',
        'inspection_date',
        'approval_date',
        'quantity_checked',
        'quantity_passed',
        'quantity_failed',
        'remarks',
        'rejected_reason',
        'checked_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'approval_date'   => 'date',
        'checked_at'      => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
