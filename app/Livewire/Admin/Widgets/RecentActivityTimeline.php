<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Component;

class RecentActivityTimeline extends Component
{
    public $activities = [];

    public function mount()
    {
        // Mock recent activity data
        $this->activities = [
            ['icon' => 'sale', 'message' => 'Sale completed: ORD-A3F8K2P1', 'user' => 'Branch Seller', 'time' => '5 mins ago'],
            ['icon' => 'stock', 'message' => 'Stock request submitted for 50x Steel Sheets', 'user' => 'Manufacturing Mgr', 'time' => '15 mins ago'],
            ['icon' => 'approval', 'message' => 'Inventory adjustment approved by Super Admin', 'user' => 'System', 'time' => '1 hour ago'],
            ['icon' => 'user', 'message' => 'New user registered: bookstore.manager@albareka.com', 'user' => 'Super Admin', 'time' => '2 hours ago'],
            ['icon' => 'product', 'message' => 'Product "Wireless Mouse" added to catalog', 'user' => 'Super Admin', 'time' => '3 hours ago'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.recent-activity-timeline');
    }
}
