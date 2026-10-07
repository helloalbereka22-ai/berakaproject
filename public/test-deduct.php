<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html><html><head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}
h2{color:#38bdf8;} .good{color:#34d399;} .bad{color:#f87171;}
</style></head><body>
<?php

// Get latest pending guest order
$order = DB::table('guest_orders')->where('status','pending')->orderByDesc('id')->first();
if (!$order) {
    echo "<p class='bad'>No pending guest orders found</p></body></html>";
    exit;
}

echo "<div class='box'><h2>Latest Pending Order: {$order->order_number}</h2>";
$items = json_decode($order->items, true) ?? [];
echo "<p>Items: " . json_encode($items) . "</p></div>";

foreach ($items as $item) {
    $name = $item['name'] ?? '';
    $qty  = (float)($item['quantity'] ?? 1);

    echo "<div class='box'><h2>Item: {$name} | Qty to deduct: {$qty}</h2>";

    // Find product
    $product = \App\Models\Product::withoutGlobalScopes()->where('name', $name)->first();
    if (!$product) {
        echo "<p class='bad'>❌ Product '{$name}' NOT FOUND in products table</p>";
        // Show all products to help debug
        $all = DB::table('products')->select('id','name','type')->get();
        echo "<p>All products:</p><ul>";
        foreach($all as $p) echo "<li>id={$p->id} | {$p->name} | {$p->type}</li>";
        echo "</ul></div>";
        continue;
    }

    echo "<p class='good'>✅ Product found: ID={$product->id}, Type={$product->type}</p>";

    if (in_array($product->type, ['finished_product','bottle_product'])) {
        $meta   = $product->metadata ?? [];
        $mfgQty = (float)($meta['manufacturing_quantity'] ?? 0);
        echo "<p>manufacturing_quantity: <b>{$mfgQty}</b> → after deduct: <b>" . max(0,$mfgQty-$qty) . "</b></p>";
    } else {
        $purchase = DB::table('purchases')
            ->where('product_id', $product->id)
            ->where('status','completed')
            ->orderByDesc('purchase_date')
            ->first();
        if ($purchase) {
            echo "<p class='good'>Purchase found: id={$purchase->id}, qty={$purchase->quantity} → after deduct: <b>" . max(0,(float)$purchase->quantity - $qty) . "</b></p>";
        } else {
            echo "<p class='bad'>❌ No purchase record with status='completed' for this product</p>";
            // Show all purchases for this product
            $allP = DB::table('purchases')->where('product_id',$product->id)->get(['id','quantity','status','purchase_date']);
            echo "<p>All purchase records:</p><ul>";
            foreach($allP as $p) echo "<li>id={$p->id} | qty={$p->quantity} | status={$p->status} | date={$p->purchase_date}</li>";
            echo "</ul>";
        }
    }
    echo "</div>";
}

// If run with ?do=1, actually perform deduction
if (isset($_GET['do'])) {
    echo "<div class='box'><h2>RUNNING DEDUCTION...</h2>";
    foreach ($items as $item) {
        $name = $item['name'] ?? '';
        $qty  = (float)($item['quantity'] ?? 1);
        $product = \App\Models\Product::withoutGlobalScopes()->where('name', $name)->first();
        if (!$product) { echo "<p class='bad'>Skip {$name}: not found</p>"; continue; }

        if (in_array($product->type, ['finished_product','bottle_product'])) {
            $meta   = $product->metadata ?? [];
            $before = (float)($meta['manufacturing_quantity'] ?? 0);
            $meta['manufacturing_quantity'] = max(0, $before - $qty);
            $product->metadata = $meta;
            $product->save();
            echo "<p class='good'>✅ {$name}: mfg_qty {$before} → " . $product->fresh()->metadata['manufacturing_quantity'] . "</p>";
        } else {
            $purchase = DB::table('purchases')->where('product_id',$product->id)->where('status','completed')->orderByDesc('purchase_date')->first();
            if ($purchase) {
                $before = (float)$purchase->quantity;
                $newQty = max(0, $before - $qty);
                DB::table('purchases')->where('id',$purchase->id)->update(['quantity'=>$newQty,'total_price'=>$newQty*(float)$purchase->unit_price,'updated_at'=>now()]);
                $after = DB::table('purchases')->where('id',$purchase->id)->value('quantity');
                echo "<p class='good'>✅ {$name}: qty {$before} → {$after}</p>";
            } else {
                echo "<p class='bad'>❌ {$name}: no purchase record</p>";
            }
        }
    }
    // Mark confirmed
    DB::table('guest_orders')->where('id',$order->id)->update(['status'=>'confirmed','updated_at'=>now()]);
    echo "<p class='good'>✅ Order marked confirmed</p>";
    echo "</div>";
}
?>
<br>
<a href="?do=1" style="padding:15px 30px;background:#10b981;color:white;font-size:18px;font-weight:bold;border-radius:10px;text-decoration:none;">
    ▶ Run Deduction Now
</a>
</body></html>
