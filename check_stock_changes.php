<?php
/**
 * Debug script: Test getStockChangesReport query with different filter scenarios
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TESTING STOCK CHANGES QUERY ===\n";

// Test 1: No filters (like fresh page load)
echo "\n--- Test 1: No filters (default fresh load) ---\n";
$filters = [];
$movements = DB::table('stock_movements')
    ->leftJoin('products', function($join) {
        $join->on('stock_movements.item_id', '=', 'products.id')
             ->where('stock_movements.item_type', '=', 'product');
    })
    ->where('stock_movements.item_type', 'product')
    ->when($filters['business_id'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.business_id', $v))
    ->when($filters['branch_id'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.branch_id', $v))
    ->when($filters['date_from'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
    ->when($filters['date_to'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
    ->select([
        'stock_movements.id',
        'stock_movements.movement_type',
        'stock_movements.direction',
        'stock_movements.quantity',
        'stock_movements.moved_at',
        'products.name as item_name',
    ])
    ->orderByDesc('stock_movements.moved_at')
    ->get();

$typeCounts = $movements->groupBy('movement_type')->map->count();
echo "Total movements: " . $movements->count() . "\n";
echo "By type: " . json_encode($typeCounts->toArray()) . "\n";
echo "Production output count: " . $movements->where('movement_type', 'production_output')->count() . "\n";

// Test 2: With today's date filters
echo "\n--- Test 2: With today's date (2026-10-08) ---\n";
$today = '2026-10-08';
$filters = ['date_from' => $today, 'date_to' => $today];
$movements = DB::table('stock_movements')
    ->leftJoin('products', function($join) {
        $join->on('stock_movements.item_id', '=', 'products.id')
             ->where('stock_movements.item_type', '=', 'product');
    })
    ->where('stock_movements.item_type', 'product')
    ->when($filters['date_from'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
    ->when($filters['date_to'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
    ->select([
        'stock_movements.id',
        'stock_movements.movement_type',
        'stock_movements.direction',
        'stock_movements.quantity',
        'stock_movements.moved_at',
        'products.name as item_name',
    ])
    ->orderByDesc('stock_movements.moved_at')
    ->get();

$typeCounts = $movements->groupBy('movement_type')->map->count();
echo "Total movements: " . $movements->count() . "\n";
echo "By type: " . json_encode($typeCounts->toArray()) . "\n";
echo "Production output count: " . $movements->where('movement_type', 'production_output')->count() . "\n";

// Show first few production_output records
echo "\nFirst production_output records for today:\n";
$movements->where('movement_type', 'production_output')->take(3)->each(function($m) {
    echo "  ID: {$m->id} | Qty: {$m->quantity} | Item: {$m->item_name} | Time: {$m->moved_at}\n";
});

// Test 3: Check business_id and branch_id on today's production_output
echo "\n--- Test 3: Check business_id/branch_id on production_output ---\n";
$prodOutput = DB::table('stock_movements')
    ->where('item_type', 'product')
    ->where('movement_type', 'production_output')
    ->where('moved_at', '>=', '2026-10-08 00:00:00')
    ->where('moved_at', '<=', '2026-10-08 23:59:59')
    ->select('id', 'business_id', 'branch_id', 'moved_at', 'quantity')
    ->orderByDesc('moved_at')
    ->get();

echo "Production output records for 2026-10-08:\n";
$prodOutput->each(function($m) {
    echo "  ID: {$m->id} | Business: {$m->business_id} | Branch: {$m->branch_id} | Qty: {$m->quantity} | Time: {$m->moved_at}\n";
});

// Test 4: With business_id filter
echo "\n--- Test 4: With business_id = 1 filter ---\n";
$filters = ['date_from' => $today, 'date_to' => $today, 'business_id' => 1];
$movements = DB::table('stock_movements')
    ->leftJoin('products', function($join) {
        $join->on('stock_movements.item_id', '=', 'products.id')
             ->where('stock_movements.item_type', '=', 'product');
    })
    ->where('stock_movements.item_type', 'product')
    ->when($filters['business_id'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.business_id', $v))
    ->when($filters['date_from'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.moved_at', '>=', $v . ' 00:00:00'))
    ->when($filters['date_to'] ?? null, fn($q, $v) =>
        $q->where('stock_movements.moved_at', '<=', $v . ' 23:59:59'))
    ->select([
        'stock_movements.id',
        'stock_movements.movement_type',
        'stock_movements.direction',
        'stock_movements.quantity',
        'stock_movements.business_id',
        'stock_movements.branch_id',
        'stock_movements.moved_at',
        'products.name as item_name',
    ])
    ->orderByDesc('stock_movements.moved_at')
    ->get();

$typeCounts = $movements->groupBy('movement_type')->map->count();
echo "Total movements: " . $movements->count() . "\n";
echo "By type: " . json_encode($typeCounts->toArray()) . "\n";
echo "Production output count: " . $movements->where('movement_type', 'production_output')->count() . "\n";

// Show first few records
echo "\nFirst 5 records:\n";
$movements->take(5)->each(function($m) {
    $label = match($m->movement_type) {
        'production_output' => '📦 Manufactured',
        'production_consumption' => '⚙️ Consumed',
        'sale' => '🛒 Sale',
        'purchase' => '📥 Purchase',
        default => ucfirst($m->movement_type),
    };
    $qty = $m->direction === 'in' ? '+' : '-';
    echo "  {$label}: {$qty}{$m->quantity} | {$m->item_name} | Business: {$m->business_id} | Branch: {$m->branch_id}\n";
});

echo "\n=== END DEBUG ===\n";
