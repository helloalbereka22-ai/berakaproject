<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Payments
 * Record and retrieve manual payments. Business-scoped.
 */
class PaymentController extends ApiController
{
    public function __construct(
        private readonly AuthorizationService $authService,
        private readonly SalesService         $salesService,
    ) {}

    /**
     * List payments
     *
     * @authenticated
     * @queryParam invoice_id int Filter by invoice.
     * @queryParam order_id int Filter by order.
     * @queryParam business_id int Filter by business.
     * @queryParam from string Date from (Y-m-d).
     * @queryParam to string Date to (Y-m-d).
     * @queryParam per_page int Items per page (default 25, max 100).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Payment::with(['invoice.order', 'recorder'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $businessIds = $user->assignedBusinessIds();
                $q->whereHas('invoice.order', fn($oq) =>
                    $oq->whereIn('business_id', $businessIds)
                );
            })
            ->when($request->filled('invoice_id'), fn($q) => $q->where('invoice_id', $request->invoice_id))
            ->when($request->filled('order_id'),   fn($q) => $q->where('order_id', $request->order_id))
            ->when($request->filled('from'),        fn($q) => $q->where('payment_date', '>=', $request->from))
            ->when($request->filled('to'),          fn($q) => $q->where('payment_date', '<=', $request->to))
            ->orderByDesc('payment_date');

        $perPage = min((int) $request->input('per_page', 25), 100);

        return $this->paginated(PaymentResource::collection($query->paginate($perPage)));
    }

    /**
     * Record a payment
     *
     * Payments are recorded manually by staff. No payment gateway.
     *
     * @authenticated
     * @bodyParam invoice_id int required The invoice to pay against.
     * @bodyParam amount numeric required Must not exceed the balance due.
     * @bodyParam payment_method string required (cash|bank_transfer|cheque|other).
     * @bodyParam payment_date date required Date of payment (Y-m-d).
     * @bodyParam notes string optional.
     * @bodyParam transaction_reference string optional Bank/cheque reference.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id'            => 'required|integer|exists:invoices,id',
            'amount'                => 'required|numeric|min:0.01',
            'payment_method'        => 'required|in:cash,bank_transfer,cheque,other',
            'payment_date'          => 'required|date',
            'notes'                 => 'nullable|string|max:1000',
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        // Verify user has access to the invoice's business
        $invoice = Invoice::with('order')->findOrFail($data['invoice_id']);
        if ($invoice->order?->business_id) {
            $this->authService->assertBusinessAccess($request->user(), $invoice->order->business_id);
        }

        try {
            $payment = $this->salesService->recordPayment(
                invoiceId:      (int) $data['invoice_id'],
                amount:         (float) $data['amount'],
                method:         $data['payment_method'],
                notes:          $data['notes'] ?? '',
                transactionRef: $data['transaction_reference'] ?? null,
                paymentDate:    $data['payment_date'],
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        return $this->created(new PaymentResource($payment->load(['invoice', 'recorder'])), 'Payment recorded.');
    }

    /**
     * Get payment
     *
     * @authenticated
     * @urlParam payment int required The payment ID.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $this->assertPaymentAccess($request, $payment);

        return $this->ok(new PaymentResource($payment->load(['invoice.order', 'recorder'])));
    }

    /**
     * Update payment notes / reference
     *
     * @authenticated
     * @urlParam payment int required The payment ID.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $this->assertPaymentAccess($request, $payment);

        $data = $request->validate([
            'notes'                 => 'nullable|string|max:1000',
            'transaction_reference' => 'nullable|string|max:255',
            'payment_date'          => 'sometimes|date',
        ]);

        $original = $payment->toArray();
        $payment->update($data);

        AuditService::logUpdate('App\\Models\\Payment', $payment, $original);

        return $this->ok(new PaymentResource($payment));
    }

    /**
     * Delete payment
     *
     * @authenticated
     * @urlParam payment int required The payment ID.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $this->assertPaymentAccess($request, $payment);

        AuditService::logDelete('App\\Models\\Payment', $payment);
        $payment->delete();

        return $this->ok(null, 'Payment deleted.');
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function assertPaymentAccess(Request $request, Payment $payment): void
    {
        $businessId = $payment->invoice?->order?->business_id;
        if ($businessId) {
            $this->authService->assertBusinessAccess($request->user(), $businessId);
        } elseif (!$request->user()->isSuperAdmin()) {
            abort(403, 'Access denied.');
        }
    }
}
