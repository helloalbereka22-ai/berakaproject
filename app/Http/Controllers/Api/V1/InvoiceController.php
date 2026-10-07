<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Invoices
 * Invoices are generated automatically on checkout. Business-scoped.
 */
class InvoiceController extends ApiController
{
    public function __construct(private readonly AuthorizationService $authService) {}

    /**
     * List invoices
     *
     * @authenticated
     * @queryParam order_id int Filter by order.
     * @queryParam customer_id int Filter by customer.
     * @queryParam status string Filter by status (pending|issued|paid|cancelled).
     * @queryParam business_id int Filter by business.
     * @queryParam from string Issued date from (Y-m-d).
     * @queryParam to string Issued date to (Y-m-d).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Invoice::with(['order.customer'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('order', fn($oq) => $oq->whereIn('business_id', $businessIds));
            })
            ->when($request->filled('order_id'),    fn($q) => $q->where('order_id', $request->order_id))
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('status'),      fn($q) => $q->where('status', $request->status))
            ->when($request->filled('from'),        fn($q) => $q->where('issued_at', '>=', $request->from))
            ->when($request->filled('to'),          fn($q) => $q->where('issued_at', '<=', $request->to . ' 23:59:59'))
            ->orderByDesc('issued_at');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(InvoiceResource::collection($query->paginate($perPage)));
    }

    /**
     * Get invoice
     *
     * @authenticated
     * @urlParam invoice int required The invoice ID.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->assertInvoiceAccess($request, $invoice);

        return $this->ok(new InvoiceResource(
            $invoice->load(['order.customer', 'order.items.product', 'payments'])
        ));
    }

    /**
     * Update invoice notes / status
     *
     * @authenticated
     * @urlParam invoice int required The invoice ID.
     * @bodyParam status string (pending|issued|paid|cancelled).
     * @bodyParam notes string optional.
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $this->assertInvoiceAccess($request, $invoice);

        $data = $request->validate([
            'status' => 'sometimes|in:pending,issued,paid,cancelled',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $invoice->update($data);

        return $this->ok(new InvoiceResource($invoice->load(['order', 'payments'])));
    }

    /**
     * Delete invoice
     *
     * Invoices can only be deleted if the order is pending.
     *
     * @authenticated
     * @urlParam invoice int required The invoice ID.
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->assertInvoiceAccess($request, $invoice);

        if ($invoice->status === Invoice::STATUS_PAID) {
            return $this->error('Paid invoices cannot be deleted.', 409);
        }

        $invoice->delete();

        return $this->ok(null, 'Invoice deleted.');
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function assertInvoiceAccess(Request $request, Invoice $invoice): void
    {
        $businessId = $invoice->order?->business_id;
        if ($businessId) {
            $this->authService->assertBusinessAccess($request->user(), $businessId);
        } elseif (!$request->user()->isSuperAdmin()) {
            abort(403, 'Access denied.');
        }
    }
}
