<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:20px;'>";

// Show all branches and their status
echo "=== ALL BRANCHES ===\n";
$branches = DB::table('branches')
    ->join('businesses', 'branches.business_id', '=', 'businesses.id')
    ->select('branches.id', 'branches.name', 'branches.status', 'businesses.name as biz_name')
    ->get();

foreach ($branches as $b) {
    $color = $b->status === 'active' ? "\033[32m" : "\033[31m";
    echo "id={$b->id} | branch={$b->name} | business={$b->biz_name} | status={$b->status}\n";
}

// Activate all branches
if (isset($_GET['fix'])) {
    $updated = DB::table('branches')->update(['status' => 'active']);
    echo "\n✅ Activated ALL {$updated} branches!\n";
}
echo "</pre>";

if (!isset($_GET['fix'])):
?>
<br>
<a href="?fix=1" style="padding:15px 30px;background:#10b981;color:white;font-size:18px;font-weight:bold;border-radius:10px;text-decoration:none;">
    ✅ Activate ALL Branches
</a>
<?php endif; ?>
