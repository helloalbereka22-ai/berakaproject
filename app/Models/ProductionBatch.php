<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductionBatch extends Model
{
    // ── Status constants ─────────────────────────────────────────────────
    const STATUS_OPEN      = 'open';
    const STATUS_IN_QC     = 'in_qc';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_PACKED    = 'packed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_OPEN      => 'Open',
        self::STATUS_IN_QC     => 'In QC',
        self::STATUS_APPROVED  => 'QC Approved',
        self::STATUS_REJECTED  => 'QC Rejected',
        self::STATUS_PACKED    => 'Packed',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const OIL_UNIT_ML    = 'ml';
    const OIL_UNIT_LITRE = 'litre';

    protected $fillable = [
        'business_id',
        'branch_id',
        'manufacturing_order_id',
        'batch_number',
        'planned_quantity',
        'produced_quantity',
        'oil_quantity_produced',
        'oil_unit',
        'raw_material_cost',
        'packing_cost',
        'revenue',
        'profit',
        'status',
        'supervisor_id',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'planned_quantity'      => 'decimal:4',
        'produced_quantity'     => 'decimal:4',
        'oil_quantity_produced' => 'decimal:4',
        'raw_material_cost'     => 'decimal:4',
        'packing_cost'          => 'decimal:4',
        'revenue'               => 'decimal:4',
        'profit'                => 'decimal:4',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function manufacturingOrder()
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function materialConsumptions()
    {
        return $this->hasMany(BatchMaterialConsumption::class, 'production_batch_id');
    }

    public function rawMaterialConsumptions()
    {
        return $this->hasMany(BatchMaterialConsumption::class, 'production_batch_id')
                    ->where('consumption_type', 'raw_material');
    }

    public function packingConsumptions()
    {
        return $this->hasMany(BatchMaterialConsumption::class, 'production_batch_id')
                    ->where('consumption_type', 'packing_material');
    }

    public function qualityCheck()
    {
        return $this->hasOne(QualityCheck::class, 'production_batch_id')->latestOfMany();
    }

    public function qualityChecks()
    {
        return $this->hasMany(QualityCheck::class, 'production_batch_id');
    }

    public function finishedGood()
    {
        return $this->hasOne(FinishedGood::class, 'production_batch_id');
    }

    public function returns()
    {
        return $this->hasMany(BatchReturn::class, 'production_batch_id');
    }

    public function reconciliation()
    {
        return $this->hasOne(BatchReconciliation::class, 'production_batch_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $q, int $businessId): Builder
    {
        return $q->where('business_id', $businessId);
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForRawMaterialCategory(Builder $q, int $categoryId): Builder
    {
        return $q->whereHas('rawMaterialConsumptions.rawMaterial', function ($sub) use ($categoryId) {
            $sub->where('category_id', $categoryId);
        });
    }

    // ── Computed helpers ─────────────────────────────────────────────────

    /**
     * Yield = oil output (in ml) / raw material input (kg).
     */
    public function getYieldPercentage(): float
    {
        $rawKg  = (float) $this->rawMaterialConsumptions()->sum('quantity_used');
        $oilMl  = $this->getOilInMl();
        if ($rawKg <= 0) return 0.0;
        return round(($oilMl / 1000) / $rawKg * 100, 4); // litres / kg × 100
    }

    /**
     * Return oil quantity normalised to ml.
     */
    public function getOilInMl(): float
    {
        $qty = (float) $this->oil_quantity_produced;
        return $this->oil_unit === self::OIL_UNIT_LITRE ? $qty * 1000 : $qty;
    }

    /**
     * Return oil quantity normalised to litres.
     */
    public function getOilInLitres(): float
    {
        $qty = (float) $this->oil_quantity_produced;
        return $this->oil_unit === self::OIL_UNIT_LITRE ? $qty : $qty / 1000;
    }

    /**
     * Total raw material cost = sum of consumption lines.
     */
    public function calculateRawMaterialCost(): float
    {
        return (float) $this->rawMaterialConsumptions()->sum('total_cost');
    }

    /**
     * Total packing cost = sum of packing consumption lines.
     */
    public function calculatePackingCost(): float
    {
        return (float) $this->packingConsumptions()->sum('total_cost');
    }

    /**
     * Apply the approved profit formula.
     * Profit = Oil Qty × Selling Price − Raw Material Qty × Cost/kg − Packing Cost
     */
    public function calculateProfit(float $sellingPricePerMl): float
    {
        $oilMl      = $this->getOilInMl();
        $rmCost     = $this->calculateRawMaterialCost();
        $packCost   = $this->calculatePackingCost();
        $revenue    = $oilMl * $sellingPricePerMl;

        return round($revenue - $rmCost - $packCost, 4);
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isQcApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED
            || $this->status === self::STATUS_PACKED
            || $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Generate a unique batch number.
     * Format: BATCH-YYYYMMDD-XXXX
     */
    /**
     * SECURITY FIX (M7): Use a unique random suffix instead of a sequential
     * count-based approach. The count+1 pattern has a race condition — two
     * concurrent requests read the same count and generate the same number.
     * Using a random 6-char hex suffix + date prefix gives effective uniqueness
     * while remaining traceable. A unique DB index on batch_number enforces
     * collision prevention at the database level.
     */
    public static function generateBatchNumber(): string
    {
        $date = now()->format('Ymd');

        // Retry up to 5 times on the rare chance of a hex collision
        for ($i = 0; $i < 5; $i++) {
            $suffix = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
            $number = "BATCH-{$date}-{$suffix}";

            if (!static::where('batch_number', $number)->exists()) {
                return $number;
            }
        }

        // Absolute fallback: microsecond timestamp suffix
        return 'BATCH-' . $date . '-' . strtoupper(dechex(microtime(true) * 1000));
    }
}
