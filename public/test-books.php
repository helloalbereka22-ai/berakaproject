<!DOCTYPE html>
<html>
<head>
    <title>Books Debug</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #1e293b; color: white; }
        .box { background: #334155; padding: 20px; border-radius: 10px; margin: 10px 0; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #4b5563; }
        th { background: #1f2937; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .badge-yes { background: #10b981; color: white; }
        .badge-no { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <h1>📚 Books Debug Page</h1>
    
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
    
    echo "<div class='box'>";
    echo "<h2>All Book Products:</h2>";
    
    $books = DB::table('products')
        ->where('type', 'book')
        ->orderBy('id')
        ->get();
    
    if ($books->isEmpty()) {
        echo "<p class='error'>❌ No books found in database!</p>";
        echo "<p>You need to add some book products first.</p>";
    } else {
        echo "<p class='success'>✅ Found " . $books->count() . " book(s)</p>";
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Status</th>
            <th>Marketplace Visible</th>
            <th>Description</th>
            <th>Image</th>
        </tr>";
        
        foreach ($books as $book) {
            $statusBadge = $book->status === 'active' ? 
                "<span class='badge badge-yes'>Active</span>" : 
                "<span class='badge badge-no'>{$book->status}</span>";
            
            $visibleBadge = $book->is_marketplace_visible ? 
                "<span class='badge badge-yes'>Yes</span>" : 
                "<span class='badge badge-no'>No</span>";
            
            $hasDesc = !empty($book->description) ? '✅ Yes' : '❌ No';
            
            // Check for images
            $imageCount = DB::table('product_media')
                ->where('product_id', $book->id)
                ->count();
            $hasImage = $imageCount > 0 ? "✅ {$imageCount} image(s)" : '❌ No images';
            
            echo "<tr>";
            echo "<td>{$book->id}</td>";
            echo "<td>{$book->name}</td>";
            echo "<td>" . number_format($book->selling_price, 2) . "</td>";
            echo "<td>{$statusBadge}</td>";
            echo "<td>{$visibleBadge}</td>";
            echo "<td>{$hasDesc}</td>";
            echo "<td>{$hasImage}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Marketplace-Ready Books:</h2>";
    echo "<p>Books that SHOULD appear on the store (active + marketplace_visible = true)</p>";
    
    $marketplaceBooks = DB::table('products')
        ->where('type', 'book')
        ->where('status', 'active')
        ->where('is_marketplace_visible', true)
        ->get();
    
    if ($marketplaceBooks->isEmpty()) {
        echo "<p class='error'>❌ No marketplace-ready books!</p>";
        echo "<p><strong>To fix this:</strong></p>";
        echo "<ol>";
        echo "<li>Go to Admin Panel → Item Management</li>";
        echo "<li>Find your book products</li>";
        echo "<li>Make sure Status is 'Active'</li>";
        echo "<li>Make sure 'Marketplace Visible' is checked (enabled)</li>";
        echo "<li>Save the product</li>";
        echo "</ol>";
    } else {
        echo "<p class='success'>✅ Found " . $marketplaceBooks->count() . " marketplace-ready book(s)</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Category</th></tr>";
        
        foreach ($marketplaceBooks as $book) {
            $category = DB::table('categories')->where('id', $book->category_id)->value('name');
            echo "<tr>";
            echo "<td>{$book->id}</td>";
            echo "<td>{$book->name}</td>";
            echo "<td>" . number_format($book->selling_price, 2) . "</td>";
            echo "<td>" . ($category ?? '<em>No category</em>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>These books should appear when you visit:</strong></p>";
        echo "<p><a href='/store/products?type=book' style='color: #60a5fa; text-decoration: underline;'>";
        echo url('/store/products?type=book');
        echo "</a></p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Test Links:</h2>";
    echo "<ul>";
    echo "<li><a href='/' style='color: #60a5fa;'>Homepage</a></li>";
    echo "<li><a href='/store/products' style='color: #60a5fa;'>All Products</a></li>";
    echo "<li><a href='/store/products?type=book' style='color: #60a5fa;'>Book Products Only</a></li>";
    echo "<li><a href='/bookstore' style='color: #60a5fa;'>Bookstore Page</a></li>";
    echo "</ul>";
    echo "</div>";
    ?>
</body>
</html>
