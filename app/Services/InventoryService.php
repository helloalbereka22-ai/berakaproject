<?php

namespace App\Services;

use App\Models\FinishedGood;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    // ── Core: Record a movement & update balance ──────────────────────────

    public function recordMovement(
        int $locationId,
        string $itemType,
        int $itemId,
        string $movementType,
        float $quantity,
        float $unitCost = 0.0,
        array $meta = []
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Movement quantity must be greater than zero. Got: {$quantity}");
        }

        if ($itemId <= 0) {
            throw new \InvalidArgumentException("Item ID must be greater than zero. Got: {$itemId}");
        }

        return DB::transaction(function () use (
            $locationId, $itemType, $itemId,
            $movementType, $quantity, $unitCost, $meta
        ) {
            $direction = StockMovement::directionFor($movementType);
            
            // DEBUG: Log production_output movements
            if ($movementType === 'production_output') {
                \Log::info('Recording production_output movement', [
                    'locationId' => $locationId,
                    'itemId' => $itemId,
                    'quantity' => $quantity,
                    'direction' => $direction,
                ]);
            }

            // Determine what to lock. If it's a product, lock the product.
            if ($itemType === 'product') {
                $product = \App\Models\Product::lockForUpdate()->find($itemId);
                if (!$product) {
                    throw new \RuntimeException("Product with ID {$itemId} does not exist.");
                }
                $effectiveUnitCost = $unitCost > 0 ? $unitCost : (float) ($product->cost_price ?? 0);
            } else {
                // If there are other item types in the future, fallback to no specific row lock, 
                // but use a DB lock if needed. For now, only products exist.
                $effectiveUnitCost = $unitCost;
            }

            // Calculate current balance purely from stock movements.
            // manufacturing_quantity metadata is converted to movements before transfers (see ProductTransfer).
            $movementSum = (float) StockMovement::where('location_id', $locationId)
                ->where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));

            $currentQty = max(0, $movementSum);

            // Prevent negative stock on outbound movements
            if ($direction === 'out' && $movementType !== StockMovement::TYPE_ADJUSTMENT_DECREASE) {
                if ($quantity > $currentQty) {
                    throw new \RuntimeException(
                        "Insufficient stock. Available: {$currentQty}, Requested: {$quantity}."
                    );
                }
            }

            // Calculate new balance
            $newBalance = $direction === 'in'
                ? $currentQty + $quantity
                : max(0, $currentQty - $quantity);

            // Create movement record
            $location = Location::find($locationId);
            
            // DEBUG: Log location and business/branch info
            if ($movementType === 'production_output') {
                \Log::info('Movement location info', [
                    'location_id' => $location?->id,
                    'location_name' => $location?->name,
                    'branch_id' => $location?->branch_id,
                    'business_id' => $location?->branch?->business_id,
                ]);
            }
            
            $movement = StockMovement::create([
                'business_id'    => $location?->branch?->business_id,
                'branch_id'      => $location?->branch_id,
                'location_id'    => $locationId,
                'item_type'      => $itemType,
                'item_id'        => $itemId,
                'movement_type'  => $movementType,
                'direction'      => $direction,
                'quantity'       => $quantity,
                'unit_cost'      => $effectiveUnitCost,
                'total_cost'     => round($quantity * $effectiveUnitCost, 4),
                'balance_after'  => $newBalance,
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id'   => $meta['reference_id'] ?? null,
                'notes'          => $meta['notes'] ?? null,
                'created_by'     => Auth::id(),
                'moved_at'       => now(),
            ]);

            return $movement;
        });
    }

    // ── Opening stock ─────────────────────────────────────────────────────

    public function setOpeningStock(
        int $locationId,
        string $itemType,
        int $itemId,
        float $quantity,
        float $unitCost = 0.0
    ): StockMovement {
        $existing = StockMovement::where([
            'location_id' => $locationId,
            'item_type'   => $itemType,
            'item_id'     => $itemId,
        ])->exists();

        if ($existing) {
            throw new \RuntimeException('Opening stock can only be set on a zero-balance location without prior movements.');
        }

        return $this->recordMovement(
            $locationId, $itemType, $itemId,
            StockMovement::TYPE_OPENING, $quantity, $unitCost,
            ['notes' => 'Opening stock entry']
        );
    }

    // ── Purchase receipt ──────────────────────────────────────────────────

    public function receivePurchase(
        int $locationId,
        int $productId,
        float $quantity,
        float $unitCost,
        ?int $referenceId = null,
        string $referenceType = 'purchase'
    ): StockMovement {
        return $this->recordMovement(
            $locationId, 'product', $productId,
            StockMovement::TYPE_PURCHASE, $quantity, $unitCost,
            ['reference_type' => $referenceType, 'reference_id' => $referenceId]
        );
    }

    // ── Sale ──────────────────────────────────────────────────────────────

    public function recordSale(
        int $locationId,
        int $productId,
        float $quantity,
        float $unitCost = 0.0,
        ?int $referenceId = null,
        string $referenceType = 'order'
    ): StockMovement {
        return $this->recordMovement(
            $locationId, 'product', $productId,
            StockMovement::TYPE_SALE, $quantity, $unitCost,
            ['reference_type' => $referenceType, 'reference_id' => $referenceId]
        );
    }

    // ── Sale return ───────────────────────────────────────────────────────

    public function recordSaleReturn(
        int $locationId,
        int $productId,
        float $quantity,
        float $unitCost = 0.0,
        ?int $referenceId = null
    ): StockMovement {
        return $this->recordMovement(
            $locationId, 'product', $productId,
            StockMovement::TYPE_SALE_RETURN, $quantity, $unitCost,
            ['reference_type' => 'order', 'reference_id' => $referenceId]
        );
    }

    // ── Transfer ──────────────────────────────────────────────────────────

    /**
     * Transfer stock between two locations atomically.
     * Both movements are created or neither is.
     */
    public function transfer(
        int $fromLocationId,
        int $toLocationId,
        string $itemType,
        int $itemId,
        float $quantity,
        string $notes = ''
    ): StockTransfer {
        if ($fromLocationId === $toLocationId) {
            throw new \InvalidArgumentException('Source and destination locations must differ.');
        }

        return DB::transaction(function () use (
            $fromLocationId, $toLocationId, $itemType, $itemId, $quantity, $notes
        ) {
            // Validate source has enough stock
            $available = $this->getBalance($fromLocationId, $itemType, $itemId);

            if ($available < $quantity) {
                throw new \RuntimeException(
                    "Insufficient stock at source. Available: {$available}, Requested: {$quantity}."
                );
            }

            // Get standard cost
            $unitCost = 0.0;
            if ($itemType === 'product') {
                $product = \App\Models\Product::find($itemId);
                $unitCost = (float) ($product->cost_price ?? 0);
            }
            $reference = 'TRF-' . strtoupper(Str::random(8));

            $fromLocation = Location::find($fromLocationId);

            // Create transfer record
            $transfer = StockTransfer::create([
                'business_id'      => $fromLocation?->branch?->business_id,
                'reference'        => $reference,
                'from_location_id' => $fromLocationId,
                'to_location_id'   => $toLocationId,
                'item_type'        => $itemType,
                'item_id'          => $itemId,
                'quantity'         => $quantity,
                'unit_cost'        => $unitCost,
                'status'           => StockTransfer::STATUS_COMPLETED,
                'notes'            => $notes,
                'created_by'       => Auth::id(),
                'completed_by'     => Auth::id(),
                'completed_at'     => now(),
            ]);

            // OUT from source
            $this->recordMovement(
                $fromLocationId, $itemType, $itemId,
                StockMovement::TYPE_TRANSFER_OUT, $quantity, $unitCost,
                ['reference_type' => 'StockTransfer', 'reference_id' => $transfer->id, 'notes' => $notes]
            );

            // IN to destination
            $this->recordMovement(
                $toLocationId, $itemType, $itemId,
                StockMovement::TYPE_TRANSFER_IN, $quantity, $unitCost,
                ['reference_type' => 'StockTransfer', 'reference_id' => $transfer->id, 'notes' => $notes]
            );

            AuditService::log(
                'transfer',
                'App\\Models\\StockTransfer',
                $transfer->id,
                [],
                $transfer->toArray(),
                $transfer->business_id,
                null,
                "Stock transfer {$reference}: {$quantity} units"
            );

            return $transfer;
        });
    }

    // ── Adjustment ────────────────────────────────────────────────────────

    /**
     * Apply an approved stock adjustment directly as a movement.
     */
    public function applyAdjustment(
        int $locationId,
        string $itemType,
        int $itemId,
        string $direction,
        float $quantity,
        string $reason,
        string $notes = ''
    ): StockMovement {
        if (!in_array($direction, ['increase', 'decrease'])) {
            throw new \InvalidArgumentException("Direction must be 'increase' or 'decrease'.");
        }

        return DB::transaction(function () use (
            $locationId, $itemType, $itemId, $direction, $quantity, $reason, $notes
        ) {
            $movementType = $direction === 'increase'
                ? StockMovement::TYPE_ADJUSTMENT_INCREASE
                : StockMovement::TYPE_ADJUSTMENT_DECREASE;

            $unitCost = 0.0;
            if ($itemType === 'product') {
                $product = \App\Models\Product::find($itemId);
                $unitCost = (float) ($product->cost_price ?? 0);
            }

            $movement = $this->recordMovement(
                $locationId, $itemType, $itemId,
                $movementType, $quantity, $unitCost,
                [
                    'reference_type' => 'ManualAdjustment',
                    'notes'          => "{$reason} — {$notes}",
                ]
            );

            AuditService::log(
                'adjustment',
                'App\\Models\\StockMovement',
                $movement->id,
                [],
                $movement->toArray(),
                $movement->business_id,
                $movement->branch_id,
                "Adjustment {$direction}: {$quantity} units — {$reason}"
            );

            return $movement;
        });
    }

    // ── Balance helpers ───────────────────────────────────────────────────

    public function getBalance(int $locationId, string $itemType, int $itemId): float
    {
        return (float) StockMovement::where([
            'location_id' => $locationId,
            'item_type'   => $itemType,
            'item_id'     => $itemId,
        ])->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
    }

    public function hasStock(int $locationId, int $productId, float $quantity): bool
    {
        return $this->getBalance($locationId, 'product', $productId) >= $quantity;
    }

    /**
     * Get current stock balance for a product across ALL locations in a branch.
     * Use this for branch-level stock checks (e.g. "how many books in Branch A?").
     */
    public function getBalanceForBranch(int $branchId, int $productId): float
    {
        $locationIds = Location::where('branch_id', $branchId)->pluck('id');

        if ($locationIds->isEmpty()) {
            return 0.0;
        }

        return (float) StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $productId)
            ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));
    }

    /**
     * Get stock for every saleable product in a branch, keyed by product_id.
     * Returns only products with movements — zero-stock products are excluded.
     *
     * @return array<int, float>  product_id => qty_on_hand
     */
    public function getBranchStockMap(int $branchId): array
    {
        $locationIds = Location::where('branch_id', $branchId)->pluck('id');

        if ($locationIds->isEmpty()) {
            return [];
        }

        return StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->select(
                'item_id',
                DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as qty")
            )
            ->groupBy('item_id')
            ->get()
            ->pluck('qty', 'item_id')
            ->map(fn($q) => (float) $q)
            ->toArray();
    }

    /**
     * Get a full branch inventory report: per-product purchased, sold, transferred,
     * and current available quantity — all derived from stock_movements.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getBranchInventoryReport(int $branchId, ?int $productId = null): \Illuminate\Support\Collection
    {
        $locationIds = Location::where('branch_id', $branchId)->pluck('id');

        if ($locationIds->isEmpty()) {
            return collect();
        }

        return StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->when($productId, fn($q) => $q->where('stock_movements.item_id', $productId))
            ->select(
                'products.id   as product_id',
                'products.name as product_name',
                'products.sku',
                'products.unit',
                'products.selling_price',
                'products.cost_price',
                'products.reorder_point',
                'categories.name as category_name',
                DB::raw("SUM(CASE WHEN movement_type = 'purchase'      THEN quantity ELSE 0 END) as purchased_qty"),
                DB::raw("SUM(CASE WHEN movement_type = 'transfer_in'   THEN quantity ELSE 0 END) as transferred_in"),
                DB::raw("SUM(CASE WHEN movement_type = 'sale'          THEN quantity ELSE 0 END) as sold_qty"),
                DB::raw("SUM(CASE WHEN movement_type = 'transfer_out'  THEN quantity ELSE 0 END) as transferred_out"),
                DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END)         as qty_available")
            )
            ->groupBy(
                'products.id', 'products.name', 'products.sku',
                'products.unit', 'products.selling_price', 'products.cost_price',
                'products.reorder_point', 'categories.name'
            )
            ->orderBy('products.name')
            ->get()
            ->map(fn($row) => (object) [
                'product_id'      => $row->product_id,
                'product_name'    => $row->product_name,
                'sku'             => $row->sku,
                'unit'            => $row->unit,
                'selling_price'   => (float) $row->selling_price,
                'cost_price'      => (float) $row->cost_price,
                'category_name'   => $row->category_name,
                'purchased_qty'   => (float) $row->purchased_qty,
                'transferred_in'  => (float) $row->transferred_in,
                'sold_qty'        => (float) $row->sold_qty,
                'transferred_out' => (float) $row->transferred_out,
                'qty_available'   => (float) $row->qty_available,
                'stock_value'     => round((float) $row->qty_available * (float) $row->cost_price, 2),
                'is_low_stock'    => $row->qty_available > 0 && $row->qty_available <= (float) $row->reorder_point,
                'is_out_of_stock' => $row->qty_available <= 0,
            ]);
    }

    /**
     * Check whether a branch has sufficient stock of a product across all its locations.
     */
    public function branchHasStock(int $branchId, int $productId, float $quantity): bool
    {
        return $this->getBalanceForBranch($branchId, $productId) >= $quantity;
    }

    // ── Valuation ─────────────────────────────────────────────────────────

    public function valuationByBusiness(int $businessId): array
    {
        // Get all unique location/product combos for this business
        $movements = StockMovement::where('business_id', $businessId)
            ->where('item_type', 'product')
            ->select('item_id', 'location_id', DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('item_id', 'location_id')
            ->having('qty', '>', 0)
            ->get();

        $productIds = $movements->pluck('item_id')->unique();
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        $totalValue = 0;
        $lowStock = 0;
        $outOfStock = 0; // We filtered > 0, so outOfStock based on this query alone isn't accurate.
                         // But for true valuation, outOfStock is handled separately.

        foreach ($movements as $mov) {
            $product = $products->get($mov->item_id);
            if (!$product) continue;
            
            $totalValue += $mov->qty * (float) $product->cost_price;
            if ($product->reorder_point > 0 && $mov->qty <= $product->reorder_point) {
                $lowStock++;
            }
        }

        return [
            'total_value'    => $totalValue,
            'total_items'    => $movements->count(),
            'low_stock'      => $lowStock,
            'out_of_stock'   => 0, // Hard to calculate efficiently without full join, rely on specific methods
        ];
    }

    public function valuationByLocation(int $locationId): array
    {
        // Not implemented exactly without StockLevel, typically unused or similar to above
        return [];
    }

    // ── Low-stock check ───────────────────────────────────────────────────

    public function getLowStockItems(?int $businessId = null): \Illuminate\Support\Collection
    {
        // Subquery approach or rely on materialized view. 
        // For simplicity, we query DB
        $query = StockMovement::query()
            ->when($businessId, fn($q) => $q->where('stock_movements.business_id', $businessId))
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->select('products.id', 'products.name', 'products.reorder_point', 'products.unit', 'stock_movements.location_id', DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('products.id', 'products.name', 'products.reorder_point', 'products.unit', 'stock_movements.location_id')
            ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <= products.reorder_point")
            ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) > 0");

        return $query->get()->map(function ($row) {
            return (object) [
                'item_id' => $row->id,
                'name' => $row->name,
                'quantity_on_hand' => $row->qty,
                'reorder_level' => $row->reorder_point,
                'unit' => $row->unit,
                'location_id' => $row->location_id
            ];
        });
    }

    public function getOutOfStockItems(?int $businessId = null): \Illuminate\Support\Collection
    {
        $query = StockMovement::query()
            ->when($businessId, fn($q) => $q->where('stock_movements.business_id', $businessId))
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->select('products.id', 'products.name', 'products.reorder_point', 'products.unit', 'stock_movements.location_id', DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('products.id', 'products.name', 'products.reorder_point', 'products.unit', 'stock_movements.location_id')
            ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <= 0");

        return $query->get()->map(function ($row) {
            return (object) [
                'item_id' => $row->id,
                'name' => $row->name,
                'quantity_on_hand' => $row->qty,
                'reorder_level' => $row->reorder_point,
                'unit' => $row->unit,
                'location_id' => $row->location_id
            ];
        });
    }

    // ── Reconciliation ────────────────────────────────────────────────────

    public function reconcileBalance(int $locationId, string $itemType, int $itemId): array
    {
        $movements = StockMovement::where([
            'location_id' => $locationId,
            'item_type'   => $itemType,
            'item_id'     => $itemId,
        ])->get();

        $inbound  = $movements->where('direction', 'in')->sum('quantity');
        $outbound = $movements->where('direction', 'out')->sum('quantity');
        $calculated = $inbound - $outbound;
        
        // Since we dropped StockLevel, there is no "recorded" balance that can mismatch.
        // It's always calculated.
        return [
            'recorded_balance'   => $calculated,
            'calculated_balance' => $calculated,
            'total_inbound'      => $inbound,
            'total_outbound'     => $outbound,
            'mismatch'           => false,
            'variance'           => 0,
        ];
    }
}
