<?php

namespace App\Livewire\Business\BookStore;

use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Services\WorkflowService;
use Livewire\Component;
use Livewire\WithPagination;

class RequestManager extends Component
{
    use WithPagination;

    public $businessId;
    public $branchId;
    
    public $showNewRequestModal = false;
    public $requestType = 'Additional Stock';
    public $reason = '';
    public $notes = '';
    
    // Dynamic payload fields
    public $productId;
    public $quantity;
    
    protected $rules = [
        'requestType' => 'required|string',
        'reason' => 'required|string|max:255',
        'notes' => 'nullable|string',
    ];

    public function mount()
    {
        $this->businessId = auth()->user()->roles()->first()->pivot->business_id ?? 1;
        $this->branchId = auth()->user()->roles()->first()->pivot->branch_id ?? null;
    }

    public function submitRequest(WorkflowService $workflowService)
    {
        $this->validate();
        
        // Find or create workflow
        $workflow = ApprovalWorkflow::firstOrCreate(
            ['name' => $this->requestType . ' Request', 'module' => 'inventory'],
            ['is_active' => true]
        );

        $payload = [];
        if (in_array($this->requestType, ['Additional Stock', 'Stock Adjustment'])) {
            $this->validate([
                'productId' => 'required|integer',
                'quantity' => 'required|numeric'
            ]);
            $payload = [
                'product_id' => $this->productId,
                'quantity' => $this->quantity,
                'location_id' => $this->branchId
            ];
        }

        $workflowService->submitRequest(
            $this->businessId,
            $workflow->id,
            $this->requestType,
            $payload,
            auth()->id(),
            $this->branchId,
            $this->reason,
            $this->notes
        );

        $this->reset(['showNewRequestModal', 'reason', 'notes', 'productId', 'quantity']);
        $this->requestType = 'Additional Stock';
        session()->flash('success', 'Request submitted successfully and is pending Super Admin approval.');
    }

    public function render()
    {
        $requests = ApprovalRequest::where('business_id', $this->businessId)
            ->when($this->branchId, function($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.business.book-store.request-manager', [
            'requests' => $requests
        ]);
    }
}
