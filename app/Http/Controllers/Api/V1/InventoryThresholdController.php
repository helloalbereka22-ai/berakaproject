<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryThreshold;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;

class InventoryThresholdController extends Controller
{
    public function __construct(private readonly AuthorizationService $authService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        return InventoryThreshold::with(['location', 'item'])
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('business_id'), fn($q) =>
                $q->where('business_id', $request->business_id)
            )
            ->when($request->filled('location_id'), fn($q) =>
                $q->where('location_id', $request->location_id)
            )
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id'   => 'required|integer|exists:businesses,id',
            'location_id'   => 'required|integer|exists:locations,id',
            'item_type'     => 'required|string|in:product',
            'item_id'       => 'required|integer',
            'reorder_point' => 'required|numeric|min:0',
            'min_stock'     => 'nullable|numeric|min:0',
            'max_stock'     => 'nullable|numeric|min:0',
            'is_active'     => 'nullable|boolean',
        ]);

        $this->authService->assertBusinessAccess($request->user(), (int) $data['business_id']);

        $threshold = InventoryThreshold::updateOrCreate(
            ['location_id' => $data['location_id'], 'item_type' => $data['item_type'], 'item_id' => $data['item_id']],
            $data
        );

        // Sync reorder_point on the product row
        if ($data['item_type'] === 'product') {
            \App\Models\Product::where('id', $data['item_id'])
                ->update(['reorder_point' => $data['reorder_point']]);
        }

        return response()->json($threshold, 201);
    }

    public function show(Request $request, InventoryThreshold $inventoryThreshold)
    {
        $this->authService->assertBusinessAccess($request->user(), $inventoryThreshold->business_id);
        return response()->json($inventoryThreshold->load(['location', 'item']));
    }

    public function update(Request $request, InventoryThreshold $inventoryThreshold)
    {
        $this->authService->assertBusinessAccess($request->user(), $inventoryThreshold->business_id);

        $data = $request->validate([
            'reorder_point' => 'sometimes|numeric|min:0',
            'min_stock'     => 'nullable|numeric|min:0',
            'max_stock'     => 'nullable|numeric|min:0',
            'is_active'     => 'nullable|boolean',
        ]);

        $inventoryThreshold->update($data);

        if (isset($data['reorder_point']) && $inventoryThreshold->item_type === 'product') {
            \App\Models\Product::where('id', $inventoryThreshold->item_id)
                ->update(['reorder_point' => $data['reorder_point']]);
        }

        return response()->json($inventoryThreshold);
    }

    public function destroy(Request $request, InventoryThreshold $inventoryThreshold)
    {
        $this->authService->assertBusinessAccess($request->user(), $inventoryThreshold->business_id);
        $inventoryThreshold->delete();
        return response()->json(['message' => 'Threshold removed.']);
    }
}
