<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Business;
use Livewire\Component;

class BusinessOverview extends Component
{
    public $businesses = [];

    public function mount()
    {
        // Fetch top 5 businesses with branch counts
        $this->businesses = Business::withCount('branches')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.widgets.business-overview');
    }
}
