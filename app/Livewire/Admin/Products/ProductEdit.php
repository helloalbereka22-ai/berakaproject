<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ProductEdit extends Component
{
    public Product $product;
    public string  $categoryId          = '';
    public string  $type                = '';
    public string  $name                = '';
    public string  $shortName           = '';
    public string  $sku                 = '';
    public string  $barcode             = '';
    public string  $unit                = '';
    public string  $packageSize         = '';
    public string  $description         = '';
    public string  $sellingPrice        = '0';
    public string  $discountPrice       = '0';
    public string  $costPrice           = '0';
    public string  $reorderPoint        = '0';
    public string  $minStock            = '0';
    public string  $maxStock            = '0';
    public string  $status              = '';
    public bool    $isFeatured          = false;
    public bool    $isMarketplaceVisible = false;

    public array $categories = [];
    public array $variants   = [];

    public function mount(int $id): void
    {
        $this->authorize('product.update');
        $this->product = Product::withoutGlobalScopes()->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $this->product->business_id);

        $this->updateCategories();

        $this->categoryId           = (string) $this->product->category_id;
        $this->type                 = $this->product->type;
        $this->name                 = $this->product->name;
        $this->shortName            = $this->product->short_name ?? '';
        $this->sku                  = $this->product->sku ?? '';
        $this->barcode              = $this->product->barcode ?? '';
        $this->unit                 = $this->product->unit ?? '';
        $this->packageSize          = $this->product->package_size ?? '';
        $this->description          = $this->product->description ?? '';
        $this->sellingPrice         = (string) $this->product->selling_price;
        $this->discountPrice        = (string) $this->product->discount_price;
        $this->costPrice            = (string) $this->product->cost_price;
        $this->reorderPoint         = (string) $this->product->reorder_point;
        $this->minStock             = (string) $this->product->min_stock;
        $this->maxStock             = (string) $this->product->max_stock;
        $this->status               = $this->product->status;
        $this->isFeatured           = (bool) $this->product->is_featured;
        $this->isMarketplaceVisible = (bool) $this->product->is_marketplace_visible;

        if ($this->type === Product::TYPE_FINISHED_PRODUCT) {
            foreach ($this->product->variants as $v) {
                $this->variants[] = [
                    'id' => $v->id,
                    'size' => $v->package_size ?? '',
                    'unit' => $v->unit ?? '',
                    'selling_price' => (string) $v->selling_price,
                    'discount_price' => (string) ($v->discount_price ?: 0),
                    'sku' => $v->sku ?? '',
                ];
            }
            if (empty($this->variants)) {
                $this->addVariant();
            }
        }
    }

    public function updatedType(): void
    {
        $this->updateCategories();
        $this->categoryId = '';

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
            'id' => null,
            'size' => '',
            'unit' => '',
            'selling_price' => '0',
            'discount_price' => '0',
            'sku' => '',
        ];
    }

    public function removeVariant(int $index): void
    {
        // If it's an existing variant, we might need to track it for deletion. For now, just unset it.
        // During save, we will sync variants based on IDs.
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    private function updateCategories(): void
    {
        if (!$this->product->business_id) {
            $this->categories = [];
            return;
        }

        $query = Category::withoutGlobalScopes()
            ->where('business_id', $this->product->business_id)
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
            'categoryId'          => 'required|integer|exists:categories,id',
            'type'                => 'required|in:' . implode(',', array_keys(Product::TYPES)),
            'name'                => 'required|string|max:255',
            'shortName'           => 'nullable|string|max:100',
            'sku'                 => "nullable|string|max:100|unique:products,sku,{$this->product->id}",
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
            'variants.*.id'       => 'nullable|integer',
            'variants.*.size'     => 'required_with:variants|string|max:100',
            'variants.*.unit'     => 'required_with:variants|string|max:50',
            'variants.*.selling_price' => 'required_with:variants|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.sku'      => 'nullable|string|max:100',
        ];
    }

    public function save(): void
    {
        $this->authorize('product.update');
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            app(ProductService::class)->update($this->product, [
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
            ]);

            if ($this->type === Product::TYPE_FINISHED_PRODUCT) {
                $keptVariantIds = collect($this->variants)->pluck('id')->filter()->toArray();
                
                // Delete removed variants
                $this->product->variants()->whereNotIn('id', $keptVariantIds)->delete();

                foreach ($this->variants as $variant) {
                    if (!empty($variant['id'])) {
                        $existing = Product::withoutGlobalScopes()->find($variant['id']);
                        if ($existing) {
                            $existing->update([
                                'name'                   => $this->product->name . ' - ' . $variant['size'],
                                'package_size'           => $variant['size'],
                                'unit'                   => $variant['unit'],
                                'selling_price'          => $variant['selling_price'],
                                'discount_price'         => $variant['discount_price'] ?: 0,
                                'sku'                    => $variant['sku'],
                            ]);
                        }
                    } else {
                        Product::create([
                            'parent_id'              => $this->product->id,
                            'business_id'            => $this->product->business_id,
                            'category_id'            => $this->product->category_id,
                            'type'                   => Product::TYPE_FINISHED_PRODUCT,
                            'name'                   => $this->product->name . ' - ' . $variant['size'],
                            'package_size'           => $variant['size'],
                            'unit'                   => $variant['unit'],
                            'selling_price'          => $variant['selling_price'],
                            'discount_price'         => $variant['discount_price'] ?: 0,
                            'sku'                    => $variant['sku'],
                            'status'                 => Product::STATUS_ACTIVE,
                        ]);
                    }
                }
            }
        });

        session()->flash('success', 'Product updated.');
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.products.edit', [
            'productTypes' => Product::TYPES,
            'statuses'     => Product::STATUSES,
        ])->title('Edit Product');
    }
}
