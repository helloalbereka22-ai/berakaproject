<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:13px;'>";

echo "=== STOCK MOVEMENT TYPES IN DB ===\n";
$types = DB::table('stock_movements')
    ->select('movement_type', 'direction', DB::raw('COUNT(*) as count'))
    ->groupBy('movement_type', 'direction')
    ->get();
foreach ($types as $t) {
    echo "movement_type: {$t->movement_type} | direction: {$t->direction} | count: {$t->count}\n";
}

echo "\n=== PRODUCT TYPES IN DB ===\n";
$ptypes = DB::table('products')
    ->select('type', DB::raw('COUNT(*) as count'))
    ->groupBy('type')
    ->get();
foreach ($ptypes as $p) {
    echo "type: {$p->type} | count: {$p->count}\n";
}

echo "\n=== SAMPLE STOCK MOVEMENTS (first 10) ===\n";
$moves = DB::table('stock_movements')
    ->join('products', 'stock_movements.item_id', '=', 'products.id')
    ->select('stock_movements.movement_type', 'stock_movements.direction', 'products.name', 'products.type', 'stock_movements.quantity')
    ->limit(10)
    ->get();
foreach ($moves as $m) {
    echo "movement: {$m->movement_type} | dir: {$m->direction} | product: {$m->name} | type: {$m->type} | qty: {$m->quantity}\n";
}

echo "\n=== PURCHASES TABLE (if exists) ===\n";
if (Schema::hasTable('purchases')) {
    $purchases = DB::table('purchases')->limit(5)->get();
    foreach ($purchases as $p) {
        echo json_encode((array)$p) . "\n";
    }
} else {
    echo "purchases table does NOT exist\n";
}
echo "</pre>";
