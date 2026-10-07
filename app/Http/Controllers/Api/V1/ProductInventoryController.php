<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Http\Resources\InventoryMovementResource;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ProductInventory has been replaced by stock_movements-based inventory.
 * This controller is a compatibility shim. Write endpoints return 410 Gone.
 * Read endpoints return the current calculated stock per product/location.
 *
 * Consumers should migrate to GET /api/v1/stock-levels.
 */
class ProductInventoryController extends Controller
{
    public function __construct(private readonly AuthorizationService $authService) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds())
            )
            ->when($request->filled('product_id'), fn($q) =>
                $q->where('stock_movements.item_id', $request->product_id)
            )
            ->select(
                'stock_movements.item_id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.unit',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as quantity")
            )
            ->groupBy('stock_movements.item_id', 'products.name', 'products.sku', 'products.unit')
            ->having('quantity', '>', 0);

        return response()->json($query->paginate(50));
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'ProductInventory is deprecated. Use POST /api/v1/stock-movements/opening to set opening stock.',
        ], 410);
    }

    public function show(Request $request, int $productId)
    {
        $user = $request->user();
        $qty  = StockMovement::where('item_type', 'product')
            ->where('item_id', $productId)
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('business_id', $user->assignedBusinessIds())
            )
            ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));

        $product = \App\Models\Product::findOrFail($productId);

        return response()->json([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'sku'          => $product->sku,
            'unit'         => $product->unit,
            'quantity'     => (float) $qty,
        ]);
    }

    public function update(Request $request, int $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'ProductInventory updates are not allowed. Use the adjustment workflow instead.',
        ], 410);
    }

    public function destroy(int $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'ProductInventory records cannot be deleted. They are derived from stock movements.',
        ], 410);
    }
}
