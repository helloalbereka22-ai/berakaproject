<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre style='font-family:monospace;font-size:12px'>";

// Check stock_transfers table
$cols = DB::getSchemaBuilder()->getColumnListing('stock_transfers');
echo "stock_transfers columns:\n   " . implode(', ', $cols) . "\n\n";

$rows = DB::table('stock_transfers')->limit(5)->get();
echo "Sample stock_transfers:\n";
foreach ($rows as $r) { echo "  " . json_encode($r) . "\n"; }
echo "\n";

// Check stock_movements with transfer type
$tm = DB::table('stock_movements')
    ->join('products','stock_movements.item_id','=','products.id')
    ->leftJoin('categories','products.category_id','=','categories.id')
    ->leftJoin('branches','stock_movements.branch_id','=','branches.id')
    ->where('stock_movements.reference_type','transfer')
    ->select('stock_movements.*','products.name as item','categories.name as cat','branches.name as branch')
    ->limit(10)->get();

echo "stock_movements (transfer type):\n";
if ($tm->isEmpty()) { echo "  NONE\n"; }
foreach ($tm as $r) {
    echo "  id={$r->id} item={$r->item} cat={$r->cat} branch={$r->branch} qty={$r->quantity} dir={$r->direction} ref_id={$r->reference_id} notes={$r->notes} moved_at={$r->moved_at}\n";
}
echo "\n";

// Distinct reference_ids for transfers
$refs = DB::table('stock_movements')
    ->where('reference_type','transfer')
    ->where('direction','in')
    ->distinct()->pluck('reference_id')->toArray();
echo "Transfer reference_ids (in): " . implode(', ', $refs) . "\n";

echo "</pre>";
