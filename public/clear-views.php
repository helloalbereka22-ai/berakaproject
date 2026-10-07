<?php
$dir = __DIR__ . '/../storage/framework/views/';
$count = 0;
foreach (glob($dir . '*.php') as $file) { unlink($file); $count++; }
echo "✅ Cleared {$count} compiled views. <a href='/admin/orders'>Go to Orders</a>";
