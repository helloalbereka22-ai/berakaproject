<?php

namespace App\Livewire\Admin\Materials;

use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class MaterialCreate extends Component
{
    use WithFileUploads;

    // ── Classification ──────────────────────────────────────────────────
    public string $businessId  = '';
    public string $categoryId  = '';
    public string $type        = Product::TYPE_RAW_MATERIAL;

    // ── Product Identity ────────────────────────────────────────────────
    public string $name        = '';
    public string $sku         = '';
    public string $barcode     = '';
    public string $packageSize = '';
    public string $unit        = '';
    public string $description = '';
    public $image;

    // ── Pricing ─────────────────────────────────────────────────────────
    public string $costPrice   = '0';

    // ── Stock Thresholds ─────────────────────────────────────────────────
    public string $reorderPoint = '0';
    public string $minStock     = '0';
    public string $maxStock     = '0';

    // ── Status ───────────────────────────────────────────────────────────
    public string $status = Product::STATUS_ACTIVE;

    // ── Initial Stock ────────────────────────────────────────────────────
    public string $initialQuantity = '0';
    public string $locationId      = '';

    // ── Dropdown data ────────────────────────────────────────────────────
    public array $businesses       = [];
    public array $categories       = [];
    public array $locations        = [];
    public array $selectedCategory = []; // ['id', 'name', 'icon', 'default_unit']

    // ── Type config: fields shown per type ───────────────────────────────
    // (used in view via computed properties)

    public function mount(): void
    {
        $this->authorize('product.create');
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    // ── Lifecycle hooks ──────────────────────────────────────────────────

    public function updatedBusinessId(): void
    {
        $this->categoryId       = '';
        $this->selectedCategory = [];
        $this->unit             = '';
        $this->locations        = [];
        $this->locationId       = '';
        $this->loadCategories();
        $this->loadLocations();
    }

    public function updatedType(): void
    {
        $this->categoryId       = '';
        $this->selectedCategory = [];
        $this->unit             = '';
        // Clear packing-only fields when switching to raw
        if ($this->isRawMaterial()) {
            $this->barcode     = '';
            $this->packageSize = '';
            $this->image       = null;
        }
        $this->loadCategories();
    }

    public function updatedCategoryId(): void
    {
        if (!$this->categoryId) {
            $this->selectedCategory = [];
            $this->unit             = '';
            return;
        }

        $cat = Category::withoutGlobalScopes()
            ->where('id', $this->categoryId)
            ->first(['id', 'name', 'icon', 'default_unit', 'type']);

        if ($cat) {
            $this->selectedCategory = [
                'id'           => $cat->id,
                'name'         => $cat->name,
                'icon'         => $cat->icon ?? '',
                'default_unit' => $cat->default_unit ?? '',
                'type'         => $cat->type,
            ];
            // Pre-fill unit from category default
            if ($cat->default_unit && empty(trim($this->unit))) {
                $this->unit = $cat->default_unit;
            }
        }
    }

    // ── Type helpers (used in blade) ─────────────────────────────────────

    public function isRawMaterial(): bool
    {
        return $this->type === Product::TYPE_RAW_MATERIAL;
    }

    public function isPackingMaterial(): bool
    {
        return $this->type === Product::TYPE_PACKING_MATERIAL;
    }

    // ── Computed: Total value ─────────────────────────────────────────────

    public function getCalculatedTotalValueProperty(): float
    {
        $qty   = (float) ($this->initialQuantity ?: 0);
        $price = (float) ($this->costPrice ?: 0);
        return $qty * $price;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function loadCategories(): void
    {
        if (!$this->businessId) {
            $this->categories = [];
            return;
        }

        $query = Category::withoutGlobalScopes()
            ->where('business_id', $this->businessId)
            ->active();

        if ($this->type === Product::TYPE_RAW_MATERIAL) {
            $query->forRawMaterials();
        } else {
            $query->forPackagingMaterials();
        }

        $this->categories = $query->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'icon', 'default_unit'])
            ->toArray();
    }

    private function loadLocations(): void
    {
        if (!$this->businessId) {
            $this->locations = [];
            return;
        }

        $this->locations = Location::whereHas('branch', function ($q) {
            $q->where('business_id', $this->businessId);
        })->active()->orderBy('name')->get(['id', 'name'])->toArray();
    }

    // ── Validation ────────────────────────────────────────────────────────

    protected function rules(): array
    {
        $packingOnly = $this->isPackingMaterial();

        return [
            'businessId'      => 'required|integer|exists:businesses,id',
            'categoryId'      => 'required|integer|exists:categories,id',
            'type'            => 'required|in:' . Product::TYPE_RAW_MATERIAL . ',' . Product::TYPE_PACKING_MATERIAL,
            'name'            => 'required|string|max:255',
            'sku'             => 'nullable|string|max:100',
            'barcode'         => ($packingOnly ? 'nullable' : 'nullable') . '|string|max:100',
            'packageSize'     => 'nullable|string|max:100',
            'unit'            => 'required|string|max:50',
            'costPrice'       => 'required|numeric|min:0',
            'reorderPoint'    => 'nullable|numeric|min:0',
            'minStock'        => 'nullable|numeric|min:0',
            'maxStock'        => 'nullable|numeric|min:0',
            'description'     => 'nullable|string',
            'status'          => 'required|in:' . implode(',', array_keys(Product::STATUSES)),
            'image'           => $packingOnly ? 'nullable|image|max:2048' : 'nullable',
            'initialQuantity' => 'nullable|numeric|min:0',
            'locationId'      => 'nullable|required_if:initialQuantity,>0|exists:locations,id',
        ];
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function save(InventoryService $inventoryService, ProductService $productService)
    {
        $this->authorize('product.create');
        $data = $this->validate();

        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->businessId);

        DB::transaction(function () use ($data, $inventoryService, $productService) {
            $isPacking = $this->isPackingMaterial();

            $product = $productService->create([
                'category_id'            => $data['categoryId'],
                'type'                   => $data['type'],
                'name'                   => $data['name'],
                'sku'                    => $data['sku'] ?: null,
                'barcode'                => $isPacking ? ($data['barcode'] ?: null) : null,
                'package_size'           => $isPacking ? ($data['packageSize'] ?: null) : null,
                'unit'                   => $data['unit'],
                'description'            => $data['description'],
                'cost_price'             => $data['costPrice'],
                'reorder_point'          => $data['reorderPoint'] ?? 0,
                'min_stock'              => $data['minStock'] ?? 0,
                'max_stock'              => $data['maxStock'] ?? 0,
                'status'                 => $data['status'],
                'image_path'             => ($isPacking && $this->image) ? $this->image->store('products', 'public') : null,
                'selling_price'          => 0,
                'is_featured'            => false,
                'is_marketplace_visible' => false,
            ], (int) $data['businessId']);

            $initialQty = (float) ($data['initialQuantity'] ?? 0);
            if ($initialQty > 0 && !empty($data['locationId'])) {
                $inventoryService->receivePurchase(
                    (int) $data['locationId'],
                    $product->id,
                    $initialQty,
                    (float) $data['costPrice']
                );
            }
        });

        session()->flash('success', 'Material created successfully.');
        $this->redirect(route('admin.materials.index'), navigate: true);
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.admin.materials.create', [
            'materialTypes' => [
                Product::TYPE_RAW_MATERIAL     => 'Raw Material',
                Product::TYPE_PACKING_MATERIAL => 'Packaging Material',
            ],
            'unitOptions'   => Category::UNITS,
            'statuses'      => Product::STATUSES,
            'typeConfig'    => [
                'isRaw'     => $this->isRawMaterial(),
                'isPacking' => $this->isPackingMaterial(),
                'label'     => $this->isRawMaterial()
                    ? '🌾 Bulk agricultural or liquid input'
                    : '📦 Physical container or wrapping material',
            ],
        ])->title('Add Material');
    }
}
