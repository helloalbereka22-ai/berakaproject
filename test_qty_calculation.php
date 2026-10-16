<?php

// Simple test to verify the quantity calculation logic
// This mimics what getSourceBalance() does

// Assume these are the locations for a branch
$locationIds = [1, 2, 3]; // Bazar branch A has these locations
$productId = 5;

// Simulated StockMovement data
$movements = [
    ['location_id' => 1, 'direction' => 'in', 'quantity' => 50, 'movement_type' => 'purchase'],
    ['location_id' => 2, 'direction' => 'in', 'quantity' => 48, 'movement_type' => 'purchase'],
    ['location_id' => 3, 'direction' => 'in', 'quantity' => 0, 'movement_type' => 'purchase'],
    ['location_id' => 1, 'direction' => 'out', 'quantity' => 9, 'movement_type' => 'sale'],
    ['location_id' => 2, 'direction' => 'out', 'quantity' => 0, 'movement_type' => 'sale'],
];

// Calculate like getSourceBalance() does
$stockIn = 0;
$stockOut = 0;

foreach ($movements as $move) {
    if (in_array($move['location_id'], $locationIds)) {
        if ($move['direction'] === 'in') {
            $stockIn += $move['quantity'];
        } else {
            $stockOut += $move['quantity'];
        }
    }
}

$balance = $stockIn - $stockOut;

echo "Locations: " . implode(', ', $locationIds) . "\n";
echo "Stock In: $stockIn\n";
echo "Stock Out: $stockOut\n";
echo "Balance: $balance\n";
echo "\nThis should be 98 after transferring 9.\n";
echo "If it shows 160, we're getting locations from TWO branches.\n";
