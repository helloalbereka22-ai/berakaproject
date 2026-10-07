<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Processes a low-stock notification for a specific product.
 * Dispatched by MonitorLowStock when a product falls below its reorder point.
 */
class ProcessLowStockNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * @param int   $productId     The product that triggered the alert.
     * @param float $currentQty    Current calculated stock level.
     * @param float $reorderPoint  Configured reorder threshold.
     */
    public function __construct(
        public readonly int   $productId,
        public readonly float $currentQty,
        public readonly float $reorderPoint,
    ) {}

    public function handle(): void
    {
        $product = Product::withoutGlobalScopes()->find($this->productId);

        if (!$product) {
            Log::warning("ProcessLowStockNotifications: product {$this->productId} not found.");
            return;
        }

        Log::info(
            "ProcessLowStockNotifications: [{$product->type}] {$product->name} — "
            . "qty={$this->currentQty}, threshold={$this->reorderPoint}"
        );

        // TODO: Send email/SMS/in-app notification to business owner and
        // assigned branch sellers. Use Laravel Notifications:
        //
        // $managers = User::whereHas('roles', fn($q) =>
        //     $q->where('name', 'Business Manager')
        //       ->orWhere('name', 'Super Admin')
        // )->where('status', 'active')->get();
        //
        // Notification::send($managers, new \App\Notifications\LowStockAlert($product, $this->currentQty));
    }
}
