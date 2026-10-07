<?php

namespace App\Livewire\Admin\Items;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ItemManager extends Component
{
    use WithPagination, WithFileUploads;

    // ── Add Item Form ──────────────────────────────────────────────────────
    public string $businessId  = '';
    public string $simpleType  = 'product'; // "product" or "purchase" for oil businesses
    public string $categoryId  = '';
    public string $itemName    = '';
    public $itemImage          = null; // For image upload

    // ── Filter ────────────────────────────────────────────────────────────
    public string $filterBiz    = '';
    public string $filterCat    = '';
    public string $search       = '';

    // ── Lists ─────────────────────────────────────────────────────────────
    public array $businesses      = [];
    public array $categories      = [];   // for add form (filtered by businessId)
    public array $filterCategories = [];  // for filter bar (filtered by filterBiz)

    // ── Delete confirm ────────────────────────────────────────────────────
    public ?int  $deleteId          = null;
    public string $deleteName       = '';

    // ── Edit modal ────────────────────────────────────────────────────────
    public bool   $showEditModal    = false;
    public ?int   $editId           = null;
    public string $editBusinessId   = '';
    public string $editSimpleType   = 'product';
    public string $editCategoryId   = '';
    public string $editItemName     = '';
    public array  $editCategories   = [];
    public $editImage               = null; // For image upload in edit

    // ── Constants ─────────────────────────────────────────────────────────
    private const OIL_TYPES  = ['raw_material', 'packing_material'];
    private const BOOK_TYPES = ['book', 'fashion', 'general', 'stationery'];

    public function mount(): void
    {
        $this->businesses = Business::active()->orderBy('name')->get(['id', 'name', 'code'])->toArray();
    }

    // ── Watchers ──────────────────────────────────────────────────────────

    public function updatedBusinessId(): void
    {
        $this->simpleType = 'product';
        $this->categoryId = '';
        $this->itemName   = '';
        $this->loadFormCategories();
    }

    public function updatedSimpleType(): void
    {
        $this->categoryId = '';
        $this->itemName   = '';
        $this->loadFormCategories();
    }

    public function updatedCategoryId(): void
    {
        $this->itemName  = '';
    }

    public function updatedFilterBiz(): void
    {
        $this->filterCat = '';
        $this->resetPage();
        $this->loadFilterCategories();
    }

    public function updatedFilterCat(): void  { $this->resetPage(); }
    public function updatedSearch(): void     { $this->resetPage(); }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isOilBusiness(): bool
    {
        if (!$this->businessId) return false;
        $biz = collect($this->businesses)->firstWhere('id', (int)$this->businessId);
        if (!$biz) return false;
        $name = strtolower($biz['name'] ?? '');
        return str_contains($name, 'oil') || str_contains($name, 'factory') || str_contains($name, 'manufactur');
    }

    public function isEditOilBusiness(): bool
    {
        if (!$this->editBusinessId) return false;
        $biz = collect($this->businesses)->firstWhere('id', (int)$this->editBusinessId);
        if (!$biz) return false;
        $name = strtolower($biz['name'] ?? '');
        return str_contains($name, 'oil') || str_contains($name, 'factory') || str_contains($name, 'manufactur');
    }

    public function isBookstoreBusiness(): bool
    {
        if (!$this->businessId) return false;
        $biz = collect($this->businesses)->firstWhere('id', (int)$this->businessId);
        return $biz && str_contains(strtolower($biz['name'] ?? ''), 'book');
    }

    private function loadFormCategories(): void
    {
        $this->categories = [];
        if (!$this->businessId) return;

        $query = Category::withoutGlobalScopes()
            ->where('business_id', $this->businessId);

        // Filter by selected type
        if ($this->simpleType === 'purchase') {
            // For purchase: show ONLY raw_material, packing_material, and seed
            $query->whereIn('type', ['raw_material', 'packing_material', 'seed']);
        } else {
            // For product: exclude raw_material, packing_material, and seed
            $query->whereNotIn('type', ['raw_material', 'packing_material', 'seed']);
        }

        $this->categories = $query->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->toArray();
    }

