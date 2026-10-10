<?php
// Load Laravel app
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "=== FINDING BILAL USER ===\n";
$user = User::where('email', 'hebesha@gmail.com')->first();
if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Name: " . $user->name . "\n";
} else {
    echo "User not found\n";
    exit(1);
}

echo "\n=== BRANCH ASSIGNMENTS ===\n";
$userBranchIds = $user->assignedBranchIds();
echo "Assigned Branch IDs: " . json_encode($userBranchIds) . "\n";

foreach ($userBranchIds as $branchId) {
    $branch = DB::table('branches')->where('id', $branchId)->first();
    echo "Branch $branchId: " . ($branch ? $branch->name : 'Not found') . "\n";
}

echo "\n=== MANUFACTURED PRODUCTS ===\n";
// Get all products with manufacturing metadata
$products = DB::table('products')
    ->where('name', 'like', '%beherzaf%')
    ->orWhere('name', 'like', '%tukur%')
    ->get();

foreach ($products as $product) {
    echo "\nProduct ID: " . $product->id . "\n";
    echo "Name: " . $product->name . "\n";
    $metadata = json_decode($product->metadata, true);
    echo "Metadata: " . json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    if (isset($metadata['manufacturing_quantity'])) {
        echo "Manufacturing Qty: " . $metadata['manufacturing_quantity'] . "\n";
    }
    if (isset($metadata['manufacturing_by_branch'])) {
        echo "Manufacturing by Branch: " . json_encode($metadata['manufacturing_by_branch']) . "\n";
    }
}

echo "\n=== CHECKING FILTER LOGIC ===\n";
// Simulate what the component would do
foreach ($products as $product) {
    $metadata = json_decode($product->metadata, true) ?: [];
    
    echo "\n--- Product: " . $product->name . " ---\n";
    
    // Must have manufacturing_quantity > 0
    if (!isset($metadata['manufacturing_quantity']) || (float)$metadata['manufacturing_quantity'] <= 0) {
        echo "SKIP: No manufacturing_quantity or qty <= 0\n";
        continue;
    }
    
    // For non-super-admin users, MUST have manufacturing_by_branch data
    $manufacturingByBranch = $metadata['manufacturing_by_branch'] ?? [];
    
    if (empty($manufacturingByBranch)) {
        echo "SKIP: No manufacturing_by_branch data\n";
        continue;
    }
    
    echo "manufacturing_by_branch structure exists\n";
    
    // Check if THIS user's branch has manufactured this product
    $hasUserBranch = false;
    foreach ($userBranchIds as $branchId) {
        if (isset($manufacturingByBranch[(int)$branchId]) && (float)$manufacturingByBranch[(int)$branchId] > 0) {
            echo "  Branch $branchId (int): " . $manufacturingByBranch[(int)$branchId] . " ✓\n";
            $hasUserBranch = true;
            break;
        }
        if (isset($manufacturingByBranch[(string)$branchId]) && (float)$manufacturingByBranch[(string)$branchId] > 0) {
            echo "  Branch $branchId (str): " . $manufacturingByBranch[(string)$branchId] . " ✓\n";
            $hasUserBranch = true;
            break;
        }
    }
    
    if (!$hasUserBranch) {
        echo "EXCLUDE: User's branch not in manufacturing_by_branch\n";
    } else {
        echo "INCLUDE: Product will be shown for this user\n";
    }
}
