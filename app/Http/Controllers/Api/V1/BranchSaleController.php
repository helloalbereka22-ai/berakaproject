<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BranchSaleResource;
use App\Models\BranchSale;
use App\Models\Branch;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Branch Sales (POS)
 * Direct branch point-of-sale transactions. Branch-scoped.
 */
class BranchSaleController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List branch sales
     *
     * @authenticated
     * @queryParam branch_id int Filter by branch.
     * @queryParam product_id int Filter by product.
     * @queryParam from string Sale date from (Y-m-d).
     * @queryParam to string Sale date to (Y-m-d).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = BranchSale::with(['branch.business', 'product.category', 'recorder'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereIn('branch_id',
                    Branch::whereIn('business_id', $user->assignedBusinessIds())->pluck('id')
                );
            })
            ->when($request->filled('branch_id'), function ($q) use ($request, $user) {
                $branch = Branch::findOrFail((int) $request->branch_id);
                $this->authService->assertBusinessAccess($user, $branch->business_id);
                $q->where('branch_id', $request->branch_id);
            })
            ->when($request->filled('product_id'), fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('from'),       fn($q) => $q->where('sale_date', '>=', $request->from))
            ->when($request->filled('to'),         fn($q) => $q->where('sale_date', '<=', $request->to))
            ->orderByDesc('sale_date');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(BranchSaleResource::collection($query->paginate($perPage)));
    }

    /**
     * Record a POS sale
     *
     * @authenticated
     * @bodyParam branch_id int required
     * @bodyParam location_id int required Warehouse location within the branch.
     * @bodyParam product_id int required
     * @bodyParam quantity numeric required
     * @bodyParam unit_price numeric required Selling price per unit.
     * @bodyParam cost_price numeric required Cost price per unit (for profit calculation).
     * @bodyParam sale_date date required (Y-m-d).
     * @bodyParam production_batch_id int optional Linked batch.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id'           => 'required|integer|exists:branches,id',
            'location_id'         => 'required|integer|exists:locations,id',
            'product_id'          => 'required|integer|exists:products,id',
            'quantity'            => 'required|numeric|min:0.0001',
            'unit_price'          => 'required|numeric|min:0',
            'cost_price'          => 'required|numeric|min:0',
            'sale_date'           => 'required|date',
            'production_batch_id' => 'nullable|integer|exists:production_batches,id',
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $this->authService->assertBusinessAccess($request->user(), $branch->business_id);

        $data['total_amount'] = round((float) $data['unit_price'] * (float) $data['quantity'], 4);
        $data['recorded_by']  = $request->user()->id;

        $sale = BranchSale::create($data);

        AuditService::logCreate('App\\Models\\BranchSale', $sale, $branch->business_id, $branch->id);

        return $this->created(new BranchSaleResource($sale->load(['branch', 'product'])));
    }

    /**
     * Get branch sale
     *
     * @authenticated
     * @urlParam branch_sale int required The sale ID.
     */
    public function show(Request $request, BranchSale $branchSale): JsonResponse
    {
        $this->authService->assertBusinessAccess(
            $request->user(), $branchSale->branch->business_id
        );

        return $this->ok(new BranchSaleResource($branchSale->load(['branch.business', 'product.category', 'batch', 'recorder'])));
    }

    /**
     * Delete branch sale
     *
     * @authenticated
     * @urlParam branch_sale int required The sale ID.
     */
    public function destroy(Request $request, BranchSale $branchSale): JsonResponse
    {
        $this->authService->assertBusinessAccess(
            $request->user(), $branchSale->branch->business_id
        );

        AuditService::logDelete('App\\Models\\BranchSale', $branchSale, $branchSale->branch->business_id);
        $branchSale->delete();

        return $this->ok(null, 'Branch sale deleted.');
    }
}
