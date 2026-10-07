<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html><html><head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}
h2{color:#38bdf8;margin:0 0 10px;}
.good{color:#34d399;} .bad{color:#f87171;}
</style></head><body>
<?php
$tables = ['businesses','branches','categories','products','purchases','settings','languages','users'];
echo "<div class='box'><h2>Data Status</h2>";
foreach($tables as $t) {
    $count = DB::table($t)->count();
    $class = $count > 0 ? 'good' : 'bad';
    echo "<p class='{$class}'>".($count>0?'✅':'❌')." {$t}: {$count} records</p>";
}
echo "</div>";

// Check why products don't show on store
echo "<div class='box'><h2>Why Products Don't Show</h2>";
$products = DB::table('products')
    ->where('status','active')
    ->where('is_marketplace_visible',1)
    ->whereIn('type',['finished_product','bottle_product','book','stationery','fashion','general'])
    ->count();
echo "<p class='".($products>0?'good':'bad')."'>Active+Visible products: {$products}</p>";

$total = DB::table('products')->count();
echo "<p>Total products in DB: {$total}</p>";
echo "</div>";
?>
<br>
<a href="/store/products" style="padding:10px 20px;background:#3b82f6;color:white;border-radius:8px;text-decoration:none;">View Store</a>
<a href="/admin/dashboard" style="padding:10px 20px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;margin-left:10px;">Admin</a>
</body></html>
