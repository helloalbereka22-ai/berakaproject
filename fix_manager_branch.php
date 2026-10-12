<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Branch;

echo "=== FIXING GENERAL MANAGER BRANCH ASSIGNMENT ===\n\n";

// Get the General Manager user
$manager = User::where('email', 'manager@albareka.com')->first();

if (!$manager) {
    echo "ERROR: General Manager not found!\n";
    exit(1);
}

echo "Found General Manager: {$manager->email}\n";

// Get the branches
$oilBranch = Branch::where('code', 'OIL-HQ')->first();
$bookBranch = Branch::where('code', 'BOOK-BR1')->first();

if (!$oilBranch) {
    echo "ERROR: Oil Factory branch (OIL-HQ) not found!\n";
    exit(1);
}

if (!$bookBranch) {
    echo "ERROR: Bookstore branch (BOOK-BR1) not found!\n";
    exit(1);
}

echo "Oil Factory Branch ID: {$oilBranch->id}\n";
echo "Bookstore Branch ID: {$bookBranch->id}\n\n";

// Update the role_user table to add branch assignments
// Find Business Manager role ID
$businessManagerRole = DB::table('roles')->where('name', 'Business Manager')->first();
if (!$businessManagerRole) {
    echo "ERROR: Business Manager role not found!\n";
    exit(1);
}

echo "Business Manager Role ID: {$businessManagerRole->id}\n\n";

// Update existing role_user records for the manager
// Oil Factory assignment
$oilUpdate = DB::table('role_user')
    ->where('user_id', $manager->id)
    ->where('role_id', $businessManagerRole->id)
    ->where('business_id', $oilBranch->business_id)
    ->update(['branch_id' => $oilBranch->id]);

echo "Updated Oil Factory assignment: $oilUpdate record(s)\n";

// Bookstore assignment
$bookUpdate = DB::table('role_user')
    ->where('user_id', $manager->id)
    ->where('role_id', $businessManagerRole->id)
    ->where('business_id', $bookBranch->business_id)
    ->update(['branch_id' => $bookBranch->id]);

echo "Updated Bookstore assignment: $bookUpdate record(s)\n\n";

// Verify the change
$currentAssignments = DB::table('role_user')
    ->where('user_id', $manager->id)
    ->get();

echo "=== CURRENT ROLE_USER ASSIGNMENTS ===\n";
foreach ($currentAssignments as $assignment) {
    echo "Role ID: {$assignment->role_id}, Business: {$assignment->business_id}, Branch: {$assignment->branch_id}\n";
}

// Flush the manager's cached permissions
$manager->flushPermissionCache();

echo "\n✅ Fixed! General Manager now assigned to both branches.\n";
echo "   - Oil Factory (OIL-HQ): Branch ID {$oilBranch->id}\n";
echo "   - Bookstore (BOOK-BR1): Branch ID {$bookBranch->id}\n";
?>
