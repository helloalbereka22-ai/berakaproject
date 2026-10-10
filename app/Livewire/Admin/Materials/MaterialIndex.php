<?php

namespace App\Livewire\Admin\Materials;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Branch;
use App\Models\StockMovement;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class MaterialIndex extends Component
{
    use WithPagination;

    // ── Filters ──────────────────────────────────────────────────
    public string $search     = '';
    public string $businessId = '';
    public string $categoryId = '';
    public string $tab        = 'all'; // all | raw | packaging
    public string $unit       = '';
    public string $status     = '';

    public bool  $showDeleteConfirm = false;
    public ?int  $deleteId          = null;

    public array $businesses = [];
    public array $categories = [];

    // Material types per the spec
    const MATERIAL_TYPES = [
        Product::TYPE_RAW_MATERIAL     => 'raw',
        Product::TYPE_PACKING_MATERIAL => 'packaging',
    ];

    public function mount(): void
    {
        // Set initial tab based on current route
        $routeName = request()->route()?->getName() ?? '';
        if (str_ends_with($routeName, '.raw')) {
            $this->tab = 'raw';
        } elseif (str_ends_with($routeName, '.packaging')) {
            $this->tab = 'packaging';
        }

        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedBusinessId(): void {
        $this->resetPage();
        $this->categories = $this->businessId
            ? Category::withoutGlobalScopes()
                ->where('business_id', $this->businessId)
                ->active()->orderBy('name')->get(['id', 'name'])->toArray()
            : [];
        $this->categoryId = '';
    }
    public function updatedTab(): void        { $this->resetPage(); }
    public function updatedUnit(): void       { $this->resetPage(); }
    public function updatedStatus(): void     { $this->resetPage(); }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $this->authorize('product.update');
        $product = Product::withoutGlobalScopes()->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $product->business_id);
        app(ProductService::class)->toggleStatus($product);
        session()->flash('success', 'Material status updated.');
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
        $this->deleteId          = null;
        session()->flash('success', 'Material deleted.');
    }

    // ── Determine which product types to query based on active tab ─
    private function activeTypes(): array
    {
        return match ($this->tab) {
            'raw'       => [Product::TYPE_RAW_MATERIAL],
            'packaging' => [Product::TYPE_PACKING_MATERIAL],
            default     => [Product::TYPE_RAW_MATERIAL, Product::TYPE_PACKING_MATERIAL],
        };
    }

    public function render()
    {
        $user  = auth()->user();
        $types = $this->activeTypes();

        // Get all business IDs the user has access to
        $accessibleBusinessIds = $user->isSuperAdmin() 
            ? Business::pluck('id')->toArray()
            : $user->assignedBusinessIds();

        // Get the user's assigned branch IDs
        // If user has NO specific branches but IS assigned to businesses,
        // get ALL branches for those businesses
        $userBranchIds = $user->isSuperAdmin()
            ? Branch::pluck('id')->toArray()
            : $user->assignedBranchIds();
        
        // If user has no explicit branch assignments, get branches for their assigned businesses
        if (empty($userBranchIds) && !empty($accessibleBusinessIds)) {
            $userBranchIds = Branch::whereIn('business_id', $accessibleBusinessIds)
                ->pluck('id')
                ->toArray();
        }

        // If user has only ONE business assigned, filter to that business automatically
        // This prevents manufacturing staff from seeing other branches' data
        $isUserRestrictedToOneBusiness = (count($accessibleBusinessIds) === 1);
        if ($isUserRestrictedToOneBusiness && empty($this->businessId)) {
            $this->businessId = (string) $accessibleBusinessIds[0];
        }

        $baseQuery = Product::withoutGlobalScopes()
            ->with(['category', 'business'])
            ->whereIn('type', $types)
            ->whereIn('business_id', $accessibleBusinessIds)
            ->when($this->search,     fn($q) => $q->search($this->search))
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->unit,       fn($q) => $q->where('unit', $this->unit))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->orderBy('name');

        // Fetch materials and calculate stock per user's branches only
        $materialsCollection = $baseQuery->get();

        // Calculate per-branch stock for each material
        $materialsWithBranchStock = $materialsCollection->map(function ($product) use ($userBranchIds) {
            // Calculate stock only from the user's assigned branches
            $branchStock = StockMovement::where('item_id', $product->id)
                ->where('item_type', 'product')
                ->whereIn('branch_id', $userBranchIds)
                ->get()
                ->reduce(function ($carry, $mov) {
                    return $carry + ($mov->direction === 'in' ? $mov->quantity : -$mov->quantity);
                }, 0);
            
            // Override the current_stock with branch-filtered value
            $product->current_stock = (float) $branchStock;
            return $product;
        })->values();

        // Manually paginate the collection
        $page = request()->query('page', 1);
        $perPage = 25;
        $paginatedItems = $materialsWithBranchStock->forPage($page, $perPage);
        $materials = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $materialsWithBranchStock->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Aggregate totals for header KPIs across accessible businesses
        $summaryTotals = [
            'raw'       => Product::withoutGlobalScopes()->where('type', Product::TYPE_RAW_MATERIAL)
                ->whereIn('business_id', $accessibleBusinessIds)
                ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
                ->count(),
            'packaging' => Product::withoutGlobalScopes()->where('type', Product::TYPE_PACKING_MATERIAL)
                ->whereIn('business_id', $accessibleBusinessIds)
                ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
                ->count(),
        ];

        // Distinct units for filter dropdown
        $units = Product::withoutGlobalScopes()
            ->whereIn('type', [Product::TYPE_RAW_MATERIAL, Product::TYPE_PACKING_MATERIAL])
            ->whereIn('business_id', $accessibleBusinessIds)
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->toArray();

        return view('livewire.admin.materials.index', [
            'materials'     => $materials,
            'summaryTotals' => $summaryTotals,
            'units'         => $units,
            'statuses'      => Product::STATUSES,
        ])->title('Raw Materials');
    }
}
