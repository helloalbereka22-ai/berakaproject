<!DOCTYPE html>
<html>
<head>
    <title>Database Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #111827; color: white; }
        .box { background: #1f2937; padding: 30px; border-radius: 12px; margin: 20px auto; max-width: 900px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .btn { display: inline-block; padding: 15px 30px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; margin: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #374151; }
        th { background: #1f2937; }
    </style>
</head>
<body>
    <h1>🔍 Database Check</h1>
    
    <?php
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "<div class='box'>";
    echo "<h2>📊 Database Status:</h2>";
    
    // Check products table
    $productCount = DB::table('products')->count();
    echo "<p><strong>Total Products in Database:</strong> <span style='font-size: 24px; color: " . ($productCount > 0 ? "#10b981" : "#ef4444") . "'>{$productCount}</span></p>";
    
    if ($productCount === 0) {
        echo "<p class='error' style='font-size: 20px;'>❌ NO PRODUCTS FOUND!</p>";
        echo "<p style='font-size: 16px; margin: 20px 0;'>You need to add products first before they can appear in the store.</p>";
        echo "<h3>📝 How to Add Products:</h3>";
        echo "<ol style='text-align: left; line-height: 2;'>";
        echo "<li><strong>Login to Admin Panel:</strong> <a href='/login' style='color: #60a5fa;'>Click here to login</a></li>";
        echo "<li><strong>Go to Item Management:</strong><br>Navigate to: <strong>Super Admin → Item Management</strong></li>";
        echo "<li><strong>Click 'Add New Item'</strong></li>";
        echo "<li><strong>Fill in the details:</strong>";
        echo "<ul style='margin-left: 20px;'>";
        echo "<li>Item Name (e.g., 'Men T-Shirt', 'Book Title', 'Cooking Oil 1L')</li>";
        echo "<li>Type: Select <strong>'Saleable - Book'</strong>, <strong>'Saleable - Fashion'</strong>, or <strong>'Manufacturing - Finished Product'</strong></li>";
        echo "<li>Category: Select appropriate category</li>";
        echo "<li>Price: Enter selling price</li>";
        echo "<li>Description: Add product description</li>";
        echo "<li>Image: Upload product image</li>";
        echo "<li>Status: Select <strong>'Active'</strong></li>";
        echo "<li>Marketplace Visible: <strong>✅ CHECK THIS BOX</strong></li>";
        echo "</ul></li>";
        echo "<li><strong>Click Save</strong></li>";
        echo "<li><strong>Add more products</strong> (repeat steps 3-5)</li>";
        echo "<li><strong>Visit products page:</strong> <a href='/store/products' style='color: #60a5fa;'>View Store</a></li>";
        echo "</ol>";
    } else {
        echo "<p class='success'>✅ {$productCount} products found!</p>";
        
        // Show product details
        $products = DB::table('products')->select('id', 'name', 'type', 'status', 'is_marketplace_visible', 'selling_price')->get();
        
        echo "<h3>Product List:</h3>";
        echo "<div style='max-height: 400px; overflow-y: auto;'>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Visible?</th><th>Price</th></tr>";
        
        $visibleCount = 0;
        foreach ($products as $p) {
            $isVisible = ($p->status === 'active' && $p->is_marketplace_visible);
            if ($isVisible) $visibleCount++;
            
            echo "<tr>";
            echo "<td>{$p->id}</td>";
            echo "<td>" . htmlspecialchars(substr($p->name, 0, 40)) . "</td>";
            echo "<td>{$p->type}</td>";
            echo "<td>{$p->status}</td>";
            echo "<td>" . ($p->is_marketplace_visible ? "✅" : "❌") . "</td>";
            echo "<td>" . number_format($p->selling_price, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        echo "<p style='margin-top: 20px;'><strong>Visible in Store:</strong> <span style='font-size: 20px; color: " . ($visibleCount > 0 ? "#10b981" : "#ef4444") . "'>{$visibleCount}</span></p>";
        
        if ($visibleCount === 0) {
            echo "<p class='error'>❌ No products are set up correctly for the store!</p>";
            echo "<p>Use the fix page: <a href='/quick-fix.php' class='btn'>Fix Products</a></p>";
        } else {
            echo "<p class='success'>✅ Products should appear in store!</p>";
            echo "<a href='/store/products' class='btn'>View Products Page</a>";
        }
    }
    
    echo "</div>";
    
    // Check categories
    echo "<div class='box'>";
    echo "<h2>📁 Categories:</h2>";
    $catCount = DB::table('categories')->count();
    echo "<p><strong>Total Categories:</strong> {$catCount}</p>";
    
    if ($catCount > 0) {
        $categories = DB::table('categories')->select('id', 'name', 'type')->limit(10)->get();
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Type</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr><td>{$cat->id}</td><td>{$cat->name}</td><td>{$cat->type}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Quick links
    echo "<div class='box'>";
    echo "<h2>🔗 Quick Links:</h2>";
    echo "<a href='/login' class='btn'>🔐 Admin Login</a>";
    echo "<a href='/admin/item-manager' class='btn'>📝 Item Management</a>";
    echo "<a href='/store/products' class='btn'>🛍️ Products Page</a>";
    echo "<a href='/quick-fix.php' class='btn'>🔧 Fix Products</a>";
    echo "</div>";
    ?>
</body>
</html>
