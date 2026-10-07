<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Business;
use App\Models\Category;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class CategoryIndex extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $businessId = '';
    public string $type       = '';

    // Inline create/edit form
    public bool   $showForm    = false;
    public ?int   $editId      = null;
    public string $formBizId   = '';
    public string $formName    = '';
    public string $formType    = Category::TYPE_GENERAL;
    public string $formParent  = '';
    public string $formDesc    = '';
    public string $formUnit    = '';
    public bool   $formActive  = true;
    public string $formIcon    = '';

    public bool  $showDeleteConfirm = false;
    public ?int  $deleteId          = null;

    public array $businesses     = [];
    public array $parentOptions  = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id','name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id','name'])->toArray();
    }

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedBusinessId(): void { $this->resetPage(); }
    public function updatedType(): void       { $this->resetPage(); }

    public function updatedFormBizId(): void
    {
        $this->updateParentOptions();
        $this->formParent = '';
    }

    public function updatedFormType(): void
    {
        $this->updateParentOptions();
        $this->formParent = '';
    }

    private function updateParentOptions(): void
    {
        $this->parentOptions = $this->formBizId
            ? Category::withoutGlobalScopes()
                ->where('business_id', $this->formBizId)
                ->where('type', $this->formType)
                ->whereNull('parent_id')
                ->active()->orderBy('name')->get(['id','name'])->toArray()
            : [];
    }

    public function openCreate(): void
    {
        $this->authorize('category.create');
        $this->editId    = null;
        $this->formBizId = $this->businessId;
        $this->formName  = '';
        $this->formType  = Category::TYPE_GENERAL;
        $this->formParent= '';
        $this->formDesc  = '';
        $this->formUnit  = '';
        $this->formActive= true;
        $this->formIcon  = '';
        $this->showForm  = true;
        $this->updateParentOptions();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('category.update');
        $cat = Category::withoutGlobalScopes()->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $cat->business_id);

        $this->editId    = $id;
        $this->formBizId = (string) $cat->business_id;
        $this->formName  = $cat->name;
        $this->formType  = $cat->type;
        $this->formParent= (string) ($cat->parent_id ?? '');
        $this->formDesc  = $cat->description ?? '';
        $this->formUnit  = $cat->default_unit ?? '';
        $this->formActive= (bool) $cat->is_active;
        $this->formIcon  = $cat->icon ?? '';
        $this->showForm  = true;
        $this->updateParentOptions();
    }

    public function save(): void
    {
        $this->validate([
            'formBizId' => 'required|integer|exists:businesses,id',
            'formName'  => 'required|string|max:255',
            'formType'  => 'required|in:' . implode(',', array_keys(Category::TYPES)),
        ]);

        $data = [
            'name'         => $this->formName,
            'type'         => $this->formType,
            'parent_id'    => $this->formParent ?: null,
            'description'  => $this->formDesc,
            'default_unit' => $this->formUnit ?: null,
            'is_active'    => $this->formActive,
            'icon'         => $this->formIcon,
        ];

        $svc = app(ProductService::class);
        if ($this->editId) {
            $this->authorize('category.update');
            $cat = Category::withoutGlobalScopes()->findOrFail($this->editId);
            $svc->updateCategory($cat, $data);
            session()->flash('success', 'Category updated.');
        } else {
            $this->authorize('category.create');
            $data['business_id'] = $this->formBizId;
            $svc->createCategory($data, (int) $this->formBizId);
            session()->flash('success', 'Category created.');
        }

        $this->showForm = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('category.delete');
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $this->authorize('category.delete');
        $cat = Category::withoutGlobalScopes()->findOrFail($this->deleteId);
        try {
            app(ProductService::class)->deleteCategory($cat);
            session()->flash('success', 'Category deleted.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    public function render()
    {
        $user  = auth()->user();
        $query = Category::withoutGlobalScopes()
            ->with(['parent', 'business'])
            ->withCount('products')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($this->search,     fn($q) => $q->where('name','like',"%{$this->search}%"))
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->type,       fn($q) => $q->where('type', $this->type))
            ->orderBy('business_id')->orderBy('sort_order')->orderBy('name');

        return view('livewire.admin.categories.index', [
            'categories'    => $query->paginate(30),
            'categoryTypes' => Category::TYPES,
            'categoryUnits' => Category::UNITS,
        ])->title('Categories');
    }
}
