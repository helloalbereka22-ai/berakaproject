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
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class SaleableProductEdit extends Component
{
    use WithFileUploads;

    public int    $productId;
    public string $businessId  = '';
    public string $branchId    = '';
    public string $categoryId  = '';
    public string $productType = '';

    // Core fields
    public string $name         = '';
    public string $sku          = '';
    public string $barcode      = '';
    public string $description  = '';
    public string $status       = Product::STATUS_ACTIVE;
    public $image               = null;
    public ?string $currentImage = null;

    // Pricing
    public string $costPrice     = '0';
    public string $sellingPrice  = '0';
    public string $discountPrice = '';

    // Book metadata
    public string $author    = '';
    public string $publisher = '';
    public string $isbn      = '';
    public string $edition   = '';
    public string $language  = '';

    // Fashion metadata
    public string $brand    = '';
    public string $size     = '';
    public string $color    = '';
    public string $material = '';
    public string $gender   = '';

    // Discount preview
    public float $discountAmount     = 0;
    public float $discountPercentage = 0;

    public array $businesses  = [];
    public array $branches    = [];
    public array $categories  = [];

    public function mount(int $id): void
    {
        $this->authorize('product.update');
        $product = Product::withoutGlobalScopes()->findOrFail($id);

        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $product->business_id);

        $this->productId  = $product->id;
        $this->businessId = (string) $product->business_id;
        $this->categoryId = (string) ($product->category_id ?? '');
        $this->name         = $product->name;
        $this->sku          = $product->sku ?? '';
        $this->barcode      = $product->barcode ?? '';
        $this->description  = $product->description ?? '';
        $this->status       = $product->status;
        $this->costPrice    = (string) $product->cost_price;
        $this->sellingPrice = (string) $product->selling_price;
        $this->discountPrice= $product->discount_price ? (string) $product->discount_price : '';
        $this->currentImage = $product->image_path;
        $this->productType  = $product->type;

        // Load metadata
        $meta = $product->metadata ?? [];
        $this->author    = $meta['author']    ?? '';
        $this->publisher = $meta['publisher'] ?? '';
        $this->isbn      = $meta['isbn']      ?? '';
        $this->edition   = $meta['edition']   ?? '';
        $this->language  = $meta['language']  ?? '';
        $this->brand     = $meta['brand']     ?? '';
        $this->size      = $meta['size']      ?? '';
        $this->color     = $meta['color']     ?? '';
        $this->material  = $meta['material']  ?? '';
        $this->gender    = $meta['gender']    ?? '';

        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();

        $this->categories = Category::withoutGlobalScopes()
            ->where('business_id', $this->businessId)
            ->whereIn('type', Category::SALEABLE_CATEGORY_TYPES)
            ->active()->orderBy('name')
            ->get(['id', 'name', 'type'])->toArray();

        $this->recalcDiscount();
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

    protected function rules(): array
    {
        $rules = [
            'categoryId'    => 'required|integer|exists:categories,id',
            'name'          => 'required|string|max:255',
            'sku'           => "nullable|string|max:100|unique:products,sku,{$this->productId}",
            'description'   => 'nullable|string',
            'status'        => 'required|in:' . implode(',', array_keys(Product::STATUSES)),
            'costPrice'     => 'required|numeric|min:0',
            'sellingPrice'  => 'required|numeric|min:0',
            'discountPrice' => 'nullable|numeric|min:0',
            'image'         => 'nullable|image|max:2048',
        ];

        if ($this->productType === Product::TYPE_BOOK) {
            $rules['author']    = 'nullable|string|max:255';
            $rules['publisher'] = 'nullable|string|max:255';
            $rules['isbn']      = 'nullable|string|max:100';
            $rules['edition']   = 'nullable|string|max:100';
            $rules['language']  = 'nullable|string|max:100';
        }

        if ($this->productType === Product::TYPE_FASHION) {
            $rules['brand']    = 'nullable|string|max:255';
            $rules['size']     = 'nullable|string|max:100';
            $rules['color']    = 'nullable|string|max:100';
            $rules['material'] = 'nullable|string|max:255';
            $rules['gender']   = 'nullable|in:Male,Female,Unisex,Kids';
        }

        return $rules;
    }

    public function save(ProductService $productService): void
    {
        $this->authorize('product.update');
        $data = $this->validate();

        if ((float) $this->discountPrice > (float) $this->sellingPrice) {
            $this->addError('discountPrice', 'Discount price cannot exceed selling price.');
            return;
        }

        $product = Product::withoutGlobalScopes()->findOrFail($this->productId);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $product->business_id);

        $metadata = [];
        if ($product->type === Product::TYPE_BOOK) {
            $metadata = array_filter([
                'author'    => $this->author,
                'publisher' => $this->publisher,
                'isbn'      => $this->isbn,
                'edition'   => $this->edition,
                'language'  => $this->language,
            ]);
        } elseif ($product->type === Product::TYPE_FASHION) {
            $metadata = array_filter([
                'brand'    => $this->brand,
                'size'     => $this->size,
                'color'    => $this->color,
                'material' => $this->material,
                'gender'   => $this->gender,
            ]);
        }

        $imagePath = $this->currentImage;
        if ($this->image) {
            $imagePath = $this->image->store('products', 'public');
        }

        $productService->update($product, [
            'category_id'    => $data['categoryId'],
            'name'           => $data['name'],
            'sku'            => $data['sku'] ?? null,
            'barcode'        => $this->barcode ?: null,
            'description'    => $data['description'] ?? null,
            'selling_price'  => $data['sellingPrice'],
            'discount_price' => $this->discountPrice ?: null,
            'cost_price'     => $data['costPrice'],
            'status'         => $data['status'],
            'metadata'       => $metadata ?: null,
            'image_path'     => $imagePath,
        ]);

        session()->flash('success', 'Product updated successfully.');
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.saleable-products.edit', [
            'statuses'      => Product::STATUSES,
            'genderOptions' => ['Male', 'Female', 'Unisex', 'Kids'],
        ])->title('Edit Product');
    }
}
