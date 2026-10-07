<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$businesses = DB::table('businesses')->count();
$categories = DB::table('categories')->count();
$products   = DB::table('products')->count();

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:20px;font-family:Arial;'>";
echo "Businesses: {$businesses}\n";
echo "Categories: {$categories}\n";
echo "Products:   {$products}\n";
echo "</pre>";

if ($businesses == 0) {
    echo "<p style='color:#f87171;font-family:Arial;padding:10px;'>❌ No businesses found. Please create a business first in Admin → Business Management</p>";
}
if ($categories == 0) {
    echo "<p style='color:#f87171;font-family:Arial;padding:10px;'>❌ No categories found. Please create a category first in Admin → Category Management</p>";
}
if ($businesses > 0 && $categories > 0) {
    echo "<p style='color:#34d399;font-family:Arial;padding:10px;'>✅ Ready! Go to Item Management to add products.</p>";
    echo "<a href='/admin/item-manager' style='padding:12px 24px;background:#6366f1;color:white;border-radius:8px;text-decoration:none;font-weight:bold;'>📦 Go to Item Management</a>";
}
