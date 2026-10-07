<?php

namespace App\Livewire\Admin\Requests;

use App\Services\WorkflowService;
use App\Models\ApprovalWorkflow;
use Livewire\Component;

class SubmitRequest extends Component
{
    public $businessId;
    public $branchId;
    public $workflowId;
    
    // Dynamic fields depending on workflow
    public $productId;
    public $quantity;
    public $reason;
    public $notes;
    
    public $workflows = [];

    protected $rules = [
        'workflowId' => 'required|exists:approval_workflows,id',
        'productId' => 'required_unless:workflowId,null',
        'quantity' => 'nullable|numeric|min:1',
        'reason' => 'required|string',
    ];

    public function mount(int $businessId, int $branchId)
    {
        $this->businessId = $businessId;
        $this->branchId = $branchId;
        $this->workflows = ApprovalWorkflow::where('is_active', true)->get();
    }

    public function submit(WorkflowService $workflowService)
    {
        $this->validate();
        
        $workflow = ApprovalWorkflow::find($this->workflowId);
        
        $payload = [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'location_id' => $this->branchId,
        ];
        
        try {
            // Assume authenticated user ID is 2 (Branch Seller)
            $userId = 2;
            
            $workflowService->submitRequest(
                $this->businessId,
                $this->workflowId,
                $workflow->name,
                $payload,
                $userId,
                $this->branchId,
                $this->reason,
                $this->notes
            );
            
            session()->flash('success', 'Request submitted successfully and is pending approval.');
            $this->reset(['workflowId', 'productId', 'quantity', 'reason', 'notes']);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.requests.submit-request');
    }
}
