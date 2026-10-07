<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Http\Resources\ApprovalRequestResource;
use Illuminate\Http\Request;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        return ApprovalRequestResource::collection(ApprovalRequest::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['workflow_id'=>'required|integer|exists:approval_workflows,id','requestable_type'=>'required|string','requestable_id'=>'required|integer']);
        $approvalRequest = ApprovalRequest::create($data);
        return new ApprovalRequestResource($approvalRequest);
    }

    public function show(ApprovalRequest $approvalRequest)
    {
        return new ApprovalRequestResource($approvalRequest);
    }

    public function update(Request $request, ApprovalRequest $approvalRequest)
    {
        $data = $request->validate(['workflow_id'=>'required|integer|exists:approval_workflows,id','requestable_type'=>'required|string','requestable_id'=>'required|integer']);
        $approvalRequest->update($data);
        return new ApprovalRequestResource($approvalRequest);
    }

    public function destroy(ApprovalRequest $approvalRequest)
    {
        $approvalRequest->delete();
        return response()->json(['message' => 'ApprovalRequest deleted.']);
    }
}
