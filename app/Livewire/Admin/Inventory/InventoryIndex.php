<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Business;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AuthorizationService;
use App\Services\InventoryService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class InventoryIndex extends Component
{
    use WithPagination;

    // ── Filters ──────────────────────────────────────────────────────────
    public string $search       = '';
    public string $businessId   = '';
    public string $locationId   = '';
    public string $stockFilter  = ''; // low_stock | out_of_stock
    public string $tab          = 'levels'; // levels | movements | transfers | adjustments
    public string $dateFrom     = '';
    public string $dateTo       = '';

    // ── Adjustment modal ─────────────────────────────────────────────────
    public bool   $showAdjustModal  = false;
    public string $adjLocationId    = '';
    public string $adjProductId     = '';
    public string $adjDirection     = 'increase';
    public string $adjQuantity      = '';
    public string $adjReason        = '';
    public string $adjNotes         = '';

    // ── Transfer modal ────────────────────────────────────────────────────
    public bool   $showTransferModal   = false;
    public string $tfrFromLocationId   = '';
    public string $tfrToLocationId     = '';
    public string $tfrProductId        = '';
    public string $tfrQuantity         = '';
    public string $tfrNotes            = '';

    public array $businesses = [];
    public array $locations  = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::active()->orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();

        $this->loadLocations();
        
        // Set tab based on current route
        $route = request()->route()->getName();
        $today = now()->toDateString(); // Returns Y-m-d format
        
        if ($route === 'inventory.transfers') {
            $this->tab = 'transfers';
            $this->dateFrom = $today;
            $this->dateTo = $today;
        } elseif ($route === 'inventory.movements') {
            $this->tab = 'movements';
            $this->dateFrom = $today;
            $this->dateTo = $today;
        } elseif ($route === 'inventory.adjustments') {
            $this->tab = 'adjustments';
        } else {
            $this->tab = 'levels'; // default
        }
    }

    public function updatedBusinessId(): void
    {
        $this->resetPage();
        $this->loadLocations();
        $this->locationId = '';
    }

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedStockFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    private function loadLocations(): void
    {
        $user = auth()->user();
        $query = Location::with('branch')
            ->when($this->businessId, fn($q) =>
                $q->whereHas('branch', fn($b) => $b->where('business_id', $this->businessId))
            )
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereHas('branch', fn($b) => $b->whereIn('business_id', $user->assignedBusinessIds()))
            );

        $this->locations = $query->orderBy('name')->get(['id', 'name', 'type'])->toArray();
    }

    // ── Stock levels query ────────────────────────────────────────────────

    public function getStockLevelsProperty()
    {
        $user  = auth()->user();
        $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
        
        $query = StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->where('products.status', 'active')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds())
            )
            ->when($isSeller, function($q) use ($user) {
                // Sellers only see stock for their assigned branch
                $branchIds = $user->assignedBranchIds();
                if (!empty($branchIds)) {
                    $locationIds = Location::whereIn('branch_id', $branchIds)->pluck('id');
                    $q->whereIn('stock_movements.location_id', $locationIds);
                }
            })
            ->when($this->tab === 'transfers' && $isSeller, function($q) {
                // For transfers tab, show only products that have transfer movements
                $transferredProductIds = StockMovement::whereIn('movement_type', [
                        StockMovement::TYPE_TRANSFER_IN,
                        StockMovement::TYPE_TRANSFER_OUT
                    ])
                    ->distinct()
                    ->pluck('item_id');
                $q->whereIn('products.id', $transferredProductIds);
            })
            ->when($this->businessId,  fn($q) => $q->where('stock_movements.business_id', $this->businessId))
            ->when($this->locationId,  fn($q) => $q->where('stock_movements.location_id', $this->locationId))
            ->select(
                'stock_movements.location_id',
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.type as product_type',
                'products.unit',
                'products.cost_price',
                'products.reorder_point',
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as quantity_on_hand"),
                \Illuminate\Support\Facades\DB::raw("MAX(stock_movements.moved_at) as updated_at")
            )
            ->groupBy(
                'stock_movements.location_id',
                'products.id', 'products.name', 'products.sku', 'products.type', 'products.unit', 'products.cost_price', 'products.reorder_point'
            );

        if ($this->search) {
            $query->where(function($q) {
                $q->where('products.name', 'like', "%{$this->search}%")
                  ->orWhere('products.sku', 'like', "%{$this->search}%");
            });
        }

        if ($this->stockFilter === 'low_stock') {
            $query->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) > 0")
                  ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <= products.reorder_point");
        } elseif ($this->stockFilter === 'out_of_stock') {
            $query->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <= 0");
        }

        $paginator = $query->orderByDesc('updated_at')->paginate(25);

        return $paginator->through(function ($row) {
            return new class($row) {
                public $location;
                public $item;
                public $quantity_on_hand;
                public $updated_at;
                public $unit_cost;
                private $reorder_point;

                public function __construct($row) {
                    $this->location = \App\Models\Location::with('branch.business')->find($row->location_id);
                    $this->item = new class($row) {
                        public $id;
                        public $name;
                        public $sku;
                        public $type;
                        public $unit;
                        public $cost_price;
                        public $reorder_point;
                        public function __construct($r) {
                            $this->id = $r->product_id;
                            $this->name = $r->product_name;
                            $this->sku = $r->sku;
                            $this->type = $r->product_type;
                            $this->unit = $r->unit;
                            $this->cost_price = $r->cost_price;
                            $this->reorder_point = $r->reorder_point;
                        }
                    };
                    $this->quantity_on_hand = (float) $row->quantity_on_hand;
                    $this->updated_at = \Illuminate\Support\Carbon::parse($row->updated_at);
                    $this->unit_cost = (float) $row->cost_price;
                    $this->reorder_point = (float) $row->reorder_point;
                    $this->reorder_level = (float) $row->reorder_point;
                }

                public function isLowStock() {
                    return $this->quantity_on_hand > 0 && $this->quantity_on_hand <= $this->reorder_point;
                }
                
                public function isOutOfStock() {
                    return $this->quantity_on_hand <= 0;
                }
                
                public function getAvailableQuantity() {
                    return $this->quantity_on_hand;
                }
                
                public function getStockValue() {
                    return $this->quantity_on_hand * $this->unit_cost;
                }
            };
        });
    }

    // ── Movements query ───────────────────────────────────────────────────

    public function getMovementsProperty()
    {
        $user = auth()->user();
        $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
        
        return StockMovement::with(['location.branch', 'creator', 'item'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($isSeller, function($q) use ($user) {
                // Sellers only see movements for their assigned branch
                $branchIds = $user->assignedBranchIds();
                if (!empty($branchIds)) {
                    $q->whereHas('location', fn($loc) => $loc->whereIn('branch_id', $branchIds));
                }
            })
            ->when($this->businessId, fn($q) => $q->where('business_id', $this->businessId))
            ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
            ->when($this->tab === 'transfers' && $isSeller, function($q) {
                // For sellers on transfers tab, show only transfer movements
                $q->whereIn('movement_type', [
                    StockMovement::TYPE_TRANSFER_IN,
                    StockMovement::TYPE_TRANSFER_OUT
                ]);
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('moved_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('moved_at', '<=', $this->dateTo))
            ->orderByDesc('moved_at')
            ->paginate(25);
    }

    // ── KPI summary ───────────────────────────────────────────────────────

    public function getSummaryProperty(): array
    {
        $user = auth()->user();
        $isSeller = $user->roles->whereIn('name', ['Seller','Branch Seller','Sales Staff'])->isNotEmpty();
        
        $query = StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->where('products.status', 'active')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds())
            )
            ->when($isSeller, function($q) use ($user) {
                // Sellers only see summary for their assigned branch
                $branchIds = $user->assignedBranchIds();
                if (!empty($branchIds)) {
                    $locationIds = Location::whereIn('branch_id', $branchIds)->pluck('id');
                    $q->whereIn('stock_movements.location_id', $locationIds);
                }
            })
            ->when($this->businessId, fn($q) => $q->where('stock_movements.business_id', $this->businessId))
            ->select(
                'products.cost_price',
                'products.reorder_point',
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty")
            )
            ->groupBy('stock_movements.location_id', 'products.id', 'products.cost_price', 'products.reorder_point');

        $all = $query->get();

        return [
            'total_items'  => $all->count(),
            'total_value'  => $all->sum(fn($r) => ((float)$r->cost_price) * (float)$r->qty),
            'low_stock'    => $all->filter(fn($r) => $r->qty > 0 && $r->qty <= $r->reorder_point)->count(),
            'out_of_stock' => $all->filter(fn($r) => $r->qty <= 0)->count(),
        ];
    }

    // ── Adjustment ────────────────────────────────────────────────────────

    public function openAdjustModal(string $locationId, string $productId): void
    {
        $this->adjLocationId = $locationId;
        $this->adjProductId  = $productId;
        $this->showAdjustModal = true;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjLocationId' => 'required',
            'adjProductId'  => 'required',
            'adjDirection'  => 'required|in:increase,decrease',
            'adjQuantity'   => 'required|numeric|min:0.0001',
            'adjReason'     => 'required|string|min:5',
        ]);

        try {
            app(InventoryService::class)->applyAdjustment(
                (int) $this->adjLocationId,
                'product',
                (int) $this->adjProductId,
                $this->adjDirection,
                (float) $this->adjQuantity,
                $this->adjReason,
                $this->adjNotes
            );

            $this->showAdjustModal = false;
            $this->reset(['adjLocationId', 'adjProductId', 'adjDirection', 'adjQuantity', 'adjReason', 'adjNotes']);
            session()->flash('success', 'Adjustment applied successfully.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ── Transfer ──────────────────────────────────────────────────────────

    public function openTransferModal(): void
    {
        $this->showTransferModal = true;
    }

    public function saveTransfer(): void
    {
        $this->validate([
            'tfrFromLocationId' => 'required|different:tfrToLocationId',
            'tfrToLocationId'   => 'required',
            'tfrProductId'      => 'required',
            'tfrQuantity'       => 'required|numeric|min:0.0001',
        ]);

        try {
            app(InventoryService::class)->transfer(
                (int) $this->tfrFromLocationId,
                (int) $this->tfrToLocationId,
                'product',
                (int) $this->tfrProductId,
                (float) $this->tfrQuantity,
                $this->tfrNotes
            );

            $this->showTransferModal = false;
            $this->reset(['tfrFromLocationId', 'tfrToLocationId', 'tfrProductId', 'tfrQuantity', 'tfrNotes']);
            session()->flash('success', 'Transfer completed successfully.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.inventory.index', [
            'stockLevels' => $this->stockLevels,
            'movements'   => $this->movements,
            'summary'     => $this->summary,
            'tab'         => $this->tab,
        ])->title('Inventory Management');
    }
}
