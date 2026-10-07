<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$products = DB::table('products')->orderBy('type')->orderBy('name')->get(['id','name','type','status','is_marketplace_visible']);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>All Products</title>
<style>
body{font-family:Arial;background:#0f172a;color:white;padding:20px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border:1px solid #334155;text-align:left;}
th{background:#1e40af;}
.book{color:#34d399;}
.fashion{color:#f472b6;}
.stationery{color:#a78bfa;}
.finished_product,.bottle_product{color:#fbbf24;}
.general{color:#94a3b8;}
form{display:inline;}
button{padding:6px 14px;border-radius:6px;border:none;cursor:pointer;font-weight:bold;margin:2px;}
.to-manuf{background:#f59e0b;color:#000;}
.to-prod{background:#10b981;color:#fff;}
</style>
</head>
<body>
<h1>All Products — Current Types</h1>

<?php
if(isset($_POST['fix_id']) && isset($_POST['new_type'])){
    DB::table('products')->where('id',$_POST['fix_id'])->update(['type'=>$_POST['new_type']]);
    echo "<p style='color:#34d399;font-size:18px;'>✅ Fixed! Refreshing...</p>";
    echo "<script>setTimeout(()=>location.reload(),500);</script>";
}
if(isset($_POST['fix_all_oils'])){
    // Fix by ID list or by name patterns
    $ids = explode(',', $_POST['oil_ids'] ?? '');
    $ids = array_filter(array_map('intval', $ids));
    if(!empty($ids)){
        DB::table('products')->whereIn('id',$ids)->update(['type'=>'finished_product']);
        echo "<p style='color:#34d399;font-size:18px;'>✅ Moved ".count($ids)." products to Manufacturing!</p>";
        echo "<script>setTimeout(()=>location.reload(),500);</script>";
    }
}
?>

<p style="color:#94a3b8;margin-bottom:20px;">
    🟡 Yellow = Shows on <strong>Manufacturing</strong> page &nbsp;|&nbsp;
    🟢 Green/Pink/Purple = Shows on <strong>Products</strong> page
</p>

<table>
<tr>
    <th>ID</th>
    <th>Product Name</th>
    <th>Current Type</th>
    <th>Shows On</th>
    <th>Action</th>
</tr>
<?php foreach($products as $p): 
    $onProducts = in_array($p->type, ['book','stationery','fashion','general']);
    $onManuf    = in_array($p->type, ['finished_product','bottle_product']);
?>
<tr>
    <td><?= $p->id ?></td>
    <td><?= htmlspecialchars($p->name) ?></td>
    <td class="<?= $p->type ?>"><?= $p->type ?></td>
    <td>
        <?php if($onProducts): ?>
            <span style="color:#34d399;">🛍️ Products</span>
        <?php elseif($onManuf): ?>
            <span style="color:#fbbf24;">🏭 Manufacturing</span>
        <?php else: ?>
            <span style="color:#f87171;">❌ Hidden</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if($onProducts): ?>
        <form method="POST">
            <input type="hidden" name="fix_id" value="<?= $p->id ?>">
            <input type="hidden" name="new_type" value="finished_product">
            <button class="to-manuf">→ Move to Manufacturing</button>
        </form>
        <?php elseif($onManuf): ?>
        <form method="POST">
            <input type="hidden" name="fix_id" value="<?= $p->id ?>">
            <input type="hidden" name="new_type" value="book">
            <button class="to-prod">→ Move to Products</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<div style="margin-top:30px;padding:20px;background:#1e293b;border-radius:10px;">
    <a href="/store/products" style="padding:12px 25px;background:#3b82f6;color:white;text-decoration:none;border-radius:8px;font-weight:bold;margin-right:10px;">🛍️ Products Page</a>
    <a href="/manufacturing"  style="padding:12px 25px;background:#f59e0b;color:black;text-decoration:none;border-radius:8px;font-weight:bold;">🏭 Manufacturing Page</a>
</div>
</body>
</html>