    private function loadFilterCategories(): void
    {
        $this->filterCategories = [];
        if (!$this->filterBiz) return;

        $this->filterCategories = Category::withoutGlobalScopes()
            ->where('business_id', $this->filterBiz)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    private function resolveProductType(): string
    {
        if (!$this->categoryId) return 'general';
        $cat = collect($this->categories)->firstWhere('id', (int)$this->categoryId);
        return $cat['type'] ?? 'general';
    }

    // ── Add Item ──────────────────────────────────────────────────────────

    public function addItem(): void
    {
        $this->validate([
            'businessId' => 'required|integer|exists:businesses,id',
            'categoryId' => 'required|integer|exists:categories,id',
            'itemName'   => 'required|string|max:255',
            'itemImage'  => 'nullable|image|max:2048', // max 2MB
        ]);

        $type = $this->resolveProductType();

        $product = app(ProductService::class)->create([
            'category_id'   => $this->categoryId,
            'type'          => $type,
            'name'          => trim($this->itemName),
            'unit'          => '',
            'cost_price'    => 0,
            'selling_price' => 0,
            'status'        => Product::STATUS_ACTIVE,
            'is_featured'   => false,
        ], (int) $this->businessId);

        // Handle image upload
        if ($this->itemImage) {
            $path = $this->itemImage->store('products', 'public');
            $product->addImage($path, $this->itemName);
        }

        $addedName = $this->itemName;
        $this->itemName = '';
        $this->itemImage = null;

        session()->flash('success', "Item \"{$addedName}\" added successfully.");
        $this->resetPage();
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $item = Product::withoutGlobalScopes()->findOrFail($id);
        $this->deleteId   = $id;
        $this->deleteName = $item->name;
    }

    public function cancelDelete(): void
    {
        $this->deleteId   = null;
        $this->deleteName = '';
    }

    public function deleteItem(): void
    {
        if (!$this->deleteId) return;
        try {
            Product::withoutGlobalScopes()->findOrFail($this->deleteId)->forceDelete();
            session()->flash('success', 'Item deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->deleteId   = null;
        $this->deleteName = '';
    }

    // ── Edit ──────────────────────────────────────────────────────────────

    public function openEdit(int $id): void
    {
        $item = Product::withoutGlobalScopes()->with('category')->findOrFail($id);
        $this->editId         = $id;
        $this->editBusinessId = (string) $item->business_id;
        $this->editCategoryId = (string) $item->category_id;
        $this->editItemName   = $item->name;

        // Determine simple type from category type
        if ($item->category) {
            $isPurchase = in_array($item->category->type, ['raw_material', 'packing_material']);
            $this->editSimpleType = $isPurchase ? 'purchase' : 'product';
        } else {
            $this->editSimpleType = 'product';
        }

        $this->loadEditCategories();
        $this->resetValidation();
        $this->showEditModal  = true;
    }

    public function updatedEditBusinessId(): void
    {
        $this->editCategoryId = '';
        $this->editSimpleType = 'product';
        $this->loadEditCategories();
    }

    public function updatedEditSimpleType(): void
    {
        $this->editCategoryId = '';
        $this->loadEditCategories();
    }

    private function loadEditCategories(): void
    {
        if (!$this->editBusinessId) {
            $this->editCategories = [];
            return;
        }

        $query = Category::withoutGlobalScopes()
            ->where('business_id', $this->editBusinessId);

        // Filter by selected type
        if ($this->editSimpleType === 'purchase') {
            // For purchase: show ONLY raw_material, packing_material, and seed
            $query->whereIn('type', ['raw_material', 'packing_material', 'seed']);
        } else {
            // For product: exclude raw_material, packing_material, and seed
            $query->whereNotIn('type', ['raw_material', 'packing_material', 'seed']);
        }

        $this->editCategories = $query->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function closeEdit(): void
    {
        $this->showEditModal  = false;
        $this->editId         = null;
        $this->editBusinessId = '';
        $this->editSimpleType = 'product';
        $this->editCategoryId = '';
        $this->editItemName   = '';
        $this->editCategories = [];
        $this->editImage      = null;
        $this->resetValidation();
    }

    public function updateItem(): void
    {
        $this->validate([
            'editBusinessId' => 'required|integer|exists:businesses,id',
            'editCategoryId' => 'required|integer|exists:categories,id',
            'editItemName'   => 'required|string|max:255',
            'editImage'      => 'nullable|image|max:2048', // max 2MB
        ]);

        $item = Product::withoutGlobalScopes()->findOrFail($this->editId);
        $item->business_id  = (int) $this->editBusinessId;
        $item->category_id  = (int) $this->editCategoryId;
        $item->name         = trim($this->editItemName);
        $item->save();

        // Handle image upload
        if ($this->editImage) {
            $path = $this->editImage->store('products', 'public');
            $item->addImage($path, $this->editItemName);
        }

        session()->flash('success', "Item \"{$item->name}\" updated successfully.");
        $this->closeEdit();
        $this->resetPage();
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render()
    {
        $items = Product::withoutGlobalScopes()
            ->with(['category', 'business'])
            ->whereNull('deleted_at')
            // Show all item types, not just specific ones
            ->when($this->filterBiz, fn($q) => $q->where('business_id', $this->filterBiz))
            ->when($this->filterCat, fn($q) => $q->where('category_id', $this->filterCat))
            ->when($this->search,    fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('business_id')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.items.manager', compact('items'))
            ->title('Item Management');
    }
}
