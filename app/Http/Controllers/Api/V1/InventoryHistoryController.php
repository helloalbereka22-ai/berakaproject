<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Http\Resources\InventoryMovementResource;
use Illuminate\Http\Request;

/**
 * Inventory History has been replaced by the Stock Movements ledger.
 * This controller is a compatibility shim that forwards requests to
 * StockMovement so existing API consumers do not receive 500 errors.
 *
 * Consumers should migrate to GET /api/v1/stock-movements.
 */
class InventoryHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = StockMovement::query()
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('location_id'), fn($q) =>
                $q->where('location_id', $request->location_id)
            )
            ->when($request->filled('item_id'), fn($q) =>
                $q->where('item_id', $request->item_id)
            )
            ->orderByDesc('moved_at');

        return InventoryMovementResource::collection($query->paginate(50));
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'InventoryHistory is deprecated. Use POST /api/v1/stock-movements/adjustment instead.',
        ], 410);
    }

    public function show(int $id)
    {
        $movement = StockMovement::findOrFail($id);
        return new InventoryMovementResource($movement);
    }

    public function update(Request $request, int $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'InventoryHistory is deprecated and cannot be updated.',
        ], 410);
    }

    public function destroy(int $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'InventoryHistory records are immutable. Use stock adjustment workflow instead.',
        ], 410);
    }
}
