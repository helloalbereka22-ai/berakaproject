<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html>
<html>
<head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:20px;border-radius:10px;margin:15px 0;}
h2{color:#38bdf8;margin:0 0 10px;}
table{width:100%;border-collapse:collapse;}
th{background:#334155;padding:8px;text-align:left;font-size:12px;}
td{padding:8px;border-bottom:1px solid #334155;font-size:12px;}
.good{color:#34d399;} .bad{color:#f87171;}
</style></head>
<body>
<?php

// 1. Check all tables
echo "<div class='box'><h2>All Tables in Database</h2><table><tr><th>Table</th><th>Rows</th></tr>";
$tables = DB::select("SHOW TABLES");
foreach ($tables as $t) {
    $tname = array_values((array)$t)[0];
    $count = DB::table($tname)->count();
    echo "<tr><td>{$tname}</td><td>{$count}</td></tr>";
}
echo "</table></div>";

// 2. Products with type raw_material or packing_material
echo "<div class='box'><h2>Raw Material Products</h2><table><tr><th>ID</th><th>Name</th><th>Type</th></tr>";
$raws = DB::table('products')->whereIn('type', ['raw_material', 'packing_material'])->get();
foreach ($raws as $r) {
    echo "<tr><td>{$r->id}</td><td>{$r->name}</td><td>{$r->type}</td></tr>";
}
if ($raws->isEmpty()) echo "<tr><td colspan='3' class='bad'>NO raw_material or packing_material products found!</td></tr>";
echo "</table></div>";

// 3. Purchases table
if (Schema::hasTable('purchases')) {
    echo "<div class='box'><h2>Purchases Table - All Records</h2><table><tr><th>ID</th><th>Product</th><th>Type</th><th>Qty</th><th>Status</th><th>Date</th></tr>";
    $purchases = DB::table('purchases')
        ->join('products', 'purchases.product_id', '=', 'products.id')
        ->select('purchases.*', 'products.name as product_name', 'products.type as product_type')
        ->limit(20)->get();
    foreach ($purchases as $p) {
        echo "<tr><td>{$p->id}</td><td>{$p->product_name}</td><td>{$p->product_type}</td><td>{$p->quantity}</td><td>{$p->status}</td><td>{$p->purchase_date}</td></tr>";
    }
    if ($purchases->isEmpty()) echo "<tr><td colspan='6' class='bad'>PURCHASES TABLE IS EMPTY!</td></tr>";
    echo "</table></div>";
} else {
    echo "<div class='box bad'><h2>purchases table does NOT EXIST</h2></div>";
}

// 4. Stock movements - raw materials
echo "<div class='box'><h2>Stock Movements - All Types</h2><table><tr><th>movement_type</th><th>direction</th><th>count</th></tr>";
$moves = DB::table('stock_movements')
    ->select('movement_type', 'direction', DB::raw('COUNT(*) as cnt'))
    ->groupBy('movement_type', 'direction')
    ->get();
foreach ($moves as $m) {
    echo "<tr><td>{$m->movement_type}</td><td>{$m->direction}</td><td>{$m->cnt}</td></tr>";
}
echo "</table></div>";

?>
</body>
</html>
