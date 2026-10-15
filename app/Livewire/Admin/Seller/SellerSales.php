<?php

namespace App\Livewire\Admin\Seller;

use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\Category;
use App\Models\Location;
use App\Models\ProfitLedger;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class SellerSales extends Component
{
    // ── Branch context ────────────────────────────────────────────────────
    public ?int $branchId   = null;
    public ?int $businessId = null;

    // ── Product picker ────────────────────────────────────────────────────
    public string $sellType          = 'product'; // 'product' or 'purchase'
    public string $filterCategoryId  = '';
    public string $searchItem        = '';
    public string $selectedProductId = '';
    public string $sellQty           = '';

    // ── Cart: [['product_id','name','unit','unit_price','cost','qty','stock','location_id']] ──
    public array $cartItems = [];

    // ── Messages ─────────────────────────────────────────────────────────
    public string $errorMessage   = '';
    public string $successMessage = '';
    public bool $showConfirmModal = false;

    // ─────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $user      = auth()->user();
        $branchIds = $user->isSuperAdmin() ? [] : $user->assignedBranchIds();
        $this->branchId = $branchIds[0] ?? null;

        if ($this->branchId) {
            $branch = Branch::find($this->branchId);
            $this->businessId = $branch?->business_id;
        }
    }

    // ── Reset computed caches when filters change ─────────────────────────
    public function updatedFilterCategoryId(): void
    {
        $this->selectedProductId = '';
        $this->sellQty           = '';
        $this->searchItem        = '';
        unset($this->computedStockProducts);
    }

    public function updatedSellType(): void
    {
        $this->filterCategoryId  = '';
        $this->selectedProductId = '';
        $this->sellQty           = '';
        $this->searchItem        = '';
        unset($this->computedStockProducts);
    }

    // ── Categories for this branch's business, filtered by sell type ─────
    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->sellType === 'purchase') {
            // Purchased items: categories belonging to this business
            return Category::withoutGlobalScopes()
                ->where('business_id', $this->businessId)
                ->whereIn('type', [
                    Category::TYPE_BOOK,
                    Category::TYPE_STATIONERY,
                    Category::TYPE_FASHION,
                    Category::TYPE_GENERAL,
                ])
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // For 'product' mode: show ALL saleable categories that have stock in this branch
        // This matches SaleableProductIndex behavior
        $locationIds = Location::where('branch_id', $this->branchId)->pluck('id');

        $categoryIds = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->select('products.category_id')
            ->groupBy('products.category_id')
            ->havingRaw("SUM(CASE WHEN stock_movements.direction='in' THEN stock_movements.quantity ELSE -stock_movements.quantity END) > 0")
            ->pluck('products.category_id');

        return Category::withoutGlobalScopes()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── In-stock saleable products for this branch ────────────────────────
    #[Computed]
    public function stockProducts(): \Illuminate\Support\Collection
    {
        if (!$this->branchId) return collect();

        $locationIds = Location::where('branch_id', $this->branchId)->pluck('id');
        if ($locationIds->isEmpty()) return collect();

        $q = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->join('products',   'stock_movements.item_id',      '=', 'products.id')
            ->join('categories', 'products.category_id',         '=', 'categories.id')
            ->join('locations',  'stock_movements.location_id',  '=', 'locations.id')
            ->whereNull('products.deleted_at')
            ->where('products.status', Product::STATUS_ACTIVE);

        if ($this->sellType === 'purchase') {
            // Purchased items: book/stationery/fashion/general categories owned by this business
            $q->whereIn('categories.type', [
                    Category::TYPE_BOOK,
                    Category::TYPE_STATIONERY,
                    Category::TYPE_FASHION,
                    Category::TYPE_GENERAL,
                ]);
        } else {
            // For 'product' mode: show ALL saleable products (both purchased and transferred)
            // No additional filter - show everything that has stock
            // This matches SaleableProductIndex behavior
        }

        return $q->when($this->filterCategoryId, fn($q) =>
                $q->where('products.category_id', $this->filterCategoryId)
            )
            ->when($this->searchItem, fn($q) =>
                $q->where('products.name', 'like', '%' . $this->searchItem . '%')
            )
            ->select(
                'products.id',
                'products.name',
                'products.unit',
                'products.selling_price',
                'products.cost_price',
                'categories.name as category_name',
                // Best location = highest balance
                DB::raw('stock_movements.location_id'),
                DB::raw("SUM(CASE WHEN stock_movements.direction='in' 
                                  AND stock_movements.movement_type NOT IN ('" . implode("','", StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND) . "')
                              THEN stock_movements.quantity
                              WHEN stock_movements.direction='out'
                                  AND stock_movements.movement_type IN ('" . implode("','", StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND) . "')
                              THEN -stock_movements.quantity
                              ELSE 0
                          END) as qty_on_hand")
            )
            ->groupBy(
                'products.id', 'products.name', 'products.unit',
                'products.selling_price', 'products.cost_price',
                'categories.name', 'stock_movements.location_id'
            )
            ->having('qty_on_hand', '>', 0)
            ->orderBy('products.name')
            ->get()
            // Collapse per-product: pick highest-stock location
            ->groupBy('id')
            ->map(function ($rows) {
                $best = $rows->sortByDesc('qty_on_hand')->first();
                return (object) [
                    'id'            => $best->id,
                    'name'          => $best->name,
                    'unit'          => $best->unit,
                    'selling_price' => $best->selling_price,
                    'cost_price'    => $best->cost_price,
                    'category_name' => $best->category_name,
                    'location_id'   => $best->location_id,
                    'qty_on_hand'   => (float) $best->qty_on_hand,
                ];
            })
            ->values();
    }

    // ── Selected product details ─────────────────────────────────────────
    #[Computed]
    public function selectedProduct(): ?object
    {
        if (!$this->selectedProductId) return null;
        return $this->stockProducts->firstWhere('id', (int) $this->selectedProductId);
    }

    // ── Selected product details when clicking from dropdown ─────────────
    public function selectProduct(int $productId): void
    {
        $this->selectedProductId = (string) $productId;
        $this->searchItem = '';
        $this->sellQty = '';
    }

    // ── Add selected product + qty to cart ───────────────────────────────
    public function addItem(): void
    {
        $this->errorMessage   = '';
        $this->successMessage = '';

        $product = $this->selectedProduct;

        if (!$product) {
            $this->errorMessage = 'Please select a product.';
            return;
        }

        $qty = (float) $this->sellQty;

        if ($qty <= 0) {
            $this->errorMessage = 'Selling quantity must be greater than 0.';
            return;
        }

        if ($qty > $product->qty_on_hand) {
            $this->errorMessage = "Only {$product->qty_on_hand} {$product->unit} available in stock.";
            return;
        }

        // If already in cart — update qty
        foreach ($this->cartItems as $i => $item) {
            if ($item['product_id'] === (int) $this->selectedProductId) {
                $newQty = $item['qty'] + $qty;
                if ($newQty > $product->qty_on_hand) {
                    $this->errorMessage = "Total qty for \"{$product->name}\" would exceed available stock ({$product->qty_on_hand} {$product->unit}).";
                    return;
                }
                $this->cartItems[$i]['qty']         = $newQty;
                $this->cartItems[$i]['total_price']  = round($newQty * $item['unit_price'], 2);
                $this->selectedProductId = '';
                $this->sellQty           = '';
                unset($this->computedStockProducts, $this->computedSelectedProduct);
                return;
            }
        }

        $this->cartItems[] = [
            'product_id'  => (int) $this->selectedProductId,
            'name'        => $product->name,
            'unit'        => $product->unit,
            'unit_price'  => (float) $product->selling_price,
            'cost'        => (float) $product->cost_price,
            'qty'         => $qty,
            'stock'       => $product->qty_on_hand,
            'location_id' => $product->location_id,
            'total_price' => round($qty * (float) $product->selling_price, 2),
        ];

        $this->selectedProductId = '';
        $this->sellQty           = '';
        unset($this->computedStockProducts, $this->computedSelectedProduct);
    }

    public function removeItem(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
        unset($this->computedStockProducts);
    }

    // ── Grand total ───────────────────────────────────────────────────────
    #[Computed]
    public function grandTotal(): float
    {
        return collect($this->cartItems)->sum('total_price');
    }

    // ── Approve / complete the sale ───────────────────────────────────────
    public function confirmSale(): void
    {
        $this->showConfirmModal = true;
    }

    public function sell(): void
    {
        $this->errorMessage   = '';
        $this->successMessage = '';
        $this->showConfirmModal = false;

        if (empty($this->cartItems)) {
            $this->errorMessage = 'Add at least one product before selling.';
            return;
        }

        $inventory = app(InventoryService::class);

        // Re-validate stock for every item before touching the DB
        foreach ($this->cartItems as $item) {
            if (!$inventory->hasStock($item['location_id'], $item['product_id'], $item['qty'])) {
                $available = $inventory->getBalance($item['location_id'], 'product', $item['product_id']);
                $this->errorMessage = "Insufficient stock for \"{$item['name']}\". Available: {$available} {$item['unit']}.";
                return;
            }
        }

        DB::transaction(function () use ($inventory) {
            foreach ($this->cartItems as $item) {
                // 1. Deduct stock immediately
                $movement = $inventory->recordSale(
                    $item['location_id'],
                    $item['product_id'],
                    (float) $item['qty'],
                    (float) $item['cost'],
                    null,
                    'branch_sale'
                );

                // 2. Record the branch sale
                BranchSale::create([
                    'branch_id'   => $this->branchId,
                    'location_id' => $item['location_id'],
                    'product_id'  => $item['product_id'],
                    'quantity'    => (float) $item['qty'],
                    'unit_price'  => (float) $item['unit_price'],
                    'cost_price'  => (float) $item['cost'],
                    'total_amount'=> (float) $item['total_price'],
                    'sale_date'   => now()->toDateString(),
                    'recorded_by' => auth()->id(),
                ]);

                // 3. Record profit (only if profit_ledger.order_item_id is nullable — skip if not)
                $revenue = round((float) $item['unit_price'] * (float) $item['qty'], 4);
                $cost    = round((float) $item['cost']       * (float) $item['qty'], 4);

                try {
                    ProfitLedger::create([
                        'business_id'   => $this->businessId,
                        'branch_id'     => $this->branchId,
                        'order_item_id' => null,
                        'product_id'    => $item['product_id'],
                        'quantity'      => (float) $item['qty'],
                        'unit_price'    => (float) $item['unit_price'],
                        'selling_price' => (float) $item['unit_price'],
                        'cost_price'    => (float) $item['cost'],
                        'revenue'       => $revenue,
                        'total_cost'    => $cost,
                        'profit'        => $revenue - $cost,
                        'recorded_at'   => now(),
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // order_item_id is still NOT NULL in DB — migration pending.
                    // Sale and stock deduction already recorded; profit will be
                    // back-filled once the migration runs.
                    \Illuminate\Support\Facades\Log::warning(
                        'SellerSales: profit_ledger skipped — order_item_id NOT NULL constraint. Run migration 2026_08_22_000001.',
                        ['product_id' => $item['product_id'], 'error' => $e->getMessage()]
                    );
                }
            }
        });

        $count = count($this->cartItems);
        $total = number_format($this->grandTotal, 2);

        $this->cartItems      = [];
        $this->selectedProductId = '';
        $this->sellQty        = '';

        unset($this->computedStockProducts, $this->computedSelectedProduct, $this->computedGrandTotal);

        $this->successMessage = "Sale completed — {$count} item(s) sold for {$total} ETB. Stock updated.";
    }

    public function render()
    {
        return view('livewire.admin.seller.sales')
            ->title('Sell Products');
    }
}

