<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Component;

class RawMaterialAvailability extends Component
{
    public $materials = [];

    public function mount()
    {
        // Fetch mock data for the dashboard widget
        // In reality, this would query Inventory joined with Products where category = 'Raw Material'
        
        $this->materials = [
            ['id' => 1, 'name' => 'Steel Sheets (2mm)', 'quantity' => 1450, 'unit' => 'kg', 'status' => 'Healthy'],
            ['id' => 2, 'name' => 'Rubber Gaskets', 'quantity' => 120, 'unit' => 'pcs', 'status' => 'Low Stock'],
            ['id' => 3, 'name' => 'Industrial Lubricant', 'quantity' => 500, 'unit' => 'L', 'status' => 'Healthy'],
            ['id' => 4, 'name' => 'Copper Wiring', 'quantity' => 10, 'unit' => 'm', 'status' => 'Critical'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.widgets.raw-material-availability');
    }
}
