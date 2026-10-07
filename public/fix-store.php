<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:20px;font-family:Arial;font-size:13px;'>";

// Show all products and their current status
echo "=== ALL PRODUCTS ===\n";
$products = DB::table('products')->get(['id','name','type','status','is_marketplace_visible','selling_price']);
foreach($products as $p) {
    $ok = $p->status === 'active' && $p->is_marketplace_visible;
    echo ($ok ? '✅' : '❌') . " id={$p->id} | {$p->name} | type={$p->type} | status={$p->status} | visible={$p->is_marketplace_visible} | price={$p->selling_price}\n";
}

// Fix if requested
if (isset($_POST['fix'])) {
    // Make ALL saleable products active and visible
    $updated = DB::table('products')
        ->whereIn('type', ['finished_product','bottle_product','book','stationery','fashion','general'])
        ->update(['status' => 'active', 'is_marketplace_visible' => 1]);
    
    // Also fix type - if type is wrong for clothing etc
    DB::table('products')
        ->whereNotIn('type', ['finished_product','bottle_product','book','stationery','fashion','general','raw_material','packing_material'])
        ->update(['type' => 'fashion', 'status' => 'active', 'is_marketplace_visible' => 1]);
    
    echo "\n✅ Fixed! {$updated} products are now active and visible.\n";
    
    // Show result
    $visible = DB::table('products')
        ->where('status','active')
        ->where('is_marketplace_visible',1)
        ->whereIn('type',['finished_product','bottle_product','book','stationery','fashion','general'])
        ->count();
    echo "Products now visible on store: {$visible}\n";
}

echo "</pre>";

if (!isset($_POST['fix'])):
?>
<form method="POST" style="padding:20px;">
    <button type="submit" name="fix" 
        style="padding:15px 30px;background:#10b981;color:white;font-size:18px;font-weight:bold;border:none;border-radius:10px;cursor:pointer;">
        ✅ Make All Products Visible on Store
    </button>
</form>
<a href="/store/products" style="display:inline-block;padding:12px 24px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;font-weight:bold;margin:10px 20px;">
    🛍️ View Store
</a>
<?php endif; ?>
