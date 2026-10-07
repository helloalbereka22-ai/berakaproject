<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='font-family:monospace;font-size:13px'>";

// 1. guest_orders columns
$cols = DB::getSchemaBuilder()->getColumnListing('guest_orders');
echo "1) guest_orders columns:\n   " . implode(', ', $cols) . "\n\n";

// 2. Sample guest_orders
$orders = DB::table('guest_orders')->limit(5)->get();
echo "2) Sample guest_orders:\n";
foreach ($orders as $o) {
    echo "   " . json_encode($o) . "\n";
}
echo "\n";

// 3. order_items / guest_order_items table?
$tables = DB::select("SHOW TABLES");
echo "3) All tables:\n";
foreach ($tables as $t) {
    $vals = (array)$t;
    echo "   " . reset($vals) . "\n";
}

// 4. Today's confirmed orders sum
$today = now()->toDateString();
$sum = DB::table('guest_orders')->whereDate('created_at', $today)->where('status', 'confirmed')->sum('total_amount');
echo "\n4) Today confirmed total_amount sum: $sum\n";

// 5. All statuses
$statuses = DB::table('guest_orders')->distinct()->pluck('status')->toArray();
echo "5) guest_orders statuses: " . implode(', ', $statuses) . "\n";

// 6. Total amount column check
$allOrders = DB::table('guest_orders')->get(['id','status','total_amount','created_at']);
echo "6) All orders with total_amount:\n";
foreach ($allOrders as $o) {
    echo "   id={$o->id} status={$o->status} total={$o->total_amount} date={$o->created_at}\n";
}

echo "</pre>";
