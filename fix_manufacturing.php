<?php
// Read the file
$filePath = __DIR__ . '/app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php';
$content = file_get_contents($filePath);

// Find and replace the problematic section
$oldPattern = <<<'OLD'
            // For non-super-admin users, MUST have manufacturing_by_branch data
            $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
            
            // If no manufacturing_by_branch exists, skip entirely
            if (empty($manufacturingByBranch)) {
                continue;
            }
OLD;

$newPattern = <<<'NEW'
            // For non-super-admin users, check manufacturing_by_branch structure
            $manufacturingByBranch = $meta['manufacturing_by_branch'] ?? [];
            
            // NEW LOGIC: If manufacturing_by_branch doesn't exist, fall back to checking if the product
            // has manufacturing_quantity > 0. This handles legacy products that were manufactured
            // before the manufacturing_by_branch structure was implemented.
            if (empty($manufacturingByBranch)) {
                // Legacy fallback: product has manufacturing_quantity but no branch tracking
                // Assume user's first branch can access it if they're viewing this product
                $legacyMfgQty = (float)($meta['manufacturing_quantity'] ?? 0);
                if ($legacyMfgQty > 0 && !empty($userBranchIds)) {
                    // Include this product for non-super-admin users
                    // Set branch_qty to the legacy manufacturing_quantity
                    $product->branch_qty = $legacyMfgQty;
                    $filtered->push($product);
                }
                continue;
            }
NEW;

if (strpos($content, $oldPattern) === false) {
    echo "ERROR: Could not find the exact pattern to replace.\n";
    echo "File length: " . strlen($content) . " bytes\n";
    
    // Try to find a nearby pattern
    if (strpos($content, 'manufacturing_by_branch') === false) {
        echo "String 'manufacturing_by_branch' not found in file!\n";
    } else {
        echo "Found 'manufacturing_by_branch' in file\n";
        // Show context
        $pos = strpos($content, 'MUST have manufacturing_by_branch');
        if ($pos !== false) {
            echo "Found 'MUST have manufacturing_by_branch' at position $pos\n";
            echo "Context: " . substr($content, $pos - 50, 200) . "\n";
        }
    }
    exit(1);
}

$newContent = str_replace($oldPattern, $newPattern, $content);

if ($newContent === $content) {
    echo "ERROR: Replacement did not make any changes.\n";
    exit(1);
}

// Write the file back
if (!file_put_contents($filePath, $newContent)) {
    echo "ERROR: Could not write file.\n";
    exit(1);
}

echo "SUCCESS: File updated successfully.\n";
echo "Replaced old filtering logic with fallback support for legacy products.\n";
