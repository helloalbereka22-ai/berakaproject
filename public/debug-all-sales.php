<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='font-family:monospace;font-size:13px'>";

// 1. branch_sales columns
$cols = DB::getSchemaBuilder()->getColumnListing('branch_sales');
echo "1) branch_sales columns:\n   " . implode(', ', $cols) . "\n\n";

// 2. Sample branch_sales
$rows = DB::table('branch_sales')
    ->leftJoin('branches','branch_sales.branch_id','=','branches.id')
    ->select('branch_sales.*','branches.name as branch_name')
    ->limit(10)->get();
echo "2) Sample branch_sales:\n";
foreach ($rows as $r) {
    echo "   " . json_encode($r) . "\n";
}
echo "\n";

// 3. orders table columns
$cols2 = DB::getSchemaBuilder()->getColumnListing('orders');
echo "3) orders columns:\n   " . implode(', ', $cols2) . "\n\n";

// 4. Sample orders
$orders = DB::table('orders')
    ->leftJoin('branches','orders.branch_id','=','branches.id')
    ->select('orders.id','orders.total_amount','orders.status','orders.created_at','branches.name as branch')
    ->limit(10)->get();
echo "4) Sample orders:\n";
foreach ($orders as $o) {
    echo "   id={$o->id} branch={$o->branch} total={$o->total_amount} status={$o->status} date={$o->created_at}\n";
}
echo "\n";

// 5. invoices columns
$cols3 = DB::getSchemaBuilder()->getColumnListing('invoices');
echo "5) invoices columns:\n   " . implode(', ', $cols3) . "\n\n";

// 6. Sample invoices
$invoices = DB::table('invoices')->limit(5)->get();
echo "6) Sample invoices:\n";
foreach ($invoices as $inv) {
    echo "   " . json_encode($inv) . "\n";
}

echo "\n\n";
// 7. profit_ledger columns
$cols4 = DB::getSchemaBuilder()->getColumnListing('profit_ledger');
echo "7) profit_ledger columns:\n   " . implode(', ', $cols4) . "\n\n";
$pl = DB::table('profit_ledger')->limit(5)->get();
echo "8) Sample profit_ledger:\n";
foreach ($pl as $r) {
    echo "   " . json_encode($r) . "\n";
}

echo "</pre>";
