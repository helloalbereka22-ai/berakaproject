<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckLowStock extends Command
{
    protected $signature   = 'inventory:check-low-stock {--business=}';
    protected $description = 'Scan inventory against reorder levels and report low/out-of-stock items.';

    public function handle(): int
    {
        $businessId = $this->option('business') ? (int) $this->option('business') : null;

        $this->info('Running low-stock check' . ($businessId ? " for business #{$businessId}" : ' platform-wide') . '...');

        // Aggregate current stock per product per location from stock_movements
        $agg = StockMovement::query()
            ->where('item_type', 'product')
            ->join('products', 'stock_movements.item_id', '=', 'products.id')
            ->join('locations', 'stock_movements.location_id', '=', 'locations.id')
            ->leftJoin('branches', 'locations.branch_id', '=', 'branches.id')
            ->leftJoin('businesses', 'branches.business_id', '=', 'businesses.id')
            ->when($businessId, fn($q) => $q->where('stock_movements.business_id', $businessId))
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.reorder_point',
                'locations.name as location_name',
                'businesses.name as business_name',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as qty")
            )
            ->groupBy(
                'products.id', 'products.name', 'products.sku', 'products.reorder_point',
                'locations.name', 'businesses.name'
            )
            ->get();

        // ── Out of stock ──────────────────────────────────────────────────
        $outOfStock = $agg->filter(fn($r) => $r->qty <= 0);

        if ($outOfStock->isNotEmpty()) {
            $this->newLine();
            $this->error("OUT OF STOCK ({$outOfStock->count()} items):");
            $headers = ['Product', 'SKU', 'Location', 'Business', 'On Hand'];
            $rows    = $outOfStock->map(fn($s) => [
                $s->product_name,
                $s->sku ?? '—',
                $s->location_name ?? '—',
                $s->business_name ?? '—',
                number_format($s->qty, 2),
            ])->toArray();
            $this->table($headers, $rows);
        }

        // ── Low stock ─────────────────────────────────────────────────────
        $lowStock = $agg->filter(fn($r) => $r->qty > 0 && $r->qty <= ($r->reorder_point ?? 0));

        if ($lowStock->isNotEmpty()) {
            $this->newLine();
            $this->warn("LOW STOCK ({$lowStock->count()} items):");
            $headers = ['Product', 'SKU', 'Location', 'Business', 'On Hand', 'Reorder Level'];
            $rows    = $lowStock->map(fn($s) => [
                $s->product_name,
                $s->sku ?? '—',
                $s->location_name ?? '—',
                $s->business_name ?? '—',
                number_format($s->qty, 2),
                number_format($s->reorder_point, 2),
            ])->toArray();
            $this->table($headers, $rows);
        }

        if ($outOfStock->isEmpty() && $lowStock->isEmpty()) {
            $this->info('All stock levels are healthy. No alerts.');
        }

        $this->newLine();
        $this->info(sprintf(
            'Check complete. Out of stock: %d | Low stock: %d',
            $outOfStock->count(),
            $lowStock->count()
        ));

        return self::SUCCESS;
    }
}
