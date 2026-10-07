<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    const STATUS_PENDING   = 'pending';
    const STATUS_IN_TRANSIT= 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RETURNED  = 'returned';

    protected $fillable = [
        'finished_good_id',
        'source_location_id',
        'destination_branch_id',
        'destination_location_id',
        'quantity',
        'quantity_received',
        'reference',
        'notes',
        'status',
        'distributed_at',
        'received_at',
        'created_by',
    ];

    protected $casts = [
        'quantity'          => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'distributed_at'    => 'datetime',
        'received_at'       => 'datetime',
    ];

    public function finishedGood()
    {
        return $this->belongsTo(FinishedGood::class);
    }

    public function sourceLocation()
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function destinationLocation()
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Navigate back to the production batch via finished_good */
    public function getProductionBatch(): ?ProductionBatch
    {
        return $this->finishedGood?->batch;
    }
}
