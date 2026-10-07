<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
?>
<!DOCTYPE html><html><head><style>
body{font-family:Arial;background:#0f172a;color:#e2e8f0;padding:20px;}
.box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}
h2{color:#fbbf24;} .good{color:#34d399;} .bad{color:#f87171;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th{background:#334155;padding:6px 8px;text-align:left;}
td{padding:5px 8px;border-bottom:1px solid #334155;}
</style></head><body>
<?php

// Show all packing material and bottle products with their purchase totals
$types = ['packing_material', 'bottle_product'];
foreach($types as $type) {
    $products = DB::table('products')
        ->where('type', $type)
        ->orWhere('name','like','%bottle%')
        ->orWhere('name','like','%mashagiya%')
        ->orWhere('name','like','%meshagiya%')
        ->select('id','name','type')
        ->get();
    
    foreach($products as $p) {
        $completed = DB::table('purchases')->where('product_id',$p->id)->where('status','completed')->sum('quantity');
        $all       = DB::table('purchases')->where('product_id',$p->id)->sum('quantity');
        $byStatus  = DB::table('purchases')->where('product_id',$p->id)
            ->select('status', DB::raw('SUM(quantity) as total'))
            ->groupBy('status')->get();
        
        echo "<div class='box'><h2>{$p->name} (type: {$p->type})</h2>";
        echo "<p>Completed purchases total: <b class='good'>{$completed}</b></p>";
        echo "<p>All purchases total: <b>{$all}</b></p>";
        echo "<table><tr><th>Status</th><th>Total Qty</th></tr>";
        foreach($byStatus as $s) echo "<tr><td>{$s->status}</td><td>{$s->total}</td></tr>";
        echo "</table></div>";
    }
}
?>
<form method="POST">
<?php if(isset($_POST['fix'])): ?>
<?php
    // Fix: set all purchases to completed for packing/bottle items
    $updated = DB::table('purchases')
        ->whereIn('product_id', function($q) {
            $q->select('id')->from('products')
              ->where('type','packing_material')
              ->orWhere('name','like','%bottle%')
              ->orWhere('name','like','%mashagiya%')
              ->orWhere('name','like','%meshagiya%');
        })
        ->update(['status' => 'completed']);
    echo "<p style='color:#34d399;padding:10px;'>✅ Fixed! {$updated} purchase records set to completed.</p>";
endif; ?>
<button type="submit" name="fix" style="padding:12px 24px;background:#10b981;color:white;font-size:16px;font-weight:bold;border:none;border-radius:8px;cursor:pointer;margin:10px;">
    ✅ Fix: Set all to Completed
</button>
</form>
</body></html>
