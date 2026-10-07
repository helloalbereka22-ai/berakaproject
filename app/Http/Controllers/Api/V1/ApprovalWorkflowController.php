<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApprovalWorkflow;
use App\Http\Resources\ApprovalWorkflowResource;
use Illuminate\Http\Request;

class ApprovalWorkflowController extends Controller
{
    public function index(Request $request)
    {
        return ApprovalWorkflowResource::collection(ApprovalWorkflow::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:255','is_active'=>'nullable|boolean']);
        $approvalWorkflow = ApprovalWorkflow::create($data);
        return new ApprovalWorkflowResource($approvalWorkflow);
    }

    public function show(ApprovalWorkflow $approvalWorkflow)
    {
        return new ApprovalWorkflowResource($approvalWorkflow);
    }

    public function update(Request $request, ApprovalWorkflow $approvalWorkflow)
    {
        $data = $request->validate(['name'=>'required|string|max:255','is_active'=>'nullable|boolean']);
        $approvalWorkflow->update($data);
        return new ApprovalWorkflowResource($approvalWorkflow);
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow)
    {
        $approvalWorkflow->delete();
        return response()->json(['message' => 'ApprovalWorkflow deleted.']);
    }
}
