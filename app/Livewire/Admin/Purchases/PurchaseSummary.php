<?php

namespace App\Livewire\Admin\Purchases;

use App\Models\Business;
use App\Models\Purchase;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class PurchaseSummary extends Component
{
    public string $businessId = '';
    public string $dateFrom   = '';
    public string $dateTo     = '';
    public string $purchaseType = ''; // raw_material | packing_material | product

    public array $businesses = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();

        // Default to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo   = now()->endOfMonth()->format('Y-m-d');
    }

    private function baseQuery()
    {
        $user = auth()->user();

        return Purchase::with(['business', 'product.category'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->dateFrom,   fn($q) => $q->where('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo,     fn($q) => $q->where('purchase_date', '<=', $this->dateTo))
            ->when($this->purchaseType, fn($q) => $q->where('purchase_type', $this->purchaseType))
            ->where('status', 'completed');
    }

    public function render()
    {
        $user = auth()->user();

        // ── Global KPIs ────────────────────────────────────────
        $totalPurchased  = (clone $this->baseQuery())->sum('total_price');
        $totalQty        = (clone $this->baseQuery())->sum('quantity');
        $purchaseCount   = (clone $this->baseQuery())->count();

        // ── By Purchase Type ───────────────────────────────────
        $byType = (clone $this->baseQuery())
            ->selectRaw('purchase_type, SUM(total_price) as total_value, SUM(quantity) as total_qty, COUNT(id) as count')
            ->groupBy('purchase_type')
            ->get();

        // ── By Category ────────────────────────────────────────
        $byCategory = (clone $this->baseQuery())
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, SUM(purchases.total_price) as total_value, SUM(purchases.quantity) as total_qty, purchases.unit')
            ->groupBy('categories.name', 'purchases.unit')
            ->orderByDesc('total_value')
            ->get();

        // ── By Business (for super admin global view) ──────────
        $byBusiness = [];
        if ($user->isSuperAdmin() && !$this->businessId) {
            $byBusiness = (clone $this->baseQuery())
                ->join('businesses', 'purchases.business_id', '=', 'businesses.id')
                ->selectRaw('businesses.name as business_name, SUM(purchases.total_price) as total_value, COUNT(purchases.id) as count')
                ->groupBy('businesses.name')
                ->orderByDesc('total_value')
                ->get();
        }

        // ── Recent Purchases ───────────────────────────────────
        $recent = (clone $this->baseQuery())
            ->with(['product', 'business'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $purchaseTypes = ['' => 'All Types'] + Purchase::PURCHASE_TYPES;

        return view('livewire.admin.purchases.summary', [
            'totalPurchased'  => $totalPurchased,
            'totalQty'        => $totalQty,
            'purchaseCount'   => $purchaseCount,
            'byType'          => $byType,
            'byCategory'      => $byCategory,
            'byBusiness'      => $byBusiness,
            'recent'          => $recent,
            'purchaseTypes'   => $purchaseTypes,
        ])->title('Purchase Summary');
    }
}
