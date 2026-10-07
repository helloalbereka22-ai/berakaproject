<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Products</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: white; padding: 20px; }
        .box { background: #2a2a2a; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .good { color: #00ff00; }
        .bad { color: #ff0000; }
        .btn { display: inline-block; padding: 15px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Product Database Check</h1>
    
    <?php
    $total = DB::table('products')->count();
    echo "<div class='box'>";
    echo "<h2>Total Products: <span class='" . ($total > 0 ? "good" : "bad") . "'>{$total}</span></h2>";
    
    if ($total === 0) {
        echo "<p class='bad'>❌ NO PRODUCTS! You need to add products first.</p>";
        echo "<a href='/admin/item-manager' class='btn'>Add Products in Admin</a>";
        echo "</div></body></html>";
        exit;
    }
    
    $visible = DB::table('products')
        ->where('status', 'active')
        ->where('is_marketplace_visible', 1)
        ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general'])
        ->count();
    
    echo "<h2>Visible on Store: <span class='" . ($visible > 0 ? "good" : "bad") . "'>{$visible}</span></h2>";
    echo "</div>";
    
    if ($visible === 0 && $total > 0) {
        echo "<div class='box' style='background: #3a1a1a;'>";
        echo "<h2 class='bad'>⚠️ Products exist but are NOT visible!</h2>";
        echo "<p>Click button below to fix:</p>";
        
        if (isset($_POST['fix'])) {
            DB::table('products')->update([
                'status' => 'active',
                'is_marketplace_visible' => 1
            ]);
            
            DB::table('products')
                ->whereIn('type', ['raw_material', 'packing_material'])
                ->update(['type' => 'finished_product']);
            
            echo "<p class='good'>✅ FIXED! Refresh page to see results.</p>";
            echo "<a href='/store/products' class='btn' style='background: #00ff00; color: black;'>VIEW STORE NOW</a>";
        } else {
            echo "<form method='POST'>";
            echo "<button type='submit' name='fix' class='btn' style='background: #ff6600; font-size: 20px; padding: 20px 40px;'>🔧 FIX IT NOW</button>";
            echo "</form>";
        }
        echo "</div>";
    }
    
    echo "<div class='box'>";
    echo "<h2>All Products:</h2>";
    $products = DB::table('products')->get();
    foreach ($products as $p) {
        $ok = ($p->status === 'active' && $p->is_marketplace_visible && in_array($p->type, ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general']));
        echo "<div style='padding: 10px; margin: 5px 0; background: " . ($ok ? "#1a3a1a" : "#3a1a1a") . "; border-radius: 5px;'>";
        echo "<strong>{$p->name}</strong> - Type: {$p->type}, Status: {$p->status}, Visible: " . ($p->is_marketplace_visible ? "YES" : "NO");
        echo $ok ? " <span class='good'>✅ OK</span>" : " <span class='bad'>❌ NOT SHOWING</span>";
        echo "</div>";
    }
    echo "</div>";
    ?>
    
    <a href='/store/products' class='btn'>Go to Store</a>
</body>
</html>
