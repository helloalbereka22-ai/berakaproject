<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZakatCycle extends Model
{
    const STATUS_ACTIVE   = 'active';
    const STATUS_DUE      = 'due';
    const STATUS_PAID     = 'paid';
    const STATUS_OVERDUE  = 'overdue';

    protected $fillable = [
        'start_date', 'due_date',
        'start_date_hijri', 'due_date_hijri',
        'nisab_amount', 'zakatable_amount',
        'zakat_rate', 'zakat_due',
        'amount_paid', 'paid_date',
        'payment_reference', 'notes',
        'status', 'recorded_by', 'paid_by',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'due_date'          => 'date',
        'paid_date'         => 'date',
        'nisab_amount'      => 'decimal:2',
        'zakatable_amount'  => 'decimal:2',
        'zakat_rate'        => 'decimal:4',
        'zakat_due'         => 'decimal:2',
        'amount_paid'       => 'decimal:2',
    ];

    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
    public function paidBy()     { return $this->belongsTo(User::class, 'paid_by'); }

    public function isActive():   bool { return $this->status === self::STATUS_ACTIVE; }
    public function isDue():      bool { return $this->status === self::STATUS_DUE; }
    public function isPaid():     bool { return $this->status === self::STATUS_PAID; }
    public function isOverdue():  bool { return $this->status === self::STATUS_OVERDUE; }

    public function getRemainingAttribute(): float
    {
        return max(0, (float)$this->zakat_due - (float)$this->amount_paid);
    }

    /** Update status based on today's date */
    public function syncStatus(): void
    {
        if ($this->isPaid()) return;

        $today = now()->toDateString();

        if ($today > $this->due_date->toDateString()) {
            $this->status = self::STATUS_OVERDUE;
        } elseif ($today >= $this->due_date->subDays(30)->toDateString()) {
            $this->status = self::STATUS_DUE;
        } else {
            $this->status = self::STATUS_ACTIVE;
        }

        $this->saveQuietly();
    }
}
