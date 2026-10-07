<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Location;
use App\Services\AuthorizationService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API endpoint that returns current stock levels derived dynamically
 * from the stock_movements ledger.
 */
class StockLevelController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuthorizationService $authService,
    ) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->join('locations', 'stock_movements.location_id', '=', 'locations.id')
            ->leftJoin('branches', 'locations.branch_id', '=', 'branches.id')
            ->leftJoin('businesses', 'branches.business_id', '=', 'businesses.id')
            ->when(!$user->isSuperAdmin(), fn($q) =>
                $q->whereIn('stock_movements.business_id', $user->assignedBusinessIds())
            );

        if ($request->filled('business_id')) {
            $this->authService->assertBusinessAccess($user, (int) $request->business_id);
            $query->where('stock_movements.business_id', $request->business_id);
        }
        if ($request->filled('branch_id'))   $query->where('stock_movements.branch_id', $request->branch_id);
        if ($request->filled('location_id')) $query->where('stock_movements.location_id', $request->location_id);
        if ($request->filled('item_id'))     $query->where('stock_movements.item_id', $request->item_id);

        $query->select(
            'stock_movements.location_id',
            'locations.name as location_name',
            'businesses.id as business_id',
            'businesses.name as business_name',
            'products.id as item_id',
            'products.name as item_name',
            'products.sku',
            'products.type as item_type_label',
            'products.unit',
            'products.cost_price',
            'products.reorder_point',
            DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as quantity_on_hand"),
            DB::raw("MAX(stock_movements.moved_at) as last_movement_at")
        )
        ->groupBy(
            'stock_movements.location_id', 'locations.name',
            'businesses.id', 'businesses.name',
            'products.id', 'products.name', 'products.sku', 'products.type',
            'products.unit', 'products.cost_price', 'products.reorder_point'
        );

        // Filter low / out-of-stock after aggregation
        if ($request->boolean('low_stock')) {
            $query->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) > 0")
                  ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <= products.reorder_point");
        }
        if ($request->boolean('out_of_stock')) {
            $query->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <= 0");
        }

        $paginated = $query->orderByDesc('last_movement_at')->paginate(50);

        // Shape the response to match the old StockLevelResource contract
        $paginated->through(fn($r) => [
            'location_id'      => $r->location_id,
            'location_name'    => $r->location_name,
            'business_id'      => $r->business_id,
            'business_name'    => $r->business_name,
            'item_id'          => $r->item_id,
            'item_name'        => $r->item_name,
            'item_sku'         => $r->sku,
            'item_type'        => 'product',
            'unit'             => $r->unit,
            'quantity_on_hand' => (float) $r->quantity_on_hand,
            'cost_price'       => (float) $r->cost_price,
            'stock_value'      => round((float) $r->quantity_on_hand * (float) $r->cost_price, 4),
            'reorder_point'    => (float) $r->reorder_point,
            'is_low_stock'     => $r->quantity_on_hand > 0 && $r->quantity_on_hand <= $r->reorder_point,
            'is_out_of_stock'  => $r->quantity_on_hand <= 0,
            'last_movement_at' => $r->last_movement_at,
        ]);

        return response()->json($paginated);
    }

    /**
     * Show the current stock for a single product at a specific location.
     * Route: GET /stock-levels/{productId}?location_id={id}
     */
    public function show(Request $request, int $productId)
    {
        $locationId = $request->query('location_id');

        $query = StockMovement::where('item_type', 'product')
            ->where('item_id', $productId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));

        $qty = $query->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));

        $product  = \App\Models\Product::findOrFail($productId);
        $location = $locationId ? Location::find($locationId) : null;

        return response()->json([
            'item_id'          => $product->id,
            'item_name'        => $product->name,
            'item_sku'         => $product->sku,
            'item_type'        => 'product',
            'unit'             => $product->unit,
            'location_id'      => $locationId,
            'location_name'    => $location?->name,
            'quantity_on_hand' => (float) $qty,
            'cost_price'       => (float) $product->cost_price,
            'stock_value'      => round((float) $qty * (float) $product->cost_price, 4),
            'reorder_point'    => (float) $product->reorder_point,
            'is_low_stock'     => $qty > 0 && $qty <= $product->reorder_point,
            'is_out_of_stock'  => $qty <= 0,
        ]);
    }

    public function reconcile(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'item_type'   => 'required|string',
            'item_id'     => 'required|integer',
        ]);

        $result = $this->inventory->reconcileBalance(
            $data['location_id'], $data['item_type'], (int) $data['item_id']
        );

        return response()->json($result);
    }

    public function valuation(Request $request)
    {
        $user = $request->user();

        if ($request->filled('business_id')) {
            $this->authService->assertBusinessAccess($user, (int) $request->business_id);
            return response()->json(
                $this->inventory->valuationByBusiness((int) $request->business_id)
            );
        }

        if ($request->filled('location_id')) {
            return response()->json(
                $this->inventory->valuationByLocation((int) $request->location_id)
            );
        }

        // Super admin: platform-wide summary
        if ($user->isSuperAdmin()) {
            $businessIds = \App\Models\Business::pluck('id');
            $totals = $businessIds->map(fn($id) => $this->inventory->valuationByBusiness($id));
            return response()->json([
                'total_value'  => $totals->sum('total_value'),
                'total_items'  => $totals->sum('total_items'),
                'low_stock'    => $totals->sum('low_stock'),
                'out_of_stock' => $totals->sum('out_of_stock'),
            ]);
        }

        $ids = $user->assignedBusinessIds();
        $totals = collect($ids)->map(fn($id) => $this->inventory->valuationByBusiness($id));
        return response()->json([
            'total_value'  => $totals->sum('total_value'),
            'total_items'  => $totals->sum('total_items'),
            'low_stock'    => $totals->sum('low_stock'),
            'out_of_stock' => $totals->sum('out_of_stock'),
        ]);
    }
}
