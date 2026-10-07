<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html><html><head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}
h2{color:#fbbf24;} .good{color:#34d399;} .bad{color:#f87171;}
</style></head><body>
<?php

// Find 100ml Bahrzef
$p = \App\Models\Product::withoutGlobalScopes()->where('name','like','%Bahrzef%')->orWhere('name','like','%bahrzef%')->first();
if (!$p) {
    echo "<p class='bad'>Product not found</p>";
    exit;
}

echo "<div class='box'><h2>100ml Bahrzef Product</h2>";
echo "<p>ID: {$p->id} | Type: {$p->type}</p>";
$meta = $p->metadata ?? [];
$mfgQty = (float)($meta['manufacturing_quantity'] ?? 0);
echo "<p>metadata['manufacturing_quantity']: <b class='good'>{$mfgQty}</b></p>";
$purchase = DB::table('purchases')->where('product_id',$p->id)->where('quantity','>',0)->orderByDesc('quantity')->first();
echo "<p>Purchase qty: <b class='good'>" . ($purchase ? $purchase->quantity : 'none') . "</b></p>";
echo "<p><b>Total shown = " . ($purchase ? $purchase->quantity : 0) . " + {$mfgQty} = " . (($purchase ? $purchase->quantity : 0) + $mfgQty) . "</b></p>";
echo "</div>";

// Test deduction
if (isset($_GET['deduct'])) {
    echo "<div class='box'><h2>Deducting 1 unit...</h2>";
    if (in_array($p->type, ['finished_product','bottle_product'])) {
        $before = $mfgQty;
        $meta['manufacturing_quantity'] = max(0, $mfgQty - 1);
        $p->metadata = $meta;
        $p->save();
        $fresh = \App\Models\Product::withoutGlobalScopes()->find($p->id);
        $after = (float)($fresh->metadata['manufacturing_quantity'] ?? 0);
        echo "<p class='good'>✅ mfg_qty: {$before} → {$after}</p>";
        
        $newPurchaseQty = $purchase ? $purchase->quantity : 0;
        echo "<p class='good'>New Total = {$newPurchaseQty} + {$after} = " . ($newPurchaseQty + $after) . "</p>";
    }
    echo "</div>";
}
?>
<br>
<a href="?deduct=1" style="padding:15px 30px;background:#f59e0b;color:black;font-size:18px;font-weight:bold;border-radius:10px;text-decoration:none;">
    Test: Deduct 1 from 100ml Bahrzef
</a>
</body></html>
