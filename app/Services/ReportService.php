<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\Business;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProfitLedger;
use App\Models\ProductionBatch;
use App\Models\BatchMaterialConsumption;

use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ══════════════════════════════════════════════════════════════════════
    //  SHARED FILTER BUILDER
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Build a base date-scoped query for order_items joined to orders.
     */
    private function orderItemsQuery(array $filters = [])
    {
        $q = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['confirmed', 'processing', 'completed']);

        if (!empty($filters['date_from'])) {
            $q->where('orders.placed_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $q->where('orders.placed_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['business_id'])) {
            $q->where('order_items.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('order_items.fulfilling_branch_id', $filters['branch_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('order_items.product_id', $filters['product_id']);
        }
        if (!empty($filters['category_id'])) {
            $q->join('products as p_cat', 'order_items.product_id', '=', 'p_cat.id')
              ->where('p_cat.category_id', $filters['category_id']);
        }

        return $q;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EXECUTIVE DASHBOARD KPIs
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Return all high-level KPI numbers for the executive dashboard.
     */
    public function getDashboardKpis(array $filters = []): array
    {
        $businessScope = !empty($filters['business_id']) ? $filters['business_id'] : null;
        $branchScope   = !empty($filters['branch_id'])   ? $filters['branch_id']   : null;

        // ── Sales ────────────────────────────────────────────────────────
        $orderBase = Order::query()
            ->whereIn('status', ['confirmed', 'processing', 'completed']);
        if ($businessScope) $orderBase->where('business_id', $businessScope);

        $branchSaleBase = BranchSale::query();
        if ($branchScope)   $branchSaleBase->where('branch_id', $branchScope);
        elseif ($businessScope) {
            $branchSaleBase->whereIn('branch_id',
                Branch::where('business_id', $businessScope)->pluck('id'));
        }

        $totalOrderRevenue     = (clone $orderBase)->sum('total_amount');
        $totalBranchSaleRevenue= (clone $branchSaleBase)->sum('total_amount');
        $totalRevenue          = $totalOrderRevenue + $totalBranchSaleRevenue;

        $totalOrders = (clone $orderBase)->count();
        
        // Pending Orders: include both orders table AND guest_orders table
        $pendingOrdersFromOrders = Order::query()
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhere('payment_status', 'pending');
            })
            ->when($businessScope, fn($q) => $q->where('business_id', $businessScope))
            ->count();
        
        $pendingGuestOrders = DB::table('guest_orders')
            ->where('status', 'pending')
            ->count();
        
        $pendingOrders = $pendingOrdersFromOrders + $pendingGuestOrders;

        // ── Sold Cost (Cost of Goods Sold) ───────────────────────────────
        // From Orders: quantity × cost_price_snapshot
        $soldCostOrders = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['confirmed', 'processing', 'completed'])
            ->when($businessScope, fn($q) => $q->where('orders.business_id', $businessScope))
            ->selectRaw('SUM(order_items.quantity * order_items.cost_price_snapshot) as total')
            ->value('total') ?? 0;

        // From BranchSale: quantity × cost_price
        $soldCostBranchSale = (clone $branchSaleBase)->selectRaw('SUM(quantity * cost_price) as total')->value('total') ?? 0;

        $totalSoldCost = (float) $soldCostOrders + (float) $soldCostBranchSale;

        // ── Profit ───────────────────────────────────────────────────────
        $profitBase = ProfitLedger::query();
        if ($businessScope) $profitBase->where('business_id', $businessScope);
        if ($branchScope)   $profitBase->where('branch_id', $branchScope);

        $totalProfit = (clone $profitBase)->sum('profit');

        // Branch sale profit (not in ledger)
        $branchSaleProfit = (clone $branchSaleBase)->selectRaw(
            'SUM((unit_price - cost_price) * quantity) as profit'
        )->value('profit') ?? 0;

        $totalProfit += $branchSaleProfit;

        // ── Inventory ────────────────────────────────────────────────────
        // ── Inventory ────────────────────────────────────────────────────
        // Get threshold from settings (same as low-stock page)
        $thresholdSetting = \App\Models\Setting::whereNull('business_id')
            ->whereNull('branch_id')
            ->where('key', 'low_stock_threshold')
            ->first();
        $threshold = $thresholdSetting ? (int)$thresholdSetting->value : 10;
        
        // Count low stock items: group by item_id + branch_id (same as details page)
        // This counts same product in different branches as separate items
        $lowStockRows = DB::table('stock_movements')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->when($businessScope, fn($q) => $q->where('stock_movements.business_id', $businessScope))
            ->when($branchScope, fn($q) => $q->where('stock_movements.branch_id', $branchScope))
            ->where('stock_movements.item_type', 'product')
            ->where('products.status', 'active')
            ->select('stock_movements.item_id', 'stock_movements.branch_id',
                     DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('stock_movements.item_id', 'stock_movements.branch_id')
            ->havingRaw("qty > 0 AND qty <= ?", [$threshold])
            ->get();
        
        $lowStock = $lowStockRows->count();
        
        // Count out of stock items: group by item_id + branch_id (same as details page)
        // This counts same product in different branches as separate items
        $outOfStockRows = DB::table('stock_movements')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->when($businessScope, fn($q) => $q->where('stock_movements.business_id', $businessScope))
            ->when($branchScope, fn($q) => $q->where('stock_movements.branch_id', $branchScope))
            ->where('stock_movements.item_type', 'product')
            ->where('products.status', 'active')
            ->select('stock_movements.item_id', 'stock_movements.branch_id',
                     DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('stock_movements.item_id', 'stock_movements.branch_id')
            ->havingRaw("qty = 0")
            ->get();
        
        $outOfStock = $outOfStockRows->count();
        
        // For inventory value, use product-level aggregation
        $stockAgg = StockMovement::query()
            ->when($businessScope, fn($q) => $q->where('stock_movements.business_id', $businessScope))
            ->when($branchScope, fn($q) => $q->where('stock_movements.branch_id', $branchScope))
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->where('products.status', 'active')
            ->select('products.id', 'products.cost_price',
                     DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('products.id', 'products.cost_price')
            ->get();
            
        $inventoryValue = $stockAgg->sum(fn($r) => $r->qty * (float)$r->cost_price);

        // ── Manufacturing ────────────────────────────────────────────────
        $batchBase = ProductionBatch::query();
        if ($businessScope) $batchBase->where('business_id', $businessScope);

        $mfgProfit  = (clone $batchBase)->whereIn('status', ['completed'])->sum('profit');
        $oilOutput  = (clone $batchBase)->whereIn('status', ['completed'])->sum('oil_quantity_produced');
        $activeBatches = (clone $batchBase)->whereIn('status', ['in_progress', 'in_qc'])->count();

        // ── Purchasing ───────────────────────────────────────────────────
        $purchaseCost = StockMovement::query()
            ->where('movement_type', 'purchase')
            ->when($businessScope, fn($q) => $q->where('business_id', $businessScope))
            ->sum('total_cost');

        return [
            'total_revenue'          => round($totalRevenue, 2),
            'total_orders'           => $totalOrders,
            'pending_orders'         => $pendingOrders,
            'total_profit'           => round($totalProfit, 2),
            'profit_margin_pct'      => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0,
            'total_sold_cost'        => round($totalSoldCost, 2),
            'inventory_value'        => round($inventoryValue, 2),
            'low_stock_count'        => $lowStock,
            'out_of_stock_count'     => $outOfStock,
            'manufacturing_profit'   => round($mfgProfit, 2),
            'oil_output_liters'      => round($oilOutput / 1000, 2), // ml → liters
            'active_batches'         => $activeBatches,
            'total_purchase_cost'    => round($purchaseCost, 2),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SALES REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getSalesReport(array $filters = []): array
    {
        $user = auth()->user();
        
        // ── Per-row detail from branch_sales (direct POS sales) ───────────
        $rowQuery = \App\Models\BranchSale::query()
            ->join('products',   'branch_sales.product_id',  '=', 'products.id')
            ->join('categories', 'products.category_id',     '=', 'categories.id')
            ->join('branches',   'branch_sales.branch_id',   '=', 'branches.id')
            ->leftJoin('users',  'branch_sales.recorded_by', '=', 'users.id')
            ->when(!empty($filters['business_id']), fn($q) =>
                $q->whereIn('branch_sales.branch_id',
                    Branch::where('business_id', $filters['business_id'])->pluck('id'))
            )
            ->when(!empty($filters['branch_id']), fn($q) =>
                $q->where('branch_sales.branch_id', $filters['branch_id'])
            )
            ->when(!empty($filters['category_id']), fn($q) =>
                $q->where('products.category_id', $filters['category_id'])
            )
            ->when(!empty($filters['product_id']), fn($q) =>
                $q->where('branch_sales.product_id', $filters['product_id'])
            )
            ->when(!empty($filters['date_from']), fn($q) =>
                $q->where('branch_sales.sale_date', '>=', $filters['date_from'])
            )
            ->when(!empty($filters['date_to']), fn($q) =>
                $q->where('branch_sales.sale_date', '<=', $filters['date_to'])
            )
            // Add seller filtering: sellers only see their assigned branches
            ->when(!$user->isSuperAdmin(), function($q) use ($user) {
                $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                if ($isSeller) {
                    // Sellers see all sales from their assigned branches
                    $q->whereIn('branch_sales.branch_id', $user->assignedBranchIds());
                } else {
                    // Admins see sales from their assigned businesses
                    $q->whereIn('branches.business_id', $user->assignedBusinessIds());
                }
            })
            ->select([
                'branch_sales.id',
                'branch_sales.sale_date',
                'products.name        as item_name',
                'categories.name      as category_name',
                'branches.name        as branch_name',
                'users.name           as sold_by',
                'branch_sales.quantity as sold_qty',
                'branch_sales.cost_price',
                'branch_sales.unit_price as selling_price',
                DB::raw('(branch_sales.unit_price - branch_sales.cost_price) * branch_sales.quantity as profit'),
                'branch_sales.total_amount',
            ])
            ->orderByDesc('branch_sales.sale_date')
            ->orderByDesc('branch_sales.id');

        $rows = $rowQuery->get();

        // ── Column totals ──────────────────────────────────────────────────
        $totals = [
            'sold_qty'      => $rows->sum('sold_qty'),
            'cost_price'    => $rows->sum(fn($r) => (float)$r->cost_price * (float)$r->sold_qty),
            'selling_price' => $rows->sum('total_amount'),
            'profit'        => $rows->sum('profit'),
        ];

        return compact('rows', 'totals');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PROFIT REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getProfitReport(array $filters = []): array
    {
        $user = auth()->user();
        
        $ledgerBase = ProfitLedger::query()
            ->join('order_items', 'profit_ledger.order_item_id', '=', 'order_items.id')
            ->join('orders',       'order_items.order_id',      '=', 'orders.id')
            ->join('products',     'order_items.product_id',    '=', 'products.id');

        if (!empty($filters['date_from'])) {
            $ledgerBase->where('orders.placed_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $ledgerBase->where('orders.placed_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        if (!empty($filters['business_id'])) {
            $ledgerBase->where('profit_ledger.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $ledgerBase->where('profit_ledger.branch_id', $filters['branch_id']);
        }
        
        // Add seller filtering: sellers only see their assigned branches
        if (!$user->isSuperAdmin()) {
            $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
            if ($isSeller) {
                // Sellers see profit from their assigned branches only
                $ledgerBase->whereIn('profit_ledger.branch_id', $user->assignedBranchIds());
            } else {
                // Admins see profit from their assigned businesses
                $ledgerBase->whereIn('profit_ledger.business_id', $user->assignedBusinessIds());
            }
        }

        // Totals
        $totals = (clone $ledgerBase)->selectRaw(
            'SUM(profit_ledger.revenue) as revenue,
             SUM(profit_ledger.total_cost) as cost,
             SUM(profit_ledger.profit) as profit'
        )->first();

        // By product
        $byProduct = (clone $ledgerBase)
            ->select(['products.id', 'products.name',
                DB::raw('SUM(profit_ledger.revenue) as revenue'),
                DB::raw('SUM(profit_ledger.total_cost) as cost'),
                DB::raw('SUM(profit_ledger.profit) as profit'),
                DB::raw('SUM(profit_ledger.quantity) as qty_sold'),
            ])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('profit')
            ->limit(20)
            ->get();

        // By category
        $byCategory = (clone $ledgerBase)
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(['categories.id', 'categories.name',
                DB::raw('SUM(profit_ledger.revenue) as revenue'),
                DB::raw('SUM(profit_ledger.total_cost) as cost'),
                DB::raw('SUM(profit_ledger.profit) as profit'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('profit')
            ->get();

        // By business
        $byBusiness = (clone $ledgerBase)
            ->join('businesses', 'profit_ledger.business_id', '=', 'businesses.id')
            ->select(['businesses.id', 'businesses.name',
                DB::raw('SUM(profit_ledger.revenue) as revenue'),
                DB::raw('SUM(profit_ledger.profit) as profit'),
            ])
            ->groupBy('businesses.id', 'businesses.name')
            ->orderByDesc('profit')
            ->get();

        // Trend by day
        $trend = (clone $ledgerBase)
            ->select([
                DB::raw('DATE(orders.placed_at) as day'),
                DB::raw('SUM(profit_ledger.profit) as profit'),
                DB::raw('SUM(profit_ledger.revenue) as revenue'),
            ])
            ->groupBy(DB::raw('DATE(orders.placed_at)'))
            ->orderBy(DB::raw('DATE(orders.placed_at)'))
            ->get();

        // Manufacturing batch profits
        $batchProfits = ProductionBatch::query()
            ->with('manufacturingOrder.product')
            ->whereIn('status', ['completed'])
            ->when(!empty($filters['business_id']), fn($q) => $q->where('business_id', $filters['business_id']))
            ->selectRaw('id, batch_number, oil_quantity_produced, raw_material_cost, packing_cost, revenue, profit, status, completed_at')
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get();

        // Branch sales rows: Branch | Item | Qty | Purchase Price | Selling Price | Total Cost | Profit
        $branchSaleRows = \App\Models\BranchSale::query()
            ->join('products',    'branch_sales.product_id', '=', 'products.id')
            ->join('categories',  'products.category_id',    '=', 'categories.id')
            ->join('branches',    'branch_sales.branch_id',  '=', 'branches.id')
            ->when(!empty($filters['business_id']), fn($q) =>
                $q->whereIn('branch_sales.branch_id',
                    \App\Models\Branch::where('business_id', $filters['business_id'])->pluck('id')))
            ->when(!empty($filters['branch_id']), fn($q) =>
                $q->where('branch_sales.branch_id', $filters['branch_id']))
            ->when(!empty($filters['date_from']), fn($q) =>
                $q->where('branch_sales.sale_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn($q) =>
                $q->where('branch_sales.sale_date', '<=', $filters['date_to']))
            ->select([
                'branches.name   as branch_name',
                'categories.name as category_name',
                'products.name   as item_name',
                'products.unit   as unit',
                'branch_sales.quantity',
                'branch_sales.cost_price  as purchase_price',
                'branch_sales.unit_price  as selling_price',
                DB::raw('(branch_sales.cost_price * branch_sales.quantity)                        as total_cost'),
                DB::raw('((branch_sales.unit_price - branch_sales.cost_price) * branch_sales.quantity) as profit'),
                'branch_sales.sale_date',
            ])
            ->orderBy('branches.name')
            ->orderByDesc('branch_sales.sale_date')
            ->get();

        $branchSaleTotals = [
            'qty'       => $branchSaleRows->sum('quantity'),
            'total_cost'=> $branchSaleRows->sum('total_cost'),
            'revenue'   => $branchSaleRows->sum(fn($r) => $r->quantity * $r->selling_price),
            'profit'    => $branchSaleRows->sum('profit'),
        ];

        return compact('totals', 'byProduct', 'byCategory', 'byBusiness', 'trend', 'batchProfits', 'branchSaleRows', 'branchSaleTotals');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  INVENTORY REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getInventoryReport(array $filters = []): array
    {
        $user = auth()->user();
        
        $base = StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->when(!empty($filters['business_id']), fn($q) => $q->where('stock_movements.business_id', $filters['business_id']))
            ->when(!empty($filters['branch_id']),   fn($q) => $q->where('stock_movements.branch_id', $filters['branch_id']))
            ->when(!empty($filters['warehouse_id']),fn($q) => $q->where('stock_movements.location_id', $filters['warehouse_id']))
            // Add seller filtering: sellers only see their assigned branches
            ->when(!$user->isSuperAdmin(), function($q) use ($user) {
                $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                if ($isSeller) {
                    // Sellers see inventory from their assigned branches only
                    $q->whereIn('stock_movements.branch_id', $user->assignedBranchIds());
                } else {
                    // Admins see inventory from their assigned businesses
                    $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds());
                }
            })
            ->select(
                'stock_movements.location_id',
                'products.id as product_id',
                'products.name as product_name',
                'products.cost_price',
                'products.reorder_point',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as quantity_on_hand")
            )
            ->groupBy('stock_movements.location_id', 'products.id', 'products.name', 'products.cost_price', 'products.reorder_point')
            ->get();
            
        $levels = $base->map(function ($row) {
            return new class($row) {
                public $item_id;
                public $item_type = 'product';
                public $item;
                public $quantity_on_hand;
                public $unit_cost;
                public $location;
                private $reorder_point;
                public function __construct($row) {
                    $this->item_id = $row->product_id;
                    $this->quantity_on_hand = (float) $row->quantity_on_hand;
                    $this->unit_cost = (float) $row->cost_price;
                    $this->reorder_point = (float) $row->reorder_point;
                    $this->location = \App\Models\Location::with('branch.business')->find($row->location_id);
                    $this->item = \App\Models\Product::find($row->product_id);
                }
                public function getStockValue() { return $this->quantity_on_hand * $this->unit_cost; }
                public function isLowStock() { return $this->quantity_on_hand > 0 && $this->quantity_on_hand <= $this->reorder_point; }
                public function isOutOfStock() { return $this->quantity_on_hand <= 0; }
            };
        });

        $totalValue  = $levels->sum(fn($l) => $l->getStockValue());
        $lowStock    = $levels->filter(fn($l) => $l->isLowStock());
        $outOfStock  = $levels->filter(fn($l) => $l->isOutOfStock());

        // Movement history
        $movementBase = StockMovement::query()
            ->with(['location'])
            ->when(!empty($filters['business_id']), fn($q) => $q->where('business_id', $filters['business_id']))
            ->when(!empty($filters['branch_id']),   fn($q) => $q->where('branch_id',   $filters['branch_id']))
            ->when(!empty($filters['date_from']),   fn($q) => $q->where('moved_at', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when(!empty($filters['date_to']),     fn($q) => $q->where('moved_at', '<=', Carbon::parse($filters['date_to'])->endOfDay()))
            ->when(!empty($filters['product_id']),  fn($q) => $q->where('item_id', $filters['product_id'])->where('item_type', 'App\Models\Product'))
            ->orderByDesc('moved_at')
            ->limit(100);

        $movements = $movementBase->get();

        // By type summary
        $movementSummary = StockMovement::query()
            ->when(!empty($filters['business_id']), fn($q) => $q->where('business_id', $filters['business_id']))
            ->when(!empty($filters['date_from']),   fn($q) => $q->where('moved_at', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when(!empty($filters['date_to']),     fn($q) => $q->where('moved_at', '<=', Carbon::parse($filters['date_to'])->endOfDay()))
            ->select(['movement_type', 'direction',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(total_cost) as total_cost'),
            ])
            ->groupBy('movement_type', 'direction')
            ->orderBy('movement_type')
            ->get();

        return compact('levels', 'totalValue', 'lowStock', 'outOfStock', 'movements', 'movementSummary');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MANUFACTURING REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getManufacturingReport(array $filters = []): array
    {
        $user = auth()->user();
        
        $batchBase = ProductionBatch::query()
            ->with(['manufacturingOrder.product.category', 'qualityCheck'])
            ->when(!empty($filters['business_id']), fn($q) => $q->where('business_id', $filters['business_id']))
            ->when(!empty($filters['branch_id']),   fn($q) => $q->where('branch_id',   $filters['branch_id']))
            ->when(!empty($filters['date_from']),   fn($q) => $q->where('created_at',  '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when(!empty($filters['date_to']),     fn($q) => $q->where('created_at',  '<=', Carbon::parse($filters['date_to'])->endOfDay()))
            ->when(!empty($filters['batch_id']),    fn($q) => $q->where('id', $filters['batch_id']))
            // Add seller filtering: sellers only see their assigned businesses
            ->when(!$user->isSuperAdmin(), function($q) use ($user) {
                $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                if (!$isSeller) {
                    // Admins see batches from their assigned businesses
                    $q->whereIn('business_id', $user->assignedBusinessIds());
                }
            });

        $batches = (clone $batchBase)->orderByDesc('created_at')->get();

        // Per-batch KPIs
        $batchData = $batches->map(function ($batch) {
            $rawKg   = (float) $batch->raw_material_cost;
            $oilLt   = (float) $batch->oil_quantity_produced / 1000;
            return [
                'id'              => $batch->id,
                'batch_number'    => $batch->batch_number,
                'status'          => $batch->status,
                'product'         => optional(optional($batch->manufacturingOrder)->product)->name,
                'category'        => optional(optional(optional($batch->manufacturingOrder)->product)->category)->name,
                'oil_produced_lt' => round($oilLt, 3),
                'raw_material_cost'=> round($batch->raw_material_cost, 2),
                'packing_cost'    => round($batch->packing_cost, 2),
                'revenue'         => round($batch->revenue, 2),
                'profit'          => round($batch->profit, 2),
                'yield_pct'       => 0, // calculated below via consumption
                'qc_status'       => optional($batch->qualityCheck)->status ?? 'n/a',
                'completed_at'    => $batch->completed_at,
            ];
        });

        // Raw material consumption breakdown by category
        $consumptionByCategory = BatchMaterialConsumption::query()
            ->join('production_batches', 'batch_material_consumption.production_batch_id', '=', 'production_batches.id')
            ->join('products as rm', 'batch_material_consumption.raw_material_id', '=', 'rm.id')
            ->join('categories', 'rm.category_id', '=', 'categories.id')
            ->when(!empty($filters['business_id']), fn($q) => $q->where('production_batches.business_id', $filters['business_id']))
            ->when(!empty($filters['date_from']),   fn($q) => $q->where('production_batches.created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when(!empty($filters['date_to']),     fn($q) => $q->where('production_batches.created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay()))
            ->select([
                'categories.name as category_name',
                DB::raw('SUM(batch_material_consumption.quantity_consumed) as total_kg'),
                DB::raw('SUM(batch_material_consumption.total_cost) as total_cost'),
                DB::raw('AVG(batch_material_consumption.unit_cost) as avg_cost_per_kg'),
                DB::raw('COUNT(DISTINCT batch_material_consumption.production_batch_id) as batch_count'),
            ])
            ->groupBy('categories.name')
            ->orderByDesc('total_kg')
            ->get();

        // Totals
        $totals = $batches->whereIn('status', ['completed']);
        $summary = [
            'total_batches'        => $batches->count(),
            'completed_batches'    => $totals->count(),
            'total_oil_liters'     => round($totals->sum('oil_quantity_produced') / 1000, 2),
            'total_raw_cost'       => round($totals->sum('raw_material_cost'), 2),
            'total_packing_cost'   => round($totals->sum('packing_cost'), 2),
            'total_revenue'        => round($totals->sum('revenue'), 2),
            'total_profit'         => round($totals->sum('profit'), 2),
        ];

        return compact('batches', 'batchData', 'consumptionByCategory', 'summary');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PURCHASING REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getPurchasingReport(array $filters = []): array
    {
        $user = auth()->user();
        
        $rows = \App\Models\Purchase::query()
            ->join('products',   'purchases.product_id',  '=', 'products.id')
            ->join('categories', 'purchases.category_id', '=', 'categories.id')
            ->join('branches',   'purchases.branch_id',   '=', 'branches.id')
            ->leftJoin('users',  'purchases.created_by',  '=', 'users.id')
            ->where('purchases.status', \App\Models\Purchase::STATUS_COMPLETED)
            ->whereRaw("(purchases.notes IS NULL OR (purchases.notes NOT LIKE 'Transfer from%' AND purchases.notes NOT LIKE 'from_branch:%'))")
            ->when(!empty($filters['business_id']), fn($q) =>
                $q->where('purchases.business_id', $filters['business_id'])
            )
            ->when(!empty($filters['branch_id']), fn($q) =>
                $q->where('purchases.branch_id', $filters['branch_id'])
            )
            ->when(!empty($filters['category_id']), fn($q) =>
                $q->where('purchases.category_id', $filters['category_id'])
            )
            ->when(!empty($filters['product_id']), fn($q) =>
                $q->where('purchases.product_id', $filters['product_id'])
            )
            ->when(!empty($filters['date_from']), fn($q) =>
                $q->where('purchases.purchase_date', '>=', $filters['date_from'])
            )
            ->when(!empty($filters['date_to']), fn($q) =>
                $q->where('purchases.purchase_date', '<=', $filters['date_to'])
            )
            // Add seller filtering: sellers only see their assigned branches
            ->when(!$user->isSuperAdmin(), function($q) use ($user) {
                $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                if ($isSeller) {
                    // Sellers see purchases for their assigned branches only
                    $q->whereIn('purchases.branch_id', $user->assignedBranchIds());
                } else {
                    // Admins see purchases from their assigned businesses
                    $q->whereIn('purchases.business_id', $user->assignedBusinessIds());
                }
            })
            ->select([
                'purchases.id',
                'purchases.purchase_date',
                'products.name        as item_name',
                'categories.name      as category_name',
                'branches.name        as branch_name',
                'users.name           as registered_by',
                'purchases.quantity',
                'purchases.unit',
                'purchases.unit_price as purchase_price',
                'products.selling_price',
                'purchases.total_price',
            ])
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->get();

        $totals = [
            'quantity'       => $rows->sum('quantity'),
            'purchase_cost'  => $rows->sum('total_price'),
            'selling_total'  => $rows->sum(fn($r) => (float)$r->selling_price * (float)$r->quantity),
        ];

        return compact('rows', 'totals');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BRANCH PERFORMANCE REPORT
    // ══════════════════════════════════════════════════════════════════════

    public function getBranchReport(array $filters = []): array
    {
        // Sales from orders
        $orderSales = $this->orderItemsQuery($filters)
            ->join('branches', 'order_items.fulfilling_branch_id', '=', 'branches.id')
            ->select([
                'branches.id', 'branches.name',
                DB::raw('SUM(order_items.total_amount) as revenue'),
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
            ])
            ->groupBy('branches.id', 'branches.name')
            ->get()
            ->keyBy('id');

        // Sales from branch POS
        $posSales = BranchSale::query()
            ->join('branches', 'branch_sales.branch_id', '=', 'branches.id')
            ->when(!empty($filters['business_id']), fn($q) => $q->whereIn('branch_sales.branch_id',
                Branch::where('business_id', $filters['business_id'])->pluck('id')))
            ->when(!empty($filters['date_from']), fn($q) => $q->where('sale_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']),   fn($q) => $q->where('sale_date', '<=', $filters['date_to']))
            ->select([
                'branches.id', 'branches.name',
                DB::raw('SUM(branch_sales.total_amount) as pos_revenue'),
                DB::raw('SUM(branch_sales.quantity) as pos_qty'),
                DB::raw('COUNT(*) as pos_transactions'),
            ])
            ->groupBy('branches.id', 'branches.name')
            ->get()
            ->keyBy('id');

        // Merge
        $branches = Branch::query()
            ->when(!empty($filters['business_id']), fn($q) => $q->where('business_id', $filters['business_id']))
            ->with('business')
            ->orderBy('name')
            ->get()
            ->map(function ($branch) use ($orderSales, $posSales) {
                $os = $orderSales->get($branch->id);
                $ps = $posSales->get($branch->id);
                return [
                    'id'              => $branch->id,
                    'name'            => $branch->name,
                    'business'        => optional($branch->business)->name,
                    'order_revenue'   => round((float)($os->revenue ?? 0), 2),
                    'pos_revenue'     => round((float)($ps->pos_revenue ?? 0), 2),
                    'total_revenue'   => round((float)($os->revenue ?? 0) + (float)($ps->pos_revenue ?? 0), 2),
                    'order_count'     => (int)($os->order_count ?? 0),
                    'pos_transactions'=> (int)($ps->pos_transactions ?? 0),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();

        return compact('branches');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RETAIL REPORTS  (Step 48)
    //
    //  All data sourced from stock_movements + orders + order_items.
    //  No static data. Branch A and Branch B always remain independent.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Retail daily / weekly / monthly sales summary.
     *
     * Returns three pre-computed periods in one call to avoid triple queries
     * in the dashboard.
     *
     * @param  array  $filters  [business_id, branch_id, product_id, category_id]
     */
    public function getRetailPeriodSales(array $filters = []): array
    {
        $now        = Carbon::now();
        $today      = $now->copy()->startOfDay();
        $weekStart  = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        $base = fn(Carbon $from) => $this->orderItemsQuery(array_merge($filters, ['date_from' => $from->toDateTimeString()]))
            ->selectRaw('
                COUNT(DISTINCT orders.id)          as order_count,
                SUM(order_items.quantity)           as qty_sold,
                SUM(order_items.total_amount)       as revenue,
                COUNT(DISTINCT order_items.product_id) as product_count
            ')
            ->whereIn('orders.status', ['completed'])   // only fully completed orders
            ->first();

        $daily   = $base($today);
        $weekly  = $base($weekStart);
        $monthly = $base($monthStart);

        // Products sold per branch for today (for Super Admin overview)
        $branchesToday = $this->orderItemsQuery(array_merge($filters, ['date_from' => $today->toDateTimeString()]))
            ->join('branches', 'order_items.fulfilling_branch_id', '=', 'branches.id')
            ->whereIn('orders.status', ['completed'])
            ->select(
                'branches.id as branch_id',
                'branches.name as branch_name',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.total_amount) as revenue'),
                DB::raw('COUNT(DISTINCT order_items.product_id) as products_sold'),
                DB::raw('COUNT(DISTINCT orders.id) as orders')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();

        return [
            'daily' => [
                'order_count'    => (int)   ($daily->order_count   ?? 0),
                'qty_sold'       => (float) ($daily->qty_sold       ?? 0),
                'revenue'        => round((float) ($daily->revenue  ?? 0), 2),
                'product_count'  => (int)   ($daily->product_count  ?? 0),
            ],
            'weekly' => [
                'order_count'    => (int)   ($weekly->order_count   ?? 0),
                'qty_sold'       => (float) ($weekly->qty_sold      ?? 0),
                'revenue'        => round((float) ($weekly->revenue ?? 0), 2),
                'product_count'  => (int)   ($weekly->product_count ?? 0),
            ],
            'monthly' => [
                'order_count'    => (int)   ($monthly->order_count   ?? 0),
                'qty_sold'       => (float) ($monthly->qty_sold      ?? 0),
                'revenue'        => round((float) ($monthly->revenue ?? 0), 2),
                'product_count'  => (int)   ($monthly->product_count ?? 0),
            ],
            'branches_today' => $branchesToday,
        ];
    }

    /**
     * Retail branch performance report.
     *
     * For each branch (or a specific branch), returns:
     *   - number of distinct products in stock
     *   - available quantity (current stock from movements)
     *   - sold quantity (from completed order_items)
     *   - daily / weekly / monthly sales value
     *   - purchased quantity (from purchase-type movements)
     *   - current available quantity (duplicates available_qty for clarity)
     *
     * @param  int|null $businessId  scope to a business
     * @param  int|null $branchId    scope to a specific branch
     */
    public function getRetailBranchReport(
        ?int $businessId = null,
        ?int $branchId   = null
    ): array {
        $now        = Carbon::now();
        $today      = $now->copy()->startOfDay();
        $weekStart  = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        $branchQuery = \App\Models\Branch::query()
            ->with('business')
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->when($branchId,   fn($q) => $q->where('id', $branchId))
            ->orderBy('name');

        $branches = $branchQuery->get();
        $report   = [];

        foreach ($branches as $branch) {
            $locationIds = \App\Models\Location::where('branch_id', $branch->id)->pluck('id');

            if ($locationIds->isEmpty()) {
                $report[] = $this->emptyBranchRow($branch);
                continue;
            }

            // ── Stock metrics from movements ──────────────────────────────
            $stockAgg = StockMovement::whereIn('location_id', $locationIds)
                ->where('item_type', 'product')
                ->select(
                    DB::raw('COUNT(DISTINCT item_id)                                          as product_count'),
                    DB::raw("SUM(CASE WHEN direction='in'           THEN quantity ELSE 0 END) as total_in"),
                    DB::raw("SUM(CASE WHEN direction='out'          THEN quantity ELSE 0 END) as total_out"),
                    DB::raw("SUM(CASE WHEN movement_type='purchase' THEN quantity ELSE 0 END) as purchased_qty"),
                    DB::raw("SUM(CASE WHEN movement_type='sale'     THEN quantity ELSE 0 END) as sold_qty"),
                    DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END)   as available_qty")
                )
                ->first();

            // ── Sales values from completed orders ────────────────────────
            $salesBase = OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.fulfilling_branch_id', $branch->id)
                ->where('orders.status', Order::STATUS_COMPLETED);

            $dailySales   = (clone $salesBase)->where('orders.placed_at', '>=', $today)->sum('order_items.total_amount');
            $weeklySales  = (clone $salesBase)->where('orders.placed_at', '>=', $weekStart)->sum('order_items.total_amount');
            $monthlySales = (clone $salesBase)->where('orders.placed_at', '>=', $monthStart)->sum('order_items.total_amount');

            $report[] = [
                'branch_id'       => $branch->id,
                'branch_name'     => $branch->name,
                'business_name'   => optional($branch->business)->name,
                // Inventory
                'product_count'   => (int)   ($stockAgg->product_count  ?? 0),
                'purchased_qty'   => (float) ($stockAgg->purchased_qty  ?? 0),
                'sold_qty'        => (float) ($stockAgg->sold_qty        ?? 0),
                'available_qty'   => (float) ($stockAgg->available_qty  ?? 0),
                // Sales
                'daily_sales'     => round((float) $dailySales,   2),
                'weekly_sales'    => round((float) $weeklySales,  2),
                'monthly_sales'   => round((float) $monthlySales, 2),
            ];
        }

        return $report;
    }

    /**
     * Retail branch → product drill-down report.
     *
     * Super Admin selects:  Business → Branch → Product
     * Returns:  purchased_qty, transferred_in, sold_qty, transferred_out, available_qty
     *
     * All values come from stock_movements — no duplicate stock tables.
     *
     * @param  int       $branchId
     * @param  int|null  $productId  null = all products in the branch
     */
    public function getRetailBranchProductReport(int $branchId, ?int $productId = null): array
    {
        $inventory = app(\App\Services\InventoryService::class)
            ->getBranchInventoryReport($branchId, $productId);

        // Attach sales order data for each product (completed orders only)
        $salesData = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.fulfilling_branch_id', $branchId)
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as confirmed_sold_qty'),
                DB::raw('SUM(order_items.total_amount) as confirmed_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        $rows = $inventory->map(function ($row) use ($salesData) {
            $sales = $salesData->get($row->product_id);
            return [
                'product_id'       => $row->product_id,
                'product_name'     => $row->product_name,
                'sku'              => $row->sku,
                'category'         => $row->category_name,
                'unit'             => $row->unit,
                'selling_price'    => $row->selling_price,
                // From stock_movements
                'purchased_qty'    => $row->purchased_qty,
                'transferred_in'   => $row->transferred_in,
                'sold_qty'         => $row->sold_qty,
                'transferred_out'  => $row->transferred_out,
                'available_qty'    => $row->qty_available,
                'stock_value'      => $row->stock_value,
                'is_low_stock'     => $row->is_low_stock,
                'is_out_of_stock'  => $row->is_out_of_stock,
                // Cross-verified from completed orders
                'confirmed_sold_qty' => (float) ($sales->confirmed_sold_qty ?? 0),
                'confirmed_revenue'  => round((float) ($sales->confirmed_revenue ?? 0), 2),
                'order_count'        => (int)   ($sales->order_count ?? 0),
            ];
        })->values()->toArray();

        $totals = [
            'total_products'    => count($rows),
            'total_purchased'   => collect($rows)->sum('purchased_qty'),
            'total_sold'        => collect($rows)->sum('sold_qty'),
            'total_available'   => collect($rows)->sum('available_qty'),
            'total_stock_value' => round(collect($rows)->sum('stock_value'), 2),
            'total_revenue'     => round(collect($rows)->sum('confirmed_revenue'), 2),
        ];

        return compact('rows', 'totals');
    }

    // ── Private helper ────────────────────────────────────────────────────

    private function emptyBranchRow(\App\Models\Branch $branch): array
    {
        return [
            'branch_id'     => $branch->id,
            'branch_name'   => $branch->name,
            'business_name' => optional($branch->business)->name,
            'product_count' => 0, 'purchased_qty' => 0, 'sold_qty' => 0,
            'available_qty' => 0, 'daily_sales' => 0, 'weekly_sales' => 0, 'monthly_sales' => 0,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EXPORT HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Export data as CSV and return the string.
     */
    public function exportCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    /**
     * Format report data for CSV export based on report type.
     */
    public function buildCsvExport(string $reportType, array $data): array
    {
        return match ($reportType) {
            'raw_material_deduction' => [
                'headers' => ['Branch', 'Item Name', 'Category', 'Purchased Qty', 'Consumed Qty', 'Remaining Qty', 'Unit Cost', 'Total Cost', 'Date'],
                'rows'    => collect($data)->map(fn($r) => [
                    $r['branch_name'],
                    $r['item_name'],
                    $r['category_name'],
                    $r['purchased_qty'],
                    $r['consumed_qty'],
                    $r['remaining_qty'],
                    $r['unit_cost'],
                    $r['total_value'],
                    $r['date'],
                ])->toArray(),
            ],
            'sales' => [
                'headers' => ['Date', 'Item Name', 'Category', 'Branch', 'Sold Qty', 'Cost Price', 'Selling Price', 'Profit', 'Sold By'],
                'rows'    => ($data['rows'] ?? collect())->map(fn($r) => [
                    $r->sale_date,
                    $r->item_name,
                    $r->category_name,
                    $r->branch_name,
                    $r->sold_qty,
                    $r->cost_price,
                    $r->selling_price,
                    $r->profit,
                    $r->sold_by ?? '',
                ])->toArray(),
            ],
            'profit' => [
                'headers' => ['Product', 'Category', 'Qty Sold', 'Revenue', 'Cost', 'Profit', 'Margin %'],
                'rows'    => ($data['byProduct'] ?? collect())->map(fn($r) => [
                    $r->name, '',
                    $r->qty_sold, $r->revenue, $r->cost, $r->profit,
                    $r->revenue > 0 ? round(($r->profit / $r->revenue) * 100, 1).'%' : '0%',
                ])->toArray(),
            ],
            'inventory' => [
                'headers' => ['Item', 'Type', 'Location', 'Qty On Hand', 'Unit Cost', 'Total Value', 'Status'],
                'rows'    => ($data['levels'] ?? collect())->map(fn($l) => [
                    $l->item_id, $l->item_type,
                    optional(optional($l->location)->branch)->name ?? '',
                    $l->quantity_on_hand, $l->unit_cost, $l->getStockValue(),
                    $l->isOutOfStock() ? 'Out of Stock' : ($l->isLowStock() ? 'Low Stock' : 'OK'),
                ])->toArray(),
            ],
            'manufacturing' => [
                'headers' => ['Batch', 'Product', 'Category', 'Oil (L)', 'Raw Cost', 'Packing', 'Revenue', 'Profit', 'QC'],
                'rows'    => ($data['batchData'] ?? collect())->map(fn($r) => [
                    $r['batch_number'], $r['product'], $r['category'],
                    $r['oil_produced_lt'], $r['raw_material_cost'],
                    $r['packing_cost'], $r['revenue'], $r['profit'], $r['qc_status'],
                ])->toArray(),
            ],
            'purchasing' => [
                'headers' => ['Date', 'Item Name', 'Category', 'Branch', 'Qty', 'Unit', 'Purchase Price', 'Selling Price', 'Total Cost', 'Registered By'],
                'rows'    => ($data['rows'] ?? collect())->map(fn($r) => [
                    $r->purchase_date,
                    $r->item_name,
                    $r->category_name,
                    $r->branch_name,
                    $r->quantity,
                    $r->unit,
                    $r->purchase_price,
                    $r->selling_price,
                    $r->total_price,
                    $r->registered_by ?? '',
                ])->toArray(),
            ],
            'branches' => [
                'headers' => ['Branch', 'Business', 'Order Revenue', 'POS Revenue', 'Total Revenue', 'Orders', 'POS Transactions'],
                'rows'    => ($data['branches'] ?? collect())->map(fn($r) => [
                    $r['name'], $r['business'], $r['order_revenue'], $r['pos_revenue'],
                    $r['total_revenue'], $r['order_count'], $r['pos_transactions'],
                ])->toArray(),
            ],
            default => ['headers' => [], 'rows' => []],
        };
    }
}
