<?php

namespace App\Livewire\Admin\Materials;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
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

        $query = Product::withoutGlobalScopes()
            ->with(['category', 'business'])
            ->whereIn('type', $types)
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->search,     fn($q) => $q->search($this->search))
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->unit,       fn($q) => $q->where('unit', $this->unit))
            ->when($this->status,     fn($q) => $q->where('status', $this->status))
            ->orderBy('name');

        // Aggregate totals for header KPIs
        $totalQuery = Product::withoutGlobalScopes()
            ->whereIn('type', $types)
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId));

        $summaryTotals = [
            'raw'       => Product::withoutGlobalScopes()->where('type', Product::TYPE_RAW_MATERIAL)
                ->when(!$user->isSuperAdmin(), fn($q) => $q->whereIn('business_id', $user->assignedBusinessIds()))
                ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
                ->count(),
            'packaging' => Product::withoutGlobalScopes()->where('type', Product::TYPE_PACKING_MATERIAL)
                ->when(!$user->isSuperAdmin(), fn($q) => $q->whereIn('business_id', $user->assignedBusinessIds()))
                ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
                ->count(),
        ];

        // Distinct units for filter dropdown
        $units = Product::withoutGlobalScopes()
            ->whereIn('type', [Product::TYPE_RAW_MATERIAL, Product::TYPE_PACKING_MATERIAL])
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->toArray();

        return view('livewire.admin.materials.index', [
            'materials'     => $query->paginate(25),
            'summaryTotals' => $summaryTotals,
            'units'         => $units,
            'statuses'      => Product::STATUSES,
        ])->title('Raw Materials');
    }
}
