<!DOCTYPE html>
<html>
<head>
    <title>All Products Debug</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #1e293b; color: white; }
        .box { background: #334155; padding: 20px; border-radius: 10px; margin: 10px 0; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #4b5563; }
        th { background: #1f2937; position: sticky; top: 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-yes { background: #10b981; color: white; }
        .badge-no { background: #ef4444; color: white; }
        .badge-type { background: #3b82f6; color: white; }
        .btn { display: inline-block; padding: 8px 16px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; margin: 5px; font-weight: bold; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>🔍 All Products Debug</h1>
    
    <?php
    // Bootstrap Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "<div class='box'>";
    echo "<h2>Database Connection:</h2>";
    try {
        DB::connection()->getPdo();
        echo "<p class='success'>✅ Connected to database</p>";
    } catch (\Exception $e) {
        echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        die();
    }
    echo "</div>";
    
    // Get all products
    $allProducts = DB::table('products')->orderBy('id')->get();
    
    echo "<div class='box'>";
    echo "<h2>Total Products in Database:</h2>";
    echo "<p class='success'>✅ Found " . $allProducts->count() . " total products</p>";
    echo "</div>";
    
    // Saleable types
    $saleableTypes = ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion'];
    
    echo "<div class='box'>";
    echo "<h2>Saleable Products (What SHOULD appear in store):</h2>";
    echo "<p>Products with type: finished_product, bottle_product, book, stationery, or fashion</p>";
    
    $saleableProducts = DB::table('products')
        ->whereIn('type', $saleableTypes)
        ->orderBy('id')
        ->get();
    
    if ($saleableProducts->isEmpty()) {
        echo "<p class='error'>❌ No saleable products found!</p>";
        echo "<p class='warning'>⚠️ Your products have the wrong type!</p>";
        echo "<p><strong>Solution:</strong> Change product types in Admin → Item Management</p>";
    } else {
        echo "<p class='success'>✅ Found " . $saleableProducts->count() . " saleable product(s)</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Marketplace-Ready Products:</h2>";
    echo "<p>Products that WILL appear on store (saleable type + active + marketplace_visible)</p>";
    
    $marketplaceProducts = DB::table('products')
        ->whereIn('type', $saleableTypes)
        ->where('status', 'active')
        ->where('is_marketplace_visible', true)
        ->get();
    
    if ($marketplaceProducts->isEmpty()) {
        echo "<p class='error'>❌ No marketplace-ready products!</p>";
        echo "<p class='warning'>⚠️ This is why your store is empty!</p>";
    } else {
        echo "<p class='success'>✅ Found " . $marketplaceProducts->count() . " marketplace-ready product(s)</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>All Products Breakdown:</h2>";
    
    if ($allProducts->isEmpty()) {
        echo "<p class='error'>❌ No products in database at all!</p>";
        echo "<p>Go to Admin → Item Management to add products.</p>";
    } else {
        echo "<div style='max-height: 600px; overflow-y: auto;'>";
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Marketplace Visible</th>
            <th>Price</th>
            <th>Problem?</th>
        </tr>";
        
        foreach ($allProducts as $product) {
            $isSaleable = in_array($product->type, $saleableTypes);
            $isActive = $product->status === 'active';
            $isVisible = $product->is_marketplace_visible;
            
            $problems = [];
            if (!$isSaleable) $problems[] = "Wrong type (not saleable)";
            if (!$isActive) $problems[] = "Not active";
            if (!$isVisible) $problems[] = "Not marketplace visible";
            
            $statusBadge = $isActive ? 
                "<span class='badge badge-yes'>Active</span>" : 
                "<span class='badge badge-no'>{$product->status}</span>";
            
            $visibleBadge = $isVisible ? 
                "<span class='badge badge-yes'>Yes</span>" : 
                "<span class='badge badge-no'>No</span>";
            
            $typeBadge = $isSaleable ?
                "<span class='badge badge-yes'>{$product->type}</span>" :
                "<span class='badge badge-no'>{$product->type}</span>";
            
            $problemText = empty($problems) ? 
                "<span class='success'>✅ OK</span>" : 
                "<span class='error'>" . implode(", ", $problems) . "</span>";
            
            echo "<tr>";
            echo "<td>{$product->id}</td>";
            echo "<td>" . htmlspecialchars($product->name) . "</td>";
            echo "<td>{$typeBadge}</td>";
            echo "<td>{$statusBadge}</td>";
            echo "<td>{$visibleBadge}</td>";
            echo "<td>" . number_format($product->selling_price, 2) . "</td>";
            echo "<td>{$problemText}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>📝 How to Fix:</h2>";
    echo "<ol style='line-height: 1.8;'>";
    echo "<li><strong>Go to Admin Panel</strong> (login as Super Admin)</li>";
    echo "<li><strong>Navigate to:</strong> Super Admin → Item Management</li>";
    echo "<li><strong>For each product</strong> you want to show in the store:</li>";
    echo "<ul style='margin-left: 20px; margin-top: 10px;'>";
    echo "<li>✅ Change <strong>Type</strong> to one of:";
    echo "<ul style='margin-left: 20px;'>";
    echo "<li>📚 <strong>Book</strong> (for books)</li>";
    echo "<li>🫗 <strong>Finished Product</strong> (for oils)</li>";
    echo "<li>🫗 <strong>Bottle Product</strong> (for bottled oils)</li>";
    echo "<li>✏️ <strong>Stationery</strong> (for pens, notebooks)</li>";
    echo "<li>👕 <strong>Fashion</strong> (for clothing)</li>";
    echo "</ul></li>";
    echo "<li>✅ Set <strong>Status</strong> to <strong>Active</strong></li>";
    echo "<li>✅ Check the <strong>Marketplace Visible</strong> checkbox ✔️</li>";
    echo "<li>✅ Click <strong>Save</strong></li>";
    echo "</ul>";
    echo "<li><strong>Refresh</strong> the products page</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>🔗 Test Links:</h2>";
    echo "<a href='/admin/item-manager' class='btn'>📝 Item Management</a>";
    echo "<a href='/store/products' class='btn'>🛍️ Products Page</a>";
    echo "<a href='/' class='btn'>🏠 Homepage</a>";
    echo "<a href='/login' class='btn'>🔐 Login</a>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>ℹ️ Product Types Explained:</h2>";
    echo "<table>";
    echo "<tr><th>Type</th><th>Shows in Store?</th><th>Use For</th></tr>";
    echo "<tr><td><strong>finished_product</strong></td><td class='success'>✅ YES</td><td>Manufactured oils, finished goods</td></tr>";
    echo "<tr><td><strong>bottle_product</strong></td><td class='success'>✅ YES</td><td>Bottled products ready for sale</td></tr>";
    echo "<tr><td><strong>book</strong></td><td class='success'>✅ YES</td><td>Books, publications</td></tr>";
    echo "<tr><td><strong>stationery</strong></td><td class='success'>✅ YES</td><td>Pens, notebooks, paper</td></tr>";
    echo "<tr><td><strong>fashion</strong></td><td class='success'>✅ YES</td><td>Clothing, accessories</td></tr>";
    echo "<tr><td><strong>raw_material</strong></td><td class='error'>❌ NO</td><td>Manufacturing input (internal only)</td></tr>";
    echo "<tr><td><strong>packing_material</strong></td><td class='error'>❌ NO</td><td>Bottles, boxes (internal only)</td></tr>";
    echo "<tr><td><strong>general</strong></td><td class='error'>❌ NO</td><td>Legacy type (deprecated)</td></tr>";
    echo "</table>";
    echo "</div>";
    ?>
</body>
</html>
