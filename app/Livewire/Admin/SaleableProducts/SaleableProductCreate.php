<?php

namespace App\Livewire\Admin\SaleableProducts;

use App\Models\Branch;
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
class SaleableProductCreate extends Component
{
    use WithFileUploads;

    // ── Classification ────────────────────────────────────────────────────
    public string $businessId  = '';
    public string $branchId    = '';
    public string $categoryId  = '';
    public string $productType = ''; // derived from category

    // ── Core product fields ───────────────────────────────────────────────
    public string $name          = '';
    public string $sku           = '';
    public string $barcode       = '';
    public string $description   = '';
    public string $status        = Product::STATUS_ACTIVE;
    public $image                = null;

    // ── Pricing (admin-only) ──────────────────────────────────────────────
    public string $costPrice     = '0';
    public string $sellingPrice  = '0';
    public string $discountPrice = '';   // blank = no discount

    // ── Stock ─────────────────────────────────────────────────────────────
    public string $initialStock  = '0';
    public string $unit          = 'piece'; // locked to piece for saleable

    // ── Book-specific metadata ────────────────────────────────────────────
    public string $author    = '';
    public string $publisher = '';
    public string $isbn      = '';
    public string $edition   = '';
    public string $language  = '';

    // ── Fashion-specific metadata ─────────────────────────────────────────
    public string $brand    = '';
    public string $size     = '';
    public string $color    = '';
    public string $material = '';
    public string $gender   = ''; // Male, Female, Unisex

    // ── Lists ─────────────────────────────────────────────────────────────
    public array $businesses  = [];
    public array $branches    = [];
    public array $categories  = [];
    public array $locations   = [];

    // ── Computed: live discount preview ──────────────────────────────────
    public float $discountAmount     = 0;
    public float $discountPercentage = 0;

