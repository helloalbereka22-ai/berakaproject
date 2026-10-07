<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Fix - Online Store</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #333; font-size: 28px; margin-bottom: 20px; text-align: center; }
        .result { padding: 20px; border-radius: 10px; margin: 20px 0; }
        .success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 2px solid #bee5eb; }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-large { padding: 20px 40px; font-size: 20px; }
        .count { font-size: 48px; font-weight: bold; color: #667eea; display: block; text-align: center; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; color: #666; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🚀 Online Store Quick Fix</h1>
        
        <?php
        require __DIR__ . '/../vendor/autoload.php';
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        // Apply fix if button clicked
        if (isset($_POST['apply_fix'])) {
            try {
                // Update ALL products to be active and visible
                $updated = DB::table('products')->update([
                    'status' => 'active',
                    'is_marketplace_visible' => 1
                ]);
                
                // Fix product types - convert non-saleable to saleable
                DB::table('products')
                    ->where('type', 'raw_material')
                    ->update(['type' => 'finished_product']);
                    
                DB::table('products')
                    ->where('type', 'packing_material')
                    ->update(['type' => 'finished_product']);
                
                $visible = DB::table('products')
                    ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general'])
                    ->where('status', 'active')
                    ->where('is_marketplace_visible', 1)
                    ->count();
                
                echo "<div class='result success'>";
                echo "<h2 style='margin-bottom: 15px;'>✅ Success!</h2>";
                echo "<p style='font-size: 18px; margin-bottom: 15px;'><strong>{$visible} products</strong> are now visible on your online store!</p>";
                echo "<div style='text-align: center;'>";
                echo "<a href='/store/products' class='btn btn-large' style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);'>🛍️ VIEW STORE NOW</a>";
                echo "</div>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='result error'>";
                echo "<h2>❌ Error</h2>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        }
        
        // Show current status
        $total = DB::table('products')->count();
        
        if ($total === 0) {
            echo "<div class='result error'>";
            echo "<h2>❌ No Products</h2>";
            echo "<p>You don't have any products in the database.</p>";
            echo "<p style='margin-top: 15px;'><strong>Add products first:</strong></p>";
            echo "<ol style='margin: 10px 0 10px 20px; line-height: 2;'>";
            echo "<li>Go to Admin Panel</li>";
            echo "<li>Click 'Item Management'</li>";
            echo "<li>Add products with images</li>";
            echo "</ol>";
            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<a href='/admin/item-manager' class='btn'>📝 Item Management</a>";
            echo "</div>";
            echo "</div>";
            exit;
        }
        
        $visible = DB::table('products')
            ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general'])
            ->where('status', 'active')
            ->where('is_marketplace_visible', 1)
            ->count();
        
        if ($visible === 0) {
            echo "<div class='result info'>";
            echo "<h2 style='color: #0c5460;'>📦 Products Need Activation</h2>";
            echo "<span class='count'>{$total}</span>";
            echo "<p style='text-align: center; font-size: 18px; margin-bottom: 20px;'>products in database</p>";
            
            echo "<table>";
            echo "<tr><td>Total Products:</td><td><strong>{$total}</strong></td></tr>";
            echo "<tr><td>Currently Visible:</td><td><strong style='color: #dc3545;'>{$visible}</strong></td></tr>";
            echo "</table>";
            
            echo "<p style='margin: 20px 0; text-align: center;'>Click below to make all products visible to customers:</p>";
            
            echo "<form method='POST' style='text-align: center;'>";
            echo "<button type='submit' name='apply_fix' class='btn btn-large' style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);'>";
            echo "✨ ACTIVATE ONLINE STORE";
            echo "</button>";
            echo "</form>";
            echo "</div>";
            
        } else {
            echo "<div class='result success'>";
            echo "<h2>✅ Store is Active!</h2>";
            echo "<span class='count'>{$visible}</span>";
            echo "<p style='text-align: center; font-size: 18px; margin-bottom: 20px;'>products visible to customers</p>";
            echo "<div style='text-align: center;'>";
            echo "<a href='/store/products' class='btn btn-large' style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);'>🛍️ VIEW STORE</a>";
            echo "</div>";
            echo "</div>";
        }
        
        echo "<div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>";
        echo "<a href='/' class='btn' style='padding: 10px 20px; font-size: 14px;'>🏠 Home</a>";
        echo "<a href='/admin' class='btn' style='padding: 10px 20px; font-size: 14px;'>👤 Admin</a>";
        echo "</div>";
        ?>
    </div>
</body>
</html>
