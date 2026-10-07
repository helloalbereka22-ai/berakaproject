<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Step 1: Show what we have BEFORE fix
$before = DB::table('products')
    ->whereIn('type', ['book','stationery','fashion','general','finished_product','bottle_product'])
    ->get(['id','name','type']);

// Step 2: Fix ALL oil/ml products — change their type to finished_product
// This uses both name patterns AND any type they currently have
$updated = DB::table('products')
    ->where(function($q){
        $q->where('name','like','%ml%')
          ->orWhere('name','like','%liter%')
          ->orWhere('name','like','%litre%')
          ->orWhere('name','like','%tukur%')
          ->orWhere('name','like','%telba%')
          ->orWhere('name','like','%selit%')
          ->orWhere('name','like','%azmud%')
          ->orWhere('name','like','%azmut%')
          ->orWhere('name','like','%bahrzef%')
          ->orWhere('name','like','%zait%')
          ->orWhere('name','like','%zeit%')
          ->orWhere('name','like','%oil%');
    })
    ->update(['type' => 'finished_product']);

// Step 3: Show what is NOW on Products page (after fix)
$afterProducts = DB::table('products')
    ->whereIn('type',['book','stationery','fashion','general'])
    ->where('status','active')
    ->where('is_marketplace_visible',1)
    ->get(['id','name','type']);

$afterManuf = DB::table('products')
    ->whereIn('type',['finished_product','bottle_product'])
    ->where('status','active')
    ->where('is_marketplace_visible',1)
    ->get(['id','name','type']);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Direct Fix</title>
<style>
body{font-family:Arial;background:#0f172a;color:white;padding:30px;max-width:900px;margin:0 auto;}
.box{background:#1e293b;padding:20px;border-radius:12px;margin:20px 0;}
.green{color:#34d399;} .yellow{color:#fbbf24;} .red{color:#f87171;}
h2{margin-top:0;}
ul{line-height:2;}
.btn{display:inline-block;padding:14px 30px;border-radius:10px;font-weight:bold;font-size:16px;text-decoration:none;margin:8px 5px;}
.blue{background:#3b82f6;color:white;}
.amber{background:#f59e0b;color:black;}
</style>
</head>
<body>

<h1>🔧 Auto-Fix Complete!</h1>

<div class="box">
    <h2>Result</h2>
    <p class="green" style="font-size:20px;">✅ Moved <strong><?= $updated ?></strong> oil/ml products to Manufacturing</p>
</div>

<div class="box">
    <h2 class="green">🛍️ Products Page now shows (<?= count($afterProducts) ?> items):</h2>
    <?php if(count($afterProducts) === 0): ?>
        <p class="red">⚠️ No products! Your purchased items (bulug, quran, shito, etc.) may need their type set.</p>
        <p>Go to Admin → Item Management → edit each purchased item → set type to <strong>book</strong>, <strong>stationery</strong>, or <strong>fashion</strong></p>
    <?php else: ?>
    <ul>
        <?php foreach($afterProducts as $p): ?>
        <li class="green">✅ <?= htmlspecialchars($p->name) ?> <small>(<?= $p->type ?>)</small></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<div class="box">
    <h2 class="yellow">🏭 Manufacturing Page now shows (<?= count($afterManuf) ?> items):</h2>
    <?php if(count($afterManuf) === 0): ?>
        <p class="red">⚠️ No manufacturing products visible yet.</p>
    <?php else: ?>
    <ul>
        <?php foreach($afterManuf as $p): ?>
        <li class="yellow">🫗 <?= htmlspecialchars($p->name) ?> <small>(<?= $p->type ?>)</small></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<a href="/store/products" class="btn blue">🛍️ View Products Page</a>
<a href="/manufacturing"  class="btn amber">🏭 View Manufacturing Page</a>

</body>
</html>
