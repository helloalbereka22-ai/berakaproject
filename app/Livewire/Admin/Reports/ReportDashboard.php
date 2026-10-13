<?php

namespace App\Livewire\Admin\Reports;

use App\Models\BatchMaterialConsumption;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\FinishedGood;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ReportDashboard extends Component
{
    // ── Active tab ────────────────────────────────────────────────────────
    public string $activeTab = 'raw_material_deduction';

    // Tab options (for sidebar navigation)
    public const TABS = [
        'executive'              => 'Executive Summary',
        'stock_changes'          => 'Stock Changes',
        'raw_material_deduction' => 'Deducted Raw Materials',
        'sales'                  => 'Sales Report',
        'profit'                 => 'Profit Analysis',
        'manufacturing'          => 'Manufacturing',
        'gm_manufacturing'       => 'GM Manufacturing',
        'purchasing'             => 'Purchasing',
        'branches'               => 'Branch Performance',
        'manufacturer_report'    => 'Manufacturer Report',
    ];

    // ── Filters ───────────────────────────────────────────────────────────
    public string $dateFrom    = '';
    public string $dateTo      = '';
    public string $businessId  = '';
    public string $branchId    = '';
    public string $warehouseId = '';
    public string $productId   = '';
    public string $categoryId  = '';
    public string $batchId     = '';

    // ── Manufacturer Report filters ───────────────────────────────────────
    public string $mfrReportType       = 'product';   // 'product' or 'purchase'
    public string $mfrCategoryId       = '';
    public string $mfrItemId           = '';
    public $mfrCategories              = [];
    public $mfrItems                   = [];

    // ── Drill-down state (for exec dashboard) ────────────────────────────
    public ?int $drillBusinessId = null;
    public ?int $drillBranchId   = null;

    // ── Selector data ─────────────────────────────────────────────────────
    public $businesses = [];
    public $branches   = [];
    public $categories = [];
    public $products   = [];

    public function mount(): void
    {
        $this->authorize('report.view');

        // Set default dates to today for all reports (format: Y-m-d for HTML date input)
        $this->dateFrom = now()->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');

        $this->businesses    = Business::orderBy('name')->get(['id', 'name']);
        $this->categories    = Category::withoutGlobalScopes()->orderBy('name')->get(['id', 'name']);
        $this->branches      = collect();
        $this->products      = collect();
        $this->mfrCategories = collect();
        $this->mfrItems      = collect();

        $this->loadMfrCategories();

        // Manufacturing staff only see the Mfr Report tab — land there directly
        $isManufacturingStaff = auth()->user()->can('manufacturing.produce')
            && !auth()->user()->can('business.update')
            && !auth()->user()->isSuperAdmin();

        // Sellers only see Sales and Transfers — land on Sales
        $isSeller = auth()->user()->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();

        if ($isManufacturingStaff) {
            $this->activeTab = 'manufacturer_report';
        } elseif ($isSeller) {
            $this->activeTab = 'sales';
        } else {
            // Super Admin defaults to stock_changes with today's date
            $this->activeTab = 'stock_changes';
        }
    }

    public function updatedBusinessId(): void
    {
        $this->branchId   = '';
        $this->categoryId = '';
        $this->productId  = '';
        $this->branches   = $this->businessId
            ? Branch::where('business_id', $this->businessId)->orderBy('name')->get(['id', 'name'])
            : collect();
        $this->categories = $this->businessId
            ? Category::withoutGlobalScopes()->where('business_id', $this->businessId)->orderBy('name')->get(['id', 'name'])
            : Category::withoutGlobalScopes()->orderBy('name')->get(['id', 'name']);
        $this->products = collect();
    }

    public function updatedCategoryId(): void
    {
        $this->productId = '';
        $this->products  = $this->categoryId
            ? Product::withoutGlobalScopes()
                ->where('category_id', $this->categoryId)
                ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();
    }

    // ── Manufacturer Report filter handlers ───────────────────────────────
    public function setMfrReportType(string $type): void
    {
        $this->mfrReportType = $type;
        $this->mfrCategoryId = '';
        $this->mfrItemId     = '';
        $this->mfrItems      = collect();
        $this->loadMfrCategories();
    }

    public function updatedMfrReportType(): void
    {
        $this->mfrCategoryId = '';
        $this->mfrItemId     = '';
        $this->mfrItems      = collect();
        $this->loadMfrCategories();
    }

    public function updatedMfrCategoryId(): void
    {
        $this->mfrItemId = '';
        $this->mfrItems  = $this->mfrCategoryId
            ? $this->loadMfrItems()
            : collect();
    }

    private function loadMfrCategories(): void
    {
        $user = auth()->user();
        $isManufacturingStaff = $user->can('manufacturing.produce')
            && !$user->can('business.update')
            && !$user->isSuperAdmin();

        // Determine which business to filter by
        $businessIds = null;
        if ($this->businessId) {
            // Admin selected specific business
            $businessIds = [(int) $this->businessId];
        } elseif ($isManufacturingStaff) {
            // Manufacturer staff: use their assigned business
            $businessIds = $user->assignedBusinessIds();
            // Ensure we have businesses to filter by
            if (empty($businessIds)) {
                $this->mfrCategories = collect();
                return;
            }
        }

        if ($this->mfrReportType === 'product') {
            // finished / bottle product categories
            $query = Category::withoutGlobalScopes()
                ->whereIn('type', ['finished_product', 'bottle_product', 'oil'])
                ->orderBy('name');
            
            if ($businessIds !== null) {
                $query->whereIn('business_id', $businessIds);
            }
            
            $this->mfrCategories = $query->get(['id', 'name']);
        } else {
            // purchase → categories of bookstore/transferred items actually sold
            $query = Category::withoutGlobalScopes()
                ->whereNotIn('type', ['raw_material', 'packing_material', 'finished_product', 'bottle_product'])
                ->orderBy('name');
            
            if ($businessIds !== null) {
                $query->whereIn('business_id', $businessIds);
            }
            
            $this->mfrCategories = $query->get(['id', 'name']);
        }
    }

    private function loadMfrItems(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $isManufacturingStaff = $user->can('manufacturing.produce')
            && !$user->can('business.update')
            && !$user->isSuperAdmin();

        // Determine which business to filter by
        $businessIds = null;
        if ($this->businessId) {
            // Admin selected specific business
            $businessIds = [(int) $this->businessId];
        } elseif ($isManufacturingStaff) {
            // Manufacturer staff: use their assigned business
            $businessIds = $user->assignedBusinessIds();
            // Ensure we have businesses to filter by
            if (empty($businessIds)) {
                return collect();
            }
        }

        if ($this->mfrReportType === 'product') {
            $query = Product::withoutGlobalScopes()
                ->whereIn('type', ['finished_product', 'bottle_product'])
                ->where('category_id', $this->mfrCategoryId)
                ->whereNull('deleted_at')
                ->orderBy('name');
            
            if ($businessIds !== null) {
                $query->whereIn('business_id', $businessIds);
            }
            
            return $query->get(['id', 'name']);
        } else {
            $query = Product::withoutGlobalScopes()
                ->whereNotIn('type', ['raw_material', 'packing_material', 'finished_product', 'bottle_product'])
                ->where('category_id', $this->mfrCategoryId)
                ->whereNull('deleted_at')
                ->orderBy('name');
            
            if ($businessIds !== null) {
                $query->whereIn('business_id', $businessIds);
            }
            
            return $query->get(['id', 'name']);
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        
        // When switching to stock_changes, set to today's date
        if ($tab === 'stock_changes') {
            $this->dateFrom = now()->format('Y-m-d');
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    // ── Drill-down ────────────────────────────────────────────────────────
    public function drillInto(?int $businessId = null, ?int $branchId = null): void
    {
        $this->drillBusinessId = $businessId;
        $this->drillBranchId   = $branchId;
        if ($businessId) {
            $this->businessId = (string) $businessId;
            $this->updatedBusinessId();
        }
        if ($branchId) {
            $this->branchId = (string) $branchId;
        }
    }

    public function clearDrill(): void
    {
        $this->drillBusinessId = null;
        $this->drillBranchId   = null;
        $this->businessId = '';
        $this->branchId   = '';
        $this->updatedBusinessId();
    }

    // ── Export ────────────────────────────────────────────────────────────
    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('report.view');

        $service = app(ReportService::class);
        $filters = $this->buildFilters();

        $data = match ($this->activeTab) {
            'stock_changes'          => $this->getStockChangesReport($filters),
            'raw_material_deduction' => $this->getRawMaterialDeductionReport($filters),
            'sales'                  => $service->getSalesReport($filters),
            'profit'                 => $service->getProfitReport($filters),
            'inventory'              => $service->getInventoryReport($filters),
            'manufacturing'          => $service->getManufacturingReport($filters),
            'purchasing'             => $service->getPurchasingReport($filters),
            'branches'               => $service->getBranchReport($filters),
            default                  => [],
        };

        $export   = $service->buildCsvExport($this->activeTab, $data);
        $filename = $this->activeTab . '_report_' . now()->format('Y-m-d') . '.csv';
        $csv      = $service->exportCsv($export['headers'], $export['rows']);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function buildFilters(): array
    {
        return array_filter([
            'date_from'    => $this->dateFrom  ?: null,
            'date_to'      => $this->dateTo    ?: null,
            'business_id'  => $this->businessId  ? (int) $this->businessId  : null,
            'branch_id'    => $this->branchId    ? (int) $this->branchId    : null,
            'warehouse_id' => $this->warehouseId ? (int) $this->warehouseId : null,
            'product_id'   => $this->productId   ? (int) $this->productId   : null,
            'category_id'  => $this->categoryId  ? (int) $this->categoryId  : null,
            'batch_id'     => $this->batchId      ? (int) $this->batchId     : null,
        ]);
    }

    // ── Raw Material Report ────────────────────────────────────────────────
    private function getRawMaterialDeductionReport(array $filters): \Illuminate\Support\Collection
    {
        // Simple rules — no back-calculation from price:
        //
        // purchased_qty = SUM(purchases.quantity) — exactly what the manager typed, accumulates per row
        //
        // consumed_qty (SEEDS)             = SUM(seed_quantity_used) from finished product metadata
        // consumed_qty (BOTTLES & PACKING) = SUM(manufacturing_quantity) from finished product metadata
        //                                    (1 bottle / 1 pack used per manufactured unit)
        //
        // remaining_qty = purchased_qty - consumed_qty
        //
        // New purchase row just adds to purchased_qty. Consumed is never touched by purchases.

        // ── Step 1: SUM purchased qty per product ─────────────────────────
        $rows = \DB::table('purchases')
            ->join('products',     'purchases.product_id',  '=', 'products.id')
            ->join('categories',   'purchases.category_id', '=', 'categories.id')
            ->leftJoin('branches', 'purchases.branch_id',   '=', 'branches.id')
            ->whereIn('purchases.purchase_type', ['raw_material', 'packing_material'])
            ->where('purchases.status', 'completed')
            // Exclude transfer purchases: notes should NOT start with 'Transfer from' or 'from_branch:'
            ->whereRaw("(purchases.notes IS NULL OR (purchases.notes NOT LIKE 'Transfer from%' AND purchases.notes NOT LIKE 'from_branch:%'))")
            ->when($filters['business_id'] ?? null, fn($q, $v) =>
                $q->where('purchases.business_id', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) =>
                $q->where('purchases.branch_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) =>
                $q->where('purchases.purchase_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) =>
                $q->where('purchases.purchase_date', '<=', $v))
            ->select([
                'products.id          as product_id',
                'products.name        as item_name',
                'products.type        as product_type',
                'categories.name      as category_name',
                'categories.type      as category_type',
                'branches.name        as branch_name',
                'purchases.unit',
                'purchases.unit_price as unit_cost',
                'purchases.purchase_date as date',
                \DB::raw('SUM(purchases.quantity) as purchased_qty'),
            ])
            ->groupBy(
                'products.id', 'products.name', 'products.type',
                'categories.name', 'categories.type',
                'branches.name', 'purchases.unit', 'purchases.unit_price',
                'purchases.purchase_date'
            )
            ->orderByDesc('date')
            ->get();

        // ── Step 2: Total all-time purchased qty per product (for remaining stock calculation)
        $totalPurchasedMap = \DB::table('purchases')
            ->whereIn('purchase_type', ['raw_material', 'packing_material'])
            ->where('status', 'completed')
            // Exclude transfer purchases: notes should NOT start with 'Transfer from' or 'from_branch:'
            ->whereRaw("(notes IS NULL OR (notes NOT LIKE 'Transfer from%' AND notes NOT LIKE 'from_branch:%'))")
            ->when($filters['business_id'] ?? null, fn($q, $v) => $q->where('business_id', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->groupBy('product_id')
            ->select('product_id', \DB::raw('SUM(quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // ── Step 3: Build consumed maps from finished product metadata ─────
        $seedConsumedMap   = []; // key: lowercase seed name → SUM(seed_quantity_used)
        $bottleConsumedMap = []; // key: lowercase finished product name → SUM(manufacturing_quantity)
        $totalMfgQty       = 0.0; // total units manufactured across ALL finished products (for packing)

        \DB::table('products')
            ->whereIn('type', ['finished_product', 'bottle_product'])
            ->whereNotNull('metadata')
            ->get(['id', 'name', 'metadata'])
            ->each(function ($fp) use (&$seedConsumedMap, &$bottleConsumedMap, &$totalMfgQty) {
                $meta   = json_decode($fp->metadata, true);
                if (empty($meta)) return;

                $mfgQty = (float) ($meta['manufacturing_quantity'] ?? 0);

                // Seed consumed
                $seedName = trim($meta['seed_name'] ?? '');
                $seedQty  = (float) ($meta['seed_quantity_used'] ?? 0);
                if ($seedName !== '' && $seedQty > 0) {
                    $k = strtolower($seedName);
                    $seedConsumedMap[$k] = ($seedConsumedMap[$k] ?? 0) + $seedQty;
                }

                // Bottle consumed — keyed by finished product name for size-matching
                if ($mfgQty > 0) {
                    $k = strtolower(trim($fp->name));
                    $bottleConsumedMap[$k] = ($bottleConsumedMap[$k] ?? 0) + $mfgQty;
                    $totalMfgQty += $mfgQty; // accumulate for packing materials
                }
            });

        // ── Step 4: Map rows to report ────────────────────────────────────
        return $rows->map(function ($r) use ($seedConsumedMap, $bottleConsumedMap, $totalMfgQty, $totalPurchasedMap) {
            $unitCost              = (float) $r->unit_cost;
            $purchasedQty          = round((float) $r->purchased_qty, 3);
            $totalPurchasedAllTime = round((float) ($totalPurchasedMap[$r->product_id] ?? $purchasedQty), 3);

            // Detect bottle: has "bottle" in name OR is purely "NNN ml"
            $isBottle = stripos($r->item_name, 'bottle') !== false
                     || preg_match('/^\d+\s*ml$/i', trim($r->item_name));

            // Detect packing: category name contains "packing" (handles extra spaces)
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
                $smConsumed = (float) \App\Models\StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', \App\Models\StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty  = max($consumedQty, $smConsumed);
                $consumedQty  = round($consumedQty, 3);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));

            } elseif ($isPacking) {
                // Packing material — 1 unit used per manufactured product
                $smConsumed = (float) \App\Models\StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', \App\Models\StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty  = max($totalMfgQty, $smConsumed);
                $consumedQty  = round($consumedQty, 3);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));

            } else {
                // Seeds
                $key          = strtolower(trim($r->item_name));
                $consumedQty  = (float) ($seedConsumedMap[$key] ?? 0);
                $smConsumed = (float) \App\Models\StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', \App\Models\StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty  = max($consumedQty, $smConsumed);
                $consumedQty  = round($consumedQty, 3);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));
            }

            $totalValue = round($remainingQty * $unitCost, 2);

            return [
                'item_name'     => $r->item_name,
                'category_name' => $r->category_name,
                'branch_name'   => $r->branch_name ?? '—',
                'unit'          => $r->unit ?? '',
                'unit_cost'     => $unitCost,
                'purchased_qty' => $purchasedQty,
                'consumed_qty'  => $consumedQty,
                'remaining_qty' => $remainingQty,
                'total_value'   => $totalValue,
                'total_cost'    => $totalValue,
                'date'          => $r->date,
            ];
        });
    }

    // ── GM Manufacturing Report ───────────────────────────────────────────
    // Shows manufactured products from FinishedGood records with per-batch seed usage
    // Filters by date range (daily manufacturing items with daily seed used per batch)
    private function getGmManufacturingReport(array $filters): \Illuminate\Support\Collection
    {
        $user        = auth()->user();
        
        // Determine business scope:
        // - If user selected a business (admin), use that
        // - If manufacturer staff, use ONLY their assigned business
        // - If super admin with no selection, show all
        $businessIds = [];
        
        if ($this->businessId) {
            // Admin selected a specific business
            $businessIds = [(int) $this->businessId];
        } else {
            // Check if manufacturer staff
            $isManufacturingStaff = $user->can('manufacturing.produce')
                && !$user->can('business.update')
                && !$user->isSuperAdmin();
            
            if ($isManufacturingStaff) {
                // Manufacturer staff: show ONLY their assigned business
                $businessIds = $user->assignedBusinessIds();
            } else {
                // Super admin with no selection: show all
                $businessIds = $user->isSuperAdmin() 
                    ? \App\Models\Business::withoutGlobalScopes()->pluck('id')->toArray() 
                    : $user->assignedBusinessIds();
            }
        }

        // Parse dates properly - handle both formats
        $dateFrom = null;
        $dateTo = null;
        if (!empty($filters['date_from'])) {
            try {
                // Try Y-m-d format (from HTML date input)
                $dateFrom = \Carbon\Carbon::createFromFormat('Y-m-d', $filters['date_from'])->startOfDay();
            } catch (\Exception $e) {
                try {
                    // Try m/d/Y format (for backwards compatibility)
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $filters['date_from'])->startOfDay();
                } catch (\Exception $e2) {
                    // Last resort: use parse
                    $dateFrom = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
                }
            }
        }
        if (!empty($filters['date_to'])) {
            try {
                // Try Y-m-d format (from HTML date input)
                $dateTo = \Carbon\Carbon::createFromFormat('Y-m-d', $filters['date_to'])->endOfDay();
            } catch (\Exception $e) {
                try {
                    // Try m/d/Y format (for backwards compatibility)
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $filters['date_to'])->endOfDay();
                } catch (\Exception $e2) {
                    // Last resort: use parse
                    $dateTo = \Carbon\Carbon::parse($filters['date_to'])->endOfDay();
                }
            }
        }

        // Get FinishedGood records within the date range to show daily manufacturing
        $finishedGoods = \App\Models\FinishedGood::query()
            ->with(['product.category', 'product.business', 'batch.rawMaterialConsumptions.rawMaterial'])
            ->whereHas('product', function($q) use ($businessIds) {
                $q->whereIn('business_id', $businessIds)
                  ->whereIn('type', ['finished_product', 'bottle_product']);
            })
            ->whereHas('batch', function($q) use ($dateFrom, $dateTo) {
                // Filter by date range on batch completion date (when manufacturing finished)
                if ($dateFrom) {
                    $q->where('completed_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('completed_at', '<=', $dateTo);
                }
            })
            ->when($filters['category_id'] ?? null, fn($q, $v) => 
                $q->whereHas('product', fn($sq) => $sq->where('category_id', $v))
            )
            // Also filter by mfr-specific category and item
            ->when($this->mfrCategoryId ?? null, fn($q) => 
                $q->whereHas('product', fn($sq) => $sq->where('category_id', $this->mfrCategoryId))
            )
            ->when($this->mfrItemId ?? null, fn($q) => 
                $q->where('product_id', $this->mfrItemId)
            )
            ->orderByDesc('created_at')
            ->get();

        $reportRows = $finishedGoods->map(function ($fg) {
            $product = $fg->product;
            $batch = $fg->batch;
            
            if (!$product || !$batch) return null;

            // Get seed consumption from this batch (per-batch, not accumulated)
            $seedConsumption = $batch->rawMaterialConsumptions()->first();
            $seedQty = $seedConsumption ? (float) $seedConsumption->getEffectiveQuantity() : 0;
            $seedName = $seedConsumption?->rawMaterial?->name ?? '—';
            $seedUnit = $seedConsumption?->rawMaterial?->unit ?? 'kg';
            
            // Get manufacturer branch name
            $manufacturerBy = '—';
            if ($batch->branch_id) {
                $branch = \App\Models\Branch::find($batch->branch_id);
                $manufacturerBy = $branch?->name ?? '—';
            }

            return [
                'product_id'       => $product->id,
                'item_name'        => $product->name,
                'category_name'    => $product->category?->name ?? '—',
                'business_name'    => $product->business?->name ?? '—',
                'manufactured_by'  => $manufacturerBy,
                'mfg_qty'          => (float) $fg->quantity,
                'unit'             => $product->unit ?? 'pcs',
                'selling_price'    => (float) $product->selling_price,
                'total_value'      => round((float) $fg->quantity * (float) $product->selling_price, 2),
                'seed_name'        => $seedName,
                'seed_qty_used'    => $seedQty,
                'seed_unit'        => $seedUnit,
                'last_mfg_at'      => $batch->completed_at?->format('Y-m-d H:i:s') ?? $fg->created_at->format('Y-m-d H:i:s'),
            ];
        })->filter()->values();

        // Get user's assigned branch IDs for per-branch filtering
        $userBranchIds = $user->assignedBranchIds();
        
        // For users without explicit branch assignment: resolve from business
        if (empty($userBranchIds) && !$user->isSuperAdmin()) {
            $userBusinessIds = $user->assignedBusinessIds();
            if (!empty($userBusinessIds)) {
                // Get all branches for assigned businesses
                $userBranchIds = Branch::whereIn('business_id', $userBusinessIds)
                    ->pluck('id')
                    ->toArray();
            } else {
                // Fallback: if no business assignment either, show all branches (for GM with broad access)
                $userBranchIds = Branch::pluck('id')->toArray();
            }
        }

        // Also include products from Product metadata (manufacturing_quantity stored in metadata)
        $productMetaItems = \App\Models\Product::withoutGlobalScopes()
            ->whereIn('business_id', $businessIds)
            ->where('status', 'active')
            ->get()
            ->filter(function ($product) use ($dateFrom, $dateTo, $user, $userBranchIds) {
                $meta = $product->metadata ?? [];
                
                // For non-super-admin: only show items manufactured by their branch
                if (!$user->isSuperAdmin()) {
                    $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                    $hasUserBranchContribution = false;
                    
                    // Check if any user branch (or all branches if GM) have this product manufactured
                    foreach ($userBranchIds as $branchId) {
                        if (isset($manufacturingByBranch[$branchId]) && (float)$manufacturingByBranch[$branchId] > 0) {
                            $hasUserBranchContribution = true;
                            break;
                        }
                    }
                    
                    if (!$hasUserBranchContribution) {
                        return false;  // Skip if user's branch didn't manufacture
                    }
                }
                
                // For super admin: show all items with manufacturing_quantity
                if (!isset($meta['manufacturing_quantity']) || (float)$meta['manufacturing_quantity'] <= 0) {
                    return false;
                }

                // Check date range
                if ($dateFrom && isset($meta['manufacturing_added_at'])) {
                    $addedAt = \Carbon\Carbon::parse($meta['manufacturing_added_at']);
                    if ($addedAt < $dateFrom) return false;
                }
                if ($dateTo && isset($meta['manufacturing_added_at'])) {
                    $addedAt = \Carbon\Carbon::parse($meta['manufacturing_added_at']);
                    if ($addedAt > $dateTo) return false;
                }

                return true;
            })
            ->when($this->mfrCategoryId ?? null, fn($collection) => 
                $collection->filter(fn($p) => $p->category_id == $this->mfrCategoryId)
            )
            ->when($this->mfrItemId ?? null, fn($collection) => 
                $collection->filter(fn($p) => $p->id == $this->mfrItemId)
            )
            ->map(function ($product) use ($user, $userBranchIds) {
                $meta = $product->metadata ?? [];
                $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                $manufacturedByPerBranch = $meta['manufactured_by_per_branch'] ?? [];
                
                // For super admin: create separate rows for each branch that manufactured this item
                if ($user->isSuperAdmin() && !empty($manufacturingByBranch)) {
                    $rows = [];
                    
                    foreach ($manufacturingByBranch as $branchId => $qty) {
                        if (is_numeric($branchId) && (float)$qty > 0) {
                            $branch = \App\Models\Branch::find($branchId);
                            if ($branch) {
                                $username = $manufacturedByPerBranch[$branchId]['username'] ?? '—';
                                
                                // Get last_mfr_quantity for this specific branch
                                // For the original manufacturing branch: use last_mfr_quantity
                                // For branches that received transferred products: use the daily qty they created
                                $branchLastMfrQty = 0;
                                if ($branchId == ($meta['manufactured_by_branch_id'] ?? null)) {
                                    $branchLastMfrQty = (float) ($meta['last_mfr_quantity'] ?? 0);
                                } else {
                                    // For other branches: check if they have daily manufacturing activity (transfers)
                                    $dailyByBranch = $meta['manufacturing_daily_qty_by_branch'] ?? [];
                                    $branchLastMfrQty = (float)($dailyByBranch[$branchId] ?? $dailyByBranch[(string)$branchId] ?? 0);
                                }
                                
                                // ONLY add row if this branch has NEW Mfr quantity (manufactured or transferred)
                                if ($branchLastMfrQty > 0) {
                                    $rows[] = [
                                        'product_id'               => $product->id,
                                        'item_name'                => $product->name,
                                        'category_name'            => $product->category?->name ?? '—',
                                        'business_name'            => $product->business?->name ?? '—',
                                        'manufactured_by'          => $branch->name,
                                        'manufactured_by_username' => $username,
                                        'manufactured_by_branch'   => $branch->name,
                                        'new_mfr_quantity'         => $branchLastMfrQty,
                                        'mfg_qty'                  => $branchLastMfrQty,
                                        'used_seed_qty'            => (float) ($meta['current_seed_deduction'] ?? 0),
                                        'unit'                     => $product->unit ?? 'pcs',
                                        'selling_price'            => (float) $product->selling_price,
                                        'total_value'              => round($branchLastMfrQty * (float) $product->selling_price, 2),
                                        'seed_name'                => $meta['seed_name'] ?? '—',
                                        'seed_qty_used'            => (float) ($meta['seed_quantity_used'] ?? 0),
                                        'seed_unit'                => $meta['seed_unit'] ?? 'kg',
                                        'last_mfg_at'              => isset($meta['manufacturing_added_at']) 
                                            ? \Carbon\Carbon::parse($meta['manufacturing_added_at'])->format('Y-m-d H:i:s')
                                            : '—',
                                    ];
                                }
                            }
                        }
                    }
                    
                    return $rows;
                }
                
                // For non-admin: show one row per product (like Super Admin) but filtered to their branches
                // If any of their branches manufactured this product, show ONE row with the latest entry
                $mfgQty = 0;
                $branchName = '—';
                $manufacturerUsername = '—';
                $mfgBranchId = isset($meta['manufactured_by_branch_id']) ? (int)$meta['manufactured_by_branch_id'] : null;
                
                // Sum quantities across all user's branches
                foreach ($userBranchIds as $userBranchId) {
                    if (isset($manufacturingByBranch[$userBranchId]) && (float)$manufacturingByBranch[$userBranchId] > 0) {
                        $mfgQty += (float) $manufacturingByBranch[$userBranchId];
                        
                        // Use the branch that made the LATEST entry (manufactured_by_branch_id)
                        // not just the last one in the loop
                        if ($userBranchId == $mfgBranchId) {
                            $branch = \App\Models\Branch::find($userBranchId);
                            $branchName = $branch?->name ?? '—';
                            
                            // Get username for the branch that made the latest entry
                            if (isset($manufacturedByPerBranch[$userBranchId])) {
                                $manufacturerUsername = $manufacturedByPerBranch[$userBranchId]['username'] ?? '—';
                            }
                        }
                    }
                }
                
                // If no branch found yet (manufactured_by_branch_id not in user's branches), use first one
                if ($branchName === '—' && $mfgQty > 0) {
                    foreach ($userBranchIds as $userBranchId) {
                        if (isset($manufacturingByBranch[$userBranchId]) && (float)$manufacturingByBranch[$userBranchId] > 0) {
                            $branch = \App\Models\Branch::find($userBranchId);
                            $branchName = $branch?->name ?? '—';
                            if (isset($manufacturedByPerBranch[$userBranchId])) {
                                $manufacturerUsername = $manufacturedByPerBranch[$userBranchId]['username'] ?? '—';
                            }
                            break;
                        }
                    }
                }
                
                // Only return if any branch has manufacturing data for this product
                if ($mfgQty <= 0) {
                    return [];
                }
                
                // Filter out rows where there's no NEW manufacturing today (New Mfr = 0)
                $newMfrQty = (float) ($meta['last_mfr_quantity'] ?? 0);
                if ($newMfrQty <= 0) {
                    return [];
                }
                
                return [[
                    'product_id'               => $product->id,
                    'item_name'                => $product->name,
                    'category_name'            => $product->category?->name ?? '—',
                    'business_name'            => $product->business?->name ?? '—',
                    'manufactured_by'          => $branchName,
                    'manufactured_by_username' => $manufacturerUsername,
                    'manufactured_by_branch'   => $branchName,
                    'new_mfr_quantity'         => (float) ($meta['last_mfr_quantity'] ?? 0),
                    'mfg_qty'                  => (float) ($meta['last_mfr_quantity'] ?? 0),
                    'used_seed_qty'            => (float) ($meta['current_seed_deduction'] ?? 0),
                    'unit'                     => $product->unit ?? 'pcs',
                    'selling_price'            => (float) $product->selling_price,
                    'total_value'              => round((float) ($meta['last_mfr_quantity'] ?? 0) * (float) $product->selling_price, 2),
                    'seed_name'                => $meta['seed_name'] ?? '—',
                    'seed_qty_used'            => (float) ($meta['seed_quantity_used'] ?? 0),
                    'seed_unit'                => $meta['seed_unit'] ?? 'kg',
                    'last_mfg_at'              => isset($meta['manufacturing_added_at']) 
                        ? \Carbon\Carbon::parse($meta['manufacturing_added_at'])->format('Y-m-d H:i:s')
                        : '—',
                ]];
            })
            ->flatten(1)
            ->values();

        // Merge both sources and convert to array
        $allArray = array_merge($reportRows->toArray(), $productMetaItems->toArray());
        
        // Sort by last_mfg_at descending
        usort($allArray, function ($a, $b) {
            $dateA = $a['last_mfg_at'] ?? '';
            $dateB = $b['last_mfg_at'] ?? '';
            return strcmp($dateB, $dateA); // Descending (B - A)
        });
        
        return collect($allArray)->values();
    }

    private function getManufacturerProductReport(array $filters): array
    {
        // Use the GM Manufacturing Report as the source of truth for manufactured products.
        $gmr = $this->getGmManufacturingReport($filters);
        // $gmr is a collection of manufacturing rows.
        $rows = $gmr->map(function ($r) {
            return [
                'item_name'     => $r['item_name'] ?? $r['product_id'] ?? '',
                'category_name' => $r['category_name'] ?? '—',
                'quantity'      => $r['mfg_qty'] ?? 0,
                'unit'          => $r['unit'] ?? 'pcs',
                'unit_price'    => $r['selling_price'] ?? 0,
                'total_selling' => $r['total_value'] ?? 0,
            ];
        })->values();

        // Calculate totals for quantity and total selling price.
        $totals = [
            'quantity'      => $rows->sum('quantity'),
            'total_selling' => $rows->sum('total_selling'),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    // ── Manufacturer Sales Report (for mfr_sales tab) ─────────────────────
    // Shows all sales made by the logged-in manufacturer user
    private function getMfrSalesReport(array $filters): array
    {
        $user = auth()->user();
        
        $rows = \App\Models\BranchSale::withoutGlobalScopes()
            ->join('products',    'branch_sales.product_id',   '=', 'products.id')
            ->join('categories',  'products.category_id',      '=', 'categories.id')
            ->leftJoin('branches','branch_sales.branch_id',    '=', 'branches.id')
            ->where('branch_sales.recorded_by', $user->id)
            ->when($filters['date_from'] ?? null, fn($q, $v) =>
                $q->where('branch_sales.sale_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) =>
                $q->where('branch_sales.sale_date', '<=', $v))
            ->select([
                'branch_sales.id',
                'products.name as item_name',
                'categories.name as category_name',
                'branch_sales.quantity as sold_qty',
                'branch_sales.cost_price',
                'branch_sales.unit_price as selling_price',
                DB::raw('(branch_sales.unit_price - branch_sales.cost_price) * branch_sales.quantity as profit'),
                'branch_sales.sale_date',
            ])
            ->orderByDesc('branch_sales.sale_date')
            ->get();

        $totals = [
            'sold_qty'      => $rows->sum('sold_qty'),
            'cost_price'    => $rows->sum(fn($r) => $r->cost_price * $r->sold_qty),
            'selling_price' => $rows->sum(fn($r) => $r->selling_price * $r->sold_qty),
            'profit'        => $rows->sum('profit'),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    // ── Manufacturer Purchase-Sale Report ────────────────────────────────
    // Shows bookstore/transferred items sold by the manufacturer (via BranchSale)
    private function getManufacturerPurchaseReport(array $filters): array
    {
        $rows = \App\Models\BranchSale::withoutGlobalScopes()
            ->join('products',    'branch_sales.product_id',   '=', 'products.id')
            ->join('categories',  'products.category_id',      '=', 'categories.id')
            ->leftJoin('users',   'branch_sales.recorded_by',  '=', 'users.id')
            ->leftJoin('branches','branch_sales.branch_id',    '=', 'branches.id')
            // only transferred-purchase sales: exclude manufactured product types
            ->whereNotIn('products.type', [
                'finished_product',
                'bottle_product',
                'raw_material',
                'packing_material',
            ])
            ->when($this->businessId, fn($q) =>
                $q->where('branches.business_id', (int) $this->businessId))
            ->when($filters['branch_id'] ?? null, fn($q, $v) =>
                $q->where('branch_sales.branch_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) =>
                $q->where('branch_sales.sale_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) =>
                $q->where('branch_sales.sale_date', '<=', $v))
            ->when($this->mfrCategoryId, fn($q) =>
                $q->where('products.category_id', (int) $this->mfrCategoryId))
            ->when($this->mfrItemId, fn($q) =>
                $q->where('branch_sales.product_id', (int) $this->mfrItemId))
            ->select([
                'products.id   as product_id',
                'products.name as item_name',
                'products.unit as unit',
                'categories.name as category_name',
                DB::raw('SUM(branch_sales.quantity)     as total_qty'),
                DB::raw('SUM(branch_sales.total_amount) as total_selling'),
                DB::raw('AVG(branch_sales.unit_price)   as avg_unit_price'),
            ])
            ->groupBy('products.id', 'products.name', 'products.unit', 'categories.name')
            ->orderByDesc('total_selling')
            ->get();

        $totals = [
            'total_qty'     => $rows->sum('total_qty'),
            'total_selling' => $rows->sum('total_selling'),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    // ── Stock Changes Audit Report ────────────────────────────────────────
    // Shows all stock movements (additions, deductions, transfers) across all branches
    private function getStockChangesReport(array $filters): array
    {
        // Get all stock movements ordered by date (descending) - SORT AT DATABASE LEVEL
        $movements = DB::table('stock_movements')
            ->leftJoin('products', function($join) {
                $join->on('stock_movements.item_id', '=', 'products.id')
                     ->where('stock_movements.item_type', '=', 'product');
            })
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('branches', 'stock_movements.branch_id', '=', 'branches.id')
            ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
            ->where('stock_movements.item_type', 'product')
            ->when($filters['business_id'] ?? null, fn($q, $v) =>
                $q->where('stock_movements.business_id', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) =>
                $q->where('stock_movements.branch_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) =>
                $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn($q, $v) =>
                $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
            ->select([
                'stock_movements.id',
                'stock_movements.movement_type',
                'stock_movements.direction',
                'stock_movements.quantity',
                'stock_movements.moved_at',
                'stock_movements.notes',
                'stock_movements.created_by',
                'stock_movements.item_id',
                'stock_movements.business_id',
                'stock_movements.branch_id',
                'products.id as product_id',
                'products.name as item_name',
                'categories.name as category_name',
                'branches.name as branch_name',
                'users.name as user_name',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->orderByDesc('stock_movements.id')
            ->get();
        
        // DEBUG: Log count of each movement type
        $typeCounts = DB::table('stock_movements')
            ->where('stock_movements.item_type', 'product')
            ->when($filters['business_id'] ?? null, fn($q, $v) => $q->where('stock_movements.business_id', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('stock_movements.branch_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
            ->select('movement_type', DB::raw('COUNT(*) as count'))
            ->groupBy('movement_type')
            ->get();
        
        // Also check production_output movements with NULL business_id
        $nullBusinessMovements = DB::table('stock_movements')
            ->where('stock_movements.item_type', 'product')
            ->where('stock_movements.movement_type', 'production_output')
            ->whereNull('stock_movements.business_id')
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
            ->count();
        
        \Log::info('Stock Changes Query Debug', [
            'total_returned' => $movements->count(),
            'filters' => $filters,
            'type_counts_with_filters' => $typeCounts->pluck('count', 'movement_type')->toArray(),
            'production_output_with_null_business' => $nullBusinessMovements,
        ]);
        
        // If we're missing production_output records due to NULL business_id,
        // add a separate query to fetch them
        // These records should be included regardless of business filter, as long as they match other filters
        if ($nullBusinessMovements > 0) {
            $nullBusinessOutputs = DB::table('stock_movements')
                ->leftJoin('products', function($join) {
                    $join->on('stock_movements.item_id', '=', 'products.id')
                         ->where('stock_movements.item_type', '=', 'product');
                })
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('branches', 'stock_movements.branch_id', '=', 'branches.id')
                ->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
                ->where('stock_movements.item_type', 'product')
                ->where('stock_movements.movement_type', 'production_output')
                ->whereNull('stock_movements.business_id')
                // Still apply date and branch filters (but not business_id since it's NULL)
                ->when($filters['branch_id'] ?? null, fn($q, $v) =>
                    $q->where('stock_movements.branch_id', $v))
                ->when($filters['date_from'] ?? null, fn($q, $v) =>
                    $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
                ->when($filters['date_to'] ?? null, fn($q, $v) =>
                    $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
                ->select([
                    'stock_movements.id',
                    'stock_movements.movement_type',
                    'stock_movements.direction',
                    'stock_movements.quantity',
                    'stock_movements.moved_at',
                    'stock_movements.notes',
                    'stock_movements.created_by',
                    'stock_movements.item_id',
                    'stock_movements.business_id',
                    'stock_movements.branch_id',
                    'products.id as product_id',
                    'products.name as item_name',
                    'categories.name as category_name',
                    'branches.name as branch_name',
                    'users.name as user_name',
                ])
                ->get();
            
            // Merge the null business movements with the main collection
            $movements = $movements->merge($nullBusinessOutputs);
            
            // Re-sort the merged collection by moved_at and id (descending)
            $movements = $movements->sortByDesc(function($m) {
                return [$m->moved_at, $m->id];
            })->values();
        }

        // Post-process to look up user from audit logs if user_name is null
        $movements = $movements->map(function($movement) {
            if (empty($movement->user_name) && !empty($movement->created_by)) {
                // Convert moved_at to Carbon instance if it's a string
                $movedAtCarbon = is_string($movement->moved_at) 
                    ? \Carbon\Carbon::parse($movement->moved_at)
                    : $movement->moved_at;
                
                // For production_output movements, look more broadly (within 2 hours)
                $timeRange = $movement->movement_type === 'production_output' ? 2 : 1;
                
                // Look for the most recent login in audit logs around the stock movement time
                $auditUser = DB::table('audit_logs')
                    ->join('users', 'audit_logs.user_id', '=', 'users.id')
                    ->where('audit_logs.user_id', $movement->created_by)
                    ->where('audit_logs.action', 'login')
                    ->whereBetween('audit_logs.created_at', [
                        $movedAtCarbon->copy()->subHours($timeRange * 24),
                        $movedAtCarbon->copy()->addHours($timeRange)
                    ])
                    ->orderByDesc('audit_logs.created_at')
                    ->select('users.name')
                    ->first();
                
                if ($auditUser) {
                    $movement->user_name = $auditUser->name;
                }
            }
            
            // Final fallback - if still no user_name, try to get it from users table directly
            if (empty($movement->user_name) && !empty($movement->created_by)) {
                $user = DB::table('users')->where('id', $movement->created_by)->select('name')->first();
                if ($user) {
                    $movement->user_name = $user->name;
                }
            }
            
            // Ultimate fallback
            if (empty($movement->user_name)) {
                $movement->user_name = 'System';
            }
            
            return $movement;
        });

        // Re-sort by moved_at descending to ensure newest first after map operation
        $movements = $movements->sortByDesc(function($movement) {
            // Handle both string and Carbon datetime formats
            $timestamp = is_string($movement->moved_at) 
                ? strtotime($movement->moved_at) 
                : $movement->moved_at->timestamp;
            return $timestamp;
        })->values();  // Reset array keys to maintain proper ordering

        // Calculate totals
        $totalRecords = $movements->count();
        $inboundQty = $movements
            ->filter(fn($m) => $m->direction === 'in')
            ->sum('quantity');
        $outboundQty = $movements
            ->filter(fn($m) => $m->direction === 'out')
            ->sum(fn($m) => abs($m->quantity));

        // Count active staff affecting stock
        $activeStaffCount = $movements
            ->pluck('created_by')
            ->unique()
            ->count();

        return [
            'movements'         => $movements,
            'totalRecords'      => $totalRecords,
            'inboundQty'        => round($inboundQty, 2),
            'outboundQty'       => round($outboundQty, 2),
            'activeStaffCount'  => $activeStaffCount,
        ];
    }

    public function render()
    {
        $service = app(ReportService::class);
        $filters = $this->buildFilters();

        // Always load executive KPIs
        $kpis = $service->getDashboardKpis($filters);

        // Load active report data
        $reportData = match ($this->activeTab) {
            'stock_changes'          => $this->getStockChangesReport($filters),
            'raw_material_deduction' => $this->getRawMaterialDeductionReport($filters),
            'sales'                  => $service->getSalesReport($filters),
            'profit'                 => $service->getProfitReport($filters),
            'inventory'              => $service->getInventoryReport($filters),
            'manufacturing'          => array_merge(
                                            $service->getManufacturingReport($filters),
                                            ['mfg_products' => $this->getGmManufacturingReport($filters)]
                                        ),
            'gm_manufacturing'       => $this->getGmManufacturingReport($filters),
            'purchasing'             => $service->getPurchasingReport($filters),
            'transfers'              => $this->getTransfersReport($filters),
            'branches'               => $service->getBranchReport($filters),
            'manufacturer_report'    => $this->mfrReportType === 'product'
                                                                                        ? $this->getManufacturerProductReport($filters)

                                            : $this->getManufacturerPurchaseReport($filters),
            'mfr_sales'              => $this->getMfrSalesReport($filters),
            default                  => [],
        };

        return view('livewire.admin.reports.index', compact('kpis', 'reportData', 'filters'))
            ->title('Reports & Analytics');
    }

    // ── Product Transfers Report ──────────────────────────────────────────
    // Shows stock levels for products that have been transferred between branches
    private function getTransfersReport(array $filters): array
    {
        $user = auth()->user();

        // Transfer movements are stored as pairs (out from source, in to destination)
        // The 'in' direction row contains notes with from_branch|to_branch|notes
        // We read only 'in' rows to avoid duplicates, parse notes for from/to branch
        $rows = DB::table('stock_movements')
            ->join('products',    'stock_movements.item_id',    '=', 'products.id')
            ->join('categories',  'products.category_id',       '=', 'categories.id')
            ->leftJoin('branches','stock_movements.branch_id',  '=', 'branches.id')
            ->where('stock_movements.reference_type', 'StockTransfer')
            ->where('stock_movements.direction', 'in')
            ->where('stock_movements.item_type', 'product')
            ->when($filters['business_id'] ?? null, fn($q, $v) =>
                $q->where('stock_movements.business_id', $v))
            ->when($filters['branch_id'] ?? null, fn($q, $v) =>
                $q->where('stock_movements.branch_id', $v))
            ->when(!$user->isSuperAdmin(), function($q) use ($user) {
                // Sellers: only show transfers TO their assigned branch(es)
                $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
                if ($isSeller) {
                    $q->whereIn('stock_movements.branch_id', $user->assignedBranchIds());
                } else {
                    $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds());
                }
            })
            ->when($filters['date_from'] ?? null, fn($q, $v) =>
                $q->whereDate('stock_movements.moved_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) =>
                $q->whereDate('stock_movements.moved_at', '<=', $v))
            ->select([
                'products.name        as item',
                'products.unit        as unit',
                'categories.name      as category',
                'branches.name        as to_branch',
                'stock_movements.quantity',
                'stock_movements.notes',
                'stock_movements.moved_at',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->get()
            ->map(function ($r) {
                // Parse notes: "from_branch:X|to_branch:Y|notes:Z"
                $fromBranch = '';
                $noteText   = '';
                if ($r->notes) {
                    $parts = explode('|', $r->notes);
                    foreach ($parts as $part) {
                        if (str_starts_with($part, 'from_branch:')) {
                            $fromBranch = substr($part, strlen('from_branch:'));
                        } elseif (str_starts_with($part, 'notes:')) {
                            $noteText = substr($part, strlen('notes:'));
                        }
                    }
                }
                return [
                    'category'   => $r->category,
                    'item'       => $r->item,
                    'unit'       => $r->unit ?? '',
                    'qty'        => (float) $r->quantity,
                    'from_branch'=> $fromBranch ?: '—',
                    'to_branch'  => $r->to_branch ?? '—',
                    'notes'      => $noteText ?: '—',
                    'date'       => $r->moved_at,
                ];
            });

        return [
            'rows'        => $rows,
            'total_qty'   => $rows->sum('qty'),
            'total_count' => $rows->count(),
        ];
    }
}
