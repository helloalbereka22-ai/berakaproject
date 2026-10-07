<!DOCTYPE html>
<html>
<head>
    <title>Enable Online Store</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 20px; padding: 40px; max-width: 800px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #333; margin-bottom: 30px; font-size: 32px; text-align: center; }
        .status-box { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; border-left: 4px solid #6c757d; }
        .status-box.success { background: #d4edda; border-color: #28a745; }
        .status-box.error { background: #f8d7da; border-color: #dc3545; }
        .status-box.warning { background: #fff3cd; border-color: #ffc107; }
        .btn { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 18px; border: none; cursor: pointer; transition: transform 0.2s; margin: 10px 5px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .btn-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .btn-large { padding: 20px 50px; font-size: 22px; }
        .stat { display: inline-block; background: #f8f9fa; padding: 15px 25px; border-radius: 10px; margin: 10px; }
        .stat-value { font-size: 36px; font-weight: bold; color: #667eea; display: block; }
        .stat-label { font-size: 14px; color: #6c757d; display: block; margin-top: 5px; }
        .step { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 15px 0; border-left: 4px solid #667eea; }
        .step-number { display: inline-block; width: 30px; height: 30px; background: #667eea; color: white; border-radius: 50%; text-align: center; line-height: 30px; font-weight: bold; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Enable Online Store</h1>
        
        <?php
        require __DIR__ . '/../vendor/autoload.php';
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        // Handle enable button
        if (isset($_POST['enable_store'])) {
            try {
                // Make ALL products visible and active
                DB::table('products')->update([
                    'status' => 'active',
                    'is_marketplace_visible' => 1,
                    'updated_at' => now()
                ]);
                
                // Fix product types - make them saleable
                DB::table('products')
                    ->whereIn('type', ['raw_material', 'packing_material', 'general', 'finished_oil'])
                    ->update(['type' => 'finished_product', 'updated_at' => now()]);
                
                echo "<div class='status-box success'>";
                echo "<h2 style='color: #28a745; margin-bottom: 15px;'>✅ Success!</h2>";
                echo "<p style='font-size: 18px;'>Your online store is now enabled! All products are now visible to customers.</p>";
                echo "</div>";
                
                echo "<div style='text-align: center; margin: 30px 0;'>";
                echo "<a href='/store/products' class='btn btn-success btn-large'>🛍️ VIEW YOUR STORE NOW</a>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='status-box error'>";
                echo "<h2 style='color: #dc3545;'>❌ Error</h2>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        }
        
        // Check current status
        $totalProducts = DB::table('products')->count();
        $visibleProducts = DB::table('products')
            ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion'])
            ->where('status', 'active')
            ->where('is_marketplace_visible', 1)
            ->count();
        
        $withImages = DB::table('products')
            ->whereRaw("JSON_LENGTH(metadata, '$.images') > 0")
            ->count();
        
        echo "<div style='text-align: center; margin: 30px 0;'>";
        echo "<div class='stat'>";
        echo "<span class='stat-value'>{$totalProducts}</span>";
        echo "<span class='stat-label'>Total Products</span>";
        echo "</div>";
        
        echo "<div class='stat'>";
        echo "<span class='stat-value' style='color: " . ($visibleProducts > 0 ? "#28a745" : "#dc3545") . ";'>{$visibleProducts}</span>";
        echo "<span class='stat-label'>Visible to Customers</span>";
        echo "</div>";
        
        echo "<div class='stat'>";
        echo "<span class='stat-value' style='color: " . ($withImages > 0 ? "#28a745" : "#ffc107") . ";'>{$withImages}</span>";
        echo "<span class='stat-label'>With Images</span>";
        echo "</div>";
        echo "</div>";
        
        if ($totalProducts === 0) {
            echo "<div class='status-box error'>";
            echo "<h2 style='color: #dc3545;'>❌ No Products Found</h2>";
            echo "<p style='font-size: 16px; margin: 15px 0;'>You don't have any products in your database yet.</p>";
            echo "<p><strong>You need to add products first:</strong></p>";
            echo "<ol style='margin: 15px 0 15px 30px; line-height: 2;'>";
            echo "<li>Go to Admin Panel → Item Management</li>";
            echo "<li>Click 'Add New Item'</li>";
            echo "<li>Fill in product details (name, price, category)</li>";
            echo "<li>Upload product image</li>";
            echo "<li>Click Save</li>";
            echo "</ol>";
            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<a href='/admin/item-manager' class='btn'>📝 Go to Item Management</a>";
            echo "</div>";
            echo "</div>";
            
        } else if ($visibleProducts === 0) {
            echo "<div class='status-box warning'>";
            echo "<h2 style='color: #856404;'>⚠️ Products Not Visible</h2>";
            echo "<p style='font-size: 16px; margin: 15px 0;'>You have {$totalProducts} products, but none are visible to customers yet.</p>";
            echo "<p><strong>Click the button below to enable your online store:</strong></p>";
            echo "</div>";
            
            echo "<form method='POST' style='text-align: center; margin: 30px 0;'>";
            echo "<button type='submit' name='enable_store' class='btn btn-success btn-large'>";
            echo "🚀 ENABLE ONLINE STORE<br><small style='font-size: 14px; font-weight: normal;'>Make all {$totalProducts} products visible</small>";
            echo "</button>";
            echo "</form>";
            
        } else {
            echo "<div class='status-box success'>";
            echo "<h2 style='color: #28a745;'>✅ Store is Active!</h2>";
            echo "<p style='font-size: 16px;'>{$visibleProducts} products are visible to customers and ready for online ordering.</p>";
            echo "</div>";
            
            echo "<div style='text-align: center; margin: 30px 0;'>";
            echo "<a href='/store/products' class='btn btn-success btn-large'>🛍️ VIEW YOUR STORE</a>";
            echo "<a href='/' class='btn'>🏠 Go to Homepage</a>";
            echo "</div>";
        }
        
        // Instructions
        echo "<div style='margin-top: 40px; padding-top: 30px; border-top: 2px solid #e9ecef;'>";
        echo "<h2 style='color: #333; margin-bottom: 20px;'>📝 How It Works:</h2>";
        
        echo "<div class='step'>";
        echo "<span class='step-number'>1</span>";
        echo "<strong>Add Products:</strong> Create products in Item Management with images and prices";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<span class='step-number'>2</span>";
        echo "<strong>Enable Store:</strong> Click the 'Enable Online Store' button to make products visible";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<span class='step-number'>3</span>";
        echo "<strong>Customers Shop:</strong> Customers visit your website, browse products, and place orders";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<span class='step-number'>4</span>";
        echo "<strong>You Fulfill:</strong> View orders in Admin Panel and fulfill them";
        echo "</div>";
        
        echo "</div>";
        
        // Quick links
        echo "<div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;'>";
        echo "<p style='color: #6c757d; font-size: 14px;'>Quick Links:</p>";
        echo "<a href='/admin/item-manager' class='btn' style='padding: 10px 20px; font-size: 14px;'>Item Management</a>";
        echo "<a href='/login' class='btn' style='padding: 10px 20px; font-size: 14px;'>Admin Login</a>";
        echo "<a href='/diagnose.php' class='btn' style='padding: 10px 20px; font-size: 14px;'>Diagnostics</a>";
        echo "</div>";
        ?>
    </div>
</body>
</html>
