<?php

namespace App\Livewire\Admin\Requests;

use App\Models\ApprovalRequest;
use App\Services\WorkflowService;
use Livewire\Component;

class RequestQueue extends Component
{
    public $requests;

    public function mount()
    {
        // Load pending requests
        $this->loadRequests();
    }

    public function loadRequests()
    {
        $this->requests = ApprovalRequest::with(['business', 'branch', 'workflow'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function approveRequest(int $requestId, WorkflowService $workflowService)
    {
        try {
            // For demo purposes, assuming authenticated user ID is 1 (Super Admin)
            $workflowService->approveRequest($requestId, 1);
            
            session()->flash('success', "Request #{$requestId} approved successfully and actions executed.");
            $this->loadRequests();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function rejectRequest(int $requestId)
    {
        $request = ApprovalRequest::find($requestId);
        if ($request) {
            $request->update([
                'status' => 'rejected',
                'reviewed_by' => 1,
            ]);
            session()->flash('success', "Request #{$requestId} has been rejected.");
            $this->loadRequests();
        }
    }

    public function render()
    {
        return view('livewire.admin.requests.request-queue');
    }
}
