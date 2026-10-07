<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$before = DB::table('products')->where('status','active')->where('is_marketplace_visible',1)->count();

// Fix ALL products - set active and visible
DB::table('products')->update([
    'status' => 'active',
    'is_marketplace_visible' => 1,
]);

$after = DB::table('products')->where('status','active')->where('is_marketplace_visible',1)->count();

echo "<p style='font-family:Arial;font-size:18px;color:#34d399;padding:20px;background:#0f172a;'>
✅ Fixed! Before: {$before} visible → After: {$after} visible products
</p>";
echo "<a href='/store/products' style='padding:12px 24px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;font-weight:bold;margin:10px;display:inline-block;'>🛍️ View Products</a>";
echo "<a href='/manufacturing' style='padding:12px 24px;background:#f59e0b;color:black;border-radius:8px;text-decoration:none;font-weight:bold;margin:10px;display:inline-block;'>🏭 View Manufacturing</a>";
