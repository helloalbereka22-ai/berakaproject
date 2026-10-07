<!DOCTYPE html>
<html>
<head>
    <title>Complete Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #0f172a; color: white; }
        .box { background: #1e293b; padding: 20px; border-radius: 10px; margin: 20px 0; border: 2px solid #334155; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .btn { display: inline-block; padding: 15px 30px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; margin: 10px 5px; font-weight: bold; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #2563eb; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #475569; }
        th { background: #334155; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #60a5fa; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🔍 Complete System Diagnostic</h1>
    
    <?php
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Fix button handler
    if (isset($_POST['auto_fix'])) {
        echo "<div class='box' style='border-color: #10b981;'>";
        echo "<h2 class='success'>🔧 AUTO-FIX RUNNING...</h2>";
        
        try {
            // Step 1: Make all products active and visible
            DB::statement("UPDATE products SET status = 'active', is_marketplace_visible = 1");
            echo "<p class='success'>✅ Set all products to Active & Marketplace Visible</p>";
            
            // Step 2: Fix types
            DB::statement("UPDATE products SET type = 'finished_product' WHERE type IN ('raw_material', 'packing_material', 'general', 'finished_oil')");
            echo "<p class='success'>✅ Changed product types to saleable types</p>";
            
            // Step 3: Check results
            $fixed = DB::table('products')
                ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion'])
                ->where('status', 'active')
                ->where('is_marketplace_visible', 1)
                ->count();
            
            echo "<p class='success' style='font-size: 20px; margin: 20px 0;'>✅ SUCCESS! {$fixed} products are now ready!</p>";
            echo "<a href='/store/products' class='btn btn-success' style='font-size: 18px;'>🛍️ VIEW PRODUCTS NOW</a>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "</div>";
    }
    
    // DIAGNOSTIC START
    echo "<div class='box'>";
    echo "<h2>1️⃣ PRODUCTS IN DATABASE</h2>";
    
    $total = DB::table('products')->count();
    echo "<p style='font-size: 18px;'>Total products: <span class='" . ($total > 0 ? "success" : "error") . "'>{$total}</span></p>";
    
    if ($total === 0) {
        echo "<p class='error' style='font-size: 20px;'>❌ NO PRODUCTS! You need to add products first.</p>";
        echo "<h3>How to Add Products:</h3>";
        echo "<ol>";
        echo "<li>Go to: <a href='/admin/item-manager' style='color: #60a5fa;'>Item Management</a></li>";
        echo "<li>Click 'Add New Item'</li>";
        echo "<li>Fill all fields (name, category, price, upload image)</li>";
        echo "<li>Click Save</li>";
        echo "</ol>";
        echo "</div>";
        echo "</body></html>";
        exit;
    }
    
    // Get saleable products
    $saleable = DB::table('products')
        ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion'])
        ->count();
    
    $activeVisible = DB::table('products')
        ->where('status', 'active')
        ->where('is_marketplace_visible', 1)
        ->count();
    
    $marketplaceReady = DB::table('products')
        ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion'])
        ->where('status', 'active')
        ->where('is_marketplace_visible', 1)
        ->count();
    
    echo "<table>";
    echo "<tr><th>Check</th><th>Result</th><th>Status</th></tr>";
    echo "<tr><td>Total Products</td><td>{$total}</td><td class='success'>✅</td></tr>";
    echo "<tr><td>Saleable Type</td><td>{$saleable}</td><td class='" . ($saleable > 0 ? "success" : "error") . "'>" . ($saleable > 0 ? "✅" : "❌") . "</td></tr>";
    echo "<tr><td>Active & Visible</td><td>{$activeVisible}</td><td class='" . ($activeVisible > 0 ? "success" : "error") . "'>" . ($activeVisible > 0 ? "✅" : "❌") . "</td></tr>";
    echo "<tr><td><strong>READY FOR STORE</strong></td><td><strong>{$marketplaceReady}</strong></td><td class='" . ($marketplaceReady > 0 ? "success" : "error") . "'><strong>" . ($marketplaceReady > 0 ? "✅ GOOD!" : "❌ PROBLEM!") . "</strong></td></tr>";
    echo "</table>";
    
    if ($marketplaceReady === 0) {
        echo "<div style='background: #7f1d1d; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<p class='error' style='font-size: 20px;'>❌ NO PRODUCTS ARE READY FOR THE STORE!</p>";
        echo "<p>This is why your products page is empty.</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='auto_fix' class='btn btn-success' style='font-size: 20px; padding: 20px 40px;'>🔧 AUTO-FIX ALL PROBLEMS</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<div style='background: #064e3b; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<p class='success' style='font-size: 20px;'>✅ {$marketplaceReady} PRODUCTS ARE READY!</p>";
        echo "<a href='/store/products' class='btn btn-success' style='font-size: 18px;'>🛍️ VIEW PRODUCTS PAGE</a>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Show all products
    echo "<div class='box'>";
    echo "<h2>2️⃣ ALL PRODUCTS DETAILS</h2>";
    
    $products = DB::table('products')->select('id', 'name', 'type', 'status', 'is_marketplace_visible', 'selling_price', 'metadata')->get();
    
    echo "<div style='overflow-x: auto;'>";
    echo "<table style='font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Visible?</th><th>Price</th><th>Has Image?</th><th>Problem</th></tr>";
    
    foreach ($products as $p) {
        $isSaleable = in_array($p->type, ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion']);
        $isActive = $p->status === 'active';
        $isVisible = $p->is_marketplace_visible;
        $isReady = $isSaleable && $isActive && $isVisible;
        
        // Check if has image
        $metadata = json_decode($p->metadata, true);
        $hasImage = !empty($metadata['images'] ?? []);
        
        $problems = [];
        if (!$isSaleable) $problems[] = "Wrong type";
        if (!$isActive) $problems[] = "Not active";
        if (!$isVisible) $problems[] = "Not visible";
        if (!$hasImage) $problems[] = "No image";
        
        $problemText = empty($problems) ? "<span class='success'>✅ OK</span>" : "<span class='error'>" . implode(", ", $problems) . "</span>";
        
        echo "<tr" . ($isReady ? " style='background: #064e3b;'" : "") . ">";
        echo "<td>{$p->id}</td>";
        echo "<td>" . htmlspecialchars(substr($p->name, 0, 30)) . "</td>";
        echo "<td><span class='" . ($isSaleable ? "success" : "error") . "'>{$p->type}</span></td>";
        echo "<td><span class='" . ($isActive ? "success" : "warning") . "'>{$p->status}</span></td>";
        echo "<td>" . ($isVisible ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
        echo "<td>" . number_format($p->selling_price, 2) . "</td>";
        echo "<td>" . ($hasImage ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
        echo "<td>{$problemText}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    echo "</div>";
    
    // Storage check
    echo "<div class='box'>";
    echo "<h2>3️⃣ STORAGE & IMAGES CHECK</h2>";
    
    $storageExists = is_dir(storage_path('app/public/products'));
    $publicLinkExists = file_exists(public_path('storage'));
    
    echo "<p>Storage directory exists: " . ($storageExists ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</p>";
    echo "<p>Public storage link exists: " . ($publicLinkExists ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</p>";
    
    if ($storageExists) {
        $imageFiles = glob(storage_path('app/public/products/*'));
        $imageCount = count($imageFiles);
        echo "<p>Images in storage: <span class='" . ($imageCount > 0 ? "success" : "warning") . "'>{$imageCount}</span></p>";
        
        if ($imageCount > 0) {
            echo "<p class='success'>✅ Images are being saved correctly!</p>";
        }
    }
    
    if (!$publicLinkExists) {
        echo "<div style='background: #7f1d1d; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<p class='error'>⚠️ Public storage link is missing!</p>";
        echo "<p>But the /storage/ route should work as a fallback.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test URL
    echo "<div class='box'>";
    echo "<h2>4️⃣ TEST LINKS</h2>";
    echo "<a href='/admin/item-manager' class='btn'>📝 Item Management</a>";
    echo "<a href='/store/products' class='btn'>🛍️ Products Page</a>";
    echo "<a href='/' class='btn'>🏠 Homepage</a>";
    echo "<a href='?refresh=1' class='btn' style='background: #8b5cf6;'>🔄 Refresh Diagnostic</a>";
    echo "</div>";
    ?>
</body>
</html>
