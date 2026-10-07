<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Location;
use App\Services\SalesService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BranchSellerDashboard extends Component
{
    // The primary branch this seller is assigned to
    public ?int $branchId   = null;
    public ?int $businessId = null;

    // Tabs: pending | confirmed | processing | completed
    public string $activeTab = 'pending';

    // Order being actioned in the modal
    public ?int $actionOrderId = null;
    public string $rejectReason = '';

    public string $successMessage = '';
    public string $errorMessage   = '';

    // ── Boot ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $user = auth()->user();

        $branchIds = $user->assignedBranchIds();
        if (empty($branchIds)) {
            return;
        }

        $this->branchId = $branchIds[0];

        // Derive business from first assigned branch
        $branch = \App\Models\Branch::find($this->branchId);
        $this->businessId = $branch?->business_id;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  KPI PROPERTIES  — all computed from real DB data
    // ══════════════════════════════════════════════════════════════════════

    #[Computed]
    public function kpis(): array
    {
        if (!$this->branchId) return $this->emptyKpis();

        $today      = now()->startOfDay();
        $weekStart  = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        // ── Get location IDs that belong to this branch ───────────────────
        $locationIds = Location::where('branch_id', $this->branchId)->pluck('id');

        // ── 1. Total Categories (counting distinct products with stock) ───
        // Count number of distinct products that have stock in this branch
        $totalCategories = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->select('item_id', DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as qty"))
            ->groupBy('item_id')
            ->having('qty', '>', 0)
            ->count();

        // ── 2. Total Item Quantity ────────────────────────────────────────
        // Sum of all product quantities currently in stock at this branch
        $totalItemQuantity = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->select(DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as total_qty"))
            ->value('total_qty') ?? 0;

        // ── 3. Total Transferred Products ─────────────────────────────────
        // Count distinct products that have been transferred to this branch TODAY
        $totalTransferredProducts = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('movement_type', StockMovement::TYPE_TRANSFER_IN)
            ->whereDate('moved_at', today())
            ->distinct()
            ->count('item_id');

        // ── 4. Total Daily Sales ──────────────────────────────────────────
        // Sum of branch sales for today at this branch
        $totalDailySales = DB::table('branch_sales')
            ->where('branch_id', $this->branchId)
            ->whereDate('sale_date', today())
            ->sum('total_amount');

        // ── Order counts (keep existing) ──────────────────────────────────
        $orderBase = Order::forBranch($this->branchId);

        $todayOrders   = (clone $orderBase)->whereDate('placed_at', today())->count();
        $pendingOrders = (clone $orderBase)
            ->where('status', Order::STATUS_PENDING)
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->count();
        $paymentPending = (clone $orderBase)
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->count();
        $paymentApproved = (clone $orderBase)
            ->where('payment_status', Order::PAYMENT_APPROVED)
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_PROCESSING])
            ->count();
        $processingOrders = (clone $orderBase)
            ->where('status', Order::STATUS_PROCESSING)
            ->count();
        $completedOrders = (clone $orderBase)
            ->where('status', Order::STATUS_COMPLETED)
            ->count();

        // ── Sales values from completed orders (keep existing) ────────────
        $completedBase = (clone $orderBase)->where('status', Order::STATUS_COMPLETED);

        $todaySales   = (clone $completedBase)
            ->whereDate('placed_at', today())
            ->sum('total_amount');
        $weeklySales  = (clone $completedBase)
            ->where('placed_at', '>=', $weekStart)
            ->sum('total_amount');
        $monthlySales = (clone $completedBase)
            ->where('placed_at', '>=', $monthStart)
            ->sum('total_amount');

        // ── Branch stock (keep existing) ──────────────────────────────────
        $branchStock = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->whereIn('products.type', Product::SALEABLE_PRODUCT_TYPES)
            ->select(
                'products.id',
                DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as qty")
            )
            ->groupBy('products.id')
            ->having('qty', '>', 0)
            ->get();

        $availableProducts = $branchStock->count();
        $totalStockUnits   = $branchStock->sum('qty');

        return [
            // NEW KPIs (top priority)
            'total_categories'         => $totalCategories,
            'total_item_quantity'      => round($totalItemQuantity, 2),
            'total_transferred_products' => $totalTransferredProducts,
            'total_daily_sales'        => round($totalDailySales, 2),
            // Orders
            'today_orders'      => $todayOrders,
            'pending_orders'    => $pendingOrders,
            'payment_pending'   => $paymentPending,
            'payment_approved'  => $paymentApproved,
            'processing_orders' => $processingOrders,
            'completed_orders'  => $completedOrders,
            // Inventory
            'available_products'=> $availableProducts,
            'total_stock_units' => round($totalStockUnits, 2),
            // Sales
            'today_sales'       => round($todaySales, 2),
            'weekly_sales'      => round($weeklySales, 2),
            'monthly_sales'     => round($monthlySales, 2),
        ];
    }

    // ── Orders list for active tab ────────────────────────────────────────

    #[Computed]
    public function orders(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if (!$this->branchId) {
            return Order::whereRaw('1=0')->paginate(10);
        }

        $query = Order::forBranch($this->branchId)
            ->with(['customer', 'items.product', 'invoice'])
            ->orderByDesc('placed_at');

        match ($this->activeTab) {
            'pending'    => $query->where('payment_status', Order::PAYMENT_PENDING),
            'confirmed'  => $query->where('payment_status', Order::PAYMENT_APPROVED)
                                  ->whereIn('status', [Order::STATUS_CONFIRMED]),
            'processing' => $query->where('status', Order::STATUS_PROCESSING),
            'completed'  => $query->where('status', Order::STATUS_COMPLETED),
            default      => null,
        };

        return $query->paginate(15);
    }

    // ── Branch stock table ────────────────────────────────────────────────

    #[Computed]
    public function branchInventory(): \Illuminate\Support\Collection
    {
        if (!$this->branchId) return collect();

        $locationIds = Location::where('branch_id', $this->branchId)->pluck('id');

        return StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('products.type', Product::SALEABLE_PRODUCT_TYPES)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.unit',
                'products.selling_price',
                'categories.name as category_name',
                DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as qty_on_hand"),
                DB::raw("SUM(CASE WHEN movement_type='purchase' THEN quantity ELSE 0 END) as purchased"),
                DB::raw("SUM(CASE WHEN movement_type='sale' THEN quantity ELSE 0 END) as sold")
            )
            ->groupBy(
                'products.id', 'products.name', 'products.sku',
                'products.unit', 'products.selling_price', 'categories.name'
            )
            ->having('qty_on_hand', '>', 0)
            ->orderBy('products.name')
            ->get();
    }

    // ── Stock grouped by category ─────────────────────────────────────────

    #[Computed]
    public function categoryStock(): \Illuminate\Support\Collection
    {
        if (!$this->branchId) return collect();

        $locationIds = Location::where('branch_id', $this->branchId)->pluck('id');

        return StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products',   'stock_movements.item_id',    '=', 'products.id')
            ->join('categories', 'products.category_id',       '=', 'categories.id')
            ->whereIn('products.type', Product::SALEABLE_PRODUCT_TYPES)
            ->whereNull('products.deleted_at')
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('COUNT(DISTINCT products.id) as product_count'),
                DB::raw("SUM(CASE WHEN stock_movements.direction='in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) as total_qty")
            )
            ->groupBy('categories.id', 'categories.name')
            ->having('total_qty', '>', 0)
            ->orderBy('categories.name')
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ACTIONS
    // ══════════════════════════════════════════════════════════════════════

    public function setTab(string $tab): void
    {
        $this->activeTab    = $tab;
        $this->successMessage = '';
        $this->errorMessage   = '';
        unset($this->orders);   // clear computed cache
    }

    public function approvePayment(int $orderId): void
    {
        $this->clearMessages();

        try {
            $order = $this->loadAuthorisedOrder($orderId);
            app(SalesService::class)->approvePayment($order, auth()->user());
            $this->successMessage = "Payment approved for order #{$order->order_number}.";
            unset($this->orders, $this->kpis);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function rejectPayment(int $orderId): void
    {
        $this->clearMessages();

        try {
            $order = $this->loadAuthorisedOrder($orderId);
            app(SalesService::class)->rejectPayment($order, auth()->user(), $this->rejectReason);
            $this->successMessage = "Payment rejected for order #{$order->order_number}.";
            $this->rejectReason   = '';
            unset($this->orders, $this->kpis);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function processOrder(int $orderId): void
    {
        $this->clearMessages();

        try {
            $order = $this->loadAuthorisedOrder($orderId);
            app(SalesService::class)->processOrder($order, auth()->user());
            $this->successMessage = "Order #{$order->order_number} moved to processing.";
            unset($this->orders, $this->kpis);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function completeOrder(int $orderId): void
    {
        $this->clearMessages();

        try {
            $order = $this->loadAuthorisedOrder($orderId);
            app(SalesService::class)->completeOrder($order, auth()->user());
            $this->successMessage = "Order #{$order->order_number} completed. Stock deducted.";
            unset($this->orders, $this->kpis, $this->branchInventory);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function loadAuthorisedOrder(int $orderId): Order
    {
        $order = Order::with(['items'])->findOrFail($orderId);

        // Ensure this order belongs to the seller's branch
        $sellerBranchIds = auth()->user()->assignedBranchIds();
        $orderBranchIds  = $order->items->pluck('fulfilling_branch_id')
            ->push($order->branch_id)
            ->filter()->unique()->values()->toArray();

        if (!empty($orderBranchIds) && empty(array_intersect($sellerBranchIds, $orderBranchIds))) {
            throw new \RuntimeException('You are not authorised to manage this order.');
        }

        return $order;
    }

    private function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage   = '';
    }

    private function emptyKpis(): array
    {
        return [
            // New KPIs
            'total_categories' => 0,
            'total_item_quantity' => 0,
            'total_transferred_products' => 0,
            'total_daily_sales' => 0,
            // Orders
            'today_orders' => 0, 'pending_orders' => 0, 'payment_pending' => 0,
            'payment_approved' => 0, 'processing_orders' => 0, 'completed_orders' => 0,
            // Inventory
            'available_products' => 0, 'total_stock_units' => 0,
            // Sales
            'today_sales' => 0, 'weekly_sales' => 0, 'monthly_sales' => 0,
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard.branch-seller-dashboard')
            ->title('Seller Dashboard');
    }
}
