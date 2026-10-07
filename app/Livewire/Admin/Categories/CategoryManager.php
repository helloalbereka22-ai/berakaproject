<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Business;
use App\Models\Category;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class CategoryManager extends Component
{
    use WithPagination;

    // ── Add Category Form ──────────────────────────────────────────────────
    public string $businessId    = '';
    public string $categoryName  = '';
    public string $simpleType    = 'product'; // "product" or "purchase"

    // ── Edit Category Modal ────────────────────────────────────────────────
    public bool   $showEditModal      = false;
    public ?int   $editCategoryId     = null;
    public string $editBusinessId     = '';
    public string $editCategoryName   = '';
    public string $editSimpleType     = 'product';

    // ── Delete Confirmation Modal ──────────────────────────────────────────
    public bool   $showDeleteModal   = false;
    public ?int   $deleteCategoryId  = null;
    public string $deleteCategoryName = '';

    // ── Search & Filters ──────────────────────────────────────────────────
    public string $filterBiz = '';
    public string $search    = '';

    // ── Business List Cache ───────────────────────────────────────────────
    public array $businesses = [];

    public function mount(): void
    {
        $this->businesses = Business::active()->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFilterBiz(): void { $this->resetPage(); }

    // ── Computed: Is oil/factory business ─────────────────────────────────

    public function isOilBusiness(): bool
    {
        if (!$this->businessId) return false;
        $biz = collect($this->businesses)->firstWhere('id', (int) $this->businessId);
        if (!$biz) return false;
        $name = strtolower($biz['name']);
        return str_contains($name, 'oil') || str_contains($name, 'factory') || str_contains($name, 'manufactur');
    }

    public function isEditOilBusiness(): bool
    {
        if (!$this->editBusinessId) return false;
        $biz = collect($this->businesses)->firstWhere('id', (int) $this->editBusinessId);
        if (!$biz) return false;
        $name = strtolower($biz['name']);
        return str_contains($name, 'oil') || str_contains($name, 'factory') || str_contains($name, 'manufactur');
    }

    // ── Add Category ───────────────────────────────────────────────────────
    public function addCategory(): void
    {
        $this->validate([
            'businessId'   => 'required|integer|exists:businesses,id',
            'categoryName' => 'required|string|max:255',
        ], [
            'businessId.required'   => 'Please select a business.',
            'categoryName.required' => 'Please enter a category name.',
        ]);

        $addedName = trim($this->categoryName);
        $bizId     = (int) $this->businessId;

        // Map simple type to Category type constants
        $isOil = $this->isOilBusiness();
        if ($isOil) {
            $type = $this->simpleType === 'purchase'
                ? Category::TYPE_RAW_MATERIAL
                : Category::TYPE_MANUFACTURED_PRODUCT;
        } else {
            $type = Category::TYPE_GENERAL;
        }

        try {
            app(ProductService::class)->createCategory([
                'business_id' => $bizId,
                'name'        => $addedName,
                'type'        => $type,
                'parent_id'   => null,
                'is_active'   => true,
                'sort_order'  => 0,
            ], $bizId);

            $this->categoryName = '';
            $this->simpleType   = 'product';
            $this->filterBiz    = (string) $bizId;
            $this->search       = '';
            $this->resetPage();

            session()->flash('success', "Category \"{$addedName}\" added successfully.");
        } catch (\Throwable $e) {
            session()->flash('error', "Error adding category: " . $e->getMessage());
        }
    }

    // ── Open / Close Edit Modal ────────────────────────────────────────────
    public function openEditModal(int $id): void
    {
        $cat = Category::withoutGlobalScopes()->findOrFail($id);
        $this->editCategoryId   = $cat->id;
        $this->editBusinessId   = (string) $cat->business_id;
        $this->editCategoryName = $cat->name;

        // Map stored type back to simple product/purchase
        $isPurchaseType = in_array($cat->type, [
            Category::TYPE_RAW_MATERIAL,
            Category::TYPE_PACKING_MATERIAL,
        ]);
        $this->editSimpleType = $isPurchaseType ? 'purchase' : 'product';

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal    = false;
        $this->editCategoryId   = null;
        $this->editBusinessId   = '';
        $this->editCategoryName = '';
        $this->editSimpleType   = 'product';
        $this->resetValidation();
    }

    // ── Update Category (Modal Submission) ────────────────────────────────
    public function updateCategory(): void
    {
        $this->validate([
            'editBusinessId'   => 'required|integer|exists:businesses,id',
            'editCategoryName' => 'required|string|max:255',
        ], [
            'editBusinessId.required'   => 'Please select a business.',
            'editCategoryName.required' => 'Please enter a category name.',
        ]);

        if (!$this->editCategoryId) return;

        $cat      = Category::withoutGlobalScopes()->findOrFail($this->editCategoryId);
        $newName  = trim($this->editCategoryName);
        $newBizId = (int) $this->editBusinessId;

        // Map simple type
        $isOil = $this->isEditOilBusiness();
        if ($isOil) {
            $type = $this->editSimpleType === 'purchase'
                ? Category::TYPE_RAW_MATERIAL
                : Category::TYPE_MANUFACTURED_PRODUCT;
        } else {
            $type = Category::TYPE_GENERAL;
        }

        try {
            $cat->update([
                'name'        => $newName,
                'type'        => $type,
                'parent_id'   => null,
                'business_id' => $newBizId,
            ]);

            $this->closeEditModal();
            session()->flash('success', "Category \"{$newName}\" updated successfully.");
            $this->resetPage();
        } catch (\Throwable $e) {
            session()->flash('error', "Error updating category: " . $e->getMessage());
        }
    }

    // ── Delete Confirmation ───────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $cat = Category::withoutGlobalScopes()->findOrFail($id);
        $this->deleteCategoryId   = $cat->id;
        $this->deleteCategoryName = $cat->name;
        $this->showDeleteModal    = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal    = false;
        $this->deleteCategoryId   = null;
        $this->deleteCategoryName = '';
    }

    // ── Delete Category ───────────────────────────────────────────────────
    public function deleteCategory(): void
    {
        if (!$this->deleteCategoryId) return;

        $cat = Category::withoutGlobalScopes()->findOrFail($this->deleteCategoryId);
        $name = $cat->name;
        try {
            app(ProductService::class)->deleteCategory($cat);
            session()->flash('success', "Category \"{$name}\" deleted successfully.");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function render()
    {
        $categories = Category::withoutGlobalScopes()
            ->with(['business'])
            ->when($this->filterBiz, fn($q) => $q->where('business_id', $this->filterBiz))
            ->when($this->search,    fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(50);

        return view('livewire.admin.categories.manager', [
            'categories' => $categories,
        ])->title('Category Management');
    }
}
