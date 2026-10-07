<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Products</h1>";
echo "<pre>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel loaded successfully\n\n";
    
    // Count total products
    $total = DB::table('products')->count();
    echo "Total products in database: {$total}\n\n";
    
    if ($total === 0) {
        echo "❌ NO PRODUCTS FOUND!\n";
        echo "You need to add products first in Admin → Item Management\n";
        exit;
    }
    
    // Show all products
    echo "All products:\n";
    echo str_repeat("=", 80) . "\n";
    
    $products = DB::table('products')->select('id', 'name', 'type', 'status', 'is_marketplace_visible', 'selling_price')->get();
    
    foreach ($products as $p) {
        echo "ID: {$p->id}\n";
        echo "Name: {$p->name}\n";
        echo "Type: {$p->type}\n";
        echo "Status: {$p->status}\n";
        echo "Marketplace Visible: " . ($p->is_marketplace_visible ? 'YES' : 'NO') . "\n";
        echo "Price: {$p->selling_price}\n";
        echo str_repeat("-", 80) . "\n";
    }
    
    // Check which are visible
    $saleable = DB::table('products')
        ->whereIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general'])
        ->where('status', 'active')
        ->where('is_marketplace_visible', 1)
        ->count();
    
    echo "\n\n";
    echo "Products that SHOULD show on store: {$saleable}\n\n";
    
    if ($saleable === 0) {
        echo "❌ NO PRODUCTS ARE CONFIGURED FOR STORE!\n\n";
        echo "Problems to fix:\n";
        
        $wrongType = DB::table('products')->whereNotIn('type', ['finished_product', 'bottle_product', 'book', 'stationery', 'fashion', 'general'])->count();
        $notActive = DB::table('products')->where('status', '!=', 'active')->count();
        $notVisible = DB::table('products')->where('is_marketplace_visible', 0)->count();
        
        if ($wrongType > 0) echo "- {$wrongType} products have wrong type (raw_material, packing_material)\n";
        if ($notActive > 0) echo "- {$notActive} products are not active\n";
        if ($notVisible > 0) echo "- {$notVisible} products are not marketplace visible\n";
    } else {
        echo "✅ {$saleable} products are ready!\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>

<style>
body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }
h1 { color: #4ec9b0; }
pre { background: #2d2d2d; padding: 20px; border-radius: 8px; }
</style>
