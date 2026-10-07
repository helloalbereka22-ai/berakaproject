<?php
require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$today = now()->format('Y-m-d');

echo "=== STOCK MOVEMENTS FOR TODAY ($today) ===\n\n";

// Check all movements for today
$allMovements = DB::table('stock_movements')
    ->join('products', 'stock_movements.item_id', '=', 'products.id')
    ->whereDate('stock_movements.moved_at', '=', $today)
    ->orderByDesc('stock_movements.moved_at')
    ->select('stock_movements.id', 'stock_movements.movement_type', 'stock_movements.direction', 
             'stock_movements.quantity', 'stock_movements.moved_at', 'products.name')
    ->get();

echo "Total movements today: " . count($allMovements) . "\n";
foreach ($allMovements as $m) {
    echo "ID: {$m->id}, Type: {$m->movement_type}, Direction: {$m->direction}, Qty: {$m->quantity}, Product: {$m->name}\n";
}

echo "\n=== PRODUCTION OUTPUT ONLY ===\n";
$productionOutput = DB::table('stock_movements')
    ->join('products', 'stock_movements.item_id', '=', 'products.id')
    ->where('stock_movements.movement_type', 'production_output')
    ->whereDate('stock_movements.moved_at', '=', $today)
    ->orderByDesc('stock_movements.moved_at')
    ->select('stock_movements.id', 'stock_movements.movement_type', 'stock_movements.quantity', 'products.name')
    ->get();

echo "Production output today: " . count($productionOutput) . "\n";
foreach ($productionOutput as $m) {
    echo "ID: {$m->id}, Type: {$m->movement_type}, Qty: {$m->quantity}, Product: {$m->name}\n";
}

echo "\n=== WITH LEFT JOIN (Like the Report) ===\n";
$withLeftJoin = DB::table('stock_movements')
    ->leftJoin('products', 'stock_movements.item_id', '=', 'products.id')
    ->where('stock_movements.movement_type', 'production_output')
    ->whereDate('stock_movements.moved_at', '=', $today)
    ->orderByDesc('stock_movements.moved_at')
    ->select('stock_movements.id', 'stock_movements.movement_type', 'stock_movements.item_id', 
             'stock_movements.quantity', 'products.name as item_name')
    ->get();

echo "Production output with LEFT JOIN: " . count($withLeftJoin) . "\n";
foreach ($withLeftJoin as $m) {
    echo "ID: {$m->id}, Type: {$m->movement_type}, Item ID: {$m->item_id}, Qty: {$m->quantity}, Item Name: " . ($m->item_name ?? 'NULL') . "\n";
}
