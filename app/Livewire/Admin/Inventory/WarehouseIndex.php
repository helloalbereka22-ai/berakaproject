<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Location;
use App\Models\Warehouse;
use App\Models\Business;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class WarehouseIndex extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $businessId = '';
    public array $businesses  = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->businesses = $user->isSuperAdmin()
            ? Business::orderBy('name')->get(['id', 'name'])->toArray()
            : Business::whereIn('id', $user->assignedBusinessIds())->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedBusinessId(): void { $this->resetPage(); }

    public function render()
    {
        $user = auth()->user();

        // Try Location model first (existing schema), fall back to Warehouse if exists
        if (class_exists(Location::class)) {
            $query = Location::with('branch.business')
                ->when(!$user->isSuperAdmin(), fn($q) =>
                    $q->whereHas('branch', fn($b) =>
                        $b->whereIn('business_id', $user->assignedBusinessIds())
                    )
                )
                ->when($this->businessId, fn($q) =>
                    $q->whereHas('branch', fn($b) => $b->where('business_id', $this->businessId))
                )
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name');

            return view('livewire.admin.inventory.warehouses', [
                'warehouses' => $query->paginate(20),
                'model'      => 'location',
            ])->title('Warehouses');
        }

        return view('livewire.admin.inventory.warehouses', [
            'warehouses' => collect(),
            'model'      => 'none',
        ])->title('Warehouses');
    }
}
