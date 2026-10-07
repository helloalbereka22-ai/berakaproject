<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\ApprovalRequest;
use Livewire\Component;

class BusinessRecentRequests extends Component
{
    public $businessId;
    public $recentRequests = [];

    public function mount()
    {
        // $this->businessId = auth()->user()->business_id;
        $this->businessId = 1; // Mock assigned business ID
        
        $this->recentRequests = ApprovalRequest::with(['branch', 'workflow'])
            ->where('business_id', $this->businessId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.widgets.business-recent-requests');
    }
}
