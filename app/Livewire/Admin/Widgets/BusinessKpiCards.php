<?php

namespace App\Livewire\Admin\Widgets;

use App\Location;
use App\Product;
use App\Models\ApprovalRequest;
use Livewire\Component;

class BusinessKpiCards extends Component
{
    public $businessId;
    public $kpis = [];

    public function mount()
    {
        // In a real application, fetch the assigned business ID from the authenticated user
        // $this->businessId = auth()->user()->business_id;
        $this->businessId = 1; // Mock assigned business ID for demonstration

        // Fetch KPIs specific to the assigned business
        $this->kpis = [
            'active_branches' => Location::where('business_id', $this->businessId)->where('type', 'branch')->where('is_active', true)->count(),
            'total_products' => Product::where('business_id', $this->businessId)->count(),
            'pending_requests' => ApprovalRequest::where('business_id', $this->businessId)
                                                 ->whereIn('status', ['submitted', 'under_review'])
                                                 ->count(),
            'total_inventory_value' => '0.00', // Placeholder: would require complex sum calculation across branches
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.business-kpi-cards');
    }
}
