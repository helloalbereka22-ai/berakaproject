<?php

namespace App\Services;

use App\Models\ZakatCycle;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class ZakatService
{
    // ── One Hijri year ≈ 354 days 8 hours 48 minutes ─────────────────────
    const HIJRI_YEAR_DAYS = 354;

    // ── Calculate current zakatable wealth ────────────────────────────────
    // Zakatable wealth = Total Selling Price (inventory value) + Raw Material Cost (remaining stock value)
    public function calculateZakatableWealth(): float
    {
        // 1. Total Selling Price = SUM(remaining_qty × selling_price)
        $totalSellingPrice = \Illuminate\Support\Facades\DB::table('stock_movements')
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->where('products.selling_price', '>', 0)
            ->select('products.id', 'products.selling_price', \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as available_qty"))
            ->groupBy('products.id', 'products.selling_price')
            ->get()
            ->sum(function($item) {
                return max(0, (float)$item->available_qty) * (float)$item->selling_price;
            });

        // 2. Raw Material Cost = SUM(remaining_qty × unit_price) for all raw/packing materials
        // Uses exact logic: for each product-price-date combination, calculate remaining_qty from purchases and consumption
        
        // Step 2a: Get all purchase rows grouped by product_id + unit_price + purchase_date
        $rows = \Illuminate\Support\Facades\DB::table('purchases as p')
            ->join('products', 'p.product_id', '=', 'products.id')
            ->leftJoin('categories', 'p.category_id', '=', 'categories.id')
            ->whereIn('p.purchase_type', ['raw_material', 'packing_material'])
            ->where('p.status', 'completed')
            ->select([
                'products.id as product_id',
                'products.name as item_name',
                'products.type as product_type',
                'categories.name as category_name',
                'p.unit_price as unit_cost',
                'p.purchase_date as date',
                \Illuminate\Support\Facades\DB::raw('SUM(p.quantity) as purchased_qty'),
            ])
            ->groupBy('products.id', 'products.name', 'products.type', 'categories.name', 'p.unit_price', 'p.purchase_date')
            ->get();

        // Step 2b: Calculate total purchased qty per product (all-time)
        $totalPurchasedMap = \Illuminate\Support\Facades\DB::table('purchases')
            ->whereIn('purchase_type', ['raw_material', 'packing_material'])
            ->where('status', 'completed')
            ->groupBy('product_id')
            ->select('product_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // Step 2c: Build consumed maps from finished product metadata
        $seedConsumedMap = [];
        $bottleConsumedMap = [];
        $totalMfgQty = 0.0;

        \Illuminate\Support\Facades\DB::table('products')
            ->whereIn('type', ['finished_product', 'bottle_product'])
            ->whereNotNull('metadata')
            ->get(['id', 'name', 'metadata'])
            ->each(function ($fp) use (&$seedConsumedMap, &$bottleConsumedMap, &$totalMfgQty) {
                $meta = json_decode($fp->metadata, true);
                if (empty($meta)) return;

                $mfgQty = (float) ($meta['manufacturing_quantity'] ?? 0);

                // Seed consumed
                $seedName = trim($meta['seed_name'] ?? '');
                $seedQty = (float) ($meta['seed_quantity_used'] ?? 0);
                if ($seedName !== '' && $seedQty > 0) {
                    $k = strtolower($seedName);
                    $seedConsumedMap[$k] = ($seedConsumedMap[$k] ?? 0) + $seedQty;
                }

                // Bottle consumed
                if ($mfgQty > 0) {
                    $k = strtolower(trim($fp->name));
                    $bottleConsumedMap[$k] = ($bottleConsumedMap[$k] ?? 0) + $mfgQty;
                    $totalMfgQty += $mfgQty;
                }
            });

        // Step 2d: Calculate total cost per row
        $rawMaterialCost = $rows->sum(function ($r) use ($seedConsumedMap, $bottleConsumedMap, $totalMfgQty, $totalPurchasedMap) {
            $unitCost = (float) $r->unit_cost;
            $totalPurchasedAllTime = round((float) ($totalPurchasedMap[$r->product_id] ?? 0), 3);

            // Detect bottle: has "bottle" in name OR is purely "NNN ml"
            $isBottle = stripos($r->item_name, 'bottle') !== false
                     || preg_match('/^\d+\s*ml$/i', trim($r->item_name));

            // Detect packing: category name contains "packing"
            $isPacking = stripos($r->category_name, 'packing') !== false;

            if ($isBottle) {
                // Consumed = SUM(manufacturing_quantity) of finished products with matching size
                preg_match('/(\d+)\s*ml/i', $r->item_name, $sizeMatch);
                $sizeNum = $sizeMatch[1] ?? '';
                $consumedQty = 0.0;
                foreach ($bottleConsumedMap as $finishedName => $mfgQty) {
                    if ($sizeNum !== '' && preg_match('/\b' . $sizeNum . '\s*ml\b/i', $finishedName)) {
                        $consumedQty += $mfgQty;
                    }
                }
                // Also check StockMovements for this bottle item
                $smConsumed = (float) \App\Models\StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', \App\Models\StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty = max($consumedQty, $smConsumed);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));

            } elseif ($isPacking) {
                // Packing material — 1 unit used per manufactured product
                $smConsumed = (float) \App\Models\StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', \App\Models\StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty = max($totalMfgQty, $smConsumed);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));

            } else {
                // Seeds
                $key = strtolower(trim($r->item_name));
                $consumedQty = (float) ($seedConsumedMap[$key] ?? 0);
                $smConsumed = (float) \App\Models\StockMovement::where('item_id', $r->product_id)
                    ->where('movement_type', \App\Models\StockMovement::TYPE_PRODUCTION_CONSUMPTION)
                    ->sum('quantity');
                $consumedQty = max($consumedQty, $smConsumed);
                $remainingQty = max(0, round($totalPurchasedAllTime - $consumedQty, 3));
            }

            return round($remainingQty * $unitCost, 2);
        });

        // Total Sales — all confirmed/completed sales from all sources
        $totalSales = (float) \Illuminate\Support\Facades\DB::table('guest_orders')
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('total_amount')
            + (float) \Illuminate\Support\Facades\DB::table('orders')
                ->where('status', 'completed')
                ->sum('total_amount')
            + (float) \Illuminate\Support\Facades\DB::table('branch_sales')
                ->sum('total_amount');

        return round($totalSellingPrice + (float)$rawMaterialCost, 2);
    }

    // ── Get or create the current active cycle ────────────────────────────
    public function currentCycle(): ?ZakatCycle
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('zakat_cycles')) {
            return null;
        }

        $cycle = ZakatCycle::whereNotIn('status', [ZakatCycle::STATUS_PAID])
            ->orderByDesc('start_date')
            ->first();

        if ($cycle) {
            $cycle->syncStatus();
        }

        return $cycle;
    }

    // ── Start a new Zakat cycle ───────────────────────────────────────────
    public function startCycle(
        float  $nisabAmount,
        float  $zakatRate  = 2.5,
        string $startDate  = null,
        string $startHijri = null
    ): ZakatCycle {
        $start   = $startDate ? \Carbon\Carbon::parse($startDate) : now();
        $due     = $start->copy()->addDays(self::HIJRI_YEAR_DAYS);

        $wealth  = $this->calculateZakatableWealth();
        $zakatDue = $wealth >= $nisabAmount
            ? round($wealth * ($zakatRate / 100), 2)
            : 0.00;

        return ZakatCycle::create([
            'start_date'        => $start->toDateString(),
            'due_date'          => $due->toDateString(),
            'start_date_hijri'  => $startHijri ?? $this->toHijriApprox($start),
            'due_date_hijri'    => $this->toHijriApprox($due),
            'nisab_amount'      => $nisabAmount,
            'zakatable_amount'  => $wealth,
            'zakat_rate'        => $zakatRate,
            'zakat_due'         => $zakatDue,
            'amount_paid'       => 0,
            'status'            => ZakatCycle::STATUS_ACTIVE,
            'recorded_by'       => auth()->id(),
        ]);
    }

    // ── Recalculate wealth on the current active cycle ────────────────────
    public function refreshWealth(ZakatCycle $cycle): void
    {
        $wealth   = $this->calculateZakatableWealth();
        $zakatDue = $wealth >= (float)$cycle->nisab_amount
            ? round($wealth * ((float)$cycle->zakat_rate / 100), 2)
            : 0.00;

        $cycle->update([
            'zakatable_amount' => $wealth,
            'zakat_due'        => $zakatDue,
        ]);
    }

    // ── Record Zakat payment ──────────────────────────────────────────────
    public function recordPayment(
        ZakatCycle $cycle,
        float      $amount,
        string     $reference = '',
        string     $notes     = ''
    ): ZakatCycle {
        $cycle->update([
            'amount_paid'       => $amount,
            'paid_date'         => now()->toDateString(),
            'payment_reference' => $reference,
            'notes'             => $notes,
            'status'            => ZakatCycle::STATUS_PAID,
            'paid_by'           => auth()->id(),
        ]);

        return $cycle;
    }

    // ── Very approximate Hijri conversion (display only) ─────────────────
    // Offset: Gregorian 2026-09-10 ≈ Safar 17, 1448 AH
    // Each Hijri year ≈ 354.37 days
    public function toHijriApprox(\Carbon\Carbon $date): string
    {
        $hijriMonths = [
            1  => 'Muharram', 2  => 'Safar',     3  => 'Rabi al-Awwal',
            4  => 'Rabi al-Thani', 5 => "Jumada al-Ula", 6 => 'Jumada al-Thani',
            7  => 'Rajab',    8  => "Sha'ban",    9  => 'Ramadan',
            10 => 'Shawwal',  11 => "Dhu al-Qi'dah", 12 => 'Dhu al-Hijjah',
        ];

        // Base: 2026-01-01 = approx 1 Rajab 1447 AH (day 30,610 of Hijri)
        $base      = \Carbon\Carbon::create(2026, 1, 1);
        $hijriBase = ['year' => 1447, 'month' => 7, 'day' => 1];

        $daysDiff  = $base->diffInDays($date, false);
        $totalHijriDays = ($hijriBase['year'] - 1) * 354.37
            + ($hijriBase['month'] - 1) * 29.53
            + $hijriBase['day']
            + $daysDiff;

        $hYear  = (int)floor($totalHijriDays / 354.37) + 1;
        $rem    = $totalHijriDays - ($hYear - 1) * 354.37;
        $hMonth = (int)floor($rem / 29.53) + 1;
        $hDay   = (int)($rem - ($hMonth - 1) * 29.53) + 1;

        $hMonth = max(1, min(12, $hMonth));
        $hDay   = max(1, min(30, $hDay));

        return "{$hDay} " . ($hijriMonths[$hMonth] ?? '') . " {$hYear} AH";
    }

    // ── Summary for dashboard card ────────────────────────────────────────
    public function dashboardSummary(): array
    {
        $cycle  = $this->currentCycle();
        $wealth = $this->calculateZakatableWealth();

        return [
            'cycle'             => $cycle,
            'wealth'            => $wealth,
            'has_cycle'         => $cycle !== null,
            'nisab'             => $cycle ? (float)$cycle->nisab_amount : 0,
            'zakat_due'         => $cycle ? (float)$cycle->zakat_due    : 0,
            'status'            => $cycle?->status ?? 'no_cycle',
            'due_date'          => $cycle?->due_date,
            'due_date_hijri'    => $cycle?->due_date_hijri,
            'days_until_due'    => $cycle ? now()->diffInDays($cycle->due_date, false) : null,
        ];
    }
}
