<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StockMovement extends Model
{
    public $timestamps = false;

    // ── Movement types ───────────────────────────────────────────────────
    const TYPE_OPENING              = 'opening';
    const TYPE_PURCHASE             = 'purchase';
    const TYPE_PRODUCTION_OUTPUT    = 'production_output';
    const TYPE_PRODUCTION_CONSUMPTION = 'production_consumption';
    const TYPE_PACKING_CONSUMPTION  = 'packing_consumption';
    const TYPE_SALE                 = 'sale';
    const TYPE_SALE_RETURN          = 'sale_return';
    const TYPE_PURCHASE_RETURN      = 'purchase_return';
    const TYPE_TRANSFER_IN          = 'transfer_in';
    const TYPE_TRANSFER_OUT         = 'transfer_out';
    const TYPE_ADJUSTMENT_INCREASE  = 'adjustment_increase';
    const TYPE_ADJUSTMENT_DECREASE  = 'adjustment_decrease';
    const TYPE_DISTRIBUTION         = 'distribution';

    const INBOUND_TYPES = [
        self::TYPE_OPENING,
        self::TYPE_PURCHASE,
        self::TYPE_PRODUCTION_OUTPUT,
        self::TYPE_SALE_RETURN,
        self::TYPE_PURCHASE_RETURN,
        self::TYPE_TRANSFER_IN,
        self::TYPE_ADJUSTMENT_INCREASE,
    ];

    const OUTBOUND_TYPES = [
        self::TYPE_PRODUCTION_CONSUMPTION,
        self::TYPE_PACKING_CONSUMPTION,
        self::TYPE_SALE,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_ADJUSTMENT_DECREASE,
        self::TYPE_DISTRIBUTION,
    ];

    // ── Movement types that reduce available quantity ──────────────────
    // Used by manufacturing and transfer modules to calculate available stock
    const AVAILABLE_QTY_EXCLUDED_INBOUND = [
        self::TYPE_PRODUCTION_CONSUMPTION,
        self::TYPE_PACKING_CONSUMPTION,
    ];

    const AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND = [
        self::TYPE_SALE,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_ADJUSTMENT_DECREASE,
        self::TYPE_DISTRIBUTION,
    ];

    const TYPE_LABELS = [
        self::TYPE_OPENING                => 'Opening Stock',
        self::TYPE_PURCHASE               => 'Purchase Receipt',
        self::TYPE_PRODUCTION_OUTPUT      => 'Production Output',
        self::TYPE_PRODUCTION_CONSUMPTION => 'Production Consumption',
        self::TYPE_PACKING_CONSUMPTION    => 'Packing Consumption',
        self::TYPE_SALE                   => 'Sale',
        self::TYPE_SALE_RETURN            => 'Sale Return',
        self::TYPE_PURCHASE_RETURN        => 'Purchase Return',
        self::TYPE_TRANSFER_IN            => 'Transfer In',
        self::TYPE_TRANSFER_OUT           => 'Transfer Out',
        self::TYPE_ADJUSTMENT_INCREASE    => 'Adjustment (Increase)',
        self::TYPE_ADJUSTMENT_DECREASE    => 'Adjustment (Decrease)',
        self::TYPE_DISTRIBUTION           => 'Distribution',
    ];

    protected $fillable = [
        'business_id',
        'branch_id',
        'location_id',
        'item_type',
        'item_id',
        'movement_type',
        'direction',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'moved_at',
    ];

    protected $casts = [
        'quantity'      => 'decimal:4',
        'unit_cost'     => 'decimal:4',
        'total_cost'    => 'decimal:4',
        'balance_after' => 'decimal:4',
        'moved_at'      => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function item()
    {
        return $this->morphTo();
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $q, int $id): Builder
    {
        return $q->where('business_id', $id);
    }

    public function scopeForBranch(Builder $q, int $id): Builder
    {
        return $q->where('branch_id', $id);
    }

    public function scopeForLocation(Builder $q, int $id): Builder
    {
        return $q->where('location_id', $id);
    }

    public function scopeForProduct(Builder $q, int $productId): Builder
    {
        return $q->where('item_type', 'product')->where('item_id', $productId);
    }

    public function scopeInbound(Builder $q): Builder
    {
        return $q->where('direction', 'in');
    }

    public function scopeOutbound(Builder $q): Builder
    {
        return $q->where('direction', 'out');
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('movement_type', $type);
    }

    public function scopeDateRange(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('moved_at', [$from, $to]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->movement_type] ?? ucwords(str_replace('_', ' ', $this->movement_type));
    }

    public static function directionFor(string $type): string
    {
        return in_array($type, self::INBOUND_TYPES) ? 'in' : 'out';
    }
}
