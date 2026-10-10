<?php
/**
 * Check production_output records for item_type and other details
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PRODUCTION_OUTPUT RECORD DETAILS ===\n\n";

// Check the first few production_output records
$records = DB::table('stock_movements')
    ->where('movement_type', 'production_output')
    ->orderByDesc('id')
    ->limit(5)
    ->select('id', 'item_type', 'item_id', 'business_id', 'branch_id', 'moved_at')
    ->get();

echo "Sample production_output records:\n";
$records->each(function($r) {
    echo "ID {$r->id}: item_type='{$r->item_type}' | item_id={$r->item_id} | biz={$r->business_id} | branch={$r->branch_id} | moved_at={$r->moved_at}\n";
});

// Check if they have matching products
echo "\nChecking product existence:\n";
$records->each(function($r) {
    $product = DB::table('products')->where('id', $r->item_id)->select('id', 'name')->first();
    if ($product) {
        echo "  Item {$r->item_id}: Product '{$product->name}' exists ✓\n";
    } else {
        echo "  Item {$r->item_id}: Product NOT FOUND ✗\n";
    }
});

// Check item_type distribution
echo "\nItem type distribution for all movements:\n";
$itemTypes = DB::table('stock_movements')
    ->select('item_type', DB::raw('COUNT(*) as count'))
    ->groupBy('item_type')
    ->get();

$itemTypes->each(function($t) {
    echo "  {$t->item_type}: {$t->count}\n";
});

// Final check: run the exact query from getStockChangesReport with today's date
echo "\n=== RUNNING ACTUAL QUERY (today only, no filters) ===\n";
$today = date('Y-m-d');
$movements = DB::table('stock_movements')
    ->leftJoin('products', function($join) {
        $join->on('stock_movements.item_id', '=', 'products.id')
             ->where('stock_movements.item_type', '=', 'product');
    })
    ->where('stock_movements.item_type', 'product')
    ->where('stock_movements.moved_at', '>=', $today . ' 00:00:00')
    ->where('stock_movements.moved_at', '<=', $today . ' 23:59:59')
    ->select([
        'stock_movements.id',
        'stock_movements.movement_type',
        'stock_movements.direction',
        'stock_movements.quantity',
        'stock_movements.moved_at',
        'products.name as item_name',
    ])
    ->get();

echo "Results for {$today}:\n";
echo "Total rows: " . $movements->count() . "\n";

$byCounts = $movements->groupBy('movement_type')->map->count();
echo "By movement_type:\n";
foreach ($byCounts as $type => $count) {
    echo "  {$type}: {$count}\n";
}

echo "\nProduction_output records:\n";
$movements->where('movement_type', 'production_output')->each(function($m) {
    echo "  ID {$m->id}: +{$m->quantity} | {$m->item_name}\n";
});

echo "\n=== END ===\n";
