<?php

namespace App\Livewire\Admin\Books;

use App\Models\Category;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BookEdit extends Component
{
    public Product $product;
    public string $categoryId          = '';
    public string $name                = '';
    public string $sku                 = '';
    public string $barcode             = ''; // ISBN
    public string $description         = '';
    public string $sellingPrice        = '0';
    public string $discountPrice       = '0';
    public string $costPrice           = '0';
    public string $status              = '';
    
    // Book specific metadata
    public string $author              = '';
    public string $publisher           = '';
    public string $edition             = '';
    public string $language            = '';

    public array $categories = [];

    public function mount(int $id): void
    {
        $this->authorize('product.update');
        $this->product = Product::withoutGlobalScopes()->findOrFail($id);
        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), $this->product->business_id);

        $this->updateCategories();

        $this->categoryId           = (string) $this->product->category_id;
        $this->name                 = $this->product->name;
        $this->sku                  = $this->product->sku ?? '';
        $this->barcode              = $this->product->barcode ?? '';
        $this->description          = $this->product->description ?? '';
        $this->sellingPrice         = (string) $this->product->selling_price;
        $this->discountPrice        = (string) $this->product->discount_price;
        $this->costPrice            = (string) $this->product->cost_price;
        $this->status               = $this->product->status;

        $metadata = is_array($this->product->metadata) ? $this->product->metadata : [];
        $this->author    = $metadata['author'] ?? '';
        $this->publisher = $metadata['publisher'] ?? '';
        $this->edition   = $metadata['edition'] ?? '';
        $this->language  = $metadata['language'] ?? '';
    }

    private function updateCategories(): void
    {
        if (!$this->product->business_id) {
            $this->categories = [];
            return;
        }

        $this->categories = Category::withoutGlobalScopes()
            ->where('business_id', $this->product->business_id)
            ->active()
            ->orderBy('name')
            ->get(['id','name'])
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'categoryId'          => 'required|integer|exists:categories,id',
            'name'                => 'required|string|max:255',
            'sku'                 => "nullable|string|max:100|unique:products,sku,{$this->product->id}",
            'barcode'             => 'nullable|string|max:100',
            'description'         => 'nullable|string',
            'sellingPrice'        => 'required|numeric|min:0',
            'discountPrice'       => 'nullable|numeric|min:0',
            'costPrice'           => 'required|numeric|min:0',
            'status'              => 'required|in:' . implode(',', array_keys(Product::STATUSES)),
            'author'              => 'nullable|string|max:255',
            'publisher'           => 'nullable|string|max:255',
            'edition'             => 'nullable|string|max:100',
            'language'            => 'nullable|string|max:100',
        ];
    }

    public function save(ProductService $productService): void
    {
        $this->authorize('product.update');
        $data = $this->validate();

        if ((float)$this->discountPrice > (float)$this->sellingPrice) {
            $this->addError('discountPrice', 'Discount price cannot be greater than selling price.');
            return;
        }

        $metadata = array_filter([
            'author'    => $data['author'],
            'publisher' => $data['publisher'],
            'edition'   => $data['edition'],
            'language'  => $data['language'],
        ]);

        $productService->update($this->product, [
            'category_id'            => $data['categoryId'],
            'name'                   => $data['name'],
            'sku'                    => $data['sku'],
            'barcode'                => $data['barcode'],
            'description'            => $data['description'],
            'selling_price'          => $data['sellingPrice'],
            'discount_price'         => $this->discountPrice,
            'cost_price'             => $data['costPrice'],
            'status'                 => $data['status'],
            'metadata'               => $metadata,
        ]);

        session()->flash('success', 'Book updated successfully.');
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.books.edit', [
            'statuses' => Product::STATUSES,
        ])->title('Edit Book');
    }
}
