<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:20px;font-size:13px;'>";

// Show all branches
echo "=== BRANCHES ===\n";
$branches = DB::table('branches')->join('businesses','branches.business_id','=','businesses.id')
    ->select('branches.id','branches.name','businesses.name as biz')->get();
foreach($branches as $b) echo "id={$b->id} | {$b->name} | {$b->biz}\n";

// Show 100ml beherzaf product
echo "\n=== 100ml beherzaf PRODUCT ===\n";
$p = \App\Models\Product::withoutGlobalScopes()->where('name','like','%beherzaf%')->orWhere('name','like','%behrzaf%')->first();
if($p) {
    echo "id={$p->id} | name={$p->name} | type={$p->type}\n";
    $meta = $p->metadata ?? [];
    echo "metadata.manufacturing_quantity: " . ($meta['manufacturing_quantity'] ?? 'NOT SET') . "\n";
    
    // Check purchases per branch
    echo "\nPurchases per branch:\n";
    $purs = DB::table('purchases')->where('product_id',$p->id)->where('status','completed')->get(['id','branch_id','quantity','status']);
    foreach($purs as $pur) {
        $brName = DB::table('branches')->where('id',$pur->branch_id)->value('name') ?? 'Unknown';
        echo "  purchase_id={$pur->id} | branch={$brName}(id={$pur->branch_id}) | qty={$pur->quantity}\n";
    }
    if($purs->isEmpty()) echo "  NO purchase records found!\n";
}

echo "</pre>";
