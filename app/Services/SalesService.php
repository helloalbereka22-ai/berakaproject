<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProfitLedger;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly MarketingService $marketing,
    ) {}

    // ══════════════════════════════════════════════════════════════════════
    //  CART MANAGEMENT
    // ══════════════════════════════════════════════════════════════════════

    public function getOrCreateCart(int $customerId): Cart
    {
        return Cart::firstOrCreate(
            ['customer_id' => $customerId, 'status' => 'active'],
            ['customer_id' => $customerId, 'status' => 'active']
        );
    }

    /**
     * Add or update a product in the cart.
     * Validates sellability and stock availability server-side.
     */
    public function addToCart(int $customerId, int $productId, float $quantity): CartItem
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantity must be greater than zero.");
        }

        $product = Product::withoutGlobalScopes()->findOrFail($productId);

        if (!$product->isSellable()) {
            throw new \RuntimeException("Product '{$product->name}' is not available for sale.");
        }
        if ($product->status !== Product::STATUS_ACTIVE) {
            throw new \RuntimeException("Product '{$product->name}' is not currently active.");
        }

        // Check sufficient stock exists somewhere
        $location = $this->findFulfillingLocation($product->id, $quantity);
        if (!$location) {
            throw new \RuntimeException(
                "'{$product->name}' has insufficient stock (requested: {$quantity})."
            );
        }

        $cart      = $this->getOrCreateCart($customerId);
        $priceData = $this->marketing->applyProductDiscount($product, (float) $product->selling_price);

        $existing = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->update([
                'quantity'        => $quantity,
                'unit_price'      => $priceData['final_price'],
                'original_price'  => (float) $product->selling_price,
                'discount_amount' => $priceData['discount_amount'],
                'discount_type'   => $priceData['discount_type'],
                'final_price'     => $priceData['final_price'],
                'business_id'     => $product->business_id,
            ]);
            return $existing->fresh();
        }

        return CartItem::create([
            'cart_id'         => $cart->id,
            'business_id'     => $product->business_id,
            'product_id'      => $productId,
            'quantity'        => $quantity,
            'unit_price'      => $priceData['final_price'],
            'original_price'  => (float) $product->selling_price,
            'discount_amount' => $priceData['discount_amount'],
            'discount_type'   => $priceData['discount_type'],
            'final_price'     => $priceData['final_price'],
        ]);
    }

    public function removeFromCart(int $customerId, int $productId): void
    {
        $cart = $this->getOrCreateCart($customerId);
        CartItem::where('cart_id', $cart->id)->where('product_id', $productId)->delete();
    }

    public function clearCart(int $customerId): void
    {
        $cart = Cart::where('customer_id', $customerId)->where('status', 'active')->first();
        if ($cart) {
            $cart->items()->delete();
        }
    }

    /**
     * Calculate cart totals. Never trusts the frontend.
     */
    public function getCartSummary(int $customerId, ?string $orderDiscountCode = null): array
    {
        $cart  = $this->getOrCreateCart($customerId);
        $items = $cart->items()->with(['product.business'])->get();

        $subtotal     = 0.0;
        $itemsSummary = [];

        foreach ($items as $item) {
            $product   = $item->product;
            $lineTotal = round((float) $item->final_price * (float) $item->quantity, 4);
            $subtotal += $lineTotal;

            $itemsSummary[] = [
                'product_id'      => $product->id,
                'product_name'    => $product->name,
                'business_id'     => $product->business_id,
                'business_name'   => optional($product->business)->name,
                'quantity'        => (float) $item->quantity,
                'original_price'  => (float) $item->original_price,
                'unit_price'      => (float) $item->final_price,
                'discount_amount' => (float) $item->discount_amount,
                'line_total'      => $lineTotal,
                // Show availability to customer — but NOT cost price
                'image_url'       => $product->image_url,
            ];
        }

        $orderDiscount = $this->marketing->applyOrderDiscount($subtotal, $orderDiscountCode);

        return [
            'items'               => $itemsSummary,
            'item_count'          => $items->count(),
            'subtotal'            => round($subtotal, 4),
            'order_discount'      => $orderDiscount['discount_amount'],
            'order_discount_type' => $orderDiscount['discount_type'],
            'total'               => $orderDiscount['final_total'],
            'currency'            => 'USD',
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CHECKOUT — creates a PENDING order, NO inventory deduction yet
    //
    //  Stock is validated at checkout time (to give the customer immediate
    //  feedback) but NOT deducted. Deduction happens only when the seller
    //  calls completeOrder() after approving payment.
    // ══════════════════════════════════════════════════════════════════════

    public function checkout(
        int     $customerId,
        ?int    $deliveryAddressId  = null,
        ?string $orderDiscountCode  = null,
        string  $notes              = ''
    ): array {
        return DB::transaction(function () use (
            $customerId, $deliveryAddressId, $orderDiscountCode, $notes
        ) {
            $cart  = $this->getOrCreateCart($customerId);
            $items = $cart->items()->with(['product.business'])->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException("Cannot checkout with an empty cart.");
            }

            // ── Step 1: Validate stock (NO deduction yet) ─────────────────
            foreach ($items as $item) {
                $product = $item->product;

                if (!$product || !$product->isSellable()) {
                    throw new \RuntimeException(
                        "Product '{$product?->name}' is no longer available."
                    );
                }

                $location = $this->findFulfillingLocation($product->id, (float) $item->quantity);
                if (!$location) {
                    throw new \RuntimeException(
                        "Insufficient stock for '{$product->name}'. " .
                        "Requested: {$item->quantity}."
                    );
                }
            }

            // ── Step 2: Calculate totals (backend only) ───────────────────
            $summary = $this->getCartSummary($customerId, $orderDiscountCode);

            // ── Step 3: Resolve branch for order header ───────────────────
            // Use the first item's fulfilling branch as the order-level branch.
            $firstProduct  = $items->first()->product;
            $firstLocation = $this->findFulfillingLocation(
                $firstProduct->id, (float) $items->first()->quantity
            );
            $orderBranchId   = $firstLocation?->branch_id;
            $orderBusinessId = $firstProduct->business_id;

            // ── Step 4: Create order in PENDING state ─────────────────────
            $order = Order::create([
                'customer_id'    => $customerId,
                'business_id'    => $orderBusinessId,
                'branch_id'      => $orderBranchId,
                'order_number'   => Order::generateOrderNumber(),
                'status'         => Order::STATUS_PENDING,      // ← PENDING, not confirmed
                'payment_status' => Order::PAYMENT_PENDING,     // ← awaiting seller approval
                'subtotal'       => $summary['subtotal'],
                'discount_amount'=> $summary['order_discount'],
                'delivery_fee'   => 0,
                'tax_amount'     => 0,
                'total_amount'   => $summary['total'],
                'notes'          => $notes,
                'currency'       => 'USD',
                'placed_at'      => now(),
            ]);

            // ── Step 5: Create order items (NO stock deduction) ───────────
            foreach ($items as $item) {
                $product  = $item->product;
                $location = $this->findFulfillingLocation($product->id, (float) $item->quantity);

                $qty        = (float) $item->quantity;
                $finalPrice = (float) $item->final_price;
                $lineTotal  = round($finalPrice * $qty, 4);

                OrderItem::create([
                    'order_id'              => $order->id,
                    'business_id'           => $product->business_id,
                    'product_id'            => $product->id,
                    'fulfilling_branch_id'  => $location?->branch_id,
                    'warehouse_location_id' => $location?->id,
                    'quantity'              => $qty,
                    'unit_price'            => $finalPrice,
                    'original_price'        => (float) $item->original_price,
                    'cost_price_snapshot'   => (float) $product->cost_price,
                    'discount_amount'       => (float) $item->discount_amount,
                    'discount_type'         => $item->discount_type,
                    'total_amount'          => $lineTotal,
                ]);
                // ← NO inventory movement here — stock deducted only at completeOrder()
            }

            // ── Step 6: Generate invoice ──────────────────────────────────
            $invoice = Invoice::create([
                'order_id'        => $order->id,
                'customer_id'     => $customerId,
                'invoice_number'  => Invoice::generateInvoiceNumber(),
                'issued_at'       => now(),
                'subtotal'        => $summary['subtotal'],
                'tax_amount'      => 0,
                'discount_amount' => $summary['order_discount'],
                'total_amount'    => $summary['total'],
                'status'          => Invoice::STATUS_ISSUED,
            ]);

            // ── Step 7: Create delivery record if address provided ────────
            $delivery = null;
            if ($deliveryAddressId) {
                $delivery = Delivery::create([
                    'order_id'            => $order->id,
                    'fulfilling_branch_id'=> $orderBranchId,
                    'delivery_address_id' => $deliveryAddressId,
                    'status'              => 'pending',
                ]);
            }

            // ── Step 8: Mark cart as checked out ──────────────────────────
            $cart->update(['status' => 'checked_out']);

            AuditService::log(
                'order_placed',
                Order::class,
                $order->id,
                [],
                ['order_number' => $order->order_number, 'total' => $order->total_amount],
                $orderBusinessId,
                $orderBranchId,
                "Order {$order->order_number} placed by customer #{$customerId} — awaiting payment approval"
            );

            return [
                'order'    => $order->load(['items.product', 'invoice']),
                'invoice'  => $invoice,
                'delivery' => $delivery,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SELLER PAYMENT APPROVAL WORKFLOW
    //
    //  Flow:  checkout() → approvePayment() → processOrder() → completeOrder()
    //
    //  Stock is deducted ONLY in completeOrder(), exactly once.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Seller manually approves the customer's payment.
     * Moves order: payment_status pending → approved, status pending → confirmed.
     * Does NOT deduct stock yet.
     *
     * @throws \RuntimeException  if order is not in approvable state
     * @throws \RuntimeException  if seller is not authorised for this order's branch
     */
    public function approvePayment(Order $order, User $seller): Order
    {
        if (!$order->canApprovePayment()) {
            throw new \RuntimeException(
                "Order #{$order->order_number} cannot be approved. " .
                "Current status: {$order->status}, payment: {$order->payment_status}."
            );
        }

        $this->assertSellerBranchAccess($seller, $order);

        return DB::transaction(function () use ($order, $seller) {
            $original = $order->only('status', 'payment_status');

            $order->update([
                'payment_status'      => Order::PAYMENT_APPROVED,
                'payment_approved_at' => now(),
                'payment_approved_by' => $seller->id,
                'status'              => Order::STATUS_CONFIRMED,
            ]);

            // Record the payment as approved in the payments table
            Payment::create([
                'invoice_id'            => $order->invoice?->id,
                'order_id'              => $order->id,
                'payment_method'        => 'manual',
                'amount'                => (float) $order->total_amount,
                'status'                => Payment::STATUS_COMPLETED,
                'transaction_reference' => 'MANUAL-APPROVAL-' . now()->format('YmdHis'),
                'notes'                 => "Payment manually approved by seller #{$seller->id}",
                'payment_date'          => now()->toDateString(),
                'recorded_by'           => $seller->id,
                'recorded_at'           => now(),
            ]);

            // Mark invoice as paid
            $order->invoice?->update(['status' => Invoice::STATUS_PAID]);

            AuditService::log(
                'payment_approved',
                Order::class,
                $order->id,
                $original,
                $order->fresh()->only('status', 'payment_status'),
                $order->business_id,
                $order->branch_id,
                "Payment approved for order {$order->order_number} by seller #{$seller->id}"
            );

            return $order->fresh();
        });
    }

    /**
     * Seller moves the order to Processing state.
     * Pre-condition: payment must be approved.
     * Does NOT deduct stock yet.
     */
    public function processOrder(Order $order, User $seller): Order
    {
        if (!$order->canProcess()) {
            throw new \RuntimeException(
                "Order #{$order->order_number} cannot be moved to processing. " .
                "Ensure payment is approved first (current: {$order->payment_status})."
            );
        }

        $this->assertSellerBranchAccess($seller, $order);

        return DB::transaction(function () use ($order, $seller) {
            $order->update(['status' => Order::STATUS_PROCESSING]);

            AuditService::log(
                'order_processing',
                Order::class,
                $order->id,
                ['status' => Order::STATUS_CONFIRMED],
                ['status' => Order::STATUS_PROCESSING],
                $order->business_id,
                $order->branch_id,
                "Order {$order->order_number} moved to processing by seller #{$seller->id}"
            );

            return $order->fresh();
        });
    }

    /**
     * Seller completes the order.
     * THIS is the only place inventory is deducted.
     * Deduction happens exactly once — guarded by status check.
     *
     * @throws \RuntimeException  if stock is now insufficient (edge case: concurrent orders)
     */
    public function completeOrder(Order $order, User $seller): Order
    {
        if (!$order->canComplete()) {
            throw new \RuntimeException(
                "Order #{$order->order_number} cannot be completed. " .
                "It must be in processing status (current: {$order->status})."
            );
        }

        $this->assertSellerBranchAccess($seller, $order);

        return DB::transaction(function () use ($order, $seller) {
            $items = $order->items()->with('product')->get();

            // ── Re-validate stock before deducting ────────────────────────
            foreach ($items as $item) {
                $product  = $item->product;
                $qty      = (float) $item->quantity;
                $locId    = $item->warehouse_location_id;

                if (!$locId) {
                    // Try to find a location now if it wasn't set at checkout
                    $loc = $this->findFulfillingLocation($product->id, $qty);
                    if (!$loc) {
                        throw new \RuntimeException(
                            "Cannot complete order: insufficient stock for '{$product->name}' " .
                            "(requested: {$qty})."
                        );
                    }
                    $locId = $loc->id;
                    $item->update(['warehouse_location_id' => $locId, 'fulfilling_branch_id' => $loc->branch_id]);
                } else {
                    if (!$this->inventory->hasStock($locId, $product->id, $qty)) {
                        $available = $this->inventory->getBalance($locId, 'product', $product->id);
                        throw new \RuntimeException(
                            "Cannot complete order: insufficient stock for '{$product->name}' " .
                            "at this location. Available: {$available}, required: {$qty}."
                        );
                    }
                }

                // ── Deduct stock — exactly once ───────────────────────────
                $this->inventory->recordSale(
                    $locId,
                    $product->id,
                    $qty,
                    (float) $item->cost_price_snapshot,
                    $order->id,
                    'order'
                );

                // ── Record profit ─────────────────────────────────────────
                $revenue   = round((float) $item->unit_price * $qty, 4);
                $cost      = round((float) $item->cost_price_snapshot * $qty, 4);
                $profit    = $revenue - $cost;

                ProfitLedger::create([
                    'business_id'   => $item->business_id,
                    'branch_id'     => $item->fulfilling_branch_id,
                    'order_item_id' => $item->id,
                    'product_id'    => $product->id,
                    'quantity'      => $qty,
                    'unit_price'    => (float) $item->unit_price,
                    'selling_price' => (float) $item->unit_price,
                    'cost_price'    => (float) $item->cost_price_snapshot,
                    'revenue'       => $revenue,
                    'total_cost'    => $cost,
                    'profit'        => $profit,
                    'recorded_at'   => now(),
                ]);
            }

            // ── Mark order completed ──────────────────────────────────────
            $order->update(['status' => Order::STATUS_COMPLETED]);

            // ── Complete delivery if it exists ────────────────────────────
            $delivery = $order->deliveries()->where('status', '!=', 'delivered')->first();
            if ($delivery) {
                $delivery->update(['status' => 'delivered', 'completed_at' => now()]);
            }

            AuditService::log(
                'order_completed',
                Order::class,
                $order->id,
                ['status' => Order::STATUS_PROCESSING],
                ['status' => Order::STATUS_COMPLETED],
                $order->business_id,
                $order->branch_id,
                "Order {$order->order_number} completed by seller #{$seller->id} — stock deducted"
            );

            return $order->fresh();
        });
    }

    /**
     * Seller rejects a payment (customer submitted invalid proof etc.)
     */
    public function rejectPayment(Order $order, User $seller, string $reason = ''): Order
    {
        if ($order->payment_status !== Order::PAYMENT_PENDING) {
            throw new \RuntimeException(
                "Only pending-payment orders can be rejected."
            );
        }

        $this->assertSellerBranchAccess($seller, $order);

        $order->update([
            'payment_status' => Order::PAYMENT_REJECTED,
            'notes'          => $order->notes . "\nPayment rejected: {$reason}",
        ]);

        AuditService::log(
            'payment_rejected',
            Order::class,
            $order->id,
            ['payment_status' => Order::PAYMENT_PENDING],
            ['payment_status' => Order::PAYMENT_REJECTED],
            $order->business_id,
            $order->branch_id,
            "Payment rejected for order {$order->order_number}: {$reason}"
        );

        return $order->fresh();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PAYMENT RECORDING (direct manual payment by seller)
    // ══════════════════════════════════════════════════════════════════════

    public function recordPayment(
        int     $invoiceId,
        float   $amount,
        string  $method,
        string  $notes           = '',
        ?string $transactionRef  = null,
        ?string $paymentDate     = null
    ): Payment {
        $invoice = Invoice::with('order')->findOrFail($invoiceId);

        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be greater than zero.");
        }
        if ($amount > $invoice->getBalanceDue() + 0.001) {
            throw new \RuntimeException(
                "Payment amount ({$amount}) exceeds balance due ({$invoice->getBalanceDue()})."
            );
        }

        return DB::transaction(function () use (
            $invoice, $amount, $method, $notes, $transactionRef, $paymentDate
        ) {
            $payment = Payment::create([
                'invoice_id'            => $invoice->id,
                'order_id'              => $invoice->order_id,
                'payment_method'        => $method,
                'amount'                => $amount,
                'status'                => Payment::STATUS_COMPLETED,
                'transaction_reference' => $transactionRef,
                'notes'                 => $notes,
                'payment_date'          => $paymentDate ?? now()->toDateString(),
                'recorded_by'           => Auth::id(),
                'recorded_at'           => now(),
            ]);

            if ($invoice->fresh()->getBalanceDue() <= 0.001) {
                $invoice->update(['status' => Invoice::STATUS_PAID]);
            }

            AuditService::log(
                'payment_recorded',
                Invoice::class,
                $invoice->id,
                [],
                ['amount' => $amount, 'method' => $method],
                null, null,
                "Payment of {$amount} recorded via {$method} for invoice {$invoice->invoice_number}"
            );

            return $payment;
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DELIVERY
    // ══════════════════════════════════════════════════════════════════════

    public function recordDelivery(
        int    $orderId,
        int    $addressId,
        int    $fulfillingBranchId,
        string $deliveryDate,
        string $notes = ''
    ): Delivery {
        $order = Order::findOrFail($orderId);

        return DB::transaction(function () use (
            $order, $addressId, $fulfillingBranchId, $deliveryDate, $notes
        ) {
            return Delivery::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'fulfilling_branch_id' => $fulfillingBranchId,
                    'delivery_address_id'  => $addressId,
                    'delivery_date'        => $deliveryDate,
                    'status'               => 'pending',
                    'notes'                => $notes,
                ]
            );
        });
    }

    public function completeDelivery(Delivery $delivery): Delivery
    {
        return DB::transaction(function () use ($delivery) {
            $delivery->update(['status' => 'delivered', 'completed_at' => now()]);
            $order = $delivery->order;
            if ($order && $order->status !== Order::STATUS_COMPLETED) {
                $order->update(['status' => Order::STATUS_COMPLETED]);
            }
            return $delivery->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CONSOLIDATED ORDER STATUS (customer-facing — hides internal data)
    // ══════════════════════════════════════════════════════════════════════

    public function getConsolidatedOrderStatus(int $orderId): array
    {
        $order = Order::with([
            'items.product',
            'items.fulfillingBranch',
            'invoice.payments',
            'deliveries',
            'customer',
        ])->findOrFail($orderId);

        $invoice    = $order->invoice;
        $delivery   = $order->deliveries->first();
        $totalPaid  = $invoice ? (float) $invoice->payments->where('status', 'completed')->sum('amount') : 0;
        $balanceDue = max(0, (float) $order->total_amount - $totalPaid);

        return [
            'order_id'        => $order->id,
            'order_number'    => $order->order_number,
            'status'          => $order->status,
            'status_label'    => $order->getStatusLabel(),
            'payment_status'  => $order->payment_status,
            'placed_at'       => $order->placed_at?->toISOString(),
            'customer'        => optional($order->customer)->name,
            'items'           => $order->items->map(fn($i) => [
                // Customer sees product name, qty, price — NOT cost or branch internals
                'product_name'    => optional($i->product)->name,
                'product_image'   => optional($i->product)->image_url,
                'quantity'        => (float) $i->quantity,
                'unit_price'      => (float) $i->unit_price,
                'discount_amount' => (float) $i->discount_amount,
                'line_total'      => (float) $i->total_amount,
            ])->values(),
            'subtotal'        => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'delivery_fee'    => (float) $order->delivery_fee,
            'total_amount'    => (float) $order->total_amount,
            'invoice_number'  => $invoice?->invoice_number,
            'invoice_status'  => $invoice?->status,
            'total_paid'      => $totalPaid,
            'balance_due'     => $balanceDue,
            'payments'        => $invoice?->payments->map(fn($p) => [
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'date'   => $p->payment_date?->toDateString(),
                'status' => $p->status,
            ])->values() ?? [],
            'delivery_status' => $delivery?->status ?? 'not_scheduled',
            'delivery_date'   => $delivery?->delivery_date?->toDateString(),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Find a location with sufficient stock for a product.
     * Returns the location with the highest balance (greedy pick).
     */
    public function findFulfillingLocation(int $productId, float $quantity): ?\App\Models\Location
    {
        $row = \App\Models\StockMovement::where('item_type', 'product')
            ->where('item_id', $productId)
            ->select(
                'location_id',
                \Illuminate\Support\Facades\DB::raw(
                    "SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as qty"
                )
            )
            ->groupBy('location_id')
            ->having('qty', '>=', $quantity)
            ->orderByDesc('qty')
            ->first();

        return $row ? \App\Models\Location::find($row->location_id) : null;
    }

    /**
     * Security guard: a seller can only act on orders belonging to their branch.
     * Super Admin and Business Manager bypass this check.
     */
    private function assertSellerBranchAccess(User $seller, Order $order): void
    {
        if ($seller->isSuperAdmin()) return;
        if ($seller->hasPermission('business.update')) return; // Business Manager

        $sellerBranchIds = $seller->assignedBranchIds();
        $orderBranchIds  = $order->items->pluck('fulfilling_branch_id')->push($order->branch_id)->filter()->unique()->values()->toArray();

        // At least one of the order's branches must be in the seller's assigned branches
        $intersection = array_intersect($sellerBranchIds, $orderBranchIds);
        if (empty($intersection)) {
            throw new \RuntimeException(
                "You are not authorised to manage order #{$order->order_number}."
            );
        }
    }
}
