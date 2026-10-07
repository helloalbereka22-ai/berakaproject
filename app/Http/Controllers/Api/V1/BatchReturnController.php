<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BatchReturn;
use App\Http\Resources\BatchReturnResource;
use Illuminate\Http\Request;

class BatchReturnController extends Controller
{
    public function index(Request $request)
    {
        return BatchReturnResource::collection(BatchReturn::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['production_batch_id'=>'required|integer|exists:production_batches,id','product_id'=>'required|integer|exists:products,id','quantity'=>'required|numeric|min:0.0001']);
        $batchReturn = BatchReturn::create($data);
        return new BatchReturnResource($batchReturn);
    }

    public function show(BatchReturn $batchReturn)
    {
        return new BatchReturnResource($batchReturn);
    }

    public function update(Request $request, BatchReturn $batchReturn)
    {
        $data = $request->validate(['production_batch_id'=>'required|integer|exists:production_batches,id','product_id'=>'required|integer|exists:products,id','quantity'=>'required|numeric|min:0.0001']);
        $batchReturn->update($data);
        return new BatchReturnResource($batchReturn);
    }

    public function destroy(BatchReturn $batchReturn)
    {
        $batchReturn->delete();
        return response()->json(['message' => 'BatchReturn deleted.']);
    }
}
