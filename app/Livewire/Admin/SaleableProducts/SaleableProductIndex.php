<?php

namespace App\Livewire\Admin\SaleableProducts;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class SaleableProductIndex extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $businessId = '';
    public string $categoryId = '';
    public string $type       = '';
    public string $status     = '';
    public string $unit       = '';

    public bool  $showDeleteConfirm = false;
    public ?int  $deleteId          = null;
    
    public float $totalPrice = 0;
    public int $totalProductCount = 0;

    public array $businesses = [];
    public array $categories = [];
    public array $units      = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id','name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id','name'])->toArray();
            
        $this->units = Product::withoutGlobalScopes()
            ->whereIn('type', Product::SALEABLE_PRODUCT_TYPES)
            ->whereNotNull('unit')
            ->where('unit', '!=', '')
            ->distinct()
            ->pluck('unit')
            ->toArray();
    }

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedBusinessId(): void {
        $this->resetPage();
        $this->updateCategories();
        $this->categoryId = '';
    }
    public function updatedType(): void {
        $this->resetPage();
        $this->updateCategories();
        $this->categoryId = '';
    }

    private function updateCategories(): void {
        if (!$this->businessId) {
            $this->categories = [];
            return;
        }

        $query = Category::withoutGlobalScopes()
            ->where('business_id', $this->businessId)
            ->forSaleableProducts()
            ->active();

        $this->categories = $query->orderBy('name')->get(['id','name'])->toArray();
    }
    public function updatedStatus(): void     { $this->resetPage(); }

    public function toggleMarketplace(int $id): void
    {
        $this->authorize('product.update');
        $product = Product::withoutGlobalScopes()->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $product->business_id);
        try {
            app(ProductService::class)->toggleMarketplace($product);
            session()->flash('success', 'Marketplace visibility updated.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleStatus(int $id): void
    {
        $this->authorize('product.update');
        $product = Product::withoutGlobalScopes()->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $product->business_id);
        app(ProductService::class)->toggleStatus($product);
        session()->flash('success', 'Product status updated.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('product.delete');
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $this->authorize('product.delete');
        $product = Product::withoutGlobalScopes()->findOrFail($this->deleteId);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $product->business_id);
        app(ProductService::class)->delete($product);
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Product deleted.');
    }

    public function render()
    {
        $user  = auth()->user();
        $userBranchIds = $user->assignedBranchIds();
        
        $typesToSearch = $this->type ? [$this->type] : Product::SALEABLE_PRODUCT_TYPES;

        // Get products that have stock in user's assigned branches
        $query = Product::withoutGlobalScopes()
            ->with(['category', 'business', 'stockMovements'])
            ->whereIn('type', $typesToSearch)
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when(!$user->isSuperAdmin(), function($q) use ($userBranchIds) {
                // Filter to show only products with stock in user's assigned branches
                $q->whereIn('id', function($subquery) use ($userBranchIds) {
                    $subquery->select('item_id')
                        ->from('stock_movements')
                        ->where('item_type', 'product')
                        ->whereIn('branch_id', $userBranchIds)
                        ->groupBy('item_id')
                        ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) > 0");
                });
            })
            ->when($this->search,     fn($q) => $q->search($this->search))
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->when($this->unit,       fn($q) => $q->where('unit', $this->unit))
            ->orderBy('sort_order')->orderBy('name');

        $products = $query->paginate(25);
        
        // Calculate total price for ALL products (across all pages) matching the filters
        $allProductsQuery = Product::withoutGlobalScopes()
            ->with(['stockMovements'])  // Eager load the relationship
            ->whereIn('type', $typesToSearch)
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when(!$user->isSuperAdmin(), function($q) use ($userBranchIds) {
                // Filter to show only products with stock in user's assigned branches
                $q->whereIn('id', function($subquery) use ($userBranchIds) {
                    $subquery->select('item_id')
                        ->from('stock_movements')
                        ->where('item_type', 'product')
                        ->whereIn('branch_id', $userBranchIds)
                        ->groupBy('item_id')
                        ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) > 0");
                });
            })
            ->when($this->search,     fn($q) => $q->search($this->search))
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->when($this->unit,       fn($q) => $q->where('unit', $this->unit));
        
        $allProducts = $allProductsQuery->get();
        
        // Calculate total price using a single aggregated query
        $this->totalPrice = 0;
        $this->totalProductCount = count($allProducts);
        
        // Get a list of product IDs
        $productIds = $allProducts->pluck('id')->toArray();
        
        // Initialize stockData as an empty collection
        $stockData = collect();
        
        if (!empty($productIds)) {
            // Get stock movements grouped by product, filtered by user's branches (or no filter for super admin)
            $stockData = \DB::table('stock_movements')
                ->whereIn('item_id', $productIds)
                ->where('item_type', 'product')
                ->when(!$user->isSuperAdmin() && !empty($userBranchIds), fn($q) =>
                    $q->whereIn('branch_id', $userBranchIds)
                )
                ->groupBy('item_id')
                ->selectRaw("item_id, SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as total_qty")
                ->get()
                ->keyBy('item_id');
            
            // Calculate total by iterating through products
            foreach ($allProducts as $product) {
                $qty = (float) ($stockData->get($product->id)->total_qty ?? 0);
                $price = (float) $product->selling_price;
                $this->totalPrice += ($qty * $price);
            }
        }

        $availableTypes = [];
        foreach (Product::SALEABLE_PRODUCT_TYPES as $t) {
            $availableTypes[$t] = Product::TYPES[$t] ?? ucfirst($t);
        }

        return view('livewire.admin.saleable-products.index', [
            'products'     => $products,
            'productTypes' => $availableTypes,
            'statuses'     => Product::STATUSES,
            'stockData'    => $stockData,
        ])->title('Bookstore Products');
    }
}
