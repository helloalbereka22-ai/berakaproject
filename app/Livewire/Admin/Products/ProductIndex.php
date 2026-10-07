<?php

namespace App\Livewire\Admin\Products;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ProductIndex extends Component
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

    public array $businesses = [];
    public array $categories = [];
    public array $units      = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id','name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id','name'])->toArray();
            
        $this->units = Product::withoutGlobalScopes()->whereNotNull('unit')->where('unit', '!=', '')->distinct()->pluck('unit')->toArray();
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
            ->active();

        if ($this->type === Product::TYPE_RAW_MATERIAL) {
            $query->forRawMaterials();
        } elseif ($this->type === Product::TYPE_PACKING_MATERIAL) {
            $query->forPackagingMaterials();
        } elseif ($this->type) {
            // For other specific types (book, general, finished_product)
            $query->forSaleableProducts();
        }

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
        $query = Product::withoutGlobalScopes()
            ->with(['category', 'business', 'stockMovements'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->search,     fn($q) => $q->search($this->search))
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->type,       fn($q) => $q->where('type', $this->type))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->when($this->unit,       fn($q) => $q->where('unit', $this->unit))
            ->orderBy('sort_order')->orderBy('name');

        return view('livewire.admin.products.index', [
            'products'     => $query->paginate(25),
            'productTypes' => Product::TYPES,
            'statuses'     => Product::STATUSES,
        ])->title('Products');
    }
}
