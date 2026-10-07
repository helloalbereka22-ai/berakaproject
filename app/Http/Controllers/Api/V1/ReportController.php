<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\AuthorizationService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Reports
 * Business intelligence endpoints. All reports are business-scoped and support filtering.
 */
class ReportController extends ApiController
{
    public function __construct(
        private readonly ReportService        $reportService,
        private readonly AuthorizationService $authService,
    ) {}

    // ── Shared filter builder ─────────────────────────────────────────────

    private function buildFilters(Request $request): array
    {
        $user    = $request->user();
        $filters = array_filter([
            'date_from'    => $request->input('from'),
            'date_to'      => $request->input('to'),
            'product_id'   => $request->integer('product_id') ?: null,
            'category_id'  => $request->integer('category_id') ?: null,
            'batch_id'     => $request->integer('batch_id') ?: null,
        ]);

        // business_id — verify access and scope
        $requestedBusiness = $request->integer('business_id') ?: null;
        if ($requestedBusiness) {
            $this->authService->assertBusinessAccess($user, $requestedBusiness);
            $filters['business_id'] = $requestedBusiness;
        } elseif (!$user->isSuperAdmin()) {
            // Non-super admins are always scoped to their first assigned business
            $ids = $user->assignedBusinessIds();
            if (!empty($ids)) $filters['business_id'] = $ids[0];
        }

        $requestedBranch = $request->integer('branch_id') ?: null;
        if ($requestedBranch) {
            $filters['branch_id'] = $requestedBranch;
        }

        return $filters;
    }

    /**
     * Executive KPIs
     *
     * High-level platform KPIs for the executive dashboard.
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam branch_id int Filter by branch.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     */
    public function kpis(Request $request): JsonResponse
    {
        return $this->ok($this->reportService->getDashboardKpis($this->buildFilters($request)));
    }

    /**
     * Sales report
     *
     * @authenticated
     * @queryParam business_id int required for non-admins.
     * @queryParam branch_id int optional.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     * @queryParam category_id int Filter by category.
     * @queryParam product_id int Filter by product.
     */
    public function sales(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesReport($this->buildFilters($request));

        return $this->ok([
            'summary'     => $data['totals'],
            'top_products'=> $data['topProducts'],
            'by_business' => $data['byBusiness'],
            'by_branch'   => $data['byBranch'],
            'trend'       => $data['trend'],
        ]);
    }

    /**
     * Profit report
     *
     * @authenticated
     * @queryParam business_id int required for non-admins.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     */
    public function profit(Request $request): JsonResponse
    {
        $data = $this->reportService->getProfitReport($this->buildFilters($request));

        return $this->ok([
            'summary'         => $data['totals'],
            'by_product'      => $data['byProduct'],
            'by_category'     => $data['byCategory'],
            'by_business'     => $data['byBusiness'],
            'trend'           => $data['trend'],
            'batch_profits'   => $data['batchProfits'],
        ]);
    }

    /**
     * Inventory report
     *
     * @authenticated
     * @queryParam business_id int required for non-admins.
     * @queryParam branch_id int optional.
     * @queryParam warehouse_id int optional.
     */
    public function inventory(Request $request): JsonResponse
    {
        $data = $this->reportService->getInventoryReport($this->buildFilters($request));

        return $this->ok([
            'total_value'      => $data['totalValue'],
            'low_stock_count'  => count($data['lowStock']),
            'out_of_stock_count'=> count($data['outOfStock']),
            'low_stock'        => $data['lowStock']->values(),
            'out_of_stock'     => $data['outOfStock']->values(),
            'movement_summary' => $data['movementSummary'],
        ]);
    }

    /**
     * Manufacturing report
     *
     * @authenticated
     * @queryParam business_id int required for non-admins.
     * @queryParam batch_id int Filter by specific batch.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     */
    public function manufacturing(Request $request): JsonResponse
    {
        $data = $this->reportService->getManufacturingReport($this->buildFilters($request));

        return $this->ok([
            'summary'                => $data['summary'],
            'consumption_by_category'=> $data['consumptionByCategory'],
            'batches'                => $data['batchData'],
        ]);
    }

