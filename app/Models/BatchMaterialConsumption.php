<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchMaterialConsumption extends Model
{
    protected $table = 'batch_material_consumption';

    public $timestamps = false;

    const TYPE_RAW_MATERIAL     = 'raw_material';
    const TYPE_PACKING_MATERIAL = 'packing_material';

    protected $fillable = [
        'production_batch_id',
        'raw_material_id',      // product_id (type = raw_material or packing_material)
        'quantity_used',        // always in kg for raw materials
        'quantity_consumed',    // legacy column, keep for compatibility
        'unit_cost',
        'total_cost',
        'lot_number',
        'warehouse_id',
        'consumption_type',     // raw_material | packing_material
        'consumed_at',
        'created_at',
    ];

    protected $casts = [
        'quantity_used'     => 'decimal:4',
        'quantity_consumed' => 'decimal:4',
        'unit_cost'         => 'decimal:4',
        'total_cost'        => 'decimal:4',
        'consumed_at'       => 'datetime',
        'created_at'        => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Location::class, 'warehouse_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function isRawMaterial(): bool
    {
        return $this->consumption_type === self::TYPE_RAW_MATERIAL;
    }

    public function isPackingMaterial(): bool
    {
        return $this->consumption_type === self::TYPE_PACKING_MATERIAL;
    }

    /**
     * Effective quantity used (supports both column names).
     */
    public function getEffectiveQuantity(): float
    {
        return (float) ($this->quantity_used ?: $this->quantity_consumed ?: 0);
    }
}
