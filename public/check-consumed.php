<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:20px;font-size:13px;'>";

// Check stock_movements for raw material consumption
echo "=== STOCK_MOVEMENTS (out direction for raw materials) ===\n";
$moves = DB::table('stock_movements')
    ->join('products','stock_movements.item_id','=','products.id')
    ->whereIn('products.type',['raw_material','packing_material'])
    ->where('stock_movements.direction','out')
    ->select('products.name','stock_movements.movement_type','stock_movements.direction','stock_movements.quantity','stock_movements.item_type')
    ->limit(20)->get();
if($moves->isEmpty()) echo "NO stock_movements found for raw materials (direction=out)\n";
foreach($moves as $m) echo "product={$m->name} | type={$m->movement_type} | dir={$m->direction} | qty={$m->quantity} | item_type={$m->item_type}\n";

// Check batch_material_consumption
echo "\n=== BATCH_MATERIAL_CONSUMPTION ===\n";
if(Schema::hasTable('batch_material_consumption')) {
    $batches = DB::table('batch_material_consumption')
        ->join('products','batch_material_consumption.raw_material_id','=','products.id')
        ->select('products.name','batch_material_consumption.quantity_consumed')
        ->limit(20)->get();
    if($batches->isEmpty()) echo "NO records in batch_material_consumption\n";
    foreach($batches as $b) echo "product={$b->name} | consumed={$b->quantity_consumed}\n";
} else {
    echo "batch_material_consumption table does NOT exist\n";
}

// Check purchases table - how manufacturer deducts
echo "\n=== PURCHASES (after manufacturer deduction) ===\n";
$purs = DB::table('purchases')
    ->join('products','purchases.product_id','=','products.id')
    ->whereIn('products.type',['raw_material','packing_material'])
    ->where('purchases.status','completed')
    ->select('products.name','purchases.quantity','purchases.unit_price','purchases.branch_id','purchases.purchase_date')
    ->limit(20)->get();
foreach($purs as $p) {
    $branch = DB::table('branches')->where('id',$p->branch_id)->value('name') ?? 'No branch';
    echo "product={$p->name} | qty={$p->quantity} | branch={$branch} | date={$p->purchase_date}\n";
}

// Check what the manufacturer page actually saves when deducting
echo "\n=== SEARCH FOR DEDUCTION in ALL stock movements ===\n";
$allMoves = DB::table('stock_movements')
    ->select('movement_type','direction',DB::raw('COUNT(*) as cnt'),DB::raw('SUM(quantity) as total'))
    ->groupBy('movement_type','direction')
    ->get();
foreach($allMoves as $m) echo "type={$m->movement_type} | dir={$m->direction} | count={$m->cnt} | total={$m->total}\n";

echo "</pre>";
