<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'albereka');

echo "=== PRODUCTION_OUTPUT MOVEMENTS ===\n";
$sql = 'SELECT id, movement_type, direction, quantity, item_id, branch_id, business_id, moved_at FROM stock_movements WHERE movement_type = "production_output" ORDER BY moved_at DESC LIMIT 5';
$result = $mysqli->query($sql);
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "ID: " . $row['id'] . " | Type: " . $row['movement_type'] . " | Dir: " . $row['direction'] . " | Qty: " . $row['quantity'] . " | Item: " . $row['item_id'] . " | Branch: " . $row['branch_id'] . " | Biz: " . $row['business_id'] . " | Time: " . $row['moved_at'] . "\n";
}
echo "Total production_output: $count\n";

echo "\n=== PRODUCTION_CONSUMPTION MOVEMENTS (Latest 5) ===\n";
$sql = 'SELECT id, movement_type, direction, quantity, item_id, branch_id, business_id, moved_at FROM stock_movements WHERE movement_type = "production_consumption" ORDER BY moved_at DESC LIMIT 5';
$result = $mysqli->query($sql);
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "ID: " . $row['id'] . " | Type: " . $row['movement_type'] . " | Dir: " . $row['direction'] . " | Qty: " . $row['quantity'] . " | Item: " . $row['item_id'] . " | Branch: " . $row['branch_id'] . " | Biz: " . $row['business_id'] . " | Time: " . $row['moved_at'] . "\n";
}

echo "\n=== COUNT BY MOVEMENT TYPE ===\n";
$sql = 'SELECT movement_type, COUNT(*) as cnt FROM stock_movements WHERE item_type = "product" GROUP BY movement_type ORDER BY cnt DESC';
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    echo $row['movement_type'] . ": " . $row['cnt'] . "\n";
}

$mysqli->close();
?>
