<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html>
<html>
<head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:15px 0;}
h2{color:#fbbf24;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th{background:#334155;padding:8px;text-align:left;}
td{padding:6px 8px;border-bottom:1px solid #334155;}
.good{color:#34d399;} .bad{color:#f87171;}
</style></head>
<body>
<?php

// 1. Find manufactured products
echo "<div class='box'><h2>Manufactured Products (finished_product / bottle_product)</h2>";
$products = DB::table('products')
    ->whereIn('type', ['finished_product', 'bottle_product'])
    ->get(['id','name','type']);
echo "<table><tr><th>ID</th><th>Name</th><th>Type</th><th>Stock (stock_movements)</th><th>In purchases?</th></tr>";
foreach($products as $p) {
    $balance = DB::table('stock_movements')
        ->where('item_id', $p->id)
        ->selectRaw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as bal")
        ->value('bal') ?? 0;
    
    $inPurchases = DB::table('purchases')->where('product_id', $p->id)->count();
    $inBranchSales = DB::table('branch_sales')->where('product_id', $p->id)->count();
    
    echo "<tr><td>{$p->id}</td><td>{$p->name}</td><td>{$p->type}</td>";
    echo "<td class='" . ($balance > 0 ? 'good' : 'bad') . "'>{$balance}</td>";
    echo "<td>{$inPurchases} purchases | {$inBranchSales} branch_sales</td></tr>";
}
echo "</table></div>";

// 2. Show stock_movements for manufactured products
echo "<div class='box'><h2>Stock Movements for Manufactured Products</h2>";
foreach($products as $p) {
    $moves = DB::table('stock_movements')->where('item_id', $p->id)->get();
    if($moves->isEmpty()) continue;
    echo "<p class='good'><b>{$p->name}</b></p>";
    echo "<table><tr><th>type</th><th>dir</th><th>qty</th><th>item_type</th></tr>";
    foreach($moves as $m) {
        echo "<tr><td>{$m->movement_type}</td><td>{$m->direction}</td><td>{$m->quantity}</td><td>{$m->item_type}</td></tr>";
    }
    echo "</table>";
}
echo "</div>";

// 3. Check branch_sales table for these products
echo "<div class='box'><h2>Branch Sales for Manufactured Products</h2>";
$bsales = DB::table('branch_sales')
    ->join('products','branch_sales.product_id','=','products.id')
    ->whereIn('products.type',['finished_product','bottle_product'])
    ->select('products.name','branch_sales.quantity','branch_sales.sale_date')
    ->limit(10)->get();
if($bsales->isEmpty()) echo "<p class='bad'>No branch sales found</p>";
foreach($bsales as $s) echo "<p>{$s->name} | qty: {$s->quantity} | date: {$s->sale_date}</p>";
echo "</div>";

?>
</body>
</html>
