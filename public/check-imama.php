<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html><html><head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}
h2{color:#38bdf8;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th{background:#334155;padding:6px 8px;text-align:left;}
td{padding:5px 8px;border-bottom:1px solid #334155;}
.good{color:#34d399;} .bad{color:#f87171;}
</style></head><body>
<?php

// Find Imama product
$product = DB::table('products')->where('name','like','%Imama%')->orWhere('name','like','%imama%')->first();
echo "<div class='box'><h2>Imama Product</h2>";
echo "<p>ID: {$product->id} | Name: {$product->name} | Type: {$product->type}</p></div>";

// Show ALL purchase records for Imama
echo "<div class='box'><h2>ALL Purchase Records for Imama</h2>";
$purchases = DB::table('purchases')
    ->where('product_id', $product->id)
    ->get();
echo "<p>Total purchase records: {$purchases->count()}</p>";
echo "<table><tr><th>ID</th><th>qty</th><th>unit_price</th><th>total_price</th><th>status</th><th>date</th></tr>";
$totalQty = 0;
foreach ($purchases as $p) {
    echo "<tr><td>{$p->id}</td><td>{$p->quantity}</td><td>{$p->unit_price}</td><td>{$p->total_price}</td><td>{$p->status}</td><td>{$p->purchase_date}</td></tr>";
    $totalQty += $p->quantity;
}
echo "</table>";
echo "<p class='good'>TOTAL QTY across all purchases: <b>{$totalQty}</b></p>";
echo "</div>";

// Show what the manager's price page shows
echo "<div class='box'><h2>How 320 is calculated (PurchaseSummary query)</h2>";
$summary = DB::table('purchases')
    ->join('products', 'purchases.product_id', '=', 'products.id')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->where('purchases.product_id', $product->id)
    ->selectRaw('products.name, SUM(purchases.quantity) as total_qty, AVG(purchases.unit_price) as avg_price, SUM(purchases.total_price) as total_value')
    ->groupBy('products.name')
    ->first();
echo "<p>Grouped total_qty: <b class='good'>{$summary->total_qty}</b></p>";
echo "<p>This is SUM of all purchase records for Imama</p>";
echo "</div>";

?>
</body></html>
