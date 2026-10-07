<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Fix by name - force these specific oil products to finished_product type
// regardless of what type they currently have
DB::statement("
    UPDATE products 
    SET type = 'finished_product'
    WHERE 
        name LIKE '%ml%' OR
        name LIKE '%tukur%' OR
        name LIKE '%telba%' OR
        name LIKE '%selit%' OR
        name LIKE '%azmud%' OR
        name LIKE '%azmut%' OR
        name LIKE '%bahrzef%' OR
        name LIKE '%liter%' OR
        name LIKE '%litre%' OR
        name LIKE '%zait%'
");

// Now show what's left on Products page
$onProducts = DB::select("
    SELECT id, name, type FROM products 
    WHERE type IN ('book','stationery','fashion','general')
    AND status = 'active' AND is_marketplace_visible = 1
    ORDER BY name
");

// Show what's on Manufacturing page  
$onManuf = DB::select("
    SELECT id, name, type FROM products 
    WHERE type IN ('finished_product','bottle_product')
    AND status = 'active' AND is_marketplace_visible = 1
    ORDER BY name
");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Fix Done</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial;background:#0f172a;color:white;padding:30px;}
.card{background:#1e293b;border-radius:12px;padding:25px;margin:20px 0;}
h1{color:#34d399;font-size:28px;margin-bottom:5px;}
h2{font-size:18px;margin-bottom:15px;}
.item{padding:8px 12px;margin:5px 0;border-radius:8px;font-size:15px;}
.green-item{background:#064e3b;color:#34d399;}
.amber-item{background:#451a03;color:#fbbf24;}
.btn{display:inline-block;padding:14px 30px;border-radius:10px;font-weight:bold;font-size:16px;text-decoration:none;margin:8px 5px;}
.btn-blue{background:#3b82f6;color:white;}
.btn-amber{background:#f59e0b;color:black;}
</style>
</head>
<body>

<h1>✅ Fix Applied!</h1>
<p style="color:#94a3b8;margin:10px 0 30px;">Oil/ml products moved to Manufacturing page.</p>

<div class="card">
    <h2 style="color:#34d399;">🛍️ Products Page — <?= count($onProducts) ?> items</h2>
    <?php if(count($onProducts) === 0): ?>
        <p style="color:#f87171;">⚠️ 0 items! Your purchased items need type fixing too — see below.</p>
    <?php else: ?>
        <?php foreach($onProducts as $p): ?>
        <div class="item green-item">✅ <?= htmlspecialchars($p->name) ?> <span style="opacity:0.5;font-size:12px;">(<?= $p->type ?>)</span></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="color:#fbbf24;">🏭 Manufacturing Page — <?= count($onManuf) ?> items</h2>
    <?php foreach($onManuf as $p): ?>
    <div class="item amber-item">🫗 <?= htmlspecialchars($p->name) ?> <span style="opacity:0.5;font-size:12px;">(<?= $p->type ?>)</span></div>
    <?php endforeach; ?>
</div>

<?php
// Show ALL products that are neither on products nor manufacturing
$hidden = DB::select("
    SELECT id, name, type FROM products 
    WHERE type NOT IN ('book','stationery','fashion','general','finished_product','bottle_product')
    ORDER BY name
");
if(count($hidden) > 0):
?>
<div class="card">
    <h2 style="color:#f87171;">❌ Hidden Products (not showing anywhere) — <?= count($hidden) ?> items</h2>
    <p style="color:#94a3b8;margin-bottom:15px;">These need a type assigned. Click button to add to Products page:</p>
    <?php foreach($hidden as $p): ?>
    <div class="item" style="background:#1e1e1e;display:flex;justify-content:space-between;align-items:center;">
        <span><?= htmlspecialchars($p->name) ?> <span style="opacity:0.5;font-size:12px;">(type: "<?= $p->type ?>")</span></span>
        <a href="?fix_hidden=<?= $p->id ?>" style="padding:5px 12px;background:#3b82f6;color:white;border-radius:6px;text-decoration:none;font-size:13px;">→ Add to Products</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
// Handle fix_hidden
if(isset($_GET['fix_hidden'])){
    DB::table('products')->where('id', (int)$_GET['fix_hidden'])->update(['type'=>'book','status'=>'active','is_marketplace_visible'=>1]);
    echo "<script>location.reload();</script>";
}
?>

<div style="margin-top:20px;">
    <a href="/store/products" class="btn btn-blue">🛍️ View Products Page</a>
    <a href="/manufacturing"  class="btn btn-amber">🏭 View Manufacturing Page</a>
</div>

</body>
</html>
