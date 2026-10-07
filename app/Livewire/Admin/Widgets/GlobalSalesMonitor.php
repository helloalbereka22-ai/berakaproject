<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Order;
use Livewire\Component;

class GlobalSalesMonitor extends Component
{
    public $salesData = [];
    public $recentOrders = [];

    public function mount()
    {
        // Calculate basic KPIs using Orders (status=completed)
        $this->salesData = [
            'today' => Order::where('status', 'completed')->whereDate('created_at', today())->sum('total_amount'),
            'this_week' => Order::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount'),
            'this_month' => Order::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('total_amount'),
        ];

        // Fetch recent global orders
        $this->recentOrders = Order::with('customer')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.widgets.global-sales-monitor');
    }
}
