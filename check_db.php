<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'albarekaaaaaa_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$today = date('Y-m-d');

echo "=== Checking stock_movements for today ($today) ===\n\n";

// Check what exists
$sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN direction = 'in' THEN 1 END) as direction_in,
    COUNT(CASE WHEN direction = 'out' THEN 1 END) as direction_out
FROM stock_movements 
WHERE DATE(moved_at) = '$today'";

$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "Today's movements:\n";
echo "  Total: " . $row['total'] . "\n";
echo "  Direction IN: " . $row['direction_in'] . "\n";
echo "  Direction OUT: " . $row['direction_out'] . "\n\n";

// Check reference types for IN movements
$sql = "SELECT DISTINCT reference_type FROM stock_movements 
WHERE DATE(moved_at) = '$today' AND direction = 'in'
ORDER BY reference_type";

$result = $conn->query($sql);
echo "Reference types for today's IN movements:\n";
while ($row = $result->fetch_assoc()) {
    $ref = $row['reference_type'] ?? 'NULL';
    echo "  - " . $ref . "\n";
}

// Count by reference type
$sql = "SELECT reference_type, COUNT(*) as cnt FROM stock_movements 
WHERE DATE(moved_at) = '$today' AND direction = 'in'
GROUP BY reference_type";

$result = $conn->query($sql);
echo "\nCount by reference_type for IN movements:\n";
while ($row = $result->fetch_assoc()) {
    $ref = $row['reference_type'] ?? 'NULL';
    echo "  " . $ref . ": " . $row['cnt'] . "\n";
}

// Show samples
$sql = "SELECT id, movement_type, reference_type, direction, quantity, moved_at FROM stock_movements 
WHERE DATE(moved_at) = '$today' AND direction = 'in'
ORDER BY moved_at DESC
LIMIT 5";

$result = $conn->query($sql);
echo "\nSample IN movements from today:\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']} | Type: {$row['movement_type']} | Ref: " . ($row['reference_type'] ?? 'NULL') . " | Qty: {$row['quantity']}\n";
    }
} else {
    echo "  (No movements found)\n";
}

$conn->close();
?>
