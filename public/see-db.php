<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$products = DB::select("SELECT id, name, type FROM products ORDER BY name");

echo "<table border='1' cellpadding='8' style='font-family:Arial;font-size:14px;border-collapse:collapse;width:100%;'>";
echo "<tr style='background:#333;color:white;'><th>ID</th><th>NAME</th><th>TYPE (exact)</th></tr>";
foreach($products as $p){
    echo "<tr><td>{$p->id}</td><td>{$p->name}</td><td><b>{$p->type}</b></td></tr>";
}
echo "</table>";
