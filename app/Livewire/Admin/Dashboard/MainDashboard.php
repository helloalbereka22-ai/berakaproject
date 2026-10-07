<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Order;
// use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

use App\Services\ReportService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.admin')]
class MainDashboard extends Component
{
    /**
     * For Super Admin: the selected business context.
     * null = all businesses (global view).
     */
    public ?int $selectedBusinessId = null;

    public $businesses = [];
    public bool $isSuperAdmin = false;

    public bool $isGeneralManager = false;

    public function mount()
    {
        $user = auth()->user();
        
        // Redirect manufacturing staff to their dedicated dashboard
        if ($user->can('manufacturing.produce') 
            && !$user->can('business.update') 
            && !$user->isSuperAdmin()) {
            return redirect()->route('admin.manufacturing.dashboard');
        }
        
        // Redirect sellers to their dedicated dashboard
        $isSeller = $user->roles->whereIn('name', ['Seller', 'Branch Seller', 'Sales Staff'])->isNotEmpty();
        if ($isSeller) {
            return redirect()->route('admin.branch-seller.dashboard');
        }
        
        $this->isSuperAdmin = $user->isSuperAdmin();
        
        // Detect general manager: has business.update permission
        $this->isGeneralManager = $user->can('business.update') && !$user->isSuperAdmin();

        if ($this->isSuperAdmin) {
            $this->businesses = Business::orderBy('name')->get(['id', 'name']);
            // Restore previously selected context from session
            $this->selectedBusinessId = session('admin_business_context');
        } else {
            // Admin: locked to their assigned business
            $this->selectedBusinessId = $user->assignedBusinessId();
        }
    }

