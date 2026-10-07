<?php

namespace App\Livewire\Business\BookStore;

use App\Models\ApprovalRequest;

use App\Models\ProductCollection;
use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public $businessId;
    public $branchId;

    public function mount()
    {
        $this->businessId = auth()->user()->roles()->first()->pivot->business_id ?? 1;
        $this->branchId = auth()->user()->roles()->first()->pivot->branch_id ?? null;
    }

    public function render()
    {
        // 1. Inventory Metrics
        $inventoryQuery = \App\Models\StockMovement::where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->where('stock_movements.business_id', $this->businessId)
            ->when($this->branchId, function ($q) {
                $q->where('stock_movements.branch_id', $this->branchId);
            })
            ->select(
                'products.cost_price',
                'products.reorder_point',
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty")
            )
            ->groupBy('stock_movements.location_id', 'products.id', 'products.cost_price', 'products.reorder_point');

        $inventoryData = $inventoryQuery->get();
        
        $totalBooks = $inventoryData->sum('qty');
        $lowStockCount = $inventoryData->filter(fn($r) => $r->qty <= 5 && $r->qty > 0)->count();
        $outOfStockCount = $inventoryData->filter(fn($r) => $r->qty <= 0)->count();
        $inventoryValue = $inventoryData->sum(fn($r) => $r->qty * $r->cost_price);

        // 2. Sales Metrics (using branch_sales / profit_ledger or invoices for total sales)
        // Since we are looking at total sales, let's use invoices joined with payments or branch_sales
        $salesQuery = \App\Models\BranchSale::whereHas('branch', function ($q) {
            $q->where('business_id', $this->businessId);
        });
            
        $todaySales = (clone $salesQuery)->whereDate('sale_date', today())->sum('total_amount');
        $monthlySales = (clone $salesQuery)->whereMonth('sale_date', now()->month)->sum('total_amount');
        $recentOrders = \App\Models\Invoice::whereHas('order.items.fulfillingBranch', function ($q) {
                $q->where('business_id', $this->businessId);
            })->orderBy('created_at', 'desc')->take(5)->get();

        // 3. Requests Metrics
        $pendingRequests = ApprovalRequest::where('business_id', $this->businessId)
            ->whereIn('status', ['draft', 'submitted', 'under_review'])
            ->count();
        
        $recentRequests = ApprovalRequest::where('business_id', $this->businessId)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // 4. Products / Collections
        $collections = ProductCollection::where('business_id', $this->businessId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('livewire.business.book-store.dashboard', compact(
            'totalBooks',
            'lowStockCount',
            'outOfStockCount',
            'inventoryValue',
            'todaySales',
            'monthlySales',
            'recentOrders',
            'pendingRequests',
            'recentRequests',
            'collections'
        ));
    }
}
