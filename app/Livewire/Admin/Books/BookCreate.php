<?php

namespace App\Livewire\Admin\Books;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuthorizationService;
use App\Services\ProductService;
use App\Services\InventoryService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BookCreate extends Component
{
    public string $businessId          = '';
    public string $categoryId          = '';
    public string $name                = '';
    public string $sku                 = '';
    public string $barcode             = ''; // ISBN
    public string $description         = '';
    public string $sellingPrice        = '0';
    public string $discountPrice       = '0';
    public string $costPrice           = '0';
    public string $initialStock        = '0';
    public string $status              = Product::STATUS_ACTIVE;
    
    // Book specific metadata
    public string $author              = '';
    public string $publisher           = '';
    public string $edition             = '';
    public string $language            = '';

    public array $businesses  = [];
    public array $categories  = [];

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

    private function updateCategories(): void
    {
        if (!$this->businessId) {
            $this->categories = [];
            return;
        }

        $this->categories = Category::withoutGlobalScopes()
            ->where('business_id', $this->businessId)
            ->active()
            ->orderBy('name')
            ->get(['id','name'])
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'businessId'          => 'required|integer|exists:businesses,id',
            'categoryId'          => 'required|integer|exists:categories,id',
            'name'                => 'required|string|max:255', // Title
            'sku'                 => 'nullable|string|max:100|unique:products,sku',
            'barcode'             => 'nullable|string|max:100', // ISBN
            'description'         => 'nullable|string',
            'sellingPrice'        => 'required|numeric|min:0',
            'discountPrice'       => 'nullable|numeric|min:0',
            'costPrice'           => 'required|numeric|min:0',
            'initialStock'        => 'required|numeric|min:0',
            'status'              => 'required|in:' . implode(',', array_keys(Product::STATUSES)),
            'author'              => 'nullable|string|max:255',
            'publisher'           => 'nullable|string|max:255',
            'edition'             => 'nullable|string|max:100',
            'language'            => 'nullable|string|max:100',
        ];
    }

    public function save(ProductService $productService, InventoryService $inventoryService): void
    {
        $this->authorize('product.create');
        $data = $this->validate();

        if ((float)$this->discountPrice > (float)$this->sellingPrice) {
            $this->addError('discountPrice', 'Discount price cannot be greater than selling price.');
            return;
        }

        app(AuthorizationService::class)->assertBusinessAccess(auth()->user(), (int) $this->businessId);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $productService, $inventoryService) {
            $metadata = array_filter([
                'author'    => $data['author'],
                'publisher' => $data['publisher'],
                'edition'   => $data['edition'],
                'language'  => $data['language'],
            ]);

            $product = $productService->create([
                'category_id'            => $data['categoryId'],
                'type'                   => Product::TYPE_BOOK,
                'name'                   => $data['name'],
                'sku'                    => $data['sku'],
                'barcode'                => $data['barcode'],
                'unit'                   => 'Piece', // Locked unit
                'description'            => $data['description'],
                'selling_price'          => $data['sellingPrice'],
                'discount_price'         => $this->discountPrice,
                'cost_price'             => $data['costPrice'],
                'status'                 => $data['status'],
                'metadata'               => $metadata,
            ], (int) $data['businessId']);

            if ((float) $data['initialStock'] > 0) {
                // Determine a location to add stock to (usually first location of business branch)
                $location = \App\Models\Location::whereHas('branch', function($q) use ($data) {
                    $q->where('business_id', $data['businessId']);
                })->first();

                if ($location) {
                    $inventoryService->receivePurchase(
                        $location->id,
                        $product->id,
                        (float) $data['initialStock'],
                        (float) $data['costPrice'],
                        'Initial stock for book registration'
                    );
                }
            }
        });

        session()->flash('success', 'Book registered successfully.');
        $this->redirect(route('admin.products.index'), navigate: true); // Or admin.books.index if exists
    }

    public function render()
    {
        return view('livewire.admin.books.create', [
            'statuses' => Product::STATUSES,
        ])->title('Register Book');
    }
}