    /**
     * Purchasing report
     *
     * @authenticated
     * @queryParam business_id int required for non-admins.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     */
    public function purchasing(Request $request): JsonResponse
    {
        $data = $this->reportService->getPurchasingReport($this->buildFilters($request));

        return $this->ok([
            'summary'     => $data['totals'],
            'by_material' => $data['byMaterial'],
            'by_category' => $data['byCategory'],
            'trend'       => $data['trend'],
        ]);
    }

    /**
     * Branch performance report
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     */
    public function branches(Request $request): JsonResponse
    {
        $data = $this->reportService->getBranchReport($this->buildFilters($request));

        return $this->ok(['branches' => $data['branches']]);
    }

    /**
     * Retail period sales — daily / weekly / monthly
     *
     * Returns pre-computed KPIs for three time windows in one call.
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam branch_id int Filter by branch.
     */
    public function retailPeriodSales(Request $request): JsonResponse
    {
        return $this->ok(
            $this->reportService->getRetailPeriodSales($this->buildFilters($request))
        );
    }

    /**
     * Retail branch performance
     *
     * Per-branch: product count, purchased qty, sold qty, available qty,
     * daily/weekly/monthly sales. All from real transactions.
     *
     * @authenticated
     * @queryParam business_id int Filter by business.
     * @queryParam branch_id int Filter to a single branch.
     */
    public function retailBranchReport(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request);

        return $this->ok(
            $this->reportService->getRetailBranchReport(
                $filters['business_id'] ?? null,
                $filters['branch_id']   ?? null,
            )
        );
    }

    /**
     * Retail branch product drill-down
     *
     * Select Business → Branch → Product.
     * Returns: purchased, transferred_in, sold, transferred_out, available.
     * All values from inventory movements — no duplicate stock tables.
     *
     * @authenticated
     * @queryParam branch_id int required The branch to report on.
     * @queryParam product_id int optional Drill down to a single product.
     */
    public function retailBranchProduct(Request $request): JsonResponse
    {
        $request->validate(['branch_id' => 'required|integer|exists:branches,id']);

        $filters = $this->buildFilters($request);
        $user    = $request->user();

        // Non-super admins can only view their own branches
        if (!$user->isSuperAdmin() && !$user->hasPermission('business.update')) {
            $branchIds = $user->assignedBranchIds();
            if (!in_array((int) $request->branch_id, $branchIds)) {
                return $this->error('Access denied to this branch.', 403);
            }
        }

        return $this->ok(
            $this->reportService->getRetailBranchProductReport(
                (int) $request->branch_id,
                $request->integer('product_id') ?: null,
            )
        );
    }

    /**
     * Export report as CSV
     *
     * @authenticated
     * @urlParam type string required Report type: sales|profit|inventory|manufacturing|purchasing|branches.
     * @queryParam business_id int required for non-admins.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     */
    public function exportCsv(Request $request, string $type): StreamedResponse
    {
        $allowed = ['sales', 'profit', 'inventory', 'manufacturing', 'purchasing', 'branches'];
        abort_unless(in_array($type, $allowed), 404, 'Unknown report type.');

        $filters = $this->buildFilters($request);

        $data = match ($type) {
            'sales'         => $this->reportService->getSalesReport($filters),
            'profit'        => $this->reportService->getProfitReport($filters),
            'inventory'     => $this->reportService->getInventoryReport($filters),
            'manufacturing' => $this->reportService->getManufacturingReport($filters),
            'purchasing'    => $this->reportService->getPurchasingReport($filters),
            'branches'      => $this->reportService->getBranchReport($filters),
        };

        $export   = $this->reportService->buildCsvExport($type, $data);
        $filename = "{$type}_report_" . now()->format('Y-m-d') . '.csv';
        $csv      = $this->reportService->exportCsv($export['headers'], $export['rows']);

        return response()->streamDownload(
            fn() => print($csv),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }
}
