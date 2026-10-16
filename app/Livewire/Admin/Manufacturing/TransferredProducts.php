<?php

namespace App\Livewire\Admin\Manufacturing;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class TransferredProducts extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $selectedLocationId = null;

    // Filters
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        $user = auth()->user();
        
        // Check if user has assigned branches
        $branchIds = $user->assignedBranchIds();
        
        if (empty($branchIds)) {
            return;
        }
        
        // Default to last 30 days to show more comprehensive transfer history
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedLocationId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function onDateFromChange(): void
    {
        $this->resetPage();
    }

    public function onDateToChange(): void
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\On('refresh')]
    public function refresh(): void
    {
        // Trigger recompute of computed properties
    }

    #[Computed]
    public function branchLocations()
    {
        $user = auth()->user();
        
        // Super Admin sees ALL branches, others see only assigned
        if ($user->isSuperAdmin()) {
            return Location::with('branch')->orderBy('name')->get();
        }
        
        $branchIds = $user->assignedBranchIds();

        if (empty($branchIds)) {
            return collect([]);
        }

        return Location::whereIn('branch_id', $branchIds)
            ->with('branch')
            ->orderBy('name')
            ->get();
    }

    private function getTransferredProducts()
    {
        $user = auth()->user();
        
        // Super Admin sees ALL branches, others see only assigned
        if ($user->isSuperAdmin()) {
            $locationIds = Location::pluck('id')->toArray();
        } else {
            $branchIds = $user->assignedBranchIds();

            if (empty($branchIds)) {
                return collect([]);
            }

            // Get location IDs for the assigned branches
            $locationIds = Location::whereIn('branch_id', $branchIds)->pluck('id')->toArray();
        }

        if (empty($locationIds)) {
            return collect([]);
        }

        // Query transfer movements - include BOTH transfer_in AND distribution (from manufacturing)
        // Also include transfer_out for completeness
        $query = StockMovement::with(['item', 'location.branch', 'creator'])
            ->whereIn('movement_type', [
                StockMovement::TYPE_TRANSFER_IN, 
                StockMovement::TYPE_TRANSFER_OUT,
                StockMovement::TYPE_DISTRIBUTION
            ])
            ->where('item_type', 'product')
            ->whereIn('location_id', $locationIds);

        // Apply date filters with proper parsing
        if (!empty($this->dateFrom)) {
            try {
                $dateFromObj = \Carbon\Carbon::createFromFormat('Y-m-d', $this->dateFrom)->startOfDay();
                $query->where('moved_at', '>=', $dateFromObj);
            } catch (\Exception $e) {
                // Fallback to string concatenation if parsing fails
                $query->where('moved_at', '>=', $this->dateFrom . ' 00:00:00');
            }
        }
        if (!empty($this->dateTo)) {
            try {
                $dateToObj = \Carbon\Carbon::createFromFormat('Y-m-d', $this->dateTo)->endOfDay();
                $query->where('moved_at', '<=', $dateToObj);
            } catch (\Exception $e) {
                // Fallback to string concatenation if parsing fails
                $query->where('moved_at', '<=', $this->dateTo . ' 23:59:59');
            }
        }

        // Apply location filter
        if ($this->selectedLocationId) {
            $query->where('location_id', $this->selectedLocationId);
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('item', function ($product) {
                    $product->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                })
                ->orWhere('reference', 'like', '%' . $this->search . '%')
                ->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderByDesc('moved_at')->paginate(25);
    }

    private function getSummary(): array
    {
        $user = auth()->user();
        
        // Super Admin sees ALL branches, others see only assigned
        if ($user->isSuperAdmin()) {
            $locationIds = Location::pluck('id')->toArray();
        } else {
            $branchIds = $user->assignedBranchIds();

            if (empty($branchIds)) {
                return [
                    'total_transfers' => 0,
                    'total_quantity' => 0,
                    'total_value' => 0,
                    'unique_products' => 0,
                ];
            }

            $locationIds = Location::whereIn('branch_id', $branchIds)->pluck('id')->toArray();
        }

        if (empty($locationIds)) {
            return [
                'total_transfers' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
                'unique_products' => 0,
            ];
        }

        $query = StockMovement::whereIn('movement_type', [
                StockMovement::TYPE_TRANSFER_IN, 
                StockMovement::TYPE_TRANSFER_OUT,
                StockMovement::TYPE_DISTRIBUTION
            ])
            ->where('item_type', 'product')
            ->whereIn('location_id', $locationIds);

        if (!empty($this->dateFrom)) {
            try {
                $dateFromObj = \Carbon\Carbon::createFromFormat('Y-m-d', $this->dateFrom)->startOfDay();
                $query->where('moved_at', '>=', $dateFromObj);
            } catch (\Exception $e) {
                $query->where('moved_at', '>=', $this->dateFrom . ' 00:00:00');
            }
        }
        if (!empty($this->dateTo)) {
            try {
                $dateToObj = \Carbon\Carbon::createFromFormat('Y-m-d', $this->dateTo)->endOfDay();
                $query->where('moved_at', '<=', $dateToObj);
            } catch (\Exception $e) {
                $query->where('moved_at', '<=', $this->dateTo . ' 23:59:59');
            }
        }
        if ($this->selectedLocationId) {
            $query->where('location_id', $this->selectedLocationId);
        }

        $transfers = clone $query;
        $totalTransfers = $transfers->count();
        $totalQuantity = (clone $query)->sum('quantity');
        $totalValue = (clone $query)->sum('total_cost');
        $uniqueProducts = (clone $query)->distinct('item_id')->count('item_id');

        return [
            'total_transfers' => $totalTransfers,
            'total_quantity' => (float) $totalQuantity,
            'total_value' => (float) $totalValue,
            'unique_products' => $uniqueProducts,
        ];
    }

    public function render()
    {
        return view('livewire.admin.manufacturing.transferred-products', [
            'transferredProducts' => $this->getTransferredProducts(),
            'summary' => $this->getSummary(),
        ])
            ->title('Transferred Products');
    }
}
