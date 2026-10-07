<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/html; charset=utf-8');

// Apply fix immediately if requested
if (isset($_GET['auto']) || isset($_POST['fix'])) {
    try {
        // Step 1: Make ALL products active and visible
        DB::statement("UPDATE products SET status = 'active', is_marketplace_visible = 1");
        
        // Step 2: Fix product types - convert to saleable types
        DB::statement("UPDATE products SET type = 'finished_product' WHERE type IN ('raw_material', 'packing_material')");
        
        // Count results
        $total = DB::table('products')->count();
        $visible = DB::table('products')
            ->where('status', 'active')
            ->where('is_marketplace_visible', 1)
            ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general'])
            ->count();
        
        echo "<!DOCTYPE html><html><head><title>Fixed!</title><style>
        body{font-family:Arial;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:20px;}
        .box{background:white;padding:50px;border-radius:20px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:500px;}
        h1{color:#10b981;font-size:48px;margin:0 0 20px;}
        p{font-size:20px;color:#333;margin:20px 0;}
        .count{font-size:72px;font-weight:900;color:#667eea;margin:30px 0;}
        a{display:inline-block;padding:20px 40px;background:linear-gradient(135deg,#10b981,#059669);color:white;text-decoration:none;border-radius:15px;font-weight:bold;font-size:20px;margin:10px;box-shadow:0 10px 30px rgba(16,185,129,0.3);}
        a:hover{transform:translateY(-2px);box-shadow:0 15px 40px rgba(16,185,129,0.4);}
        </style></head><body>
        <div class='box'>
        <h1>✅ SUCCESS!</h1>
        <p>Your online store is now active!</p>
        <div class='count'>{$visible}</div>
        <p style='font-size:18px;color:#666;'>products are now visible to customers</p>
        <a href='/store/products' style='font-size:24px;padding:25px 50px;'>🛍️ VIEW STORE NOW</a>
        <br><a href='/' style='background:linear-gradient(135deg,#667eea,#764ba2);font-size:16px;padding:15px 30px;'>🏠 Go Home</a>
        </div></body></html>";
        exit;
        
    } catch (Exception $e) {
        die("<h1>Error: " . htmlspecialchars($e->getMessage()) . "</h1>");
    }
}

// Show current status
$total = DB::table('products')->count();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fix Store - One Click</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 30px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            text-align: center;
        }
        h1 { 
            color: #333; 
            font-size: 36px; 
            margin-bottom: 20px;
            font-weight: 900;
        }
        .count {
            font-size: 96px;
            font-weight: 900;
            color: #667eea;
            margin: 30px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .label {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
        }
        .btn {
            display: inline-block;
            padding: 25px 60px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 900;
            font-size: 24px;
            border: none;
            cursor: pointer;
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4);
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(16, 185, 129, 0.5);
        }
        .info {
            background: #f0f9ff;
            border: 2px solid #bfdbfe;
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
            color: #1e40af;
            font-size: 16px;
            line-height: 1.6;
        }
        .error {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($total === 0): ?>
            <h1>❌ No Products</h1>
            <p class="label">You need to add products first</p>
            <div class="info error">
                <p><strong>Steps to add products:</strong></p>
                <ol style="text-align: left; margin: 15px 0 0 30px; line-height: 2;">
                    <li>Go to Admin Panel</li>
                    <li>Click "Item Management"</li>
                    <li>Add products with images</li>
                    <li>Come back here and click fix</li>
                </ol>
            </div>
            <a href="/admin/item-manager" class="btn" style="font-size: 18px; padding: 20px 40px; background: linear-gradient(135deg, #667eea, #764ba2);">
                📝 Add Products
            </a>
        <?php else: ?>
            <h1>🚀 Enable Store</h1>
            <div class="count"><?php echo $total; ?></div>
            <p class="label">products found in database</p>
            
            <div class="info">
                <p><strong>This will:</strong></p>
                <ul style="list-style: none; margin-top: 10px; line-height: 2;">
                    <li>✅ Make all products active</li>
                    <li>✅ Make all products visible to customers</li>
                    <li>✅ Fix product types for online store</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="fix" class="btn">
                    ✨ ENABLE STORE NOW
                </button>
            </form>
            
            <p style="margin-top: 30px; color: #666; font-size: 14px;">
                <a href="/store/products" style="color: #667eea; text-decoration: none;">View Store</a> • 
                <a href="/" style="color: #667eea; text-decoration: none;">Home</a> •
                <a href="/admin" style="color: #667eea; text-decoration: none;">Admin</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
