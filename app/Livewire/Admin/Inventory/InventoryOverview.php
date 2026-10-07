<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Business;
use App\Models\StockMovement;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class InventoryOverview extends Component
{
    use WithPagination;

    public string $businessId  = '';
    public string $locationId  = '';
    public string $productType = '';
    public string $search      = '';

    public array $businesses = [];
    public array $locations  = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();

        // Load locations for filter
        if (class_exists(\App\Models\Location::class)) {
            $query = \App\Models\Location::with('branch')
                ->when($this->businessId, fn($q) => $q->whereHas('branch', fn($b) => $b->where('business_id', $this->businessId)))
                ->when(!$user->isSuperAdmin(), fn($q) => $q->whereHas('branch', fn($b) => $b->whereIn('business_id', $user->assignedBusinessIds())));
            $this->locations = $query->orderBy('name')->get(['id', 'name'])->toArray();
        }
    }

    public function updatedBusinessId(): void
    {
        $this->resetPage();
        $this->locationId = '';
        if (class_exists(\App\Models\Location::class)) {
             $query = \App\Models\Location::with('branch')
                ->when($this->businessId, fn($q) => $q->whereHas('branch', fn($b) => $b->where('business_id', $this->businessId)));
             $this->locations = $query->orderBy('name')->get(['id', 'name'])->toArray();
        }
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedLocationId(): void { $this->resetPage(); }
    public function updatedProductType(): void { $this->resetPage(); }

    private function baseQuery()
    {
        $user = auth()->user();
        return StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds())
            )
            ->when($this->businessId, fn($q) => $q->where('stock_movements.business_id', $this->businessId))
            ->when($this->locationId, fn($q) => $q->where('stock_movements.location_id', $this->locationId))
            ->when($this->productType, fn($q) => $q->where('products.type', $this->productType));
    }

    private function getAggregatedQuery()
    {
        return $this->baseQuery()
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
    }

    public function render()
    {
        // ── KPIs ──────────────────────────────────────────────────────────────
        $allStock = $this->getAggregatedQuery()->get();
        
        $kpis = [
            'total_items'  => $allStock->count(),
            'total_value'  => $allStock->sum(fn($r) => ((float)$r->cost_price) * (float)$r->quantity_on_hand),
            'low_stock'    => $allStock->filter(fn($r) => $r->quantity_on_hand > 0 && $r->quantity_on_hand <= $r->reorder_point)->count(),
            'out_of_stock' => $allStock->filter(fn($r) => $r->quantity_on_hand <= 0)->count(),
        ];

        // ── By Product Type ───────────────────────────────────────────────────
        $byType = [
            Product::TYPE_SALE_PRODUCT => 0,
            Product::TYPE_RAW_MATERIAL => 0,
            Product::TYPE_PACKING_MATERIAL => 0,
            Product::TYPE_FINISHED_PRODUCT => 0,
        ];
        foreach ($allStock as $stock) {
            $type = $stock->product_type ?? 'unknown';
            if (isset($byType[$type])) {
                $byType[$type]++;
            }
        }

        // ── Paginated Stock List ─────────────────────────────────────────────
        $query = $this->getAggregatedQuery();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('products.name', 'like', "%{$this->search}%")
                  ->orWhere('products.sku', 'like', "%{$this->search}%");
            });
        }
        
        // Paginate the aggregated results.
        $paginator = $query->orderByDesc('updated_at')->paginate(20);

        // Map items to standard objects for the blade view
        $stockLevels = $paginator->through(function ($row) {
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
                        public $name;
                        public $sku;
                        public $type;
                        public $unit;
                        public $cost_price;
                        public $reorder_point;
                        public function __construct($r) {
                            $this->name = $r->product_name;
                            $this->sku = $r->sku;
                            $this->type = $r->product_type;
                            $this->unit = $r->unit;
                            $this->cost_price = clone $r->cost_price;
                            $this->reorder_point = $r->reorder_point;
                        }
                        public function getTypeLabel() {
                            return \App\Models\Product::TYPES[$this->type] ?? ucfirst($this->type);
                        }
                    };
                    $this->quantity_on_hand = (float) $row->quantity_on_hand;
                    $this->updated_at = \Illuminate\Support\Carbon::parse($row->updated_at);
                    $this->unit_cost = (float) $row->cost_price;
                    $this->reorder_point = (float) $row->reorder_point;
                }

                public function isLowStock() {
                    return $this->quantity_on_hand > 0 && $this->quantity_on_hand <= $this->reorder_point;
                }
                
                public function isOutOfStock() {
                    return $this->quantity_on_hand <= 0;
                }
            };
        });

        return view('livewire.admin.inventory.overview', [
            'kpis'        => $kpis,
            'byType'      => $byType,
            'stockLevels' => $stockLevels,
            'productTypes' => Product::TYPES,
        ])->title('Inventory Overview');
    }
}
