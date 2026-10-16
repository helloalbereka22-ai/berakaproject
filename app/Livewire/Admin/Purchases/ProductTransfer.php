<?php

namespace App\Livewire\Admin\Purchases;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ProductTransfer extends Component
{
    // Source & Destination — branch IDs
    public ?int   $srcBranchId  = null;
    public ?int   $destBranchId = null;

    // Product selection
    public ?int   $trCategoryId = null;
    public ?int   $trProductId  = null;
    public string $trQuantity   = '';
    public string $trNotes      = '';

    // Session history — kept for immediate feedback after transfer
    public array $trHistory = [];

    // ── Search filters for transfer history ──────────────────────────────
    public string $searchBranch   = '';
    public string $searchCategory = '';
    public string $searchItem     = '';

    // ── Dropdown options for filters ──────────────────────────────────────
    #[Computed]
    public function filterBranches(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('branches')->orderBy('name')->pluck('name','id');
    }

    #[Computed]
    public function filterCategories(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('categories')->orderBy('name')->pluck('name','id');
    }

    #[Computed]
    public function filterItems(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('products')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name','id');
    }

    // ── Persistent transfer history from DB (transfer-in movements) ───────
    #[Computed]
    public function transferHistory(): \Illuminate\Support\Collection
    {
        $query = \App\Models\StockTransfer::query()
            ->where('stock_transfers.item_type', 'product')
            ->whereNull('stock_transfers.deleted_at')
            ->join('products', 'stock_transfers.item_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('locations as from_loc', 'stock_transfers.from_location_id', '=', 'from_loc.id')
            ->leftJoin('locations as to_loc', 'stock_transfers.to_location_id', '=', 'to_loc.id')
            ->leftJoin('branches as from_br', 'from_loc.branch_id', '=', 'from_br.id')
            ->leftJoin('branches as to_br', 'to_loc.branch_id', '=', 'to_br.id')
            ->leftJoin('businesses', 'to_br.business_id', '=', 'businesses.id')
            ->whereNull('products.deleted_at')
            ->whereNull('from_loc.deleted_at')
            ->whereNull('to_loc.deleted_at')
            ->whereNull('from_br.deleted_at')
            ->whereNull('to_br.deleted_at');

        if ($this->searchBranch) {
            $query->where(function($q) {
                $q->where('from_br.name', 'like', '%' . $this->searchBranch . '%')
                  ->orWhere('to_br.name', 'like', '%' . $this->searchBranch . '%');
            });
        }
        if ($this->searchCategory) {
            $query->where('categories.name', 'like', '%' . $this->searchCategory . '%');
        }
        if ($this->searchItem) {
            $query->where('products.name', 'like', '%' . $this->searchItem . '%');
        }

        return $query->select(
                'stock_transfers.id',
                'stock_transfers.quantity',
                'stock_transfers.notes',
                'stock_transfers.completed_at as moved_at',
                'products.name as product_name',
                'categories.name as category_name',
                'from_br.name as from_branch',
                'to_br.name as to_branch',
                'businesses.name as to_business',
            )
            ->orderByDesc('stock_transfers.completed_at')
            ->limit(200)
            ->get()
            ->map(function ($row) {
                // Extract user notes from the notes field (notes format: "from_branch:X|to_branch:Y|notes:Z")
                $userNotes = '';
                if ($row->notes && str_contains($row->notes, '|notes:')) {
                    $userNotes = trim(substr($row->notes, strpos($row->notes, '|notes:') + 7));
                } elseif ($row->notes) {
                    $userNotes = $row->notes;
                }
                $row->user_notes = $userNotes;
                return $row;
            });
    }

    // ── Cascade resets ────────────────────────────────────────────────────
    public function updatedSrcBranchId(): void
    {
        // If dest equals new source, clear it
        if ($this->destBranchId === $this->srcBranchId) {
            $this->destBranchId = null;
        }
        $this->trCategoryId = null;
        $this->trProductId  = null;
        $this->trQuantity   = '';
    }

    public function updatedDestBranchId(): void
    {
        // nothing extra needed
    }

    public function updatedTrCategoryId(): void
    {
        $this->trProductId = null;
        $this->trQuantity  = '';
    }

    public function updatedTrProductId(): void { $this->trQuantity = ''; }

    // ── All branches (for source dropdown) ───────────────────────────────
    #[Computed]
    public function allBranches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::with('business')
            ->orderBy('name')
            ->get();
    }

    // ── Destination branches (exclude the selected source) ────────────────
    #[Computed]
    public function destBranches(): \Illuminate\Support\Collection
    {
        return $this->allBranches
            ->when($this->srcBranchId, fn($c) => $c->where('id', '!=', $this->srcBranchId))
            ->values();
    }

    // ── Available stock at source branch for a product ────────────────────
    private function getSourceBalance(int $productId): float
    {
        if (!$this->srcBranchId) return 0;

        $locationIds = Location::where('branch_id', $this->srcBranchId)->pluck('id');
        if ($locationIds->isEmpty()) {
            return 0;
        }

        $stockIn = (float) StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $productId)
            ->where('direction', 'in')
            ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
            ->sum('quantity');

