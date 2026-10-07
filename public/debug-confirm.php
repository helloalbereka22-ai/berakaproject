<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html><html><head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}
h2{color:#38bdf8;margin:5px 0 10px;}
.good{color:#34d399;} .bad{color:#f87171;} .warn{color:#fbbf24;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th{background:#334155;padding:6px 8px;text-align:left;}
td{padding:5px 8px;border-bottom:1px solid #334155;}
</style></head><body>
<?php

// Get latest guest order
$order = DB::table('guest_orders')->orderByDesc('id')->first();
if (!$order) { echo "<p class='bad'>No guest orders found</p>"; exit; }

echo "<div class='box'><h2>Latest Guest Order: {$order->order_number}</h2>";
echo "<p>Status: <b>{$order->status}</b></p>";
$items = json_decode($order->items, true) ?? [];

foreach ($items as $item) {
    $name = $item['name'] ?? '';
    $qty  = $item['quantity'] ?? 1;
    echo "<p class='warn'>Item: '{$name}' | Qty: {$qty}</p>";

    // Find product
    $product = DB::table('products')->where('name', $name)->first();
    if (!$product) {
        echo "<p class='bad'>❌ Product NOT FOUND by exact name '{$name}'</p>";
        // Try partial match
        $like = DB::table('products')->where('name', 'like', '%' . substr($name, 0, 5) . '%')->get(['id','name','type']);
        echo "<p>Partial matches:</p><table><tr><th>ID</th><th>Name</th><th>Type</th></tr>";
        foreach ($like as $l) echo "<tr><td>{$l->id}</td><td>{$l->name}</td><td>{$l->type}</td></tr>";
        echo "</table>";
        continue;
    }

    echo "<p class='good'>✅ Product found: ID={$product->id}, Name='{$product->name}', Type={$product->type}</p>";

    // Check metadata
    $meta = is_string($product->metadata) ? json_decode($product->metadata, true) : (array)$product->metadata;
    $mfgQty = (float)($meta['manufacturing_quantity'] ?? 0);
    echo "<p>manufacturing_quantity in metadata: <b class='" . ($mfgQty > 0 ? 'good' : 'bad') . "'>{$mfgQty}</b></p>";

    // Check purchases
    $purchase = DB::table('purchases')->where('product_id', $product->id)->where('status','completed')->orderByDesc('purchase_date')->first();
    if ($purchase) {
        echo "<p class='good'>Purchase found: qty={$purchase->quantity}, price={$purchase->unit_price}</p>";
    } else {
        echo "<p class='bad'>No purchase record found for this product</p>";
    }
}
echo "</div>";

// Now simulate deduction
if (isset($_GET['do'])) {
    echo "<div class='box'><h2>Simulating Deduction...</h2>";
    foreach ($items as $item) {
        $name = $item['name'] ?? '';
        $qty  = (float)($item['quantity'] ?? 1);
        $product = \App\Models\Product::withoutGlobalScopes()->where('name', $name)->first();
        if (!$product) { echo "<p class='bad'>Skip: product not found</p>"; continue; }

        if (in_array($product->type, ['finished_product','bottle_product'])) {
            $meta   = $product->metadata ?? [];
            $before = (float)($meta['manufacturing_quantity'] ?? 0);
            $meta['manufacturing_quantity'] = max(0, $before - $qty);
            $product->metadata = $meta;
            $product->save();
            $after = (float)($product->fresh()->metadata['manufacturing_quantity'] ?? 0);
            echo "<p class='good'>✅ {$name}: mfg_qty {$before} → {$after}</p>";
        } else {
            $purchase = DB::table('purchases')->where('product_id',$product->id)->where('status','completed')->orderByDesc('purchase_date')->first();
            if ($purchase) {
                $before = (float)$purchase->quantity;
                $newQty = max(0, $before - $qty);
                DB::table('purchases')->where('id',$purchase->id)->update(['quantity'=>$newQty,'total_price'=>$newQty*(float)$purchase->unit_price,'updated_at'=>now()]);
                echo "<p class='good'>✅ {$name}: purchase_qty {$before} → {$newQty}</p>";
            } else {
                echo "<p class='bad'>❌ {$name}: no purchase record</p>";
            }
        }
    }
    echo "</div>";
}
?>
<div class="box">
    <a href="?do=1" style="padding:12px 24px;background:#10b981;color:white;border-radius:8px;font-weight:bold;text-decoration:none;">
        ▶ Run Deduction Now
    </a>
    &nbsp;&nbsp;
    <a href="/admin/purchases/create" style="padding:12px 24px;background:#3b82f6;color:white;border-radius:8px;font-weight:bold;text-decoration:none;">
        Check Purchases Page
    </a>
</div>
</body></html>