    /**
     * Super Admin only: switch the business context.
     */
    public function switchBusinessContext(?int $businessId): void
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $this->selectedBusinessId = $businessId;
        session(['admin_business_context' => $businessId]);
    }

    /**
     * Build a query scope based on the current user and selected business context.
     */
    private function scopeByBusiness($query, string $column = 'business_id')
    {
        if ($this->selectedBusinessId) {
            return $query->where($column, $this->selectedBusinessId);
        }

        // Admin: scope to assigned businesses
        if (!$this->isSuperAdmin) {
            $businessIds = auth()->user()->assignedBusinessIds();
            return $query->whereIn($column, $businessIds);
        }

        // Super Admin without a specific selection: global
        return $query;
    }

    /**
     * Auto-refresh audit logs (called by wire:poll)
     */
    public function refreshAuditLogs(): void
    {
        // This method is called by wire:poll to trigger a re-render
        // The render() method will recalculate audit_logs stats
    }

    public function render()
    {
        $user    = auth()->user();
        $filters = $this->selectedBusinessId ? ['business_id' => $this->selectedBusinessId] : [];
        $kpis    = app(ReportService::class)->getDashboardKpis($filters);

        // ─── General Manager Dashboard (Simplified) ──────────────
        if ($this->isGeneralManager) {
            $businessIds = $user->assignedBusinessIds();
            $branchIds   = Branch::whereIn('business_id', $businessIds)->pluck('id');
            $today       = today();

            // Daily orders — guest_orders placed today
            $dailyOrders = \Illuminate\Support\Facades\DB::table('guest_orders')
                ->whereDate('created_at', $today)
                ->count();

            // ── Daily Sales: sum from ALL 3 sources ─────────────────────
            // 1) guest_orders (online/anonymous) — confirmed or completed today
            $salesGuest = \Illuminate\Support\Facades\DB::table('guest_orders')
                ->whereDate('created_at', $today)
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('total_amount');

            // 2) orders table (branch-fulfilled orders) — completed today
            $salesOrders = \Illuminate\Support\Facades\DB::table('orders')
                ->whereIn('business_id', $businessIds)
                ->where('status', 'completed')
                ->whereDate('placed_at', $today)
                ->sum('total_amount');

            // 3) branch_sales (offline/manual branch sales) — today
            $salesBranch = \Illuminate\Support\Facades\DB::table('branch_sales')
                ->whereIn('branch_id', $branchIds)
                ->whereDate('sale_date', $today)
                ->sum('total_amount');

            $dailySales = (float)$salesGuest + (float)$salesOrders + (float)$salesBranch;

            // Total orders — guest_orders + orders table combined
            $totalOrders = \Illuminate\Support\Facades\DB::table('guest_orders')->count()
                + \Illuminate\Support\Facades\DB::table('orders')->whereIn('business_id', $businessIds)->count();

            // Total purchases value = SUM(available_qty × unit_price) for all products in stock
            $totalPurchases = \Illuminate\Support\Facades\DB::table('stock_movements')
                ->where('item_type', 'product')
                ->whereIn('branch_id', $branchIds)
                ->join('products', 'stock_movements.item_id', '=', 'products.id')
                ->select('products.id', 'products.cost_price', \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as available_qty"))
                ->groupBy('products.id', 'products.cost_price')
                ->get()
                ->sum(function($item) {
                    return max(0, (float)$item->available_qty) * (float)$item->cost_price;
                });

            // Total products
            $totalProducts = Product::withoutGlobalScopes()
                ->whereIn('business_id', $businessIds)
                ->count();

            // Total items (sum of all purchase quantities in stock)
            $totalItems = \Illuminate\Support\Facades\DB::table('purchases')
                ->whereIn('business_id', $businessIds)
                ->where('status', 'completed')
                ->sum('quantity');

            // Total manufactured (sum of manufacturing_quantity from metadata)
            $totalManufactured = Product::withoutGlobalScopes()
                ->whereIn('business_id', $businessIds)
                ->whereIn('type', ['finished_product', 'bottle_product'])
                ->get()
                ->sum(fn($p) => (float)(($p->metadata ?? [])['manufacturing_quantity'] ?? 0));

            // Daily manufactured
            // Daily manufactured — products added to manufacturing today
            $dailyManufactured = Product::withoutGlobalScopes()
                ->whereIn('business_id', $businessIds)
                ->whereIn('type', ['finished_product', 'bottle_product'])
                ->where('status', 'active')
                ->whereNotNull('metadata')
                ->get()
                ->filter(function ($product) use ($today) {
                    $meta = $product->metadata ?? [];
                    if (!isset($meta['manufacturing_added_at'])) return false;
                    try {
                        $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                        if ($addedDate === $today->toDateString()) {
                            $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
                            return $qty > 0;
                        }
                    } catch (\Exception $e) {
                        return false;
                    }
                    return false;
                })
                ->sum(function($p) {
                    $meta = $p->metadata ?? [];
                    return (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
                });

            // Daily transfers — count transfer_in movements today (same as Super Admin)
            $dailyTransfers = DB::table('stock_movements')
                ->whereDate('moved_at', $today)
                ->where('reference_type', 'transfer')
                ->where('direction', 'in')
                ->count();

            // Total transfers — count all transfer_in movements ever (same as Super Admin)
            $totalTransfers = DB::table('stock_movements')
                ->where('reference_type', 'transfer')
                ->where('direction', 'in')
                ->count();

            // Low stock & out of stock — use branch_id directly (matches stock_movements structure)
            // Read threshold from settings — same value as Low Stock page
            $thresholdSetting  = \App\Models\Setting::whereNull('business_id')
                ->whereNull('branch_id')
                ->where('key', 'low_stock_threshold')
                ->first();
            $lowStockThreshold = $thresholdSetting ? (int)$thresholdSetting->value : 10;

            $stockData = DB::table('stock_movements')
                ->where('item_type', 'product')
                ->whereIn('stock_movements.business_id', $businessIds)
                ->join('products', 'stock_movements.item_id', '=', 'products.id')
                ->where('products.status', 'active')
                ->select('stock_movements.item_id', 'stock_movements.branch_id', DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as qty"))
                ->groupBy('stock_movements.item_id', 'stock_movements.branch_id')
                ->get();

            $lowStock   = $stockData->filter(fn($r) => $r->qty > 0 && $r->qty <= $lowStockThreshold)->count();
            $outOfStock = $stockData->filter(fn($r) => (float)$r->qty == 0)->count();

            $stats = [
                'daily_sales'       => $dailySales,
                'daily_orders'      => $dailyOrders,
                'daily_manufactured'=> $dailyManufactured,
                'daily_transfers'   => $dailyTransfers,
                'total_orders'      => $totalOrders,
                'total_manufactured'=> $totalManufactured,
                'total_transfers'   => $totalTransfers,
                'low_stock'         => $lowStock,
                'out_of_stock'      => $outOfStock,
                'total_products'    => $totalProducts,
                'total_items'       => $totalItems,
                'total_purchases'   => $totalPurchases,
            ];

            return view('livewire.admin.dashboard.main-dashboard', [
                'stats'            => $stats,
                'recentOrders'     => collect(),
                'recentLogs'       => collect(),
                'kpis'             => $kpis,
                'isGeneralManager' => true,
            ]);
        }

        // ─── Core Stats ──────────────────────────────────────────
        $today = today();

        // Daily stats for Super Admin
        $dailySalesGuest  = DB::table('guest_orders')->whereDate('created_at', $today)->whereIn('status', ['confirmed','completed'])->sum('total_amount');
        $dailySalesOrders = DB::table('orders')->whereDate('placed_at', $today)->where('status','completed')->sum('total_amount');
        $dailySalesBranch = DB::table('branch_sales')->whereDate('sale_date', $today)->sum('total_amount');
        $dailySales       = (float)$dailySalesGuest + (float)$dailySalesOrders + (float)$dailySalesBranch;

        $dailyOrders = DB::table('guest_orders')->whereDate('created_at', $today)->count()
                     + DB::table('orders')->whereDate('placed_at', $today)->count();

        $dailyManufactured = Product::withoutGlobalScopes()
            ->whereIn('type', ['finished_product','bottle_product'])
            ->where('status', 'active')
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($product) use ($today) {
                $meta = $product->metadata ?? [];
                if (!isset($meta['manufacturing_added_at'])) return false;
                try {
                    $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                    if ($addedDate === $today->toDateString()) {
                        $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
                        return $qty > 0;
                    }
                } catch (\Exception $e) {
                    return false;
                }
                return false;
            })
            ->sum(function($p) {
                $meta = $p->metadata ?? [];
                return (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
            });

        $dailyTransfers = DB::table('stock_movements')
            ->whereDate('moved_at', $today)
            ->where('reference_type', 'transfer')
            ->where('direction', 'in')
            ->count();

        $dailyPurchases = DB::table('purchases')
            ->whereDate('purchase_date', $today)
            ->where('status', 'completed')
            ->sum('total_price');

        // Totals
        $totalSales = (float)DB::table('guest_orders')->whereIn('status',['confirmed','completed'])->sum('total_amount')
                    + (float)DB::table('orders')->where('status','completed')->sum('total_amount')
                    + (float)DB::table('branch_sales')->sum('total_amount');

        $totalOrders = DB::table('guest_orders')->count()
                     + DB::table('orders')->count();

        $totalManufactured = Product::withoutGlobalScopes()
            ->whereIn('type', ['finished_product','bottle_product'])
            ->whereNotNull('metadata')
            ->get()
            ->sum(fn($p) => (float)(($p->metadata ?? [])['manufacturing_quantity'] ?? 0));

        $totalTransfers = DB::table('stock_movements')
            ->where('reference_type', 'transfer')
            ->where('direction', 'in')
            ->count();

        // Total Purchases = SUM(available_qty × unit_price) for all products in stock
        $totalPurchases = DB::table('stock_movements')
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->select('products.id', 'products.cost_price', DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as available_qty"))
            ->groupBy('products.id', 'products.cost_price')
            ->get()
            ->sum(function($item) {
                return max(0, (float)$item->available_qty) * (float)$item->cost_price;
            });

        // Total selling price = SUM(available_qty * products.selling_price) for items with selling_price > 0
        // Based on current stock (remaining inventory), not historical purchases
        $totalSellingPrice = DB::table('stock_movements')
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->where('products.selling_price', '>', 0)
            ->select('products.id', 'products.selling_price', DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as available_qty"))
            ->groupBy('products.id', 'products.selling_price')
            ->get()
            ->sum(function($item) {
                return max(0, (float)$item->available_qty) * (float)$item->selling_price;
            });

        // Raw Material Cost = SUM(Total Cost) from all raw/packing material rows
        // Each row = (Remaining Qty × Unit Cost), sum all rows including duplicates from different purchase dates
        // Uses exact logic from getRawMaterialDeductionReport()
        
        // Step 1: Get all purchase rows grouped by product_id + unit_price + purchase_date
        $rawMaterialQuery = DB::table('purchases as p')
            ->join('products', 'p.product_id', '=', 'products.id')
            ->leftJoin('categories', 'p.category_id', '=', 'categories.id')
            ->whereIn('p.purchase_type', ['raw_material', 'packing_material'])
            ->where('p.status', 'completed');
        
        // Apply business scope
        if ($this->selectedBusinessId) {
            $rawMaterialQuery->where('p.business_id', $this->selectedBusinessId);
        } elseif (!$this->isSuperAdmin) {
            $businessIds = $user->assignedBusinessIds();
            $rawMaterialQuery->whereIn('p.business_id', $businessIds);
        }
        
        $rows = $rawMaterialQuery->select([
                'products.id as product_id',
                'products.name as item_name',
                'products.type as product_type',
                'categories.name as category_name',
                'p.unit_price as unit_cost',
                'p.purchase_date as date',
                DB::raw('SUM(p.quantity) as purchased_qty'),
            ])
            ->groupBy('products.id', 'products.name', 'products.type', 'categories.name', 'p.unit_price', 'p.purchase_date')
            ->get();

        // Step 2: Calculate total purchased qty per product (all-time)
        $totalPurchasedQueryMap = DB::table('purchases')
            ->whereIn('purchase_type', ['raw_material', 'packing_material'])
            ->where('status', 'completed');
        
        // Apply business scope to total map query
        if ($this->selectedBusinessId) {
            $totalPurchasedQueryMap->where('business_id', $this->selectedBusinessId);
        } elseif (!$this->isSuperAdmin) {
            $businessIds = $user->assignedBusinessIds();
            $totalPurchasedQueryMap->whereIn('business_id', $businessIds);
        }
        
        $totalPurchasedMap = $totalPurchasedQueryMap->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // Step 3: Build consumed maps from finished product metadata
        $seedConsumedMap = [];
        $bottleConsumedMap = [];
        $totalMfgQty = 0.0;

        $metadataQuery = DB::table('products')
            ->whereIn('type', ['finished_product', 'bottle_product'])
            ->whereNotNull('metadata');
        
        // Apply business scope to metadata query
        if ($this->selectedBusinessId) {
            $metadataQuery->where('business_id', $this->selectedBusinessId);
        } elseif (!$this->isSuperAdmin) {
            $businessIds = $user->assignedBusinessIds();
            $metadataQuery->whereIn('business_id', $businessIds);
        }
        
        $metadataQuery->get(['id', 'name', 'metadata'])
            ->each(function ($fp) use (&$seedConsumedMap, &$bottleConsumedMap, &$totalMfgQty) {
                $meta = json_decode($fp->metadata, true);
                if (empty($meta)) return;

                $mfgQty = (float) ($meta['manufacturing_quantity'] ?? 0);

                // Seed consumed
                $seedName = trim($meta['seed_name'] ?? '');
                $seedQty = (float) ($meta['seed_quantity_used'] ?? 0);
                if ($seedName !== '' && $seedQty > 0) {
                    $k = strtolower($seedName);
                    $seedConsumedMap[$k] = ($seedConsumedMap[$k] ?? 0) + $seedQty;
                }

                // Bottle consumed
                if ($mfgQty > 0) {
                    $k = strtolower(trim($fp->name));
                    $bottleConsumedMap[$k] = ($bottleConsumedMap[$k] ?? 0) + $mfgQty;
                    $totalMfgQty += $mfgQty;
                }
            });

        // Step 4: Calculate total cost per row
        $rawMaterialCost = $rows->sum(function ($r) use ($seedConsumedMap, $bottleConsumedMap, $totalMfgQty, $totalPurchasedMap) {
            $unitCost = (float) $r->unit_cost;
            $totalPurchasedAllTime = round((float) ($totalPurchasedMap[$r->product_id] ?? 0), 3);

            // Detect bottle: has "bottle" in name OR is purely "NNN ml"
            $isBottle = stripos($r->item_name, 'bottle') !== false
                     || preg_match('/^\d+\s*ml$/i', trim($r->item_name));

            // Detect packing: category name contains "packing"
            $isPacking = stripos($r->category_name, 'packing') !== false;

            if ($isBottle) {
                // Consumed = SUM(manufacturing_quantity) of finished products with matching size
                preg_match('/(\d+)\s*ml/i', $r->item_name, $sizeMatch);
                $sizeNum = $sizeMatch[1] ?? '';
                $consumedQty = 0.0;
                foreach ($bottleConsumedMap as $finishedName => $mfgQty) {
                    if ($sizeNum !== '' && preg_match('/\b' . $sizeNum . '\s*ml\b/i', $finishedName)) {
                        $consumedQty += $mfgQty;
                    }
                }
                // Also check StockMovements for this bottle item
                $smConsumed = (float) StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty = max($consumedQty, $smConsumed);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));

            } elseif ($isPacking) {
                // Packing material — 1 unit used per manufactured product
                $smConsumed = (float) StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty = max($totalMfgQty, $smConsumed);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));

            } else {
                // Seeds
                $key = strtolower(trim($r->item_name));
                $consumedQty = (float) ($seedConsumedMap[$key] ?? 0);
                $smConsumed = (float) StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty = max($consumedQty, $smConsumed);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));
            }

            return round($remainingQty * $unitCost, 2);
        });

        $zakatulMal = ((float)$totalSellingPrice + (float)$rawMaterialCost) * 0.025;

        // Total Sold Purchase Cost (all-time, all channels)
        $totalSoldPurchaseCost = (float)DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->sum(DB::raw('order_items.quantity * order_items.cost_price_snapshot'))
        + (float)DB::table('branch_sales')
            ->sum(DB::raw('quantity * cost_price'))
        + (float)collect(DB::table('guest_orders')
            ->whereIn('status', ['confirmed', 'completed'])
            ->pluck('items'))
            ->flatten()
            ->sum(fn($item) => (float)($item['cost_price'] ?? 0) * (int)($item['qty'] ?? 0));

        // Total available items across all branches (from stock_movements)
        $totalItems = DB::table('stock_movements')
            ->where('item_type', 'product')
            ->select('item_id', DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as available_qty"))
            ->groupBy('item_id')
            ->get()
            ->sum(fn($r) => max(0, (float)$r->available_qty));

        if ($this->isSuperAdmin) {
            // Pending Orders: include both orders table AND guest_orders table
            $pendingOrdersFromOrders = Order::where(function($q) {
                $q->where('status', Order::STATUS_PENDING)
                  ->orWhere('payment_status', Order::PAYMENT_PENDING);
            })->count();
            
            $pendingGuestOrders = DB::table('guest_orders')
                ->where('status', 'pending')
                ->count();
            
            $totalPendingOrders = $pendingOrdersFromOrders + $pendingGuestOrders;
            
            $stats = [
                'businesses'       => Business::count(),
                'branches'         => Branch::count(),
                'products'         => Product::count(),
                'orders'           => $this->scopeByBusiness(Order::query())->count(),
                'revenue'          => $kpis['total_revenue'],
                'profit'           => $totalSales - $totalSoldPurchaseCost,
                'profit_margin'    => $kpis['profit_margin_pct'],
                'pending_orders'   => $totalPendingOrders,
                'active_admins'    => User::whereHas('roles', fn($q) => $q->whereIn('name', ['Admin', 'Super Admin']))->count(),
                'active_users'     => User::count(),
                'audit_logs'       => AuditLog::whereDate('created_at', today())->count(),
                'low_stock'        => $kpis['low_stock_count'],
                'out_of_stock'     => $kpis['out_of_stock_count'],
                'inventory_value'  => $kpis['inventory_value'],
                'mfg_profit'       => $kpis['manufacturing_profit'],
                'oil_output_l'     => $kpis['oil_output_liters'],
                'active_batches'   => $kpis['active_batches'],
                'purchase_cost'    => $kpis['total_purchase_cost'],
                'raw_material_cost' => (float)$rawMaterialCost,
                'total_items'       => (int)$totalItems,
                // Daily
                'daily_sales'        => $dailySales,
                'daily_orders'       => $dailyOrders,
                'daily_manufactured' => $dailyManufactured,
                'daily_transfers'    => $dailyTransfers,
                'daily_purchases'    => $dailyPurchases,
                // Totals
                'total_sales'        => $totalSales,
                'total_orders'       => $totalOrders,
                'total_manufactured' => $totalManufactured,
                'total_transfers'    => $totalTransfers,
                'total_purchases'    => $totalPurchases,
                'total_selling_price'=> $totalSellingPrice,
                'zakat_ul_mal'       => $zakatulMal,
                'total_sold_purchase_cost' => $totalSoldPurchaseCost,
            ];
        } else {
            // Admin scope
            $businessIds = $user->assignedBusinessIds();
            $stats = [
                'businesses'    => count($businessIds),
                'branches'      => Branch::whereIn('business_id', $businessIds)->count(),
                'products'      => Product::whereIn('business_id', $businessIds)->count(),
                'orders'        => Order::whereIn('business_id', $businessIds)->count(),
                'revenue'       => $kpis['total_revenue'],
                'profit'        => $kpis['total_profit'],
                'profit_margin' => $kpis['profit_margin_pct'],
                'pending_orders'=> $kpis['pending_orders'],
                'low_stock'     => $kpis['low_stock_count'],
                'out_of_stock'  => $kpis['out_of_stock_count'],
                'inventory_value' => $kpis['inventory_value'],
                'purchase_cost' => $kpis['total_purchase_cost'],
            ];
        }

        // ─── Recent Orders (scoped) ───────────────────────────────
        $recentOrders = $this->scopeByBusiness(
            Order::with('customer')->orderBy('created_at', 'desc')->limit(8)
        )->get();

        // ─── Recent Audit Logs (Super Admin full, Admin scoped) ───
        $recentLogs = $this->isSuperAdmin
            ? AuditLog::with('user')->orderBy('created_at', 'desc')->limit(10)->get()
            : AuditLog::with('user')
                ->whereIn('business_id', $user->assignedBusinessIds())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

        return view('livewire.admin.dashboard.main-dashboard', [
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
            'recentLogs'   => $recentLogs,
            'kpis'         => $kpis,
        ]);
    }
}