        $stockOut = (float) StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product')
            ->where('item_id', $productId)
            ->where('direction', 'out')
            ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
            ->sum('quantity');

        $movementBalance = max(0, (float)$stockIn - (float)$stockOut);

        // If no StockMovement records, check for legacy metadata
        if ($movementBalance == 0) {
            $product = Product::withoutGlobalScopes()->find($productId);
            if ($product) {
                $metadata = $product->metadata ?? [];
                $mfgQty = (float) ($metadata['manufacturing_quantity'] ?? 0);
                return max(0, $mfgQty);
            }
        }

        return $movementBalance;
    }

    // ── Categories at source branch ───────────────────────────────────────
    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        if (!$this->srcBranchId) return collect();

        $locationIds = Location::where('branch_id', $this->srcBranchId)->pluck('id');
        if ($locationIds->isEmpty()) {
            return collect();
        }

        // Get product IDs that have available stock (from StockMovement or metadata)
        $productIdsWithStock = collect();

        // 1. Check StockMovement-based products
        $baseQuery = StockMovement::whereIn('location_id', $locationIds)
            ->where('item_type', 'product');

        $stockInByProduct = (clone $baseQuery)
            ->where('direction', 'in')
            ->whereNotIn('movement_type', StockMovement::AVAILABLE_QTY_EXCLUDED_INBOUND)
            ->selectRaw('item_id, SUM(quantity) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        $stockOutByProduct = (clone $baseQuery)
            ->where('direction', 'out')
            ->whereIn('movement_type', StockMovement::AVAILABLE_QTY_DEDUCTIBLE_OUTBOUND)
            ->selectRaw('item_id, SUM(quantity) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        // Calculate net balance for each product
        $allProductIds = $stockInByProduct->keys()->merge($stockOutByProduct->keys())->unique();
        $movementProductIds = $allProductIds->filter(function ($productId) use ($stockInByProduct, $stockOutByProduct) {
            $inQty = (float) ($stockInByProduct->get($productId) ?? 0);
            $outQty = (float) ($stockOutByProduct->get($productId) ?? 0);
            return max(0, $inQty - $outQty) > 0;
        })->values();

        $productIdsWithStock = $productIdsWithStock->merge($movementProductIds);

        // 2. Also check products with manufacturing_quantity in metadata (legacy data)
        $productsWithMetadata = Product::withoutGlobalScopes()
            ->where('status', 'active')
            ->get()
            ->filter(function ($product) {
                $metadata = $product->metadata ?? [];
                $mfgQty = (float) ($metadata['manufacturing_quantity'] ?? 0);
                return $mfgQty > 0;
            })
            ->pluck('id')
            ->values();

        $productIdsWithStock = $productIdsWithStock->merge($productsWithMetadata)->unique();

        if ($productIdsWithStock->isEmpty()) {
            // Debug: Log what we found
            \Log::info('ProductTransfer::categories - no products with stock found', [
                'srcBranchId' => $this->srcBranchId,
                'locationIds' => $locationIds->toArray(),
                'movementProductIds' => $movementProductIds->toArray(),
                'productsWithMetadata' => $productsWithMetadata->toArray(),
            ]);
            return collect();
        }

        // Show ALL categories (both active and inactive) that have products with stock
        $categories = Category::withoutGlobalScopes()
            ->whereHas('products', fn($q) => $q->withoutGlobalScopes()
                ->where('status', 'active')
                ->whereIn('id', $productIdsWithStock))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Debug: Log what categories were found
        \Log::info('ProductTransfer::categories - found categories', [
            'count' => $categories->count(),
            'productIds' => $productIdsWithStock->toArray(),
            'categories' => $categories->pluck('name')->toArray(),
        ]);

        return $categories;
    }

    // ── Products at source branch for selected category ───────────────────
    #[Computed]
    public function products(): \Illuminate\Support\Collection
    {
        if (!$this->srcBranchId || !$this->trCategoryId) return collect();

        return Product::withoutGlobalScopes()
            ->where('category_id', $this->trCategoryId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $p->available_qty = $this->getSourceBalance($p->id);
                return $p;
            })
            ->filter(fn($p) => $p->available_qty > 0)
            ->values();
    }

    // ── Available qty for view ────────────────────────────────────────────
    public function availableQty(): float
    {
        if (!$this->trProductId) return 0;
        return $this->getSourceBalance($this->trProductId);
    }

    // ── Execute transfer ──────────────────────────────────────────────────
    public function executeTransfer(): void
    {
        $this->validate([
            'srcBranchId'  => 'required|integer|exists:branches,id|different:destBranchId',
            'destBranchId' => 'required|integer|exists:branches,id',
            'trCategoryId' => 'required|integer|exists:categories,id',
            'trProductId'  => 'required|integer|exists:products,id',
            'trQuantity'   => 'required|numeric|min:0.001',
            'trNotes'      => 'required|string|min:3|max:500',
        ]);

        $product   = Product::withoutGlobalScopes()->with('category')->findOrFail($this->trProductId);
        $qty       = (float) $this->trQuantity;
        $available = $this->availableQty();

        if ($qty > $available) {
            $this->addError('trQuantity', "Only {$available} units available at source.");
            return;
        }

        $srcBranch  = Branch::with('business')->findOrFail($this->srcBranchId);
        $destBranch = Branch::with('business')->findOrFail($this->destBranchId);

        DB::transaction(function () use ($product, $qty, $srcBranch, $destBranch) {
            $inventory = app(InventoryService::class);

            // ── Deduct from source branch ─────────────────────────────────
            $srcLocation = Location::where('branch_id', $srcBranch->id)->first();
            if ($srcLocation) {
                // Convert any un-moved manufacturing_quantity to a real stock movement
                // so the movement ledger is the single source of truth.
                $meta = $product->metadata ?? [];
                $mfgQty = (float) ($meta['manufacturing_quantity'] ?? 0);
                if ($mfgQty > 0) {
                    // Record an inbound production-output movement for the manufactured stock
                    StockMovement::create([
                        'business_id'   => $srcLocation->branch?->business_id,
                        'branch_id'     => $srcLocation->branch_id,
                        'location_id'   => $srcLocation->id,
                        'item_type'     => 'product',
                        'item_id'       => $product->id,
                        'movement_type' => StockMovement::TYPE_PRODUCTION_OUTPUT,
                        'direction'     => 'in',
                        'quantity'      => $mfgQty,
                        'unit_cost'     => (float) ($product->cost_price ?? 0),
                        'total_cost'    => round($mfgQty * (float) ($product->cost_price ?? 0), 4),
                        'balance_after' => $mfgQty,
                        'reference_type'=> 'manufacturing',
                        'notes'         => 'Converted from manufacturing_quantity metadata',
                        'created_by'    => auth()->id(),
                        'moved_at'      => now(),
                    ]);
                    // Zero the metadata so we don't double-count on future checks
                    // Also clear related manufacturing fields to avoid stale data
                    $meta['manufacturing_quantity'] = 0;
                    $meta['manufacturing_added_at'] = null;
                    $meta['seed_quantity_used'] = 0;
                    $meta['seed_name'] = null;
                    $meta['seed_unit'] = null;
                    $product->metadata = $meta;
                    $product->save();
                }
            }

            // ── Add to destination branch ─────────────────────────────────
            $destLocation = Location::where('branch_id', $destBranch->id)->first();

            // NOTE: Transfers do NOT create Purchase records
            // They only create stock_movements (TRANSFER_IN/TRANSFER_OUT)
            // This prevents double-counting in purchase reports

            $purchase = null; // Just track for history

            // Record proper TRANSFER_OUT + TRANSFER_IN movements via InventoryService::transfer()
            if ($srcLocation && $destLocation) {
                $transferNotes = "from_branch:{$srcBranch->name}|to_branch:{$destBranch->name}|notes:" . trim($this->trNotes);
                $inventory->transfer(
                    $srcLocation->id,
                    $destLocation->id,
                    'product',
                    $product->id,
                    $qty,
                    $transferNotes
                );
            } elseif ($srcLocation) {
                // Destination has no location yet — fall back to just recording the outbound
                $inventory->recordMovement(
                    $srcLocation->id, 'product', $product->id,
                    StockMovement::TYPE_TRANSFER_OUT, $qty,
                    (float) ($product->cost_price ?? 0),
                    ['reference_type' => 'transfer', 'notes' => "from_branch:{$srcBranch->name}|to_branch:{$destBranch->name}|notes:" . trim($this->trNotes)]
                );
            }

            // Preserve selling price
            if ((float) $product->selling_price > 0) {
                Product::withoutGlobalScopes()
                    ->where('id', $product->id)
                    ->update(['selling_price' => $product->selling_price]);
            }
        });

        // History log
        $this->trHistory[] = [
            'at'       => now()->format('Y-m-d H:i'),
            'category' => $product->category?->name ?? '—',
            'product'  => $product->name,
            'qty'      => $qty,
            'from'     => $srcBranch->name . ' (' . ($srcBranch->business?->name ?? '') . ')',
            'to'       => $destBranch->name . ' (' . ($destBranch->business?->name ?? '') . ')',
            'notes'    => $this->trNotes,
        ];

        $this->dispatch('notify', ['type' => 'success',
            'message' => "Transferred {$qty} × {$product->name}: {$srcBranch->name} → {$destBranch->name}."]);

        // Reset product fields — keep source/dest selection
        $this->trCategoryId = null;
        $this->trProductId  = null;
        $this->trQuantity   = '';
        $this->trNotes      = '';
    }

    public function render()
    {
        return view('livewire.admin.purchases.product-transfer')
            ->title('Transfer Products');
    }
}
