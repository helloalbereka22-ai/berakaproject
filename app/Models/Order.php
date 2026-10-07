<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Order extends Model
{
    // ── Order lifecycle statuses ─────────────────────────────────────────
    const STATUS_PENDING    = 'pending';     // customer placed order, awaiting payment
    const STATUS_CONFIRMED  = 'confirmed';   // payment approved, ready to process
    const STATUS_PROCESSING = 'processing';  // seller is processing/packing
    const STATUS_COMPLETED  = 'completed';   // fulfilled, stock deducted
    const STATUS_CANCELLED  = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING    => 'Pending',
        self::STATUS_CONFIRMED  => 'Confirmed',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED  => 'Completed',
        self::STATUS_CANCELLED  => 'Cancelled',
    ];

    // ── Payment approval statuses ────────────────────────────────────────
    const PAYMENT_PENDING  = 'pending';
    const PAYMENT_APPROVED = 'approved';
    const PAYMENT_REJECTED = 'rejected';

    const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING  => 'Payment Pending',
        self::PAYMENT_APPROVED => 'Payment Approved',
        self::PAYMENT_REJECTED => 'Payment Rejected',
    ];

    protected $fillable = [
        'business_id',
        'branch_id',
        'customer_id',
        'order_number',
        'status',
        'payment_status',
        'payment_approved_at',
        'payment_approved_by',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'tax_amount',
        'total_amount',
        'notes',
        'currency',
        'placed_at',
    ];

    protected $casts = [
        'subtotal'             => 'decimal:4',
        'discount_amount'      => 'decimal:4',
        'delivery_fee'         => 'decimal:4',
        'tax_amount'           => 'decimal:4',
        'total_amount'         => 'decimal:4',
        'placed_at'            => 'datetime',
        'payment_approved_at'  => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentApprovedBy()
    {
        return $this->belongsTo(User::class, 'payment_approved_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $q, int $businessId): Builder
    {
        return $q->where('business_id', $businessId);
    }

    public function scopeForBranch(Builder $q, int $branchId): Builder
    {
        // Orders are linked to a branch either at order level (branch_id)
        // or via their fulfilling_branch_id on any order item.
        return $q->where(function (Builder $inner) use ($branchId) {
            $inner->where('branch_id', $branchId)
                  ->orWhereHas('items', fn($qi) =>
                      $qi->where('fulfilling_branch_id', $branchId)
                  );
        });
    }

    public function scopeForCustomer(Builder $q, int $customerId): Builder
    {
        return $q->where('customer_id', $customerId);
    }

    public function scopeByStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    public function scopeByPaymentStatus(Builder $q, string $paymentStatus): Builder
    {
        return $q->where('payment_status', $paymentStatus);
    }

    public function scopePendingPayment(Builder $q): Builder
    {
        return $q->where('payment_status', self::PAYMENT_PENDING);
    }

    public function scopePaymentApproved(Builder $q): Builder
    {
        return $q->where('payment_status', self::PAYMENT_APPROVED);
    }

    // ── Payment helpers ──────────────────────────────────────────────────

    public function isPaymentPending(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING;
    }

    public function isPaymentApproved(): bool
    {
        return $this->payment_status === self::PAYMENT_APPROVED;
    }

    public function isPaymentRejected(): bool
    {
        return $this->payment_status === self::PAYMENT_REJECTED;
    }

    public function canApprovePayment(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING
            && $this->status === self::STATUS_PENDING;
    }

    public function canProcess(): bool
    {
        return $this->payment_status === self::PAYMENT_APPROVED
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function getPaymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    // ── Order amount helpers ─────────────────────────────────────────────

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getTotalPaid(): float
    {
        return (float) $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getBalanceDue(): float
    {
        return max(0, (float) $this->total_amount - $this->getTotalPaid());
    }

    public function isFullyPaid(): bool
    {
        return $this->getBalanceDue() <= 0;
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
    }
}
