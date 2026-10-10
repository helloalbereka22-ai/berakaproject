<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Business;
use App\Models\FinishedGood;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ManufacturingDashboard extends Component
{
    public ?int  $selectedBusinessId = null;
    public array $businesses         = [];
    public bool  $isSuperAdmin       = false;

    // Period filter
    public string $dateFrom = '';
    public string $dateTo   = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->isSuperAdmin = $user->isSuperAdmin();

        if ($this->isSuperAdmin) {
            $this->businesses         = Business::orderBy('name')->get(['id', 'name'])->toArray();
            $this->selectedBusinessId = session('mfg_business_context');
        } else {
            $this->selectedBusinessId = $user->assignedBusinessId();
        }

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function switchBusiness(?int $businessId): void
    {
        if (!$this->isSuperAdmin) abort(403);
        $this->selectedBusinessId = $businessId;
        session(['mfg_business_context' => $businessId]);
        unset($this->kpis, $this->rawMaterialStock, $this->packagingStock,
              $this->finishedProductStock, $this->recentBatches, $this->recentPurchases);
    }

    // ── Computed KPIs ─────────────────────────────────────────────────────

    #[Computed]
    public function kpis(): array
    {
        $today       = now()->startOfDay();
        $weekStart   = now()->startOfWeek();
        $monthStart  = now()->startOfMonth();
        $biz         = $this->selectedBusinessId;
        $user        = auth()->user();

        $batchBase = ProductionBatch::query()
            ->when($biz, fn($q) => $q->where('business_id', $biz))
            ->when(!$this->isSuperAdmin, fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            );

        // Production output from stock_movements (type = production_output)
        $outputBase = StockMovement::where('movement_type', 'production_output')
            ->when($biz, fn($q) => $q->where('business_id', $biz))
            ->when(!$this->isSuperAdmin, fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            );

        $todayOutput   = (clone $outputBase)->where('moved_at', '>=', $today)->sum('quantity');
        $weeklyOutput  = (clone $outputBase)->where('moved_at', '>=', $weekStart)->sum('quantity');
        $monthlyOutput = (clone $outputBase)->where('moved_at', '>=', $monthStart)->sum('quantity');

        // Batch counts
        $activeBatches    = (clone $batchBase)->whereIn('status', ['open', 'in_qc', 'approved'])->count();
        $completedBatches = (clone $batchBase)->where('status', 'completed')->count();
        $totalBatches     = (clone $batchBase)->count();

        // Material consumption this month (raw + packing)
        $consumptionBase = StockMovement::query()
            ->whereIn('movement_type', ['production_consumption', 'packing_consumption'])
            ->where('moved_at', '>=', $monthStart)
            ->when($biz, fn($q) => $q->where('business_id', $biz))
            ->when(!$this->isSuperAdmin, fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            );

        $monthlyRawConsumed  = (clone $consumptionBase)->where('movement_type', 'production_consumption')->sum('quantity');
        $monthlyPackConsumed = (clone $consumptionBase)->where('movement_type', 'packing_consumption')->sum('quantity');
        $monthlyConsumCost   = (clone $consumptionBase)->sum('total_cost');

        // Finished goods available
        $fgBase = FinishedGood::where('status', FinishedGood::STATUS_AVAILABLE)
            ->when(!$this->isSuperAdmin, function ($q) use ($user) {
                $q->whereHas('batch', fn($pb) =>
                    $pb->whereIn('business_id', $user->assignedBusinessIds())
                );
            })
            ->when($biz, function ($q) use ($biz) {
                $q->whereHas('batch', fn($pb) => $pb->where('business_id', $biz));
            });

        $fgAvailable = (clone $fgBase)->sum('quantity_available');

        // Low stock raw materials
        $rawMats = Product::withoutGlobalScopes()
            ->rawMaterials()
            ->when($biz, fn($q) => $q->where('business_id', $biz))
            ->when(!$this->isSuperAdmin, fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->where('reorder_point', '>', 0)
            ->get();

        $lowStockCount = $rawMats->filter(fn($p) =>
            $p->current_stock > 0 && $p->current_stock <= $p->reorder_point
        )->count();

        $outOfStockCount = $rawMats->filter(fn($p) => $p->current_stock <= 0)->count();

        return [
            'today_output'            => (float) $todayOutput,
            'weekly_output'           => (float) $weeklyOutput,
            'monthly_output'          => (float) $monthlyOutput,
            'active_batches'          => $activeBatches,
            'completed_batches'       => $completedBatches,
            'total_batches'           => $totalBatches,
            'monthly_raw_consumed_kg' => (float) $monthlyRawConsumed,
            'monthly_pack_consumed'   => (float) $monthlyPackConsumed,
            'monthly_consump_cost'    => (float) $monthlyConsumCost,
            'fg_available'            => (float) $fgAvailable,
            'low_stock_count'         => $lowStockCount,
            'out_of_stock_count'      => $outOfStockCount,
        ];
    }

    #[Computed]
    public function rawMaterialStock(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        return Product::withoutGlobalScopes()
            ->rawMaterials()
            ->when($this->selectedBusinessId, fn($q) => $q->where('business_id', $this->selectedBusinessId))
            ->when(!$this->isSuperAdmin, fn($q) => $q->whereIn('business_id', $user->assignedBusinessIds()))
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function packagingStock(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        return Product::withoutGlobalScopes()
            ->packagingMaterials()
            ->when($this->selectedBusinessId, fn($q) => $q->where('business_id', $this->selectedBusinessId))
            ->when(!$this->isSuperAdmin, fn($q) => $q->whereIn('business_id', $user->assignedBusinessIds()))
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function finishedProductStock(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        return FinishedGood::with(['product.category', 'batch', 'warehouseLocation.branch'])
            ->where('status', FinishedGood::STATUS_AVAILABLE)
            ->where('quantity_available', '>', 0)
            ->when(!$this->isSuperAdmin, function ($q) use ($user) {
                $q->whereHas('batch', fn($pb) =>
                    $pb->whereIn('business_id', $user->assignedBusinessIds())
                );
            })
            ->when($this->selectedBusinessId, function ($q) {
                $q->whereHas('batch', fn($pb) =>
                    $pb->where('business_id', $this->selectedBusinessId)
                );
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function recentBatches(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        return ProductionBatch::with(['manufacturingOrder.product', 'supervisor'])
            ->when($this->selectedBusinessId, fn($q) => $q->where('business_id', $this->selectedBusinessId))
            ->when(!$this->isSuperAdmin, fn($q) => $q->whereIn('business_id', $user->assignedBusinessIds()))
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function recentPurchases(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        return Purchase::with(['product.category', 'branch'])
            ->whereHas('product', fn($q) =>
                $q->whereIn('type', [Product::TYPE_RAW_MATERIAL, Product::TYPE_PACKING_MATERIAL])
            )
            ->when($this->selectedBusinessId, fn($q) => $q->where('business_id', $this->selectedBusinessId))
            ->when(!$this->isSuperAdmin, fn($q) => $q->whereIn('business_id', $user->assignedBusinessIds()))
            ->where('status', 'completed')
            ->orderByDesc('purchase_date')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        $user = auth()->user();
        $isMfrStaff = $user->can('manufacturing.produce')
            && !$user->can('business.update')
            && !$user->isSuperAdmin();

        return view('livewire.admin.manufacturing.dashboard', [
            'isMfrStaff' => $isMfrStaff
        ])->title('Manufacturing Dashboard');
    }

    // ── Manufacturer Staff KPIs ───────────────────────────────────────────
    // Scoped to the staff member's assigned business/branch
    #[Computed]
    public function getStaffKpis(): array
    {
        $bizId    = $this->selectedBusinessId;
        $today    = now()->toDateString();
        $user     = auth()->user();
        $bizIds   = $user->isSuperAdmin() ? null : $user->assignedBusinessIds();
        $branchIds = $user->isSuperAdmin() ? null : $user->assignedBranchIds();

        // 1. Total Products — products with manufacturing_quantity > 0 in metadata
        $totalProducts = \App\Models\Product::withoutGlobalScopes()
            ->whereNotIn('type', [\App\Models\Product::TYPE_RAW_MATERIAL, \App\Models\Product::TYPE_PACKING_MATERIAL])
            ->when($bizId,   fn($q) => $q->where('business_id', $bizId))
            ->when($bizIds,  fn($q) => $q->whereIn('business_id', $bizIds))
            ->whereNotNull('metadata')
            ->get()
            ->filter(fn($p) => (float)(($p->metadata ?? [])['manufacturing_quantity'] ?? 0) > 0)
            ->count();

        // 2. Today's Total Selling Price — sum of branch_sales.total_amount for today, filtered by user's branch
        $todaySalesQuery = \App\Models\BranchSale::withoutGlobalScopes()
            ->where('sale_date', $today)
            ->when($bizId, fn($q) => $q->whereHas('branch', fn($b) => $b->where('business_id', $bizId)))
            ->when($bizIds, fn($q) => $q->whereHas('branch', fn($b) => $b->whereIn('business_id', $bizIds)));

        // If user has branch restrictions, filter by their branches
        if ($branchIds && !$user->isSuperAdmin()) {
            $todaySalesQuery = $todaySalesQuery->whereIn('branch_id', $branchIds);
        }

        $todaySelling = $todaySalesQuery->sum('total_amount');

        // 3. Total Raw Materials — sum of available qty across all raw material Purchase records
        $totalRawMaterials = \App\Models\Purchase::withoutGlobalScopes()
            ->where('purchase_type', \App\Models\Purchase::TYPE_RAW_MATERIAL)
            ->where('status', \App\Models\Purchase::STATUS_COMPLETED)
            ->where('quantity', '>', 0)
            ->when($bizId,  fn($q) => $q->where('business_id', $bizId))
            ->when($bizIds, fn($q) => $q->whereIn('business_id', $bizIds))
            ->when($branchIds && !$user->isSuperAdmin(), fn($q) => $q->whereIn('branch_id', $branchIds))
            ->sum('quantity');

        // 4. Total Used Raw Materials — sum of seed_quantity_used stored in product metadata
        $productsQuery = \App\Models\Product::withoutGlobalScopes()
            ->whereNotNull('metadata')
            ->when($bizId,  fn($q) => $q->where('business_id', $bizId))
            ->when($bizIds, fn($q) => $q->whereIn('business_id', $bizIds))
            ->get();

        $totalUsedRaw = $productsQuery->sum(function($p) use ($branchIds, $user) {
            $meta = $p->metadata ?? [];
            $seedQtyUsed = (float)($meta['seed_quantity_used'] ?? 0);
            
            // If user has branch restrictions and is not super admin, check if this product was manufactured by their branch
            if ($branchIds && !$user->isSuperAdmin()) {
                $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                // Only count if user's branch has manufacturing data for this product
                $hasBranch = false;
                foreach ($branchIds as $branchId) {
                    if (isset($manufacturingByBranch[(int)$branchId]) || isset($manufacturingByBranch[(string)$branchId])) {
                        $hasBranch = true;
                        break;
                    }
                }
                if (!$hasBranch) {
                    return 0;
                }
            }
            
            return $seedQtyUsed;
        });

        return [
            'total_products'    => $totalProducts,
            'today_selling'     => (float) $todaySelling,
            'total_raw'         => (float) $totalRawMaterials,
            'total_used_raw'    => (float) $totalUsedRaw,
        ];
    }

    // Add property to make it accessible as $this->staffKpis in views
    public function __get($name)
    {
        if ($name === 'staffKpis') {
            return $this->getStaffKpis();
        }
        if ($name === 'dailyManufactured') {
            return $this->getDailyManufactured();
        }
        return null;
    }

    // ── Daily Manufactured Items ───────────────────────────────────────────
    // NOT using #[Computed] so it always fetches fresh data
    public function getDailyManufactured()
    {
        $user = auth()->user();
        
        // Determine which businesses and branches to query
        if ($user->isSuperAdmin()) {
            $businessIds = \App\Models\Business::pluck('id')->toArray();
            $branchIds = null; // Show all branches
        } else {
            // Non-super-admin: only their assigned branches
            $businessIds = $user->assignedBusinessIds();
            $branchIds = $user->assignedBranchIds();
        }
        
        // If there's a selected business, use that
        if ($this->selectedBusinessId) {
            $businessIds = [$this->selectedBusinessId];
            // For selected business, still show all branches unless user is restricted
            if (!$user->isSuperAdmin()) {
                $branchIds = $user->assignedBranchIds();
            }
        }

        // Get products with manufacturing activity added today
        $today = now()->toDateString();

        $query = \App\Models\Product::query()
            ->with(['category', 'business'])
            ->whereIn('business_id', $businessIds)
            ->where('status', 'active')
            ->whereNotNull('metadata');

        $items = $query->get()
            ->filter(function ($product) use ($today, $branchIds, $user) {
                $meta = $product->metadata ?? [];
                
                // Check if product has manufacturing_added_at timestamp
                if (!isset($meta['manufacturing_added_at'])) {
                    return false;
                }

                try {
                    // Parse ISO8601 string or timestamp
                    $addedDate = \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateString();
                    
                    // If added today, check quantity
                    if ($addedDate === $today) {
                        // For non-super-admin: check if THIS BRANCH manufactured TODAY
                        if ($branchIds && !$user->isSuperAdmin()) {
                            // Check per-branch daily qty (NEW MFR today, not lifetime total)
                            $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
                            
                            // Only include if this branch has a daily entry (NEW MFR today)
                            foreach ($branchIds as $branchId) {
                                $branchIdInt = (int)$branchId;
                                if (isset($dailyByBranch[$branchIdInt]) && (float)$dailyByBranch[$branchIdInt] > 0) {
                                    return true;
                                }
                            }
                            
                            // Fallback: for old products without per-branch daily tracking,
                            // check if manufacturing happened today by looking at manufacturing_daily_qty
                            $dailyTotal = (float)($meta['manufacturing_daily_qty'] ?? 0);
                            if ($dailyTotal > 0) {
                                // Now verify this branch actually manufactured something today
                                // by checking if they have any entry in manufacturing_by_branch
                                $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                                foreach ($branchIds as $branchId) {
                                    if (isset($manufacturingByBranch[$branchId]) && (float)$manufacturingByBranch[$branchId] > 0) {
                                        return true;
                                    }
                                }
                            }
                            
                            return false;
                        }

                        // Super admin: include any product manufactured today
                        $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
                        if ($qty <= 0) {
                            return false;
                        }
                        return true;
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

        return $items->map(function ($product) use ($branchIds, $user) {
            $meta = $product->metadata ?? [];
            
            // Extract NEW MFR (newly manufactured today) quantity based on user's branch(es)
            $qty = 0;
            
            if ($branchIds && !$user->isSuperAdmin()) {
                // Non-super-admin: sum NEW MFR quantities from their assigned branches only
                $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
                foreach ($branchIds as $branchId) {
                    $branchIdInt = (int)$branchId;
                    if (isset($dailyByBranch[$branchIdInt])) {
                        $qty += (float)$dailyByBranch[$branchIdInt];
                    }
                }
                
                // Graceful fallback for old products without manufacturing_daily_qty_by_branch:
                // If we got 0 qty but the product has manufacturing_by_branch data, use that
                if ($qty == 0 && isset($meta['manufacturing_by_branch'])) {
                    $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                    foreach ($branchIds as $branchId) {
                        if (isset($manufacturingByBranch[$branchId])) {
                            $qty += (float)$manufacturingByBranch[$branchId];
                        }
                    }
                }
            } else {
                // Super admin: sum NEW MFR from all branches for the day
                $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
                foreach ($dailyByBranch as $dailyQty) {
                    $qty += (float)$dailyQty;
                }
                
                // Graceful fallback for old products without per-branch tracking
                if ($qty == 0) {
                    $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
                }
            }
            
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
    

}
