<?php

namespace App\Livewire\Admin\Products;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ProductCreate extends Component
{
    public string $businessId          = '';
    public string $categoryId          = '';
    public string $type                = Product::TYPE_GENERAL;
    public string $name                = '';
    public string $shortName           = '';
    public string $sku                 = '';
    public string $barcode             = '';
    public string $unit                = '';
    public string $packageSize         = '';
    public string $description         = '';
    public string $sellingPrice        = '0';
    public string $discountPrice       = '0';
    public string $costPrice           = '0';
    public string $reorderPoint        = '0';
    public string $minStock            = '0';
    public string $maxStock            = '0';
    public string $status              = Product::STATUS_ACTIVE;
    public bool   $isFeatured          = false;
    public bool   $isMarketplaceVisible = false;

    public array $businesses  = [];
    public array $categories  = [];
    
    // Variants for Manufactured Products
    public array $variants = [];

    public function mount(): void
    {
        $this->authorize('product.create');
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id','name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id','name'])->toArray();
    }

    public function updatedBusinessId(): void
    {
        $this->updateCategories();
        $this->categoryId = '';
    }

    public function updatedType(): void
    {
        $this->updateCategories();
        $this->categoryId = '';
        
        // Initialize one variant if type is finished product
        if ($this->type === Product::TYPE_FINISHED_PRODUCT && empty($this->variants)) {
            $this->addVariant();
        }
        
        // Auto-enable marketplace visibility for manufactured products
        if (in_array($this->type, [Product::TYPE_FINISHED_PRODUCT, Product::TYPE_BOTTLE_PRODUCT])) {
            $this->isMarketplaceVisible = true;
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'size' => '',
            'unit' => '',
            'selling_price' => '0',
            'discount_price' => '0',
            'sku' => '',
        ];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    private function updateCategories(): void
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
        } elseif ($this->type === Product::TYPE_PACKING_MATERIAL) {
            $query->forPackagingMaterials();
        } else {
            $query->forSaleableProducts();
        }

        $this->categories = $query->orderBy('name')->get(['id','name'])->toArray();
    }

    protected function rules(): array
    {
        return [
            'businessId'          => 'required|integer|exists:businesses,id',
            'categoryId'          => 'required|integer|exists:categories,id',
            'type'                => 'required|in:' . implode(',', array_keys(Product::TYPES)),
            'name'                => 'required|string|max:255',
            'shortName'           => 'nullable|string|max:100',
            'sku'                 => 'nullable|string|max:100|unique:products,sku',
            'barcode'             => 'nullable|string|max:100',
            'unit'                => 'nullable|string|max:50',
            'packageSize'         => 'nullable|string|max:50',
            'description'         => 'nullable|string',
            'sellingPrice'        => 'required|numeric|min:0',
            'costPrice'           => 'required|numeric|min:0',
            'reorderPoint'        => 'nullable|numeric|min:0',
            'minStock'            => 'nullable|numeric|min:0',
            'maxStock'            => 'nullable|numeric|min:0',
            'status'              => 'required|in:' . implode(',', array_keys(Product::STATUSES)),
            'isFeatured'          => 'boolean',
            'isMarketplaceVisible'=> 'boolean',
            'variants'            => 'nullable|array',
            'variants.*.size'     => 'required_with:variants|string|max:100',
            'variants.*.unit'     => 'required_with:variants|string|max:50',
            'variants.*.selling_price' => 'required_with:variants|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.sku'      => 'nullable|string|max:100',
        ];
    }

    public function save(ProductService $productService): void
    {
        $this->authorize('product.create');
        $data = $this->validate();
        
        // Validate discounts on variants if any
        if (!empty($this->variants) && $this->type === Product::TYPE_FINISHED_PRODUCT) {
            foreach ($this->variants as $variant) {
                if ((float)$variant['discount_price'] > (float)$variant['selling_price']) {
                    $this->addError('variants', 'Discount price cannot be greater than selling price.');
                    return;
                }
            }
        } elseif ($this->type !== Product::TYPE_FINISHED_PRODUCT && (float)$this->discountPrice > (float)$this->sellingPrice) {
            $this->addError('discountPrice', 'Discount price cannot be greater than selling price.');
            return;
        }

        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->businessId);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $productService) {
            // Create main product
            $parentProduct = $productService->create([
                'category_id'            => $data['categoryId'],
                'type'                   => $data['type'],
                'name'                   => $data['name'],
                'short_name'             => $data['shortName'],
                'sku'                    => $data['sku'],
                'barcode'                => $data['barcode'],
                'unit'                   => $data['unit'],
                'package_size'           => $data['packageSize'],
                'description'            => $data['description'],
                'selling_price'          => $data['sellingPrice'],
                'discount_price'         => $this->discountPrice,
                'cost_price'             => $data['costPrice'],
                'reorder_point'          => $data['reorderPoint'],
                'min_stock'              => $data['minStock'],
                'max_stock'              => $data['maxStock'],
                'status'                 => $data['status'],
                'is_featured'            => $data['isFeatured'],
                'is_marketplace_visible' => $data['isMarketplaceVisible'],
            ], (int) $data['businessId']);

            // Create variants if finished product
            if ($this->type === Product::TYPE_FINISHED_PRODUCT && !empty($this->variants)) {
                foreach ($this->variants as $variant) {
                    $productService->create([
                        'parent_id'              => $parentProduct->id,
                        'business_id'            => $parentProduct->business_id,
                        'category_id'            => $parentProduct->category_id,
                        'type'                   => Product::TYPE_FINISHED_PRODUCT,
                        'name'                   => $parentProduct->name . ' - ' . $variant['size'],
                        'package_size'           => $variant['size'],
                        'unit'                   => $variant['unit'],
                        'selling_price'          => $variant['selling_price'],
                        'discount_price'         => $variant['discount_price'] ?: 0,
                        'sku'                    => $variant['sku'],
                        'status'                 => Product::STATUS_ACTIVE,
                    ], (int) $data['businessId']);
                }
            }
        });

        session()->flash('success', 'Product created successfully.');
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.products.create', [
            'productTypes' => Product::TYPES,
            'statuses'     => Product::STATUSES,
        ])->title('Create Product');
    }
}
