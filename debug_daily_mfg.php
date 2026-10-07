<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Carbon\Carbon;

$today = now()->toDateString();

$products = Product::query()
    ->whereIn('type', ['finished_product', 'bottle_product'])
    ->where('status', 'active')
    ->whereNotNull('metadata')
    ->get();

echo "=== Daily Manufactured Debug ===\n";
echo "Total products with metadata: " . $products->count() . "\n\n";

$todayProducts = $products->filter(function ($product) use ($today) {
    $meta = $product->metadata ?? [];
    
    if (!isset($meta['manufacturing_added_at'])) {
        return false;
    }

    try {
        $addedDate = Carbon::parse($meta['manufacturing_added_at'])->toDateString();
        if ($addedDate === $today) {
            $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
            return $qty > 0;
        }
    } catch (\Exception $e) {
        return false;
    }
    
    return false;
});

echo "Products with manufacturing_added_at TODAY: " . $todayProducts->count() . "\n";
echo "Today's date: " . $today . "\n\n";

$total = $todayProducts->sum(function ($p) {
    $meta = $p->metadata ?? [];
    return (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
});

echo "Total quantity: " . $total . "\n\n";

echo "=== Breakdown ===\n";
$todayProducts->each(function ($p) use ($today) {
    $meta = $p->metadata ?? [];
    $qty = (float)($meta['manufacturing_daily_qty'] ?? $meta['manufacturing_quantity'] ?? 0);
    $addedAt = $meta['manufacturing_added_at'] ?? 'null';
    echo "Product: " . $p->name . " | ID: " . $p->id . " | Qty: " . $qty . " | Daily Qty: " . ($meta['manufacturing_daily_qty'] ?? 'null') . " | Added At: " . $addedAt . "\n";
});
