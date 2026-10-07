<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BatchMaterialConsumption;
use App\Http\Resources\BatchMaterialConsumptionResource;
use Illuminate\Http\Request;

class BatchMaterialConsumptionController extends Controller
{
    public function index(Request $request)
    {
        return BatchMaterialConsumptionResource::collection(BatchMaterialConsumption::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['production_batch_id'=>'required|integer|exists:production_batches,id','raw_material_id'=>'required|integer|exists:products,id','quantity_used'=>'required|numeric|min:0.0001','unit_cost'=>'required|numeric|min:0']);
        $batchMaterialConsumption = BatchMaterialConsumption::create($data);
        return new BatchMaterialConsumptionResource($batchMaterialConsumption);
    }

    public function show(BatchMaterialConsumption $batchMaterialConsumption)
    {
        return new BatchMaterialConsumptionResource($batchMaterialConsumption);
    }

    public function update(Request $request, BatchMaterialConsumption $batchMaterialConsumption)
    {
        $data = $request->validate(['production_batch_id'=>'required|integer|exists:production_batches,id','raw_material_id'=>'required|integer|exists:products,id','quantity_used'=>'required|numeric|min:0.0001','unit_cost'=>'required|numeric|min:0']);
        $batchMaterialConsumption->update($data);
        return new BatchMaterialConsumptionResource($batchMaterialConsumption);
    }

    public function destroy(BatchMaterialConsumption $batchMaterialConsumption)
    {
        $batchMaterialConsumption->delete();
        return response()->json(['message' => 'BatchMaterialConsumption deleted.']);
    }
}
