<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\AuthorizationService;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuthorizationService $authService,
    ) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = StockMovement::with(['location.branch', 'creator', 'item'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->orderByDesc('moved_at');

        if ($request->filled('business_id')) {
            $this->authService->assertBusinessAccess($user, (int) $request->business_id);
            $query->where('business_id', $request->business_id);
        }
        if ($request->filled('location_id'))    $query->where('location_id', $request->location_id);
        if ($request->filled('item_id'))        $query->where('item_id', $request->item_id);
        if ($request->filled('movement_type'))  $query->where('movement_type', $request->movement_type);
        if ($request->filled('direction'))      $query->where('direction', $request->direction);
        if ($request->filled('from'))           $query->where('moved_at', '>=', $request->from);
        if ($request->filled('to'))             $query->where('moved_at', '<=', $request->to);

        return StockMovementResource::collection($query->paginate(50));
    }

    public function show(Request $request, StockMovement $stockMovement)
    {
        if ($stockMovement->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $stockMovement->business_id);
        }
        return new StockMovementResource($stockMovement->load(['location.branch', 'creator']));
    }

    /**
     * Record a manual opening-stock movement.
     */
    public function storeOpening(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'item_type'   => 'required|string|in:product',
            'item_id'     => 'required|integer|exists:products,id',
            'quantity'    => 'required|numeric|min:0.0001',
            'unit_cost'   => 'nullable|numeric|min:0',
        ]);

        $movement = $this->inventory->setOpeningStock(
            $data['location_id'],
            $data['item_type'],
            $data['item_id'],
            (float) $data['quantity'],
            (float) ($data['unit_cost'] ?? 0)
        );

        return new StockMovementResource($movement);
    }

    /**
     * Record a manual stock adjustment (increase or decrease).
     */
    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'item_type'   => 'required|string|in:product',
            'item_id'     => 'required|integer|exists:products,id',
            'direction'   => 'required|string|in:increase,decrease',
            'quantity'    => 'required|numeric|min:0.0001',
            'reason'      => 'required|string|max:500',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $adjustment = $this->inventory->applyAdjustment(
            $data['location_id'],
            $data['item_type'],
            $data['item_id'],
            $data['direction'],
            (float) $data['quantity'],
            $data['reason'],
            $data['notes'] ?? ''
        );

        return response()->json([
            'message'    => 'Adjustment applied.',
            'adjustment' => $adjustment,
        ]);
    }

    /**
     * Transfer stock between two locations.
     */
    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'from_location_id' => 'required|integer|exists:locations,id|different:to_location_id',
            'to_location_id'   => 'required|integer|exists:locations,id',
            'item_type'        => 'required|string|in:product',
            'item_id'          => 'required|integer|exists:products,id',
            'quantity'         => 'required|numeric|min:0.0001',
            'notes'            => 'nullable|string|max:500',
        ]);

        try {
            $transfer = $this->inventory->transfer(
                $data['from_location_id'],
                $data['to_location_id'],
                $data['item_type'],
                $data['item_id'],
                (float) $data['quantity'],
                $data['notes'] ?? ''
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'  => 'Transfer completed.',
            'transfer' => $transfer,
        ]);
    }
}
