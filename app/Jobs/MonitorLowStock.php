<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scans all products with a reorder_point set and logs/fires alerts
 * for any whose current stock (derived from stock_movements) is at or
 * below the threshold.
 *
 * Uses the stock_movements ledger — never the legacy stock_levels table.
 */
class MonitorLowStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(InventoryService $inventoryService): void
    {
        Log::info('MonitorLowStock: starting scan.');

        // Find all products that have a reorder point configured
        $products = Product::withoutGlobalScopes()
            ->where('reorder_point', '>', 0)
            ->whereIn('type', [
                Product::TYPE_RAW_MATERIAL,
                Product::TYPE_PACKING_MATERIAL,
                Product::TYPE_FINISHED_PRODUCT,
                Product::TYPE_BOOK,
                Product::TYPE_STATIONERY,
                Product::TYPE_FASHION,
                Product::TYPE_GENERAL,
            ])
            ->get();

        $alertCount = 0;

        foreach ($products as $product) {
            // Aggregate current stock across all locations for this product
            $currentQty = (float) StockMovement::where('item_type', 'product')
                ->where('item_id', $product->id)
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN quantity ELSE -quantity END"));

            if ($currentQty <= (float) $product->reorder_point) {
                $alertCount++;

                Log::warning(
                    "LOW STOCK: [{$product->type}] {$product->name} (ID {$product->id}) "
                    . "— Current: {$currentQty} {$product->unit}, "
                    . "Reorder Point: {$product->reorder_point}"
                );

                // Fire an event that notification listeners can hook into
                // e.g. for email, push, or in-app alerts
                event('inventory.low_stock', [
                    'product_id'      => $product->id,
                    'product_name'    => $product->name,
                    'product_type'    => $product->type,
                    'business_id'     => $product->business_id,
                    'current_qty'     => $currentQty,
                    'reorder_point'   => (float) $product->reorder_point,
                    'unit'            => $product->unit,
                ]);
            }
        }

        Log::info("MonitorLowStock: scan complete. {$alertCount} low-stock alert(s) fired.");
    }
}
