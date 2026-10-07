<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// These are the EXACT products that should go to MANUFACTURING (oil/seed products)
$moveToManufacturing = [
    '100 ml tukur',
    '100ml Bahrzef',
    '200ml tukur',
    '30ml selit',
    '30ml tukur azmut',
    '500ml tukur azmud',
    '50ml tukur',
    'humera selit seed',
    'Selit seed',
    'tukur azmud seed',
];

// These are the EXACT products that should stay on PRODUCTS page
$keepOnProducts = [
    'bulug',
    'Imama',
    'imama2',
    'jelabiya',
    'kofiya',
    'quranh',
    'tinishu qurn',
    'victory',
];

// Move oil/seed products to finished_product
foreach ($moveToManufacturing as $name) {
    DB::table('products')
        ->where('name', $name)
        ->update(['type' => 'finished_product']);
}

// Make sure purchased items are set correctly
foreach ($keepOnProducts as $name) {
    DB::table('products')
        ->where('name', $name)
        ->update([
            'type' => 'fashion',  // generic saleable type
            'status' => 'active',
            'is_marketplace_visible' => 1,
        ]);
}

// Fix specific known types
DB::table('products')->where('name','quranh')->update(['type'=>'book']);
DB::table('products')->where('name','tinishu qurn')->update(['type'=>'book']);
DB::table('products')->where('name','bulug')->update(['type'=>'book']);
DB::table('products')->where('name','Imama')->update(['type'=>'fashion']);
DB::table('products')->where('name','imama2')->update(['type'=>'fashion']);
DB::table('products')->where('name','jelabiya')->update(['type'=>'fashion']);
DB::table('products')->where('name','kofiya')->update(['type'=>'fashion']);
DB::table('products')->where('name','victory')->update(['type'=>'fashion']);

// Verify results
$onProducts = DB::table('products')
    ->whereIn('type', ['book','stationery','fashion','general'])
    ->where('status','active')
    ->where('is_marketplace_visible',1)
    ->pluck('name');

$onManuf = DB::table('products')
    ->whereIn('type', ['finished_product','bottle_product'])
    ->where('status','active')
    ->where('is_marketplace_visible',1)
    ->pluck('name');

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body{font-family:Arial;background:#0f172a;color:white;padding:30px;max-width:700px;margin:0 auto;}
.card{background:#1e293b;border-radius:12px;padding:25px;margin:20px 0;}
.g{color:#34d399;} .y{color:#fbbf24;}
.item{padding:8px 14px;margin:5px 0;border-radius:8px;}
.gi{background:#064e3b;color:#34d399;}
.yi{background:#451a03;color:#fbbf24;}
.btn{display:inline-block;padding:14px 30px;border-radius:10px;font-weight:bold;font-size:16px;text-decoration:none;margin:8px 5px;}
</style>
</head>
<body>
<h1 style="color:#34d399;">✅ Fixed!</h1>

<div class="card">
    <h2 class="g">🛍️ Products Page (<?= count($onProducts) ?> items)</h2>
    <?php foreach($onProducts as $n): ?>
    <div class="item gi">✅ <?= htmlspecialchars($n) ?></div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h2 class="y">🏭 Manufacturing Page (<?= count($onManuf) ?> items)</h2>
    <?php foreach($onManuf as $n): ?>
    <div class="item yi">🫗 <?= htmlspecialchars($n) ?></div>
    <?php endforeach; ?>
</div>

<a href="/store/products" class="btn" style="background:#3b82f6;color:white;">🛍️ View Products</a>
<a href="/manufacturing"  class="btn" style="background:#f59e0b;color:black;">🏭 View Manufacturing</a>
</body>
</html>
