<?php

namespace App\Livewire\Admin\Purchases;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FinishedGood;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Services\AuthorizationService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Carbon\Carbon;

#[Layout('components.layouts.admin')]
class PurchaseForm extends Component
{
    use WithPagination, WithFileUploads;

    // ── Add form fields ───────────────────────────────────────────────────
    public string $business_id   = '';
    public string $branch_id     = '';
    public string $type          = 'product'; // 'product' or 'purchase'
    public string $category_id   = '';
    public string $product_id    = '';
    public string $unit          = '';
    public string $quantity      = '';
    public        $image         = null;  // optional product image upload
    public string $unit_price    = '';
    public string $selling_price = '';
    public string $purchase_date = '';

    // ── Cascading lists ───────────────────────────────────────────────────
    public array $businesses  = [];
    public array $branches    = [];
    public array $categories  = [];
    public array $products    = [];
    public array $unitOptions = [
        'kg', 'liter', 'piece',
    ];

    // ── Get manufactured quantity for selected product ────────────────────
    public function getManufacturedQuantity(): float
    {
        if (!$this->product_id || $this->type !== 'product') {
            return 0;
        }

        // Get total manufactured quantity from product metadata
        $product = Product::withoutGlobalScopes()->find($this->product_id);
        if (!$product) {
            return 0;
        }

        $metadata = $product->metadata ?? [];
        return (float) ($metadata['manufacturing_quantity'] ?? 0);
    }

    // ── Transfer Form ─────────────────────────────────────────────────────
    // Transfer manufactured products from the factory to another business/branch

    public ?int  $trCategoryId  = null;  // source category
    public ?int  $trProductId   = null;  // source product
    public string $trQuantity   = '';    // qty to transfer
    public ?int  $trBusinessId  = null;  // destination business
    public ?int  $trBranchId    = null;  // destination branch
    public string $trNotes      = '';    // optional notes
    public array  $trHistory    = [];    // session log

