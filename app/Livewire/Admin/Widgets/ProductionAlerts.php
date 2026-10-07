<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Component;

class ProductionAlerts extends Component
{
    public $alerts = [];

    public function mount()
    {
        // Mock data for production alerts
        $this->alerts = [
            ['id' => 1, 'type' => 'warning', 'message' => 'Machine A requires maintenance in 2 days.', 'time' => '10 mins ago'],
            ['id' => 2, 'type' => 'critical', 'message' => 'Production line 3 halted due to material shortage.', 'time' => '1 hour ago'],
            ['id' => 3, 'type' => 'info', 'message' => 'Batch #4092 completed successfully.', 'time' => '2 hours ago'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.production-alerts');
    }
}
