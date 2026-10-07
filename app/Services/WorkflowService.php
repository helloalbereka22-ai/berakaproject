<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use Exception;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    /**
     * Submit a new request to the approval queue.
     */
    public function submitRequest(
        int $businessId,
        int $workflowId,
        string $type,
        array $payload,
        int $requestedBy,
        ?int $branchId = null,
        ?string $reason = null,
        ?string $notes = null
    ): ApprovalRequest {
        return ApprovalRequest::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'workflow_id' => $workflowId,
            'type' => $type,
            'status' => 'submitted',
            'payload' => $payload,
            'requested_by' => $requestedBy,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    /**
     * Approve a request and execute its payload via the appropriate service.
     */
    public function approveRequest(int $requestId, int $approvedByUserId): ApprovalRequest
    {
        return DB::transaction(function () use ($requestId, $approvedByUserId) {
            $request = ApprovalRequest::findOrFail($requestId);

            if ($request->status !== 'submitted' && $request->status !== 'under_review') {
                throw new Exception("Only submitted or under review requests can be approved.");
            }

            // Execute based on request type
            switch ($request->type) {
                case 'Additional Stock':
                    $this->executeStockIncrease($request, $approvedByUserId);
                    break;
                case 'Stock Transfer':
                    $this->executeStockTransfer($request, $approvedByUserId);
                    break;
                case 'New Product':
                    $this->executeNewProduct($request, $approvedByUserId);
                    break;
                case 'Product Update':
                    $this->executeProductUpdate($request, $approvedByUserId);
                    break;
                case 'Product Removal':
                    $this->executeProductRemoval($request, $approvedByUserId);
                    break;
                case 'Stock Adjustment':
                    $this->executeStockAdjustment($request, $approvedByUserId);
                    break;
                default:
                    // Support future HR, CRM, etc. module execution here via plugins or event listeners
                    event('workflow.approved.' . $request->type, [$request]);
                    break;
            }

            $request->status = 'approved';
            $request->approved_by = $approvedByUserId;
            $request->approval_date = now();
            $request->save();

            return $request;
        });
    }

    private function executeStockIncrease(ApprovalRequest $request, int $approvedByUserId)
    {
        $payload = $request->payload;
        
        if (!isset($payload['product_id']) || !isset($payload['quantity']) || !isset($payload['location_id'])) {
            throw new Exception("Invalid payload for Additional Stock request.");
        }

        $inventoryService = app(InventoryService::class);
        $inventoryService->increaseStock(
            $payload['product_id'],
            $request->business_id,
            $payload['location_id'],
            $payload['quantity'],
            $request->reason ?? 'Approved additional stock request',
            'REQ-' . $request->id,
            $approvedByUserId
        );
    }
    
    private function executeStockTransfer(ApprovalRequest $request, int $approvedByUserId)
    {
        $payload = $request->payload;
        $inventoryService = app(InventoryService::class);
        $inventoryService->transferStock(
            $payload['product_id'],
            $request->business_id,
            $payload['from_location_id'],
            $payload['to_location_id'],
            $payload['quantity'],
            $request->reason ?? 'Approved stock transfer',
            'REQ-' . $request->id,
            $approvedByUserId
        );
    }

    private function executeNewProduct(ApprovalRequest $request, int $approvedByUserId)
    {
        $payload = $request->payload;
        $productService = app(ProductService::class);
        
        $productService->createOrIncrementProduct(
            $request->business_id,
            $payload['product_data'],
            $payload['location_id'] ?? null,
            $payload['initial_stock'] ?? 0,
            $approvedByUserId
        );
    }

    private function executeProductUpdate(ApprovalRequest $request, int $approvedByUserId)
    {
        $payload = $request->payload;
        $productService = app(ProductService::class);
        $productService->updateProduct(
            $payload['product_id'],
            $payload['product_data'],
            $approvedByUserId
        );
    }

    private function executeProductRemoval(ApprovalRequest $request, int $approvedByUserId)
    {
        $payload = $request->payload;
        $productService = app(ProductService::class);
        // Soft delete or detach from business
        $productService->removeProductFromBusiness(
            $payload['product_id'],
            $request->business_id,
            $approvedByUserId
        );
    }

    private function executeStockAdjustment(ApprovalRequest $request, int $approvedByUserId)
    {
        $payload = $request->payload;
        $inventoryService = app(InventoryService::class);
        $inventoryService->adjustStock(
            $payload['product_id'],
            $request->business_id,
            $payload['location_id'],
            $payload['quantity'], // Can be negative or positive
            $request->reason ?? 'Approved stock adjustment',
            'REQ-' . $request->id,
            $approvedByUserId
        );
    }
}