    // Source business for transfer — the oil factory (manufacturing business)
    private function getManufacturerBusinessId(): ?int
    {
        // Look for the business that has manufacturing products
        // For non-super-admin: use their assigned business
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $ids = $user->assignedBusinessIds();
            return !empty($ids) ? (int) $ids[0] : null;
        }
        // Super admin: use oil factory (business with manufacturing staff)
        $biz = Business::whereRaw("LOWER(name) LIKE '%baraka%oil%' OR LOWER(name) LIKE '%factory%' OR LOWER(code) LIKE '%oil%'")
            ->first();
        return $biz?->id;
    }

    public function getProductAvailableStock(int $productId): float
    {
        $p = Product::withoutGlobalScopes()->find($productId);
        if (!$p) return 0;

        $meta = (float) ($p->metadata['manufacturing_quantity'] ?? 0);
        $fgStock = (float) FinishedGood::where('product_id', $productId)
            ->where('status', FinishedGood::STATUS_AVAILABLE)
            ->get()
            ->sum(fn($fg) => $fg->getRemaining());
        $currentStock = (float) $p->current_stock;

        return max(0, max($meta, $fgStock, $currentStock));
    }

    public function getTransferCategoriesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getTransferProductsProperty(): \Illuminate\Support\Collection
    {
        if (!$this->trCategoryId) return collect();
        return Product::with('category')
            ->where('category_id', $this->trCategoryId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $p->available_qty = $this->getProductAvailableStock($p->id);
                return $p;
            })
            ->values();
    }

    public function getTransferAvailableQtyProperty(): float
    {
        if (!$this->trProductId) return 0;
        return $this->getProductAvailableStock($this->trProductId);
    }

    public function getTransferDestinationBusinessesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Business::where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function getTransferDestinationBranchesProperty(): \Illuminate\Support\Collection
    {
        if (!$this->trBusinessId) return collect();
        return Branch::where('business_id', $this->trBusinessId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function updatedTrBusinessId(): void { $this->trBranchId = null; }
    public function updatedTrCategoryId(): void { $this->trProductId = null; }

    public function executeTransfer(): void
    {
        $this->validate([
            'trCategoryId'  => 'required|integer|exists:categories,id',
            'trProductId'   => 'required|integer|exists:products,id',
            'trQuantity'    => 'required|numeric|min:0.001',
            'trBusinessId'  => 'required|integer|exists:businesses,id',
            'trBranchId'    => 'required|integer|exists:branches,id',
        ]);

        $product   = Product::findOrFail($this->trProductId);
        $qty       = (float) $this->trQuantity;
        $available = $this->transferAvailableQty;

        if ($qty > $available) {
            $this->addError('trQuantity', "Only {$available} units available.");
            return;
        }

        $destBiz    = Business::findOrFail($this->trBusinessId);
        $destBranch = Branch::findOrFail($this->trBranchId);

        DB::transaction(function () use ($product, $qty, $destBiz, $destBranch) {
            // Deduct from manufacturer metadata
            $meta = $product->metadata ?? [];
            $meta['manufacturing_quantity'] = max(0, (float)($meta['manufacturing_quantity'] ?? 0) - $qty);
            $meta['manufacturing_added_at'] = now()->toIso8601String();
            $product->metadata = $meta;
            $product->save();

            // Add as a completed Purchase on destination
            Purchase::create([
                'business_id'   => $destBiz->id,
                'branch_id'     => $destBranch->id,
                'product_id'    => $product->id,
                'category_id'   => $product->category_id,
                'purchase_type' => Purchase::TYPE_PRODUCT,
                'purchase_date' => now()->toDateString(),
                'quantity'      => $qty,
                'unit'          => $product->unit ?? 'unit',
                'unit_price'    => (float) ($product->cost_price ?? 0),
                'status'        => Purchase::STATUS_COMPLETED,
                'notes'         => 'Transfer from manufacturer. ' . trim($this->trNotes),
                'created_by'    => auth()->id(),
            ]);
        });

        $this->trHistory[] = [
            'at'       => now()->format('Y-m-d H:i'),
            'product'  => $product->name,
            'qty'      => $qty,
            'business' => $destBiz->name,
            'branch'   => $destBranch->name,
            'notes'    => $this->trNotes,
        ];

        $this->trCategoryId = null;
        $this->trProductId  = null;
        $this->trQuantity   = '';
        $this->trBusinessId = null;
        $this->trBranchId   = null;
        $this->trNotes      = '';

        session()->flash('transfer_success',
            "Transferred {$qty} units of {$product->name} → {$destBranch->name} ({$destBiz->name}).");
    }

    // ── Edit modal ────────────────────────────────────────────────────────
    public bool   $showEditModal      = false;
    public ?int   $editId             = null;
    public string $editBusinessId     = '';
    public string $editBranchId       = '';
    public string $editCategoryId     = '';
    public string $editProductId      = '';
    public string $editUnit           = '';
    public string $editQuantity       = '1';
    public string $editUnitPrice      = '';
    public string $editSellingPrice   = '';
    public array  $editBranches       = [];
    public array  $editCategories     = [];
    public array  $editProducts       = [];

    // ── Delete modal ──────────────────────────────────────────────────────
    public bool   $showDeleteModal    = false;
    public ?int   $deleteId           = null;
    public string $deleteName         = '';

    // ── Filter dropdowns for table ────────────────────────────────────────
    public string $filterBusinessId = '';
    public string $filterBranchId   = '';
    public string $filterCategoryId = '';
    public string $filterProductId  = '';

    public array $filterBranches    = [];
    public array $filterCategories  = [];
    public array $filterProducts    = [];

    public function updatedFilterBusinessId(): void
    {
        $this->filterBranchId   = '';
        $this->filterCategoryId = '';
        $this->filterProductId  = '';
        $this->filterBranches   = [];
        $this->filterCategories = [];
        $this->filterProducts   = [];
        $this->resetPage();

        if (!$this->filterBusinessId) return;

        $this->filterBranches = Branch::where('business_id', $this->filterBusinessId)
            ->active()->orderBy('name')->get(['id', 'name'])->toArray();

        $this->filterCategories = Category::withoutGlobalScopes()
            ->where('business_id', $this->filterBusinessId)
            ->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedFilterBranchId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId(): void
    {
        $this->filterProductId = '';
        $this->filterProducts  = [];
        $this->resetPage();

        if (!$this->filterCategoryId) return;

        $this->filterProducts = Product::withoutGlobalScopes()
            ->where('business_id', $this->filterBusinessId)
            ->where('category_id', $this->filterCategoryId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function updatedFilterProductId(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->filterBusinessId = '';
        $this->filterBranchId   = '';
        $this->filterCategoryId = '';
        $this->filterProductId  = '';
        $this->filterBranches   = [];
        $this->filterCategories = [];
        $this->filterProducts   = [];
        $this->resetPage();
    }

    // ── Validation error for selling price ───────────────────────────────
    public string $sellingPriceError     = '';
    public string $editSellingPriceError = '';

    // ── Store base unit price for scaling when quantity changes ───────────
    public string $baseUnitPrice = '';

    // ── Update handler: when quantity manually changes, keep purchase price as-is ──────────────────
    public function updatedQuantity(): void
    {
        // Just validate, don't auto-scale the price
        // User should manually enter the purchase price they want
        $this->validateSellingPrice();
    }

    // ── Helper: is the currently selected business an oil factory? ────────
    public function isOilBusiness(): bool
    {
        if (!$this->business_id) return false;
        return $this->businessIdIsOil($this->business_id);
    }

    // ── Helper: is the selected branch "Main Factory"? ────────────────────
    public function isMainFactoryBranch(): bool
    {
        if (!$this->branch_id) return false;
        $branch = collect($this->branches)->firstWhere('id', (int) $this->branch_id);
        if (!$branch) return false;
        return stripos($branch['name'] ?? '', 'main factory') !== false;
    }

    // ── Combined check: Albereka Oil Factory + Main Factory branch ────────
    public function isOilFactoryMainBranch(): bool
    {
        return $this->isOilBusiness() && $this->isMainFactoryBranch();
    }

    public function isBookstoreBusiness(): bool
    {
        if (!$this->business_id) return false;
        $biz = collect($this->businesses)->firstWhere('id', (int)$this->business_id);
        if (!$biz) return false;
        $name = strtolower($biz['name'] ?? '');
        return str_contains($name, 'book') || str_contains($name, 'albereka');
    }

    // ── Check if quantity should be hidden (oil business + product type)
    public function shouldHideQuantity(): bool
    {
        return $this->isOilBusiness() && $this->type === 'product';
    }

    // ── Check if selling price should be optional (purchase type only)
    public function shouldOptionalSellingPrice(): bool
    {
        // For bookstore business: selling price is always required
        if ($this->isBookstoreBusiness()) {
            return false;
        }
        
        // Selling price is optional only for "purchase" type (oil factories)
        // For "product" type, selling price is always required
        return $this->type === 'purchase';
    }

    // ── Helper: check any business ID against the oil/factory name pattern ─
    private function nameIsOil(string $name): bool
    {
        return str_contains($name, 'oil')
            || str_contains($name, 'lubricat')
            || str_contains($name, 'factory');
    }

    private function businessIdIsOil(string|int $businessId): bool
    {
        if (!$businessId) return false;

        // First try in-memory list (cheaper)
        $biz = collect($this->businesses)->firstWhere('id', (int) $businessId);
        if ($biz) {
            return $this->nameIsOil(strtolower($biz['name'] ?? ''));
        }

        // Fall back to DB lookup (covers super-admin edge cases)
        $name = strtolower(
            Business::withoutGlobalScopes()->where('id', $businessId)->value('name') ?? ''
        );
        return $this->nameIsOil($name);
    }

    public function mount(): void
    {
        $this->purchase_date = date('Y-m-d');
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
        
        // Set default filter to user's first business
        if (!empty($this->businesses)) {
            $this->filterBusinessId = (string) $this->businesses[0]['id'];
            // Trigger the business filter update to load branches
            $this->updatedFilterBusinessId();
            
            // Also set default branch to first available branch
            if (!empty($this->filterBranches)) {
                $this->filterBranchId = (string) $this->filterBranches[0]['id'];
            }
        }
    }

    // ── Cascade: Business → Branches + Categories ─────────────────────────
    public function updatedBusinessId(): void
    {
        $this->branch_id   = '';
        
        // Set default type based on business type
        if ($this->isBookstoreBusiness()) {
            $this->type = 'purchase'; // Default to purchase for bookstores
        } else {
            $this->type = 'product'; // Default to product for others
        }
        
        $this->category_id = '';
        $this->product_id  = '';
        $this->branches    = [];
        $this->categories  = [];
        $this->products    = [];
        $this->resetValidation();

        if (!$this->business_id) return;

        $this->branches = Branch::where('business_id', $this->business_id)
            ->active()->orderBy('name')->get(['id', 'name'])->toArray();

        $this->loadCategories();
    }

    public function updatedBranchId(): void
    {
        // Reset product/category when branch changes
        $this->category_id = '';
        $this->product_id  = '';
        $this->products    = [];
        $this->unit        = '';
        $this->unit_price  = '';
        $this->selling_price = '';
        $this->sellingPriceError = '';
        $this->resetValidation();

        if (!$this->branch_id) return;

        // For oil factory main branch, switch to product type by default
        if ($this->isOilFactoryMainBranch()) {
            $this->type = 'product';
        }

        $this->loadCategories();
    }

    public function updatedType(): void
    {
        // When type changes, reset downstream fields
        $this->category_id = '';
        $this->product_id  = '';
        $this->products    = [];
        $this->unit        = '';
        $this->unit_price  = '';
        $this->selling_price = '';
        $this->sellingPriceError = '';
        $this->resetValidation();

        $this->loadCategories();
    }

    /**
     * Load categories based on business, branch, and type selection.
     * For oil factory + main factory + product type → show finished goods only.
     * For purchase type → show raw materials and packing materials only.
     */
    private function loadCategories(): void
    {
        if (!$this->business_id) {
            $this->categories = [];
            return;
        }

        $query = Category::withoutGlobalScopes()
            ->where('business_id', $this->business_id)
            ->orderBy('name');

        if ($this->isBookstoreBusiness()) {
            // Bookstore: show ALL categories
        } elseif ($this->type === 'product') {
            // Product type (manufactured/finished): show only finished goods categories
            // i.e. selit, telba, tikur azmud, behrzaf etc.
            $query->whereNotIn('type', [
                Category::TYPE_RAW_MATERIAL,
                Category::TYPE_PACKING_MATERIAL,
            ]);
        } elseif ($this->type === 'purchase') {
            // Purchase type (raw materials): show only seed/bottle/packing categories
            $query->whereIn('type', [
                Category::TYPE_RAW_MATERIAL,
                Category::TYPE_PACKING_MATERIAL,
            ]);
        }

        $this->categories = $query->get(['id', 'name', 'type'])->toArray();
    }

    // ── Cascade: Category → Products ─────────────────────────────────────
    public function updatedCategoryId(): void
    {
        $this->product_id        = '';
        $this->products          = [];
        $this->unit              = '';
        $this->unit_price        = '';
        $this->selling_price     = '';
        $this->sellingPriceError = '';
        $this->resetValidation();

        if (!$this->category_id) return;

        $this->products = Product::withoutGlobalScopes()
            ->where('business_id', $this->business_id)
            ->where('category_id', $this->category_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_price', 'selling_price'])
            ->toArray();
    }

    // ── Pre-fill unit & prices when item selected ─────────────────────────
    public function updatedProductId(): void
    {
        $this->unit              = '';
        $this->unit_price        = '';
        $this->selling_price     = '';
        $this->quantity          = '';
        $this->baseUnitPrice     = '';
        $this->sellingPriceError = '';

        if (!$this->product_id) return;

        $product = Product::withoutGlobalScopes()->find($this->product_id);
        if ($product) {
            // Auto-fill quantity to 1
            if (!$this->shouldHideQuantity()) {
                $this->quantity = '1';
            }
            
            // Auto-fill unit from product
            $this->unit = $product->unit ?? '';
            
            // Auto-fill unit price from product cost_price
            $this->unit_price = $product->cost_price > 0 ? (string) (float) $product->cost_price : '';
            
            // Store base price for later scaling
            if ($product->cost_price > 0) {
                $this->baseUnitPrice = (string) (float) $product->cost_price;
            }
            
            // Auto-fill selling price from product
            $this->selling_price = $product->selling_price > 0 ? (string) (float) $product->selling_price : '';
        }
    }

    public function updatedUnitPrice(): void    { $this->validateSellingPrice(); }
    public function updatedSellingPrice(): void { $this->validateSellingPrice(); }

    private function validateSellingPrice(): void
    {
        $sell     = (float) $this->selling_price;
        $buy      = (float) $this->unit_price;
        $hasValue = $this->selling_price !== '' && $sell > 0;

        // Oil businesses: only flag if a real non-zero value was entered and it's below purchase price
        if ($this->isOilBusiness()) {
            $this->sellingPriceError = ($hasValue && $sell < $buy)
                ? 'Selling price must be ≥ purchase price (' . number_format($buy, 2) . ' ETB).'
                : '';
        } else {
            $this->sellingPriceError = ($this->selling_price !== '' && $sell < $buy)
                ? 'Selling price must be ≥ purchase price (' . number_format($buy, 2) . ' ETB).'
                : '';
        }
    }

    // ── Resolve warehouse for a given branch ──────────────────────────────
    private function resolveWarehouseId(string $branchId, string $businessId): ?int
    {
        if (!$branchId) return null;

        $wh = Warehouse::where('branch_id', $branchId)->active()->first()
            ?? Warehouse::where('business_id', $businessId)->active()->first();

        return $wh ? $wh->id : null;
    }

    // ── Determine purchase type from product ──────────────────────────────
    private function resolvePurchaseType(Product $product): string
    {
        return match ($product->type) {
            'packing_material' => Purchase::TYPE_PACKING_MATERIAL,
            'raw_material'     => Purchase::TYPE_RAW_MATERIAL,
            default            => Purchase::TYPE_PRODUCT,
        };
    }

    // ── Save new purchase (upsert if same business+branch+product exists) ──
    public function save(): void
    {
        try {
            // Ensure type is set correctly for bookstores
            if ($this->isBookstoreBusiness() && (!$this->type || $this->type === '')) {
                $this->type = 'purchase';
            }
            
            // Quantity and Unit are required unless shouldHideQuantity is true (oil factory + main factory + product)
            $quantityRule = $this->shouldHideQuantity() ? 'nullable|numeric|min:0' : 'required|numeric|min:0.001';
            $unitRule = $this->shouldHideQuantity() ? 'nullable|string|max:50' : 'required|string|max:50';
            $sellingRule = $this->shouldOptionalSellingPrice() ? 'nullable|numeric|min:0' : 'required|numeric|min:0';

            $this->validate([
                'business_id'   => 'required|integer|exists:businesses,id',
                'branch_id'     => 'required|integer|exists:branches,id',
                'type'          => 'required|in:product,purchase',
                'category_id'   => 'required|integer|exists:categories,id',
                'product_id'    => 'required|integer|exists:products,id',
                'quantity'      => $quantityRule,
                'unit'          => $unitRule,
                'unit_price'    => 'required|numeric|min:0',
                'selling_price' => $sellingRule,
            ]);

            // Only validate ≥ constraint when selling price is actually provided
            $addSell     = (float) $this->selling_price;
            $addBuy      = (float) $this->unit_price;
            $addHasValue = $this->selling_price !== '' && $addSell > 0;
            if ($addHasValue && $addSell < $addBuy) {
                $this->sellingPriceError = 'Selling price must be ≥ purchase price.';
                return;
            }

            // For oil factory main branch + product type, save with default quantity (0)
            if ($this->shouldHideQuantity()) {
                app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->business_id);

                $product = Product::withoutGlobalScopes()->findOrFail($this->product_id);
                $warehouseId = $this->resolveWarehouseId($this->branch_id, $this->business_id);
                $type = $this->resolvePurchaseType($product);

                // Create new purchase with 0 quantity (manufacturer will add quantity later)
                $purchase = Purchase::create([
                    'business_id'   => $this->business_id,
                    'branch_id'     => $this->branch_id,
                    'warehouse_id'  => $warehouseId,
                    'product_id'    => $this->product_id,
                    'category_id'   => $this->category_id,
                    'purchase_type' => $type,
                    'purchase_date' => $this->purchase_date ?: date('Y-m-d'),
                    'quantity'      => 0, // Will be added by manufacturer
                    'unit'          => $product->unit ?? 'unit',
                    'unit_price'    => (float) $this->unit_price,
                    'status'        => Purchase::STATUS_COMPLETED,
                    'created_by'    => auth()->id(),
                ]);

                // Update product prices
                $product->cost_price = (float) $this->unit_price;
                if ($this->selling_price !== '') {
                    $product->selling_price = (float) $this->selling_price;
                }
                $product->status = 'active';
                $product->is_marketplace_visible = true;

                // Initialize metadata for manufacturing tracking
                $meta = $product->metadata ?? [];
                
                // Save image if uploaded
                if ($this->image) {
                    $imagePath = $this->image->store('products', 'public');
                    $product->image_path = $imagePath;
                    $meta['images'] = [[
                        'path'       => $imagePath,
                        'alt'        => $product->name,
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]];
                }
                
                $product->metadata = $meta;
                $product->save();

                session()->flash('success', 'Product recorded. Quantity will be added by manufacturer.');
                $this->reset(['product_id', 'unit', 'quantity', 'unit_price', 'selling_price']);
                return;
            }

            app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->business_id);

            $product     = Product::withoutGlobalScopes()->findOrFail($this->product_id);
            $warehouseId = $this->resolveWarehouseId($this->branch_id, $this->business_id);
            $type        = $this->resolvePurchaseType($product);

            DB::transaction(function () use ($product, $warehouseId, $type) {
                $newQty   = (float) $this->quantity;
                $newPrice = (float) $this->unit_price;

                // ── Create new purchase ───────────────────────────────────
                $purchase = Purchase::create([
                    'business_id'   => $this->business_id,
                    'branch_id'     => $this->branch_id,
                    'warehouse_id'  => $warehouseId,
                    'product_id'    => $this->product_id,
                    'category_id'   => $this->category_id,
                    'purchase_type' => $type,
                    'purchase_date' => $this->purchase_date ?: date('Y-m-d'),
                    'quantity'      => $newQty,
                    'unit'          => $this->unit,
                    'unit_price'    => $newPrice,
                    'status'        => Purchase::STATUS_COMPLETED,
                    'created_by'    => auth()->id(),
                ]);

                if ($warehouseId) {
                    app(InventoryService::class)->receivePurchase(
                        $warehouseId,
                        $product->id,
                        $newQty,
                        $newPrice,
                        $purchase->id,
                        'purchase'
                    );
                }

                // Always update product prices to latest values
                $product->cost_price    = $newPrice;
                if ($this->selling_price !== '') {
                    $product->selling_price = (float) $this->selling_price;
                }
                $product->status = 'active';
                $product->is_marketplace_visible = true;

                // Save image if uploaded — only update if product has no image yet or new one provided
                if ($this->image) {
                    $imagePath = $this->image->store('products', 'public');
                    $product->image_path = $imagePath;
                    // Also add to metadata images for modern accessor
                    $meta = $product->metadata ?? [];
                    $meta['images'] = [[
                        'path'       => $imagePath,
                        'alt'        => $product->name,
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]];
                    $product->metadata = $meta;
                }

                $product->save();
            });

            // Reset form fields
            $this->product_id    = '';
            $this->unit          = '';
            $this->quantity      = '';
            $this->unit_price    = '';
            $this->selling_price = '';
            $this->image         = null;
            $this->sellingPriceError = '';
            $this->products      = [];
            $this->resetPage();
            session()->flash('success', 'Purchase recorded successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving purchase: ' . $e->getMessage());
            \Log::error('Purchase save error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    /**
     * Check if purchase can be edited (all types are allowed)
     */
    public function canEditPurchase(Purchase $purchase): bool
    {
        // Allow editing for all purchase types: product (oil), purchase (raw materials), etc.
        return true;
    }

    /**
     * Check if purchase can be deleted (all types are allowed)
     */
    public function canDeletePurchase(Purchase $purchase): bool
    {
        // Allow deleting for all purchase types: product (oil), purchase (raw materials), etc.
        return true;
    }

    public function openEdit(int $id): void
    {
        $p = Purchase::with(['product'])->findOrFail($id);

        $this->editId           = $id;
        $this->editBusinessId   = (string) $p->business_id;
        $this->editBranchId     = (string) $p->branch_id;
        $this->editCategoryId   = (string) $p->category_id;
        $this->editProductId    = (string) $p->product_id;
        $this->editUnit         = $p->unit;
        $this->editQuantity     = (string) (float) $p->quantity;
        $this->editUnitPrice    = (string) (float) $p->unit_price;
        $this->editSellingPrice = (string) (float) ($p->product->selling_price ?? 0);
        $this->editSellingPriceError = '';

        $this->editBranches = Branch::where('business_id', $p->business_id)
            ->active()->orderBy('name')->get(['id', 'name'])->toArray();

        $this->editCategories = Category::withoutGlobalScopes()
            ->where('business_id', $p->business_id)
            ->orderBy('name')->get(['id', 'name'])->toArray();

        $this->editProducts = Product::withoutGlobalScopes()
            ->where('business_id', $p->business_id)
            ->where('category_id', $p->category_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_price', 'selling_price'])
            ->toArray();

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updatedEditBusinessId(): void
    {
        $this->editBranchId   = '';
        $this->editCategoryId = '';
        $this->editProductId  = '';
        $this->editBranches   = Branch::where('business_id', $this->editBusinessId)
            ->active()->orderBy('name')->get(['id', 'name'])->toArray();
        $this->editCategories = Category::withoutGlobalScopes()
            ->where('business_id', $this->editBusinessId)
            ->orderBy('name')->get(['id', 'name'])->toArray();
        $this->editProducts   = [];
    }

    public function updatedEditCategoryId(): void
    {
        $this->editProductId = '';
        $this->editProducts  = Product::withoutGlobalScopes()
            ->where('business_id', $this->editBusinessId)
            ->where('category_id', $this->editCategoryId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_price', 'selling_price'])
            ->toArray();
    }

    public function updatedEditBranchId(): void
    {
        // Branch change in edit modal - no cascading needed but can be used for future extensions
    }

    public function updatedEditProductId(): void
    {
        if (!$this->editProductId) return;
        $product = Product::withoutGlobalScopes()->find($this->editProductId);
        if ($product) {
            $this->editUnit         = $product->unit ?? '';
            $this->editUnitPrice    = $product->cost_price > 0    ? (string) (float) $product->cost_price    : '';
            $this->editSellingPrice = $product->selling_price > 0 ? (string) (float) $product->selling_price : '';
        }
        $this->validateEditSellingPrice();
    }

    public function updatedEditUnitPrice(): void    { $this->validateEditSellingPrice(); }
    public function updatedEditSellingPrice(): void { $this->validateEditSellingPrice(); }

    private function validateEditSellingPrice(): void
    {
        $sell = (float) $this->editSellingPrice;
        $buy  = (float) $this->editUnitPrice;

        // For oil businesses selling price is optional — skip the ≥ check
        // when it's blank or zero (meaning "not set")
        $isOil = $this->businessIdIsOil($this->editBusinessId);
        $hasValue = $this->editSellingPrice !== '' && $sell > 0;

        if (!$isOil && $this->editSellingPrice !== '' && $sell < $buy) {
            $this->editSellingPriceError = 'Selling price must be ≥ purchase price (' . number_format($buy, 2) . ' ETB).';
        } elseif ($isOil && $hasValue && $sell < $buy) {
            $this->editSellingPriceError = 'Selling price must be ≥ purchase price (' . number_format($buy, 2) . ' ETB).';
        } else {
            $this->editSellingPriceError = '';
        }
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editId        = null;
        $this->editSellingPriceError = '';
        $this->resetValidation();
    }

    public function updatePurchase(): void
    {
        $isOil = $this->businessIdIsOil($this->editBusinessId);

        $this->validate([
            'editBusinessId'   => 'required|integer|exists:businesses,id',
            'editBranchId'     => 'required|integer|exists:branches,id',
            'editCategoryId'   => 'required|integer|exists:categories,id',
            'editProductId'    => 'required|integer|exists:products,id',
            'editQuantity'     => 'required|numeric|min:0.001',
            'editUnit'         => 'required|string|max:50',
            'editUnitPrice'    => 'required|numeric|min:0',
            'editSellingPrice' => $isOil ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
        ]);

        if ($this->editSellingPriceError) return;

        // For oil businesses: only block if a real value was entered AND it's below purchase price
        $editSell = (float) $this->editSellingPrice;
        $editBuy  = (float) $this->editUnitPrice;
        $editHasValue = $this->editSellingPrice !== '' && $editSell > 0;
        if (!$isOil && $this->editSellingPrice !== '' && $editSell < $editBuy) {
            $this->editSellingPriceError = 'Selling price must be ≥ purchase price.';
            return;
        }
        if ($isOil && $editHasValue && $editSell < $editBuy) {
            $this->editSellingPriceError = 'Selling price must be ≥ purchase price.';
            return;
        }

        // Authorize: user must have permission to manage inventory for this business
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->editBusinessId);

        $purchase = Purchase::findOrFail($this->editId);
        $product  = Product::withoutGlobalScopes()->findOrFail($this->editProductId);
        $warehouseId = $this->resolveWarehouseId($this->editBranchId, $this->editBusinessId);

        DB::transaction(function () use ($purchase, $product, $warehouseId) {
            $purchase->update([
                'business_id'   => $this->editBusinessId,
                'branch_id'     => $this->editBranchId,
                'warehouse_id'  => $warehouseId,
                'category_id'   => $this->editCategoryId,
                'product_id'    => $this->editProductId,
                'quantity'      => (float) $this->editQuantity,
                'unit'          => $this->editUnit,
                'unit_price'    => (float) $this->editUnitPrice,
            ]);

            $product->cost_price    = (float) $this->editUnitPrice;
            $product->selling_price = (float) $this->editSellingPrice;
            $product->status = 'active';
            $product->is_marketplace_visible = true;
            $product->save();
        });

        session()->flash('success', 'Purchase updated successfully.');
        $this->closeEdit();
        $this->resetPage();
    }

    // ── Delete ────────────────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $p = Purchase::with('product')->findOrFail($id);
        $this->deleteId   = $id;
        $this->deleteName = $p->product->name ?? "Purchase #{$id}";
        $this->showDeleteModal = true;
    }

    public function closeDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId        = null;
        $this->deleteName      = '';
    }

    public function deletePurchase(): void
    {
        if (!$this->deleteId) return;
        
        $purchase = Purchase::findOrFail($this->deleteId);
        
        // Authorize: user must have permission to manage inventory for this business
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $purchase->business_id);
        
        $purchase->delete();
        session()->flash('success', 'Purchase deleted successfully.');
        $this->closeDelete();
        $this->resetPage();
    }

    // ── Render ────────────────────────────────────────────────────────────
    public function render()
    {
        $user = auth()->user();

        $purchases = Purchase::with(['business', 'branch', 'category', 'product'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->where('status', Purchase::STATUS_COMPLETED)
            ->when($this->filterBusinessId, fn($q) =>
                $q->where('business_id', $this->filterBusinessId)
            )
            ->when($this->filterBranchId, fn($q) =>
                $q->where('branch_id', $this->filterBranchId)
            )
            ->when($this->filterCategoryId, fn($q) =>
                $q->where('category_id', $this->filterCategoryId)
            )
            ->when($this->filterProductId, fn($q) =>
                $q->where('product_id', $this->filterProductId)
            )
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get();

        // Also include manufactured products (those with manufacturing_quantity > 0)
        $manufactureredProducts = collect();
        if ($this->filterBusinessId) {
            $businessId = (int) $this->filterBusinessId;
            $branchId = $this->filterBranchId ? (int) $this->filterBranchId : null;
            $categoryId = $this->filterCategoryId ? (int) $this->filterCategoryId : null;
            $productId = $this->filterProductId ? (int) $this->filterProductId : null;
            
            $products = Product::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('status', 'active')
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->when($productId, fn($q) => $q->where('id', $productId))
                ->get();
            
            $manufactureredProducts = $products
                ->filter(function ($product) {
                    $meta = $product->metadata ?? [];
                    return isset($meta['manufacturing_quantity']) && (float)$meta['manufacturing_quantity'] > 0;
                })
                ->filter(function ($product) use ($branchId) {
                    // Filter by branch if one is selected
                    if (!$branchId) return true;
                    
                    $meta = $product->metadata ?? [];
                    $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                    
                    // Check if this product was manufactured by the selected branch
                    return isset($manufacturingByBranch[$branchId]) && (float)$manufacturingByBranch[$branchId] > 0;
                })
                ->map(function ($product) use ($branchId) {
                    $meta = $product->metadata ?? [];
                    
                    // Get manufacturing quantity for this branch
                    if ($branchId) {
                        $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
                        $mfgQty = (float) ($manufacturingByBranch[$branchId] ?? 0);
                    } else {
                        $mfgQty = (float) ($meta['manufacturing_quantity'] ?? 0);
                    }
                    
                    // Get the branch object
                    $branch = Branch::find($branchId);
                    
                    // Create synthetic purchase object for manufactured products
                    return (object)[
                        'id'                    => 'mfg_' . $product->id,
                        'product_id'            => $product->id,
                        'product'               => $product,
                        'business_id'           => $product->business_id,
                        'branch_id'             => $branchId,
                        'branch'                => $branch,
                        'category_id'           => $product->category_id,
                        'category'              => $product->category,
                        'quantity'              => $mfgQty,
                        'total_purchase_qty'    => $mfgQty,
                        'unit'                  => $product->unit ?? 'pcs',
                        'unit_price'            => $product->cost_price ?? 0,
                        'purchase_type'         => 'product',
                        'purchase_date'         => isset($meta['manufacturing_added_at']) 
                            ? \Carbon\Carbon::parse($meta['manufacturing_added_at'])->toDateTimeImmutable()
                            : now(),
                        'current_available_qty' => $mfgQty,
                        'purchase_count'        => 1,
                        'is_manufactured'       => true,
                    ];
                });
        }

        // Merge regular purchases with manufactured products
        $allItems = collect($purchases)->merge($manufactureredProducts);

        // Group by product_id + branch_id to consolidate duplicates
        $grouped = $allItems->groupBy(function ($item) {
            return $item->product_id . '_' . $item->branch_id;
        })->map(function ($group) {
            // Take the first item (prefer Purchase records over synthetic objects)
            $latestItem = $group->sortBy(fn($i) => isset($i->is_manufactured) ? 1 : 0)->first();
            
            // Sum all quantities for this product
            $totalQty = (float) $group->sum('quantity');
            
            // Add consolidated data
            $latestItem->total_purchase_qty = $totalQty;
            $latestItem->purchase_count = $group->count();
            
            // Calculate current available qty
            if ($latestItem->product_id) {
                $latestItem->current_available_qty = $this->getProductAvailableStock($latestItem->product_id);
            } else {
                $latestItem->current_available_qty = 0;
            }
            
            return $latestItem;
        })->values();

        // Sort by purchase date (descending) and paginate manually
        $grouped = collect($grouped)
            ->sortByDesc('purchase_date')
            ->values();

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = 20;
        $purchases = new \Illuminate\Pagination\LengthAwarePaginator(
            $grouped->forPage($page, $perPage),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.admin.purchases.create', [
            'purchases' => $purchases,
        ])->title('Price Management');
    }
}
