<!DOCTYPE html>
<html>
<head>
    <title>Product Separation Test</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: white; padding: 20px; }
        .section { background: #2a2a2a; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .good { color: #00ff00; }
        .bad { color: #ff0000; }
        h2 { color: #ffa500; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #333; }
        .btn { display: inline-block; padding: 15px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Product Separation Test</h1>
    
    <?php
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Count products by type
    $salesProducts = DB::table('products')
        ->where('status', 'active')
        ->where('is_marketplace_visible', 1)
        ->whereIn('type', ['book', 'stationery', 'fashion', 'general'])
        ->count();
    
    $manufProducts = DB::table('products')
        ->where('status', 'active')
        ->where('is_marketplace_visible', 1)
        ->whereIn('type', ['finished_product', 'bottle_product'])
        ->count();
    
    echo "<div class='section'>";
    echo "<h2>Products Page (Saleable Items)</h2>";
    echo "<p class='". ($salesProducts > 0 ? "good" : "bad") ."'>Books, Stationery, Fashion: {$salesProducts} items</p>";
    echo "<p><small>Types: book, stationery, fashion, general</small></p>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Manufacturing Page (Manufactured Products)</h2>";
    echo "<p class='". ($manufProducts > 0 ? "good" : "bad") ."'>Oils, Bottles: {$manufProducts} items</p>";
    echo "<p><small>Types: finished_product, bottle_product</small></p>";
    echo "</div>";
    
    // Show all products with their types
    echo "<div class='section'>";
    echo "<h2>All Products Breakdown</h2>";
    $allProducts = DB::table('products')
        ->select('id', 'name', 'type', 'status', 'is_marketplace_visible')
        ->get();
    
    echo "<table>";
    echo "<tr><th>Name</th><th>Type</th><th>Shows In</th></tr>";
    foreach ($allProducts as $p) {
        $showsIn = "❌ Not Visible";
        if ($p->status === 'active' && $p->is_marketplace_visible) {
            if (in_array($p->type, ['book', 'stationery', 'fashion', 'general'])) {
                $showsIn = "<span class='good'>✅ PRODUCTS Page</span>";
            } elseif (in_array($p->type, ['finished_product', 'bottle_product'])) {
                $showsIn = "<span class='good'>✅ MANUFACTURING Page</span>";
            }
        }
        echo "<tr>";
        echo "<td>" . htmlspecialchars($p->name) . "</td>";
        echo "<td>{$p->type}</td>";
        echo "<td>{$showsIn}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Visit Pages</h2>";
    echo "<a href='/store/products' class='btn'>🛍️ PRODUCTS Page</a>";
    echo "<a href='/manufacturing' class='btn'>🏭 MANUFACTURING Page</a>";
    echo "<a href='/' class='btn'>🏠 Home</a>";
    echo "</div>";
    ?>
</body>
</html>
