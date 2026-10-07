<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\Business;
use App\Models\FinishedGood;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProfitLedger;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class SuperAdminDashboard extends Component
{
    // ── Filters ───────────────────────────────────────────────────────────
    public ?int $selectedBusinessId = null;
    public string $period = 'month';
    public string $dateFrom = '';
    public string $dateTo = '';

    public array $businesses = [];

    // ── Modal state ───────────────────────────────────────────────────────
    public bool $showSoldCostDetails = false;

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->hasPermission('business.view')) {
            abort(403);
        }

        $this->businesses = Business::orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        // Default period
        $this->period = 'today';

        $this->dateFrom = now()->toDateString();
        $this->dateTo   = now()->toDateString();

        $this->dispatch('refresh-dashboard');
    }

    public function updatedPeriod(): void
    {
        match ($this->period) {
            'today' => [
                $this->dateFrom,
                $this->dateTo
            ] = [
                now()->toDateString(),
                now()->toDateString()
            ],

            'week' => [
                $this->dateFrom,
                $this->dateTo
            ] = [
                now()->startOfWeek()->toDateString(),
                now()->toDateString()
            ],

            'month' => [
                $this->dateFrom,
                $this->dateTo
            ] = [
                now()->startOfMonth()->toDateString(),
                now()->toDateString()
            ],

            default => null,
        };

        $this->clearComputed();
    }

    public function applyFilter(): void
    {
        $this->clearComputed();
    }

    public function refreshDashboard(): void
    {
        $this->clearComputed();
    }

    private function clearComputed(): void
    {
        unset(
            $this->businessSummary,
            $this->branchSummaries,
            $this->purchaseReport,
            $this->manufacturingReport,
            $this->salesReport,
            $this->periodSales,
            $this->periodSoldCost,
            $this->totalSoldPurchaseCost,
            $this->soldCostDetails
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // SHARED QUERY HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function dateRange(): array
    {
        return [
            Carbon::parse($this->dateFrom)
                ->startOfDay()
                ->toDateTimeString(),

            Carbon::parse($this->dateTo)
                ->endOfDay()
                ->toDateTimeString(),
        ];
    }

    private function bizScope($query, string $col = 'business_id')
    {
        return $this->selectedBusinessId
            ? $query->where($col, $this->selectedBusinessId)
            : $query;
    }

    // ══════════════════════════════════════════════════════════════════════
    // BUSINESS SUMMARY — ALL TIME
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function businessSummary(): array
    {
        // Total products
        $totalProducts = $this->bizScope(
            Product::withoutGlobalScopes()
                ->whereIn('type', Product::SALEABLE_PRODUCT_TYPES)
        )->count();

        // Total purchased
        $totalPurchased = $this->bizScope(
            StockMovement::where('movement_type', 'purchase')
                ->join('products as p', function ($j) {
                    $j->on(
                        'stock_movements.item_id',
                        '=',
                        'p.id'
                    )->where(
                        'stock_movements.item_type',
                        '=',
                        'product'
                    );
                })
                ->whereIn(
                    'p.type',
                    Product::SALEABLE_PRODUCT_TYPES
                )
        )->sum('stock_movements.quantity');

        // Total produced
        $totalProduced = $this->bizScope(
            StockMovement::where(
                'movement_type',
                'production_output'
            )
        )->sum('quantity');

        // Total sold
        $totalSold = $this->bizScope(
            StockMovement::where(
                'movement_type',
                'sale'
            )
        )->sum('quantity');

        // Total revenue
        $totalRevenue = $this->bizScope(
            Order::where(
                'status',
                Order::STATUS_COMPLETED
            )
        )->sum('total_amount');

        // Total orders
        $totalOrders = $this->bizScope(
            Order::query()
        )->count();

        // Total profit
        $totalProfit = $this->bizScope(
            ProfitLedger::query()
        )->sum('profit');

        // ================================================================
        // SOLD COST
        // Sold Cost = Purchase/Cost Price × Quantity Sold
        // ================================================================

        // Customer orders
        $soldCostOrders = (float) $this->bizScope(
            OrderItem::query()
                ->join(
                    'orders',
                    'order_items.order_id',
                    '=',
                    'orders.id'
                )
                ->where(
                    'orders.status',
                    Order::STATUS_COMPLETED
                )
        )
            ->selectRaw(
                'SUM(order_items.quantity * order_items.cost_price_snapshot) AS total_cost'
            )
            ->value('total_cost') ?? 0;

        // POS / Branch sales
        $soldCostBranchSale = (float) (
            $this->selectedBusinessId

            ? BranchSale::query()
                ->whereIn(
                    'branch_id',
                    Branch::where(
                        'business_id',
                        $this->selectedBusinessId
                    )->pluck('id')
                )
                ->selectRaw(
                    'SUM(quantity * cost_price) AS total_cost'
                )
                ->value('total_cost')

            : BranchSale::query()
                ->selectRaw(
                    'SUM(quantity * cost_price) AS total_cost'
                )
                ->value('total_cost')
        ) ?? 0;

        $totalSoldCost =
            $soldCostOrders +
            $soldCostBranchSale;

        // ================================================================
        // TOTAL SOLD PURCHASE COST
        // ================================================================

        // Guest orders
        $guestOrders = DB::table('guest_orders')
            ->whereIn(
                'status',
                ['confirmed', 'completed']
            )
            ->get(['items']);

        $guestOrderCost = 0;

        foreach ($guestOrders as $order) {

            $items = is_array($order->items)
                ? $order->items
                : json_decode(
                    $order->items,
                    true
                );

            if (is_array($items)) {

                foreach ($items as $item) {

                    $qty = (float) (
                        $item['quantity'] ?? 0
                    );

                    $cost = (float) (
                        $item['cost_price_snapshot']
                        ?? $item['cost_price']
                        ?? 0
                    );

                    $guestOrderCost +=
                        $qty * $cost;
                }
            }
        }

        // Customer orders
        $customerOrderCost = OrderItem::query()
            ->join(
                'orders',
                'order_items.order_id',
                '=',
                'orders.id'
            )
            ->whereIn(
                'orders.status',
                [
                    'confirmed',
                    'processing',
                    'completed'
                ]
            )
            ->selectRaw(
                'SUM(order_items.quantity * order_items.cost_price_snapshot) AS total'
            )
            ->value('total') ?? 0;

        // Branch sales
        $branchSaleCost = BranchSale::query()
            ->selectRaw(
                'SUM(quantity * cost_price) AS total'
            )
            ->value('total') ?? 0;

        $totalSoldPurchaseCost =
            (float) $guestOrderCost +
            (float) $customerOrderCost +
            (float) $branchSaleCost;

        return [

            'total_products' =>
                $totalProducts,

            'total_purchased' =>
                (float) $totalPurchased,

            'total_produced' =>
                (float) $totalProduced,

            'total_sold' =>
                (float) $totalSold,

            'total_revenue' =>
                (float) $totalRevenue,

            'total_orders' =>
                $totalOrders,

            'total_profit' =>
                (float) $totalProfit,

            'total_sold_cost' =>
                (float) $totalSoldCost,

            'total_sold_purchase_cost' =>
                (float) $totalSoldPurchaseCost,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // BRANCH SUMMARIES
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function branchSummaries(): \Illuminate\Support\Collection
    {
        [$from, $to] = $this->dateRange();

        $branches = Branch::query()
            ->with('business')
            ->when(
                $this->selectedBusinessId,
                fn ($q) =>
                    $q->where(
                        'business_id',
                        $this->selectedBusinessId
                    )
            )
            ->orderBy('name')
            ->get();

        return $branches->map(
            function (Branch $branch) use ($from, $to) {

                $locationIds =
                    \App\Models\Location::where(
                        'branch_id',
                        $branch->id
                    )->pluck('id');

                $baseMovement =
                    fn () =>
                    StockMovement::whereIn(
                        'location_id',
                        $locationIds
                    )
                    ->where(
                        'item_type',
                        'product'
                    );

                // Available stock
                $availableQty =
                    (float) ($baseMovement)()
                        ->sum(
                            DB::raw(
                                "CASE
                                    WHEN direction='in'
                                    THEN quantity
                                    ELSE -quantity
                                 END"
                            )
                        );

                // Purchased
                $purchasedQty =
                    (float) ($baseMovement)()
                        ->where(
                            'movement_type',
                            'purchase'
                        )
                        ->sum('quantity');

                // Produced
                $producedQty =
                    (float) ($baseMovement)()
                        ->where(
                            'movement_type',
                            'production_output'
                        )
                        ->sum('quantity');

                // Sold
                $soldQty =
                    (float) ($baseMovement)()
                        ->where(
                            'movement_type',
                            'sale'
                        )
                        ->sum('quantity');

                // Transfers
                $transferIn =
                    (float) ($baseMovement)()
                        ->whereIn(
                            'movement_type',
                            [
                                'transfer_in',
                                'distribution'
                            ]
                        )
                        ->sum('quantity');

                $transferOut =
                    (float) ($baseMovement)()
                        ->where(
                            'movement_type',
                            'transfer_out'
                        )
                        ->sum('quantity');

                // Period sales
                $periodSales =
                    Order::forBranch($branch->id)
                        ->where(
                            'status',
                            Order::STATUS_COMPLETED
                        )
                        ->whereBetween(
                            'placed_at',
                            [$from, $to]
                        )
                        ->sum('total_amount');

                // Period orders
                $periodOrders =
                    Order::forBranch($branch->id)
                        ->whereBetween(
                            'placed_at',
                            [$from, $to]
                        )
                        ->count();

                // Products in stock
                $productsInStock =
                    ($baseMovement)()
                        ->select('item_id')
                        ->groupBy('item_id')
                        ->havingRaw(
                            "SUM(
                                CASE
                                    WHEN direction='in'
                                    THEN quantity
                                    ELSE -quantity
                                END
                            ) > 0"
                        )
                        ->distinct()
                        ->count();

                // Low stock
                $lowStockCount =
                    ($baseMovement)()
                        ->join(
                            'products as bp',
                            'stock_movements.item_id',
                            '=',
                            'bp.id'
                        )
                        ->select(
                            'stock_movements.item_id',
                            'bp.reorder_point'
                        )
                        ->groupBy(
                            'stock_movements.item_id',
                            'bp.reorder_point'
                        )
                        ->havingRaw(
                            "bp.reorder_point > 0
                             AND SUM(
                                CASE
                                    WHEN direction='in'
                                    THEN quantity
                                    ELSE -quantity
                                END
                             ) > 0
                             AND SUM(
                                CASE
                                    WHEN direction='in'
                                    THEN quantity
                                    ELSE -quantity
                                END
                             ) <= bp.reorder_point"
                        )
                        ->distinct()
                        ->count();

                return (object) [

                    'id' =>
                        $branch->id,

                    'name' =>
                        $branch->name,

                    'business_name' =>
                        optional(
                            $branch->business
                        )->name,

                    'available_qty' =>
                        $availableQty,

                    'purchased_qty' =>
                        $purchasedQty,

                    'produced_qty' =>
                        $producedQty,

                    'sold_qty' =>
                        $soldQty,

                    'transfer_in' =>
                        $transferIn,

                    'transfer_out' =>
                        $transferOut,

                    'period_sales' =>
                        (float) $periodSales,

                    'period_orders' =>
                        $periodOrders,

                    'products_in_stock' =>
                        $productsInStock,

                    'low_stock_count' =>
                        $lowStockCount,
                ];
            }
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // PURCHASE REPORT
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function purchaseReport(): array
    {
        [$from, $to] = $this->dateRange();

        $base = $this->bizScope(
            Purchase::whereBetween(
                'purchase_date',
                [
                    Carbon::parse($this->dateFrom)
                        ->toDateString(),

                    Carbon::parse($this->dateTo)
                        ->toDateString(),
                ]
            )
            ->where(
                'purchases.status',
                'completed'
            )
        );

        $byType = (clone $base)
            ->select(
                'purchase_type',
                DB::raw(
                    'COUNT(*) AS purchase_count'
                ),
                DB::raw(
                    'SUM(quantity) AS total_qty'
                ),
                DB::raw(
                    'SUM(total_price) AS total_value'
                )
            )
            ->groupBy('purchase_type')
            ->get()
            ->keyBy('purchase_type');

        $topProducts = (clone $base)
            ->join(
                'products as pp',
                'purchases.product_id',
                '=',
                'pp.id'
            )
            ->select(
                'pp.id',
                'pp.name',
                'pp.type',
                'purchases.unit',
                DB::raw(
                    'SUM(purchases.quantity) AS total_qty'
                ),
                DB::raw(
                    'SUM(purchases.total_price) AS total_value'
                )
            )
            ->groupBy(
                'pp.id',
                'pp.name',
                'pp.type',
                'purchases.unit'
            )
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();

        $totals = (clone $base)
            ->selectRaw(
                'COUNT(*) AS count,
                 SUM(quantity) AS qty,
                 SUM(total_price) AS value'
            )
            ->first();

        return [

            'by_type' =>
                $byType,

            'top_products' =>
                $topProducts,

            'total_count' =>
                (int) ($totals->count ?? 0),

            'total_qty' =>
                (float) ($totals->qty ?? 0),

            'total_value' =>
                (float) ($totals->value ?? 0),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // MANUFACTURING REPORT
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function manufacturingReport(): array
    {
        [$from, $to] = $this->dateRange();

        $batchBase = $this->bizScope(
            ProductionBatch::whereBetween(
                'started_at',
                [$from, $to]
            )
        );

        $totals = (clone $batchBase)
            ->selectRaw(
                'COUNT(*) AS batch_count,
                 SUM(oil_quantity_produced) AS total_produced,
                 SUM(raw_material_cost) AS raw_cost,
                 SUM(packing_cost) AS pack_cost,
                 SUM(revenue) AS revenue,
                 SUM(profit) AS profit'
            )
            ->first();

        $completedBatches =
            (clone $batchBase)
                ->where(
                    'status',
                    'completed'
                )
                ->with([
                    'manufacturingOrder.product'
                ])
                ->orderByDesc('completed_at')
                ->limit(10)
                ->get();

        $rawConsumed =
            $this->bizScope(
                StockMovement::where(
                    'movement_type',
                    'production_consumption'
                )
                ->whereBetween(
                    'moved_at',
                    [$from, $to]
                ),
                'business_id'
            )
            ->join(
                'products as rp',
                'stock_movements.item_id',
                '=',
                'rp.id'
            )
            ->select(
                'rp.id',
                'rp.name',
                'rp.unit',
                DB::raw(
                    'SUM(stock_movements.quantity) AS total_consumed'
                ),
                DB::raw(
                    'SUM(stock_movements.total_cost) AS total_cost'
                )
            )
            ->groupBy(
                'rp.id',
                'rp.name',
                'rp.unit'
            )
            ->orderByDesc('total_consumed')
            ->get();

        $packConsumed =
            $this->bizScope(
                StockMovement::where(
                    'movement_type',
                    'packing_consumption'
                )
                ->whereBetween(
                    'moved_at',
                    [$from, $to]
                ),
                'business_id'
            )
            ->join(
                'products as pkp',
                'stock_movements.item_id',
                '=',
                'pkp.id'
            )
            ->select(
                'pkp.id',
                'pkp.name',
                'pkp.unit',
                DB::raw(
                    'SUM(stock_movements.quantity) AS total_consumed'
                ),
                DB::raw(
                    'SUM(stock_movements.total_cost) AS total_cost'
                )
            )
            ->groupBy(
                'pkp.id',
                'pkp.name',
                'pkp.unit'
            )
            ->orderByDesc('total_consumed')
            ->get();

        return [

            'batch_count' =>
                (int) ($totals->batch_count ?? 0),

            'total_produced' =>
                (float) ($totals->total_produced ?? 0),

            'raw_cost' =>
                (float) ($totals->raw_cost ?? 0),

            'pack_cost' =>
                (float) ($totals->pack_cost ?? 0),

            'revenue' =>
                (float) ($totals->revenue ?? 0),

            'profit' =>
                (float) ($totals->profit ?? 0),

            'completed_batches' =>
                $completedBatches,

            'raw_consumed' =>
                $rawConsumed,

            'pack_consumed' =>
                $packConsumed,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // SALES REPORT
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function salesReport(): array
    {
        [$from, $to] = $this->dateRange();

        $orderBase = $this->bizScope(
            Order::where(
                'status',
                Order::STATUS_COMPLETED
            )
            ->whereBetween(
                'placed_at',
                [$from, $to]
            )
        );

        $totals = (clone $orderBase)
            ->selectRaw(
                'COUNT(*) AS order_count,
                 SUM(total_amount) AS revenue,
                 SUM(discount_amount) AS discounts'
            )
            ->first();

        $byBranch = (clone $orderBase)
            ->select(
                'branch_id',
                DB::raw(
                    'COUNT(*) AS order_count'
                ),
                DB::raw(
                    'SUM(total_amount) AS revenue'
                )
            )
            ->groupBy('branch_id')
            ->with('branch:id,name')
            ->orderByDesc('revenue')
            ->get();

        $topProducts =
            $this->bizScope(
                OrderItem::query()
                    ->join(
                        'orders',
                        'order_items.order_id',
                        '=',
                        'orders.id'
                    )
                    ->where(
                        'orders.status',
                        Order::STATUS_COMPLETED
                    )
                    ->whereBetween(
                        'orders.placed_at',
                        [$from, $to]
                    ),
                'order_items.business_id'
            )
            ->join(
                'products as sp',
                'order_items.product_id',
                '=',
                'sp.id'
            )
            ->select(
                'sp.id',
                'sp.name',
                'sp.type',
                DB::raw(
                    'SUM(order_items.quantity) AS qty_sold'
                ),
                DB::raw(
                    'SUM(order_items.total_amount) AS revenue'
                )
            )
            ->groupBy(
                'sp.id',
                'sp.name',
                'sp.type'
            )
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $dailyTrend = (clone $orderBase)
            ->select(
                DB::raw(
                    'DATE(placed_at) AS day'
                ),
                DB::raw(
                    'COUNT(*) AS orders'
                ),
                DB::raw(
                    'SUM(total_amount) AS revenue'
                )
            )
            ->groupBy(
                DB::raw('DATE(placed_at)')
            )
            ->orderBy(
                DB::raw('DATE(placed_at)')
            )
            ->get();

        $pendingPayment =
            $this->bizScope(
                Order::where(
                    'payment_status',
                    'pending'
                )
            )->count();

        $processingOrders =
            $this->bizScope(
                Order::where(
                    'status',
                    'processing'
                )
            )->count();

        return [

            'order_count' =>
                (int) ($totals->order_count ?? 0),

            'revenue' =>
                (float) ($totals->revenue ?? 0),

            'discounts' =>
                (float) ($totals->discounts ?? 0),

            'by_branch' =>
                $byBranch,

            'top_products' =>
                $topProducts,

            'daily_trend' =>
                $dailyTrend,

            'pending_payment' =>
                $pendingPayment,

            'processing_orders' =>
                $processingOrders,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // PERIOD SALES
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function periodSales(): array
    {
        $now = now();

        $biz =
            $this->selectedBusinessId;

        $rev = function (
            Carbon $from,
            Carbon $to = null
        ) use ($biz) {

            return
                (float) $this->bizScope(
                    Order::where(
                        'status',
                        Order::STATUS_COMPLETED
                    )
                    ->whereBetween(
                        'placed_at',
                        [
                            $from->startOfDay()
                                ->toDateTimeString(),

                            ($to ?? $from)
                                ->endOfDay()
                                ->toDateTimeString()
                        ]
                    )
                )->sum('total_amount')

                +

                (float) (
                    $biz

                    ? BranchSale::query()
                        ->whereIn(
                            'branch_id',
                            Branch::where(
                                'business_id',
                                $biz
                            )->pluck('id')
                        )
                        ->whereBetween(
                            'sale_date',
                            [
                                $from->toDateString(),
                                ($to ?? $from)
                                    ->toDateString()
                            ]
                        )
                        ->sum('total_amount')

                    : BranchSale::query()
                        ->whereBetween(
                            'sale_date',
                            [
                                $from->toDateString(),
                                ($to ?? $from)
                                    ->toDateString()
                            ]
                        )
                        ->sum('total_amount')
                );
        };

        $orders =
            fn (
                Carbon $from,
                Carbon $to = null
            ) =>
            $this->bizScope(
                Order::where(
                    'status',
                    Order::STATUS_COMPLETED
                )
                ->whereBetween(
                    'placed_at',
                    [
                        $from->startOfDay()
                            ->toDateTimeString(),

                        ($to ?? $from)
                            ->endOfDay()
                            ->toDateTimeString()
                    ]
                )
            )->count();

        return [

            'today_revenue' =>
                (float) $rev(
                    $now->copy()
                ),

            'today_orders' =>
                (int) $orders(
                    $now->copy()
                ),

            'weekly_revenue' =>
                (float) $rev(
                    $now->copy()->startOfWeek(),
                    $now->copy()
                ),

            'weekly_orders' =>
                (int) $orders(
                    $now->copy()->startOfWeek(),
                    $now->copy()
                ),

            'monthly_revenue' =>
                (float) $rev(
                    $now->copy()->startOfMonth(),
                    $now->copy()
                ),

            'monthly_orders' =>
                (int) $orders(
                    $now->copy()->startOfMonth(),
                    $now->copy()
                ),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // PERIOD SOLD COST
    //
    // Sold Cost = Purchase Price × Quantity Sold
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function periodSoldCost(): float
    {
        [$from, $to] =
            $this->dateRange();

        $fromCarbon =
            Carbon::parse($from);

        $toCarbon =
            Carbon::parse($to);

        // ================================================================
        // Customer Orders
        // ================================================================

        $soldCostOrders =
            (float) $this->bizScope(
                OrderItem::query()
                    ->join(
                        'orders',
                        'order_items.order_id',
                        '=',
                        'orders.id'
                    )
                    ->where(
                        'orders.status',
                        Order::STATUS_COMPLETED
                    )
                    ->whereBetween(
                        'orders.placed_at',
                        [
                            $fromCarbon
                                ->toDateTimeString(),

                            $toCarbon
                                ->toDateTimeString(),
                        ]
                    )
            )
            ->selectRaw(
                'SUM(
                    order_items.quantity
                    * order_items.cost_price_snapshot
                ) AS total_cost'
            )
            ->value('total_cost') ?? 0;

        // ================================================================
        // Direct POS / Branch Sales
        // ================================================================

        $soldCostBranchSale =
            (float) (
                $this->selectedBusinessId

                ? BranchSale::query()
                    ->whereIn(
                        'branch_id',
                        Branch::where(
                            'business_id',
                            $this->selectedBusinessId
                        )->pluck('id')
                    )
                    ->whereBetween(
                        'sale_date',
                        [
                            $fromCarbon->toDateString(),
                            $toCarbon->toDateString(),
                        ]
                    )
                    ->selectRaw(
                        'SUM(
                            quantity * cost_price
                        ) AS total_cost'
                    )
                    ->value('total_cost')

                : BranchSale::query()
                    ->whereBetween(
                        'sale_date',
                        [
                            $fromCarbon->toDateString(),
                            $toCarbon->toDateString(),
                        ]
                    )
                    ->selectRaw(
                        'SUM(
                            quantity * cost_price
                        ) AS total_cost'
                    )
                    ->value('total_cost')
            ) ?? 0;

        return
            (float) $soldCostOrders +
            (float) $soldCostBranchSale;
    }

    // ══════════════════════════════════════════════════════════════════════
    // INVENTORY AUDIT
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function inventoryAudit(): \Illuminate\Support\Collection
    {
        return StockMovement::query()
            ->where(
                'item_type',
                'product'
            )
            ->when(
                $this->selectedBusinessId,
                fn ($q) =>
                    $q->where(
                        'business_id',
                        $this->selectedBusinessId
                    )
            )
            ->join(
                'products as ap',
                'stock_movements.item_id',
                '=',
                'ap.id'
            )
            ->whereIn(
                'ap.type',
                array_merge(
                    Product::SALEABLE_PRODUCT_TYPES,
                    [
                        Product::TYPE_RAW_MATERIAL,
                        Product::TYPE_PACKING_MATERIAL
                    ]
                )
            )
            ->select(
                'stock_movements.item_id as product_id',
                'ap.name as product_name',
                'ap.type as product_type',
                'ap.unit',

                DB::raw(
                    "SUM(
                        CASE
                            WHEN movement_type='purchase'
                            THEN quantity
                            ELSE 0
                        END
                    ) AS purchased"
                ),

                DB::raw(
                    "SUM(
                        CASE
                            WHEN movement_type='production_output'
                            THEN quantity
                            ELSE 0
                        END
                    ) AS produced"
                ),

                DB::raw(
                    "SUM(
                        CASE
                            WHEN movement_type IN
                            ('transfer_in','distribution')
                            THEN quantity
                            ELSE 0
                        END
                    ) AS transferred_in"
                ),

                DB::raw(
                    "SUM(
                        CASE
                            WHEN movement_type='sale'
                            THEN quantity
                            ELSE 0
                        END
                    ) AS sold"
                ),

                DB::raw(
                    "SUM(
                        CASE
                            WHEN movement_type IN
                            ('production_consumption',
                             'packing_consumption')
                            THEN quantity
                            ELSE 0
                        END
                    ) AS consumed"
                ),

                DB::raw(
                    "SUM(
                        CASE
                            WHEN movement_type='transfer_out'
                            THEN quantity
                            ELSE 0
                        END
                    ) AS transferred_out"
                ),

                DB::raw(
                    "SUM(
                        CASE
                            WHEN direction='in'
                            THEN quantity
                            ELSE -quantity
                        END
                    ) AS available"
                )
            )
            ->groupBy(
                'stock_movements.item_id',
                'ap.name',
                'ap.type',
                'ap.unit'
            )
            ->orderBy('ap.type')
            ->orderBy('ap.name')
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════════
    // SOLD COST DETAILS
    // ══════════════════════════════════════════════════════════════════════

    public function toggleSoldCostDetails(): void
    {
        $this->showSoldCostDetails =
            !$this->showSoldCostDetails;
    }

    #[Computed]
    public function totalSoldPurchaseCost(): float
    {
        // Guest orders
        $guestOrders =
            DB::table('guest_orders')
                ->whereIn(
                    'status',
                    [
                        'confirmed',
                        'completed'
                    ]
                )
                ->get(['items']);

        $guestOrderCost = 0;

        foreach ($guestOrders as $order) {

            $items =
                is_array($order->items)
                    ? $order->items
                    : json_decode(
                        $order->items,
                        true
                    );

            if (is_array($items)) {

                foreach ($items as $item) {

                    $qty =
                        (float) (
                            $item['quantity']
                            ?? 0
                        );

                    $cost =
                        (float) (
                            $item['cost_price_snapshot']
                            ?? $item['cost_price']
                            ?? 0
                        );

                    $guestOrderCost +=
                        $qty * $cost;
                }
            }
        }

        // Customer orders
        $customerOrderCost =
            OrderItem::query()
                ->join(
                    'orders',
                    'order_items.order_id',
                    '=',
                    'orders.id'
                )
                ->whereIn(
                    'orders.status',
                    [
                        'confirmed',
                        'processing',
                        'completed'
                    ]
                )
                ->selectRaw(
                    'SUM(
                        order_items.quantity
                        * order_items.cost_price_snapshot
                    ) AS total'
                )
                ->value('total') ?? 0;

        // Branch sales
        $branchSaleCost =
            BranchSale::query()
                ->selectRaw(
                    'SUM(
                        quantity * cost_price
                    ) AS total'
                )
                ->value('total') ?? 0;

        return
            (float) $guestOrderCost +
            (float) $customerOrderCost +
            (float) $branchSaleCost;
    }

    #[Computed]
    public function soldCostDetails(): \Illuminate\Support\Collection
    {
        // ================================================================
        // Customer Orders
        // ================================================================

        $orderDetails =
            OrderItem::query()
                ->join(
                    'orders',
                    'order_items.order_id',
                    '=',
                    'orders.id'
                )
                ->join(
                    'products',
                    'order_items.product_id',
                    '=',
                    'products.id'
                )
                ->where(
                    'orders.status',
                    Order::STATUS_COMPLETED
                )
                ->when(
                    $this->selectedBusinessId,
                    fn ($q) =>
                        $q->where(
                            'orders.business_id',
                            $this->selectedBusinessId
                        )
                )
                ->selectRaw(
                    '
                    order_items.product_id,
                    products.name,
                    SUM(order_items.quantity) AS total_qty,
                    AVG(order_items.cost_price_snapshot) AS avg_cost,
                    SUM(
                        order_items.quantity
                        * order_items.cost_price_snapshot
                    ) AS total_cost
                    '
                )
                ->groupBy(
                    'order_items.product_id',
                    'products.name'
                )
                ->orderByDesc('total_cost')
                ->get();

        // ================================================================
        // POS Sales
        // ================================================================

        $branchSaleDetails =
            BranchSale::query()
                ->join(
                    'products',
                    'branch_sales.product_id',
                    '=',
                    'products.id'
                )
                ->when(
                    $this->selectedBusinessId,
                    fn ($q) =>
                        $q->whereIn(
                            'branch_id',
                            Branch::where(
                                'business_id',
                                $this->selectedBusinessId
                            )->pluck('id')
                        )
                )
                ->selectRaw(
                    '
                    branch_sales.product_id,
                    products.name,
                    SUM(branch_sales.quantity) AS total_qty,
                    AVG(branch_sales.cost_price) AS avg_cost,
                    SUM(
                        branch_sales.quantity
                        * branch_sales.cost_price
                    ) AS total_cost
                    '
                )
                ->groupBy(
                    'branch_sales.product_id',
                    'products.name'
                )
                ->orderByDesc('total_cost')
                ->get();

        // ================================================================
        // Combine
        // ================================================================

        $combined = collect();

        foreach ($orderDetails as $item) {

            $combined->push([

                'product_id' =>
                    $item->product_id,

                'name' =>
                    $item->name,

                'qty' =>
                    (float) $item->total_qty,

                'avg_cost' =>
                    (float) $item->avg_cost,

                'cost' =>
                    (float) $item->total_cost,

                'source' =>
                    'Orders',
            ]);
        }

        foreach ($branchSaleDetails as $item) {

            $existing =
                $combined
                    ->where(
                        'product_id',
                        $item->product_id
                    )
                    ->first();

            if ($existing) {

                $combined->transform(
                    function ($record) use ($item) {

                        if (
                            $record['product_id']
                            ==
                            $item->product_id
                        ) {

                            $record['qty'] +=
                                (float) $item->total_qty;

                            $totalCost =
                                $record['cost'] +
                                (float) $item->total_cost;

                            $record['cost'] =
                                $totalCost;

                            $record['avg_cost'] =
                                $record['qty'] > 0
                                    ? $totalCost /
                                        $record['qty']
                                    : 0;

                            $record['source'] =
                                'Orders + POS';
                        }

                        return $record;
                    }
                );

            } else {

                $combined->push([

                    'product_id' =>
                        $item->product_id,

                    'name' =>
                        $item->name,

                    'qty' =>
                        (float) $item->total_qty,

                    'avg_cost' =>
                        (float) $item->avg_cost,

                    'cost' =>
                        (float) $item->total_cost,

                    'source' =>
                        'POS Sales',
                ]);
            }
        }

        return $combined
            ->sortByDesc('cost')
            ->values();
    }

    // ══════════════════════════════════════════════════════════════════════
    // RENDER
    // ══════════════════════════════════════════════════════════════════════

    // ── Daily Manufactured Items ───────────────────────────────────────────
    public function getDailyManufactured()
    {
        $user = auth()->user();
        $businessIds = $user->isSuperAdmin() ? \App\Models\Business::pluck('id')->toArray() : $user->assignedBusinessIds();

        // Get products with manufacturing activity added today
        $today = now()->toDateString();

        $items = \App\Models\Product::query()
            ->with(['category', 'business'])
            ->whereIn('business_id', $businessIds)
            ->whereIn('type', ['finished_product', 'bottle_product'])
            ->where('status', 'active')
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($product) use ($today) {
                $meta = $product->metadata ?? [];
                
                // Check if product has manufacturing_added_at timestamp
                if (!isset($meta['manufacturing_added_at'])) {
                    return false;
                }

                try {
                    // Parse ISO8601 string or timestamp
                    $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                    
                    // If added today, include it
                    if ($addedDate === $today) {
                        // Use manufacturing_daily_qty if available, otherwise use manufacturing_quantity
                        $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
                        return $qty > 0;
                    }
                } catch (\Exception $e) {
                    return false;
                }
                
                return false;
            })
            ->sortByDesc(function ($product) {
                $meta = $product->metadata ?? [];
                return $meta['manufacturing_added_at'] ?? now()->toIso8601String();
            })
            ->values();

        return $items->map(function ($product) {
            $meta = $product->metadata ?? [];
            
            // Use manufacturing_daily_qty if available, otherwise use manufacturing_quantity
            $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
            
            return [
                'product_id'       => $product->id,
                'item_name'        => $product->name,
                'category_name'    => $product->category?->name ?? '—',
                'quantity'         => $qty,
                'unit'             => $product->unit ?? 'pcs',
                'selling_price'    => (float) $product->selling_price,
                'seed_name'        => $meta['seed_name'] ?? '—',
                'seed_qty_used'    => (float)($meta['seed_quantity_used'] ?? 0),
                'seed_unit'        => $meta['seed_unit'] ?? 'kg',
                'created_at'       => $meta['manufacturing_added_at'] ?? $product->updated_at,
            ];
        });
    }

    // Add property to make it accessible as $this->dailyManufactured in views
    public function __get($name)
    {
        if ($name === 'dailyManufactured') {
            return $this->getDailyManufactured();
        }
        return null;
    }

    public function render()
    {
        return view(
            'livewire.admin.super-admin-dashboard'
        )->title(
            'Super Admin — Business Overview'
        );
    }
}