    public function mount(): void
    {
        $this->authorize('product.create');
        $user = auth()->user();

        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    // ── Watchers ──────────────────────────────────────────────────────────

    public function updatedBusinessId(): void
    {
        $this->categoryId  = '';
        $this->productType = '';
        $this->branchId    = '';
        $this->locations   = [];

        if ($this->businessId) {
            $this->branches = Branch::where('business_id', $this->businessId)
                ->active()->orderBy('name')->get(['id', 'name'])->toArray();

            $this->categories = Category::withoutGlobalScopes()
                ->where('business_id', $this->businessId)
                ->whereIn('type', Category::SALEABLE_CATEGORY_TYPES)
                ->active()->orderBy('name')
                ->get(['id', 'name', 'type'])->toArray();
        } else {
            $this->branches   = [];
            $this->categories = [];
        }
    }

    public function updatedBranchId(): void
    {
        $this->locations = [];
        if ($this->branchId) {
            $this->locations = Location::where('branch_id', $this->branchId)
                ->active()->orderBy('name')->get(['id', 'name'])->toArray();
        }
    }

    public function updatedCategoryId(): void
    {
        // Derive the product type from the selected category
        if ($this->categoryId) {
            $cat = collect($this->categories)->firstWhere('id', (int) $this->categoryId);
            $this->productType = $cat['type'] ?? '';
        } else {
            $this->productType = '';
        }
    }

    public function updatedSellingPrice(): void  { $this->recalcDiscount(); }
    public function updatedDiscountPrice(): void { $this->recalcDiscount(); }

    private function recalcDiscount(): void
    {
        $selling  = (float) $this->sellingPrice;
        $discount = (float) $this->discountPrice;

        if ($discount > 0 && $discount < $selling) {
            $this->discountAmount     = round($selling - $discount, 2);
            $this->discountPercentage = round(($this->discountAmount / $selling) * 100, 2);
        } else {
            $this->discountAmount     = 0;
            $this->discountPercentage = 0;
        }
    }

    // ── Validation ────────────────────────────────────────────────────────

    protected function rules(): array
    {
        $rules = [
            'businessId'    => 'required|integer|exists:businesses,id',
            'branchId'      => 'nullable|integer|exists:branches,id',
            'categoryId'    => 'required|integer|exists:categories,id',
            'name'          => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100|unique:products,sku',
            'description'   => 'nullable|string',
            'status'        => 'required|in:' . implode(',', array_keys(Product::STATUSES)),
            'costPrice'     => 'required|numeric|min:0',
            'sellingPrice'  => 'required|numeric|min:0',
            'discountPrice' => 'nullable|numeric|min:0',
            'initialStock'  => 'required|numeric|min:0',
            'image'         => 'nullable|image|max:2048',
        ];

        // Book-specific rules
        if ($this->productType === Product::TYPE_BOOK) {
            $rules['barcode']   = 'nullable|string|max:100'; // ISBN
            $rules['author']    = 'nullable|string|max:255';
            $rules['publisher'] = 'nullable|string|max:255';
            $rules['isbn']      = 'nullable|string|max:100';
            $rules['edition']   = 'nullable|string|max:100';
            $rules['language']  = 'nullable|string|max:100';
        }

        // Fashion-specific rules
        if ($this->productType === Product::TYPE_FASHION) {
            $rules['brand']    = 'nullable|string|max:255';
            $rules['size']     = 'nullable|string|max:100';
            $rules['color']    = 'nullable|string|max:100';
            $rules['material'] = 'nullable|string|max:255';
            $rules['gender']   = 'nullable|in:Male,Female,Unisex,Kids';
        }

        return $rules;
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function save(ProductService $productService, InventoryService $inventoryService): void
    {
        $this->authorize('product.create');
        $data = $this->validate();

        // Discount must never exceed selling price
        if ((float) $this->discountPrice > (float) $this->sellingPrice) {
            $this->addError('discountPrice', 'Discount price cannot exceed selling price.');
            return;
        }

        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->businessId);

        // Determine the concrete product type from the category
        $type = match ($this->productType) {
            Category::TYPE_BOOK      => Product::TYPE_BOOK,
            Category::TYPE_STATIONERY => Product::TYPE_STATIONERY,
            Category::TYPE_FASHION   => Product::TYPE_FASHION,
            default                  => Product::TYPE_BOOK,
        };

        DB::transaction(function () use ($data, $type, $productService, $inventoryService) {
            // Build metadata based on type
            $metadata = [];
            if ($type === Product::TYPE_BOOK) {
                $metadata = array_filter([
                    'author'    => $this->author,
                    'publisher' => $this->publisher,
                    'isbn'      => $this->isbn ?: $this->barcode,
                    'edition'   => $this->edition,
                    'language'  => $this->language,
                ]);
            } elseif ($type === Product::TYPE_FASHION) {
                $metadata = array_filter([
                    'brand'    => $this->brand,
                    'size'     => $this->size,
                    'color'    => $this->color,
                    'material' => $this->material,
                    'gender'   => $this->gender,
                ]);
            }

            // Handle image upload
            $imagePath = null;
            if ($this->image) {
                $imagePath = $this->image->store('products', 'public');
            }

            $product = $productService->create([
                'category_id'   => $data['categoryId'],
                'type'          => $type,
                'name'          => $data['name'],
                'sku'           => $data['sku'] ?? null,
                'barcode'       => $this->barcode ?: null,
                'unit'          => 'piece',
                'description'   => $data['description'] ?? null,
                'selling_price' => $data['sellingPrice'],
                'discount_price'=> $this->discountPrice ?: null,
                'cost_price'    => $data['costPrice'],
                'status'        => $data['status'],
                'metadata'      => $metadata ?: null,
                'image_path'    => $imagePath,
            ], (int) $data['businessId']);

            // Record initial inventory IN if stock > 0
            if ((float) $data['initialStock'] > 0) {
                // Find the first available location for the selected branch or business
                $locationQuery = Location::query();
                if ($this->branchId) {
                    $locationQuery->where('branch_id', $this->branchId);
                } else {
                    $locationQuery->whereHas('branch', fn($q) => $q->where('business_id', $data['businessId']));
                }
                $location = $locationQuery->active()->first();

                if ($location) {
                    $inventoryService->receivePurchase(
                        $location->id,
                        $product->id,
                        (float) $data['initialStock'],
                        (float) $data['costPrice'],
                        null,
                        'opening_stock'
                    );
                }
            }
        });

        $typeLabel = match ($type ?? '') {
            'book'       => 'Book',
            'stationery' => 'Stationery product',
            'fashion'    => 'Fashion product',
            default      => 'Product',
        };

        session()->flash('success', "{$typeLabel} registered successfully.");
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.saleable-products.create', [
            'statuses'      => Product::STATUSES,
            'productTypes'  => Product::TYPES,
            'genderOptions' => ['Male', 'Female', 'Unisex', 'Kids'],
        ])->title('Add Saleable Product');
    }
}
