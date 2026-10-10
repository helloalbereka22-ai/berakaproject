<?php
/**
 * Check exact user role assignments
 */

require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ROLE_USER PIVOT TABLE ASSIGNMENTS ===\n\n";

// Check bilal's roles
echo "bilal (hebesha@gmail.com):\n";
$bilalRoles = DB::table('role_user')
    ->join('users', 'role_user.user_id', '=', 'users.id')
    ->join('roles', 'role_user.role_id', '=', 'roles.id')
    ->where('users.email', 'hebesha@gmail.com')
    ->select('role_user.*', 'roles.name as role_name', 'users.email')
    ->get();

foreach ($bilalRoles as $r) {
    echo "  Role: {$r->role_name}\n";
    echo "    business_id: {$r->business_id}\n";
    echo "    branch_id: {$r->branch_id}\n";
}

echo "\n";

// Check manufacurer's roles
echo "manufacurer (manf@gmail.com):\n";
$mfrRoles = DB::table('role_user')
    ->join('users', 'role_user.user_id', '=', 'users.id')
    ->join('roles', 'role_user.role_id', '=', 'roles.id')
    ->where('users.email', 'manf@gmail.com')
    ->select('role_user.*', 'roles.name as role_name', 'users.email')
    ->get();

foreach ($mfrRoles as $r) {
    echo "  Role: {$r->role_name}\n";
    echo "    business_id: {$r->business_id}\n";
    echo "    branch_id: {$r->branch_id}\n";
}

echo "\n=== BRANCH DETAILS ===\n\n";

// Get all branches
$branches = DB::table('branches')->select('id', 'name', 'business_id')->get();
foreach ($branches as $b) {
    echo "Branch {$b->id}: {$b->name} (Business {$b->business_id})\n";
}

echo "\n=== STOCK MOVEMENTS BY BRANCH ===\n\n";

// Check all branches with stock movements
$movements = DB::table('stock_movements')
    ->selectRaw('branch_id, COUNT(*) as count, SUM(CASE WHEN direction = "in" THEN quantity ELSE -quantity END) as total_qty')
    ->where('item_type', 'product')
    ->groupBy('branch_id')
    ->get();

foreach ($movements as $m) {
    $branchName = DB::table('branches')->where('id', $m->branch_id)->value('name');
    echo "Branch {$m->branch_id} ({$branchName}): {$m->count} movements, Total Qty: {$m->total_qty}\n";
}
