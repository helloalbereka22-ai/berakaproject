<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== USER BRANCH ASSIGNMENTS ===\n\n";

// Get manager's info
$manager = DB::table('users')
    ->where('email', 'manager@albareka.com')
    ->first();

if ($manager) {
    echo "GENERAL MANAGER (manager@albareka.com):\n";
    echo "  User ID: {$manager->id}\n";
    echo "  assigned_business_ids: {$manager->assigned_business_ids}\n";
    echo "  assigned_branch_ids: {$manager->assigned_branch_ids}\n";
    echo "\n";
}

// Get bilal's info
$bilal = DB::table('users')
    ->where('email', 'hebesha@gmail.com')
    ->first();

if ($bilal) {
    echo "BILAL (hebesha@gmail.com):\n";
    echo "  User ID: {$bilal->id}\n";
    echo "  assigned_business_ids: {$bilal->assigned_business_ids}\n";
    echo "  assigned_branch_ids: {$bilal->assigned_branch_ids}\n";
    echo "\n";
}

// Get manufacturer's info
$manufacturer = DB::table('users')
    ->where('email', 'manf@gmail.com')
    ->first();

if ($manufacturer) {
    echo "MANUFACTURER (manf@gmail.com):\n";
    echo "  User ID: {$manufacturer->id}\n";
    echo "  assigned_business_ids: {$manufacturer->assigned_business_ids}\n";
    echo "  assigned_branch_ids: {$manufacturer->assigned_branch_ids}\n";
    echo "\n";
}

// Get all branches
echo "=== ALL BRANCHES ===\n";
$branches = DB::table('branches')->get();
foreach ($branches as $branch) {
    echo "  Branch ID: {$branch->id}, Name: {$branch->name}\n";
}

echo "\n=== SUMMARY ===\n";
if ($manager && $bilal) {
    $managerBranch = $manager->assigned_branch_ids;
    $bilalBranch = $bilal->assigned_branch_ids;
    echo "Manager branch: {$managerBranch}\n";
    echo "Bilal branch: {$bilalBranch}\n";
    echo "Same branch? " . ($managerBranch === $bilalBranch ? "YES" : "NO - THIS IS THE PROBLEM") . "\n";
}
?>
