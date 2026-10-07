<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Http\Resources\InventoryMovementResource;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function index(Request $request)
    {
        return InventoryMovementResource::collection(InventoryMovement::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id','warehouse_id'=>'required|integer','type'=>'required|string','quantity_change'=>'required|numeric','reason'=>'nullable|string']);
        $inventoryMovement = InventoryMovement::create($data);
        return new InventoryMovementResource($inventoryMovement);
    }

    public function show(InventoryMovement $inventoryMovement)
    {
        return new InventoryMovementResource($inventoryMovement);
    }

    public function update(Request $request, InventoryMovement $inventoryMovement)
    {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id','warehouse_id'=>'required|integer','type'=>'required|string','quantity_change'=>'required|numeric','reason'=>'nullable|string']);
        $inventoryMovement->update($data);
        return new InventoryMovementResource($inventoryMovement);
    }

    public function destroy(InventoryMovement $inventoryMovement)
    {
        $inventoryMovement->delete();
        return response()->json(['message' => 'InventoryMovement deleted.']);
    }
}
