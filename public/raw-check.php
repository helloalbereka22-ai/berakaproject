<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$products = DB::select("SELECT id, name, type FROM products ORDER BY type, name");

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";
echo str_pad("ID",5) . str_pad("TYPE",25) . "NAME\n";
echo str_repeat("-",80) . "\n";
foreach($products as $p){
    echo str_pad($p->id,5) . str_pad($p->type,25) . $p->name . "\n";
}
echo "</pre>";
