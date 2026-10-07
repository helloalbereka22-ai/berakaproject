<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchReconciliation extends Model
{
    const STATUS_CLEAN        = 'clean';
    const STATUS_MISMATCH     = 'mismatch';
    const STATUS_INVESTIGATING= 'investigating';
    const STATUS_RESOLVED     = 'resolved';

    protected $table = 'batch_reconciliations';

    protected $fillable = [
        'production_batch_id',
        'produced_quantity',
        'distributed_quantity',
        'sold_quantity',
        'returned_quantity',
        'remaining_quantity',
        'has_mismatch',
        'expected_quantity',
        'actual_quantity',
        'variance',
        'status',
        'investigation_notes',
        'investigated_by',
        'investigated_at',
        'reconciled_at',
    ];

    protected $casts = [
        'produced_quantity'     => 'decimal:4',
        'distributed_quantity'  => 'decimal:4',
        'sold_quantity'         => 'decimal:4',
        'returned_quantity'     => 'decimal:4',
        'remaining_quantity'    => 'decimal:4',
        'expected_quantity'     => 'decimal:4',
        'actual_quantity'       => 'decimal:4',
        'variance'              => 'decimal:4',
        'has_mismatch'          => 'boolean',
        'reconciled_at'         => 'datetime',
        'investigated_at'       => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function investigatedBy()
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    public function hasMismatch(): bool
    {
        return (bool) $this->has_mismatch;
    }

    public function requiresInvestigation(): bool
    {
        return $this->has_mismatch && $this->status === self::STATUS_MISMATCH;
    }
}
