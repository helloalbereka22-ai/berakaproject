<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    // ── Purchase type mirrors product domain ─────────────────────────────
    const TYPE_RAW_MATERIAL      = 'raw_material';
    const TYPE_PACKING_MATERIAL  = 'packing_material';
    const TYPE_PRODUCT           = 'product';   // covers all saleable product types

    const PURCHASE_TYPES = [
        self::TYPE_RAW_MATERIAL     => 'Raw Material',
        self::TYPE_PACKING_MATERIAL => 'Packaging Material',
        self::TYPE_PRODUCT          => 'Product',
    ];

    // ── Common raw-material units ─────────────────────────────────────────
    const RAW_MATERIAL_UNITS = ['kg', 'g', 'ton', 'liter', 'ml', 'quantity'];

    // ── Common packaging units ────────────────────────────────────────────
    const PACKAGING_UNITS = ['bottle', 'piece', 'carton', 'bag', 'cap', 'label', 'roll', 'box'];

    // ── Status ───────────────────────────────────────────────────────────
    const STATUS_COMPLETED = 'completed';
    const STATUS_VOIDED    = 'voided';

    protected $fillable = [
        'reference',
        'business_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'purchase_type',
        'category_id',
        'purchase_date',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity'      => 'decimal:2',
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
    ];

    // ── Auto-populate reference and recalculate total on every save ───────
    protected static function booted(): void
    {
        static::saving(function (Purchase $purchase) {
            if (empty($purchase->reference)) {
                $purchase->reference = 'PUR-' . strtoupper(Str::random(8));
            }
            // Backend always recalculates — never trust submitted total
            $purchase->total_price = round((float) $purchase->quantity * (float) $purchase->unit_price, 2);
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Domain helpers ────────────────────────────────────────────────────

    public function isRawMaterialPurchase(): bool
    {
        return $this->purchase_type === self::TYPE_RAW_MATERIAL;
    }

    public function isPackagingPurchase(): bool
    {
        return $this->purchase_type === self::TYPE_PACKING_MATERIAL;
    }

    public function isProductPurchase(): bool
    {
        return $this->purchase_type === self::TYPE_PRODUCT;
    }

    public function getPurchaseTypeLabel(): string
    {
        return self::PURCHASE_TYPES[$this->purchase_type] ?? ucfirst($this->purchase_type);
    }
}

