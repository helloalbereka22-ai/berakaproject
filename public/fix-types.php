<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fix Product Types</title>
    <style>
        body { font-family: Arial; background: #0f172a; color: white; padding: 20px; }
        .box { background: #1e293b; padding: 20px; border-radius: 12px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #334155; text-align: left; }
        th { background: #334155; }
        .products { color: #34d399; font-weight: bold; }
        .manuf    { color: #fbbf24; font-weight: bold; }
        .unknown  { color: #f87171; font-weight: bold; }
        .btn  { display:inline-block; padding:12px 28px; border-radius:8px; font-weight:bold; font-size:15px; border:none; cursor:pointer; margin:6px 4px; text-decoration:none; }
        .btn-manuf   { background:#f59e0b; color:#000; }
        .btn-product { background:#10b981; color:#fff; }
        .btn-view    { background:#3b82f6; color:#fff; }
        .success { color: #34d399; font-size: 20px; }
    </style>
</head>
<body>
<h1>🔧 Fix Product Types</h1>

<?php
// Handle individual type fix
if (isset($_POST['set_type']) && isset($_POST['product_id'])) {
    DB::table('products')
        ->where('id', $_POST['product_id'])
        ->update(['type' => $_POST['set_type']]);
    echo "<div class='box'><p class='success'>✅ Updated! Refreshing...</p></div>";
    echo "<script>setTimeout(()=>location.reload(),800);</script>";
}

// Handle bulk fix
if (isset($_POST['move_all_oils'])) {
    // Move all products with oil-related names to finished_product
    DB::table('products')
        ->whereIn('type', ['book', 'stationery', 'fashion', 'general', 'finished_product', 'bottle_product'])
        ->where(function($q) {
            $q->where('name', 'like', '%ml%')
              ->orWhere('name', 'like', '%telba%')
              ->orWhere('name', 'like', '%azmud%')
              ->orWhere('name', 'like', '%oil%')
              ->orWhere('name', 'like', '%zait%')
              ->orWhere('name', 'like', '%zeyt%')
              ->orWhere('name', 'like', '%liter%')
              ->orWhere('name', 'like', '%litre%')
              ->orWhere('name', 'like', '%selit%')
              ->orWhere('name', 'like', '%silet%');
        })
        ->update(['type' => 'finished_product']);
    echo "<div class='box'><p class='success'>✅ Moved oil/ml products to Manufacturing!</p></div>";
    echo "<script>setTimeout(()=>location.reload(),800);</script>";
}

// Show all products
$products = DB::table('products')
    ->where('status', 'active')
    ->where('is_marketplace_visible', 1)
    ->orderBy('type')
    ->orderBy('name')
    ->get(['id', 'name', 'type']);

echo "<div class='box'>";
echo "<h2>All Visible Products — Click to Change Type</h2>";
echo "<table>";
echo "<tr><th>Product Name</th><th>Current Page</th><th>Move To:</th></tr>";

foreach ($products as $p) {
    if (in_array($p->type, ['finished_product', 'bottle_product'])) {
        $page = "<span class='manuf'>🏭 Manufacturing</span>";
        $moveBtn = "<form method='POST' style='display:inline'>
            <input type='hidden' name='product_id' value='{$p->id}'>
            <input type='hidden' name='set_type' value='general'>
            <button class='btn btn-product'>→ Move to Products</button>
        </form>";
    } else {
        $page = "<span class='products'>🛍️ Products</span>";
        $moveBtn = "<form method='POST' style='display:inline'>
            <input type='hidden' name='product_id' value='{$p->id}'>
            <input type='hidden' name='set_type' value='finished_product'>
            <button class='btn btn-manuf'>→ Move to Manufacturing</button>
        </form>";
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($p->name) . "</td>";
    echo "<td>{$page}</td>";
    echo "<td>{$moveBtn}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";
?>

<div class="box">
    <h2>🚀 Quick Bulk Action</h2>
    <p style="margin-bottom:15px; color:#94a3b8;">Click below to automatically move ALL oil/ml products (100ml telba, azmud, etc.) to Manufacturing:</p>
    <form method="POST">
        <button type="submit" name="move_all_oils" class="btn btn-manuf" style="font-size:18px; padding:15px 40px;">
            🏭 Move All Oil/ML Products → Manufacturing
        </button>
    </form>
</div>

<div class="box">
    <a href="/store/products" class="btn btn-view">🛍️ View Products Page</a>
    <a href="/manufacturing" class="btn btn-view">🏭 View Manufacturing Page</a>
</div>

</body>
</html>
