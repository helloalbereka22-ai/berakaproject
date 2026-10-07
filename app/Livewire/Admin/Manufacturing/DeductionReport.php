<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Business;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class DeductionReport extends Component
{
    use WithPagination;

    // ── Filters ───────────────────────────────────────────────────────────
    public string $dateFrom = '';
    public string $dateTo   = '';
    public string $businessId = '';
    public string $branchId   = '';
    public string $productId  = '';

    // ── Selector data ─────────────────────────────────────────────────────
    public $businesses = [];
    public $branches   = [];
    public $products   = [];

    // ── Constants ─────────────────────────────────────────────────────────
    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->dateFrom = now()->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');
        $this->businesses = Business::orderBy('name')->get(['id', 'name']);
    }

    public function updatedBusinessId(): void
    {
        $this->branchId  = '';
        $this->productId = '';
        $this->branches  = $this->businessId
            ? \App\Models\Branch::where('business_id', $this->businessId)->orderBy('name')->get(['id', 'name'])
            : collect();
        $this->products = collect();
    }

    public function updatedBranchId(): void
    {
        $this->productId = '';
    }

    public function resetFilters(): void
    {
        $this->dateFrom   = now()->format('Y-m-d');
        $this->dateTo     = now()->format('Y-m-d');
        $this->businessId = '';
        $this->branchId   = '';
        $this->productId  = '';
        $this->branches   = collect();
        $this->products  = collect();
        $this->resetPage();
    }

    // ── Query ─────────────────────────────────────────────────────────────
    protected function query()
    {
        return StockMovement::withoutGlobalScopes()
            ->with(['product', 'location.branch', 'creator'])
            ->where('movement_type', StockMovement::TYPE_PRODUCTION_CONSUMPTION)
            ->where('direction', 'out')
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->businessId, fn($q) => $q->whereHas('location.branch', fn($sq) => $sq->where('business_id', (int) $this->businessId)))
            ->when($this->branchId, fn($q) => $q->where('location_id', Location::where('branch_id', (int) $this->branchId)->pluck('id')))
            ->when($this->productId, fn($q) => $q->where('item_id', (int) $this->productId));
    }

    public function getRowsProperty()
    {
        return $this->query()
            ->select(['id', 'item_id', 'location_id', 'quantity', 'unit_cost', 'notes', 'created_by', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getReportDataProperty()
    {
        return $this->query()
            ->select(['id', 'item_id', 'location_id', 'quantity', 'unit_cost', 'notes', 'created_by', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'id'           => $row->id,
                    'item_name'    => $row->product?->name ?? 'Unknown',
                    'item_id'      => $row->item_id,
                    'quantity'     => (float) $row->quantity,
                    'unit'         => $row->product?->unit ?? '',
                    'unit_cost'    => (float) $row->unit_cost,
                    'total_cost'   => (float) $row->quantity * (float) $row->unit_cost,
                    'branch_name'  => $row->location?->branch?->name ?? 'Unknown',
                    'location_id'  => $row->location_id,
                    'deducted_by'  => $row->creator?->name ?? 'System',
                    'notes'        => $row->notes,
                    'created_at'   => $row->created_at->format('Y-m-d H:i'),
                    'date'         => $row->created_at->format('Y-m-d'),
                ];
            });
    }

    public function getTotalQuantityProperty(): float
    {
        return $this->reportData->sum('quantity');
    }

    public function getTotalCostProperty(): float
    {
        return $this->reportData->sum('total_cost');
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.deduction-report', [
            'rows' => $this->rows,
        ])->title('Deduction Report');
    }
}