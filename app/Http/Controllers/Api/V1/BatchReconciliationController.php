<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BatchReconciliation;
use App\Http\Resources\BatchReconciliationResource;
use Illuminate\Http\Request;

class BatchReconciliationController extends Controller
{
    public function index(Request $request)
    {
        return BatchReconciliationResource::collection(BatchReconciliation::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['production_batch_id'=>'required|integer|exists:production_batches,id','produced_quantity'=>'required|numeric|min:0']);
        $batchReconciliation = BatchReconciliation::create($data);
        return new BatchReconciliationResource($batchReconciliation);
    }

    public function show(BatchReconciliation $batchReconciliation)
    {
        return new BatchReconciliationResource($batchReconciliation);
    }

    public function update(Request $request, BatchReconciliation $batchReconciliation)
    {
        $data = $request->validate(['production_batch_id'=>'required|integer|exists:production_batches,id','produced_quantity'=>'required|numeric|min:0']);
        $batchReconciliation->update($data);
        return new BatchReconciliationResource($batchReconciliation);
    }

    public function destroy(BatchReconciliation $batchReconciliation)
    {
        $batchReconciliation->delete();
        return response()->json(['message' => 'BatchReconciliation deleted.']);
    }
}
