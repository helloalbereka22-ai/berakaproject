<?php

namespace App\Livewire\Admin\Widgets;

use Illuminate\Support\Facades\Cache;
use App\Models\Business;
use App\Models\Location;
use App\Models\User;
use App\Models\Product;
use App\Models\ApprovalRequest;
use Livewire\Component;

class GlobalKpiCards extends Component
{
    public $kpis = [];

    public function mount()
    {
        // Cache these heavy metrics for 15 minutes to ensure Super Admin dashboard loads instantly
        $this->kpis = Cache::remember('admin.global_kpis', 60 * 15, function () {
            return [
                'total_businesses' => Business::count(),
                'active_branches' => Location::where('type', 'branch')->where('status', 'active')->count(),
                'total_warehouses' => Location::where('type', 'warehouse')->count(),
                'total_users' => User::count(),
                'total_products' => Product::count(),
                'pending_approvals' => ApprovalRequest::where('status', 'submitted')->count(),
            ];
        });
    }

    public function render()
    {
        return view('livewire.admin.widgets.global-kpi-cards');
    }
}
