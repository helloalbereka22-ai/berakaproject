<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\ApprovalRequest;
use App\Services\WorkflowService;
use Livewire\Component;

class PendingApprovalsQueue extends Component
{
    public $requests = [];

    public function mount()
    {
        $this->loadRequests();
    }

    public function loadRequests()
    {
        $this->requests = ApprovalRequest::with(['branch', 'workflow'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get();
    }

    public function approveRequest(int $requestId, WorkflowService $workflowService)
    {
        try {
            $workflowService->approveRequest($requestId, auth()->id());
            session()->flash('success', 'Request approved successfully.');
            $this->loadRequests();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve request: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.widgets.pending-approvals-queue');
    }
}
