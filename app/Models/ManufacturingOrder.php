<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ManufacturingOrder extends Model
{
    const STATUS_PENDING     = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_CANCELLED   = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING     => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED   => 'Completed',
        self::STATUS_CANCELLED   => 'Cancelled',
    ];

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'package_size',        // added in extend migration
        'planned_quantity',
        'status',
        'planned_start_date',
        'planned_end_date',
        'supervisor_id',       // added in extend migration
        'notes',               // added in extend migration
        'created_by',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date'   => 'date',
        'planned_quantity'   => 'decimal:4',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches()
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function activeBatch()
    {
        return $this->hasOne(ProductionBatch::class)
            ->whereIn('status', [
                ProductionBatch::STATUS_OPEN,
                ProductionBatch::STATUS_IN_QC,
                ProductionBatch::STATUS_APPROVED,
            ])
            ->latestOfMany();
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $q, int $businessId): Builder
    {
        return $q->where('business_id', $businessId);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function canOpenBatch(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }
}
