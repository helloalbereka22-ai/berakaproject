<!DOCTYPE html>
<html>
<head>
    <title>Fix Products - Make Them Visible</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #1e293b; color: white; }
        .box { background: #334155; padding: 20px; border-radius: 10px; margin: 10px 0; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; margin: 10px 5px; font-weight: bold; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #2563eb; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #4b5563; font-size: 13px; }
        th { background: #1f2937; }
    </style>
</head>
<body>
    <h1>🔧 Fix Products - Make Them Visible in Store</h1>
    
    <?php
    // Bootstrap Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Check if fix button was clicked
    $fixed = false;
    if (isset($_POST['fix_all'])) {
        // Update all products to be marketplace visible and active
        $updated = DB::table('products')
            ->where('status', '!=', 'deleted')
            ->update([
                'status' => 'active',
                'is_marketplace_visible' => true,
                'updated_at' => now()
            ]);
        
        $fixed = true;
        $fixedCount = $updated;
    }
    
    if (isset($_POST['fix_types'])) {
        // Fix product types - change common wrong types to saleable types
        $typeMapping = [
            'raw_material' => 'finished_product',
            'packing_material' => 'finished_product',
            'general' => 'finished_product',
            'finished_oil' => 'finished_product',
        ];
        
        $updatedCount = 0;
        foreach ($typeMapping as $oldType => $newType) {
            $count = DB::table('products')
                ->where('type', $oldType)
                ->update([
                    'type' => $newType,
                    'updated_at' => now()
                ]);
            $updatedCount += $count;
        }
        
        $fixed = true;
        $fixedCount = $updatedCount;
    }
    
    if ($fixed) {
        echo "<div class='box'>";
        echo "<p class='success'>✅ Fixed {$fixedCount} products!</p>";
        echo "<p><a href='/store/products' class='btn btn-success'>🛍️ View Products Page</a></p>";
        echo "</div>";
    }
    
    // Show current status
    $allProducts = DB::table('products')->get();
    $activeAndVisible = DB::table('products')
        ->where('status', 'active')
        ->where('is_marketplace_visible', true)
        ->count();
    
    $saleableTypes = ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion'];
    $correctType = DB::table('products')
        ->whereIn('type', $saleableTypes)
        ->count();
    
    echo "<div class='box'>";
    echo "<h2>📊 Current Status:</h2>";
    echo "<p><strong>Total Products:</strong> " . $allProducts->count() . "</p>";
    echo "<p><strong>Active & Visible:</strong> " . $activeAndVisible . " " . ($activeAndVisible > 0 ? "✅" : "❌") . "</p>";
    echo "<p><strong>Correct Saleable Type:</strong> " . $correctType . " " . ($correctType > 0 ? "✅" : "❌") . "</p>";
    
    if ($activeAndVisible > 0 && $correctType > 0) {
        echo "<p class='success'>✅ Products should be visible now!</p>";
        echo "<p><a href='/store/products' class='btn btn-success'>🛍️ Go to Products Page</a></p>";
    } else {
        echo "<p class='warning'>⚠️ Products need to be fixed</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>🔧 Quick Fixes:</h2>";
    echo "<form method='POST' style='display: inline;'>";
    echo "<button type='submit' name='fix_all' class='btn btn-success'>✅ Fix All Products<br><small>(Make Active & Visible)</small></button>";
    echo "</form>";
    
    echo "<form method='POST' style='display: inline;'>";
    echo "<button type='submit' name='fix_types' class='btn btn-warning' style='background: #f59e0b;'>🔄 Fix Product Types<br><small>(Change to Saleable Types)</small></button>";
    echo "</form>";
    
    echo "<p class='warning' style='margin-top: 20px; font-size: 13px;'>⚠️ This will update ALL products. Make sure you want to do this!</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>📋 All Products:</h2>";
    
    if ($allProducts->isEmpty()) {
        echo "<p class='error'>❌ No products found in database!</p>";
        echo "<p>Go to Admin → Item Management to add products.</p>";
    } else {
        echo "<div style='max-height: 500px; overflow-y: auto;'>";
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Visible?</th>
            <th>Price</th>
        </tr>";
        
        foreach ($allProducts as $product) {
            $isCorrectType = in_array($product->type, $saleableTypes);
            $isActive = $product->status === 'active';
            $isVisible = $product->is_marketplace_visible;
            
            $typeColor = $isCorrectType ? 'success' : 'error';
            $statusColor = $isActive ? 'success' : 'warning';
            $visibleColor = $isVisible ? 'success' : 'error';
            
            echo "<tr>";
            echo "<td>{$product->id}</td>";
            echo "<td>" . htmlspecialchars($product->name) . "</td>";
            echo "<td class='{$typeColor}'>{$product->type}</td>";
            echo "<td class='{$statusColor}'>{$product->status}</td>";
            echo "<td class='{$visibleColor}'>" . ($isVisible ? 'Yes ✅' : 'No ❌') . "</td>";
            echo "<td>" . number_format($product->selling_price, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>🔗 Links:</h2>";
    echo "<a href='/admin/item-manager' class='btn'>📝 Item Management</a>";
    echo "<a href='/store/products' class='btn'>🛍️ Products Page</a>";
    echo "<a href='/test-all-products.php' class='btn'>🔍 Detailed Debug</a>";
    echo "<a href='/' class='btn'>🏠 Homepage</a>";
    echo "</div>";
    ?>
</body>
</html>
