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
h2{color:#38bdf8;margin:5px 0;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th{background:#334155;padding:8px;text-align:left;}
td{padding:6px 8px;border-bottom:1px solid #334155;}
.good{color:#34d399;} .bad{color:#f87171;} .warn{color:#fbbf24;}
</style></head>
<body>
<?php

// 1. Find jelabiya product
echo "<div class='box'><h2>1. Product Search</h2>";
$products = DB::table('products')
    ->whereIn('type', ['book','stationery','fashion','general'])
    ->get(['id','name','type']);
echo "<table><tr><th>ID</th><th>Name</th><th>Type</th></tr>";
foreach($products as $p) echo "<tr><td>{$p->id}</td><td>{$p->name}</td><td>{$p->type}</td></tr>";
echo "</table></div>";

// 2. For each product, show stock movements
echo "<div class='box'><h2>2. Stock Movements per Product</h2>";
foreach($products as $p) {
    $moves = DB::table('stock_movements')->where('item_id', $p->id)->get();
    $balance = DB::table('stock_movements')
        ->where('item_id', $p->id)
        ->selectRaw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as bal")
        ->value('bal') ?? 0;
    
    $class = $balance > 0 ? 'good' : ($balance < 0 ? 'bad' : 'warn');
    echo "<p class='{$class}'><b>{$p->name}</b> (id:{$p->id}) — Balance: <b>{$balance}</b> — Movements: {$moves->count()}</p>";
    
    if($moves->count() > 0) {
        echo "<table><tr><th>movement_type</th><th>direction</th><th>qty</th><th>item_type</th><th>ref_type</th><th>ref_id</th></tr>";
        foreach($moves as $m) {
            echo "<tr><td>{$m->movement_type}</td><td>{$m->direction}</td><td>{$m->quantity}</td><td>{$m->item_type}</td><td>{$m->reference_type}</td><td>{$m->reference_id}</td></tr>";
        }
        echo "</table>";
    }
}
echo "</div>";

// 3. Show all stock_movements item_type values
echo "<div class='box'><h2>3. All item_type values in stock_movements</h2>";
$types = DB::table('stock_movements')->select('item_type',DB::raw('COUNT(*) as cnt'))->groupBy('item_type')->get();
foreach($types as $t) echo "<p>{$t->item_type} — {$t->cnt} rows</p>";
echo "</div>";

// 4. Guest orders
echo "<div class='box'><h2>4. Guest Orders</h2>";
$orders = DB::table('guest_orders')->orderByDesc('id')->limit(5)->get();
foreach($orders as $o) {
    echo "<p><b>{$o->order_number}</b> | status: {$o->status} | items: " . htmlspecialchars($o->items) . "</p>";
}
echo "</div>";
?>
</body>
</html>
