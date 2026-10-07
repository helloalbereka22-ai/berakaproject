<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class RawMaterialDeduction extends Component
{
    // ── Picker ────────────────────────────────────────────────────────────
    public string $filterCategoryId  = '';
    public string $selectedProductId = '';
    public string $deductQty         = '';

    // ── Products list (plain array, reloaded reactively) ─────────────────
    public array  $stockProducts  = [];
    public array  $categoryList   = [];
    public float  $availableQty   = 0.0;
    public string $selectedUnit   = '';

    // ── Cart ──────────────────────────────────────────────────────────────
    public array $cartItems = [];

    // ── Context ───────────────────────────────────────────────────────────
    public ?int $businessId = null;
    public ?int $branchId   = null;

    // ── Messages ─────────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage   = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $this->businessId = $user->assignedBusinessId();
            $branchIds        = $user->assignedBranchIds();
            $this->branchId   = $branchIds[0] ?? null;
        }
        // Only show Purchase-type categories (raw_material, packing_material)
        $this->categoryList = Category::withoutGlobalScopes()
            ->whereIn('type', ['raw_material', 'packing_material'])
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
        $this->loadStockProducts();
    }

    // ── Category changed ──────────────────────────────────────────────────

    public function updatedFilterCategoryId(): void
    {
        $this->selectedProductId = '';
        $this->availableQty      = 0.0;
        $this->selectedUnit      = '';
        $this->deductQty         = '';
        $this->errorMessage      = '';
        $this->loadStockProducts();
    }

    // ── Item selected → populate available qty ────────────────────────────

    public function updatedSelectedProductId(): void
    {
        $this->deductQty    = '';
        $this->availableQty = 0.0;
        $this->selectedUnit = '';
        $this->errorMessage = '';

        if (!$this->selectedProductId) return;

        $product = collect($this->stockProducts)
            ->firstWhere('id', (int) $this->selectedProductId);

        if ($product) {
            $this->availableQty = (float) ($product['available_qty'] ?? 0);
            $this->selectedUnit = $product['unit'] ?? '';
        }
    }

    // ── Load products ─────────────────────────────────────────────────────

    private function loadStockProducts(): void
    {
        // Require category selection before loading items
        if ($this->filterCategoryId === '') {
            $this->stockProducts = [];
            return;
        }

        // Show all products in the selected category (category is already filtered to Purchase-type)
        $products = Product::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('category_id', (int) $this->filterCategoryId)
            ->when($this->businessId, fn($q) =>
                $q->where('business_id', $this->businessId)
            )
            ->select('id', 'name', 'unit')
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            $this->stockProducts = [];
            return;
        }

        // Compute available qty per product from stock_movements
        $locationIds = $this->locationIds();
        $stockMap    = [];

        if ($locationIds->isNotEmpty()) {
            StockMovement::whereIn('location_id', $locationIds)
                ->where('item_type', 'product')
                ->whereIn('item_id', $products->pluck('id'))
                ->select(
                    'item_id',
                    'location_id',
                    DB::raw("SUM(CASE WHEN direction='in'
                                  THEN quantity ELSE -quantity END) as qty")
                )
                ->groupBy('item_id', 'location_id')
                ->get()
                ->each(function ($row) use (&$stockMap) {
                    $id  = (int) $row->item_id;
                    $qty = (float) $row->qty;

                    if (!isset($stockMap[$id])) {
                        $stockMap[$id] = ['total_qty' => 0.0, 'location_id' => $row->location_id, 'best_qty' => 0.0];
                    }

                    $stockMap[$id]['total_qty'] += $qty;

                    if ($qty > $stockMap[$id]['best_qty']) {
                        $stockMap[$id]['best_qty']    = $qty;
                        $stockMap[$id]['location_id'] = $row->location_id;
                    }
                });
        }

        // Get category name for display
        $category = Category::withoutGlobalScopes()->find((int) $this->filterCategoryId);
        $categoryName = $category?->name ?? '';

        $this->stockProducts = $products->map(function ($p) use ($stockMap, $categoryName) {
            $stock = $stockMap[$p->id] ?? ['total_qty' => 0.0, 'location_id' => null];
            return [
                'id'            => (int)   $p->id,
                'name'          =>         $p->name,
                'unit'          =>         $p->unit,
                'category_name' =>         $categoryName,
                'location_id'   =>         $stock['location_id'],
                'available_qty' => max(0.0, (float) $stock['total_qty']),
            ];
        })->toArray();
    }

    // ── Location IDs scoped to business ──────────────────────────────────

    private function locationIds(): \Illuminate\Support\Collection
    {
        if ($this->businessId) {
            return Location::whereHas('branch', fn($q) =>
                $q->where('business_id', $this->businessId)
            )->pluck('id');
        }
        return Location::pluck('id');
    }

    // ── Add to deduction list ─────────────────────────────────────────────

    public function addItem(): void
    {
        $this->errorMessage   = '';
        $this->successMessage = '';

        if (!$this->selectedProductId) {
            $this->errorMessage = 'Please select a raw material.';
            return;
        }

        $product = collect($this->stockProducts)
            ->firstWhere('id', (int) $this->selectedProductId);

        if (!$product) {
            $this->errorMessage = 'Selected item not found.';
            return;
        }

        $qty = (float) $this->deductQty;

        if ($qty <= 0) {
            $this->errorMessage = 'Deduct quantity must be greater than 0.';
            return;
        }

        if ($product['available_qty'] <= 0) {
            $this->errorMessage = "No stock available for \"{$product['name']}\".";
            return;
        }

        if ($qty > $product['available_qty']) {
            $this->errorMessage = "Qty ({$qty}) exceeds available stock ({$product['available_qty']} {$product['unit']}).";
            return;
        }

        // Accumulate if already in list
        foreach ($this->cartItems as $i => $item) {
            if ($item['product_id'] === (int) $this->selectedProductId) {
                $newQty = $item['deduct_qty'] + $qty;
                if ($newQty > $product['available_qty']) {
                    $this->errorMessage = "Total ({$newQty}) exceeds available stock ({$product['available_qty']} {$product['unit']}).";
                    return;
                }
                $this->cartItems[$i]['deduct_qty'] = $newQty;
                $this->reset(['selectedProductId', 'deductQty', 'availableQty', 'selectedUnit']);
                return;
            }
        }

        $this->cartItems[] = [
            'product_id'    => (int)   $product['id'],
            'name'          =>         $product['name'],
            'unit'          =>         $product['unit'],
            'category_name' =>         $product['category_name'],
            'available_qty' => (float) $product['available_qty'],
            'location_id'   =>         $product['location_id'],
            'deduct_qty'    =>         $qty,
        ];

        $this->reset(['selectedProductId', 'deductQty', 'availableQty', 'selectedUnit']);
    }

    public function removeItem(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
    }

    // ── Confirm deduction ─────────────────────────────────────────────────

    public function confirmDeduction(): void
    {
        $this->errorMessage   = '';
        $this->successMessage = '';

        if (empty($this->cartItems)) {
            $this->errorMessage = 'Add at least one item before confirming.';
            return;
        }

        $inventory = app(InventoryService::class);

        // Validate each item has a location and enough stock
        foreach ($this->cartItems as $item) {
            if (!$item['location_id']) {
                $this->errorMessage = "No warehouse location found for \"{$item['name']}\". Ensure a purchase has been recorded first.";
                return;
            }

            $available = $inventory->getBalance($item['location_id'], 'product', $item['product_id']);
            if ((float) $item['deduct_qty'] > $available) {
                $this->errorMessage = "Insufficient stock for \"{$item['name']}\". Available: {$available} {$item['unit']}.";
                return;
            }
        }

        DB::transaction(function () use ($inventory) {
            foreach ($this->cartItems as $item) {
                $inventory->recordMovement(
                    $item['location_id'],
                    'product',
                    $item['product_id'],
                    StockMovement::TYPE_PRODUCTION_CONSUMPTION,
                    (float) $item['deduct_qty'],
                    0.0,
                    [
                        'reference_type' => 'manual_deduction',
                        'reference_id'   => null,
                        'notes'          => 'Manual deduction by ' . auth()->user()->name,
                        'created_by'     => auth()->id(),
                    ]
                );
            }
        });

        $count = count($this->cartItems);
        $this->cartItems = [];
        $this->reset(['selectedProductId', 'deductQty', 'availableQty', 'selectedUnit', 'filterCategoryId']);
        $this->loadStockProducts();

        $this->successMessage = "{$count} item(s) deducted from raw material stock successfully.";
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.raw-material-deduction')
            ->title('Deduct Raw Material');
    }
}
