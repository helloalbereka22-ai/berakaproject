<?php
// Simple database connection test
$host = '127.0.0.1';
$user = 'root';
$pass = '';

// Try to connect without database first
$mysqli = new mysqli($host, $user, $pass);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "<h2>Database Connection Status</h2>\n";
echo "<p><strong>Host:</strong> $host</p>\n";
echo "<p><strong>User:</strong> $user</p>\n";
echo "<p><strong>Connection:</strong> ✓ SUCCESS</p>\n\n";

// List all databases
echo "<h3>Available Databases:</h3>\n";
echo "<ul>\n";
$result = $mysqli->query("SHOW DATABASES");
while ($row = $result->fetch_assoc()) {
    $dbname = $row['Database'];
    echo "<li>$dbname";
    if (strtolower($dbname) === 'albereka' || strtolower($dbname) === 'albarekaaaaaa') {
        echo " ✓ (Your database)";
    }
    echo "</li>\n";
}
echo "</ul>\n";

// Try to connect to albereka database
echo "<h3>Try connecting to 'albereka':</h3>\n";
$mysqli2 = new mysqli($host, $user, $pass, 'albereka');
if ($mysqli2->connect_error) {
    echo "<p style='color:red;'>✗ Failed: " . $mysqli2->connect_error . "</p>\n";
} else {
    echo "<p style='color:green;'>✓ Connected to 'albereka' database</p>\n";
    
    // Check if contact_messages table exists
    $result = $mysqli2->query("SHOW TABLES LIKE 'contact_messages'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color:green;'>✓ Table 'contact_messages' EXISTS</p>\n";
    } else {
        echo "<p style='color:orange;'>⚠ Table 'contact_messages' DOES NOT EXIST (will be created on first form submission)</p>\n";
    }
    
    $mysqli2->close();
}

$mysqli->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f5f5f5;
    }
    h2, h3 {
        color: #333;
    }
    p {
        line-height: 1.8;
    }
    ul {
        line-height: 1.8;
    }
</style>
