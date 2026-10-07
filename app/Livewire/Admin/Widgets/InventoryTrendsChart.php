<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Component;

class InventoryTrendsChart extends Component
{
    public $trendData = [];

    public function mount()
    {
        // Mock trend data for the last 7 days
        $this->trendData = [
            ['day' => 'Mon', 'stock_in' => 120, 'stock_out' => 85],
            ['day' => 'Tue', 'stock_in' => 95, 'stock_out' => 110],
            ['day' => 'Wed', 'stock_in' => 200, 'stock_out' => 78],
            ['day' => 'Thu', 'stock_in' => 60, 'stock_out' => 130],
            ['day' => 'Fri', 'stock_in' => 150, 'stock_out' => 95],
            ['day' => 'Sat', 'stock_in' => 80, 'stock_out' => 60],
            ['day' => 'Sun', 'stock_in' => 45, 'stock_out' => 30],
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.inventory-trends-chart');
    }
}
