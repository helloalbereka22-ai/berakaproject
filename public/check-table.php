<?php
// Get env file and parse it correctly
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die('Error: .env file not found');
}

// Read .env file line by line to avoid parsing issues
$env = [];
$lines = file($envFile);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim($value);
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$database = $env['DB_DATABASE'] ?? 'albereka';
$username = $env['DB_USERNAME'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

// Connect to database directly
try {
    $mysqli = new mysqli($host, $username, $password, $database);
    if ($mysqli->connect_error) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}

// Check if table exists
$result = $mysqli->query("SHOW TABLES LIKE 'contact_messages'");

if ($result && $result->num_rows > 0) {
    echo "<h2>✓ Table 'contact_messages' EXISTS</h2>\n\n";
    
    // Get columns
    $result = $mysqli->query("DESC contact_messages");
    echo "<h3>Columns:</h3>\n";
    echo "<ul>\n";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>{$row['Field']}</strong> ({$row['Type']})</li>\n";
    }
    echo "</ul>\n";
    
    // Count messages
    $result = $mysqli->query("SELECT COUNT(*) as count FROM contact_messages");
    $count_row = $result->fetch_assoc();
    $count = $count_row['count'];
    
    echo "<h3>Total Messages: <strong>$count</strong></h3>\n";
    
    if ($count > 0) {
        echo "<h3>Recent Messages:</h3>\n";
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse:collapse; width:100%;'>\n";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Phone</th><th>Status</th><th>Date</th></tr>\n";
        
        $result = $mysqli->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 10");
        while ($row = $result->fetch_assoc()) {
            $date = date('M d, Y h:i A', strtotime($row['created_at']));
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['subject']}</td>";
            echo "<td>" . ($row['phone'] ? $row['phone'] : '-') . "</td>";
            echo "<td><span style='background:" . ($row['status'] === 'unread' ? '#ffcccc' : '#ccffcc') . "; padding:5px 10px; border-radius:3px;'>{$row['status']}</span></td>";
            echo "<td>$date</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    }
} else {
    echo "<h2>✗ Table 'contact_messages' DOES NOT EXIST</h2>\n";
    echo "<p>It will be created automatically when you submit the contact form for the first time.</p>\n";
    echo "<p><a href='../contact'>Go to Contact Form</a></p>\n";
}

$mysqli->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
    }
    h3 {
        color: #555;
        margin-top: 20px;
    }
    table {
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    table th {
        background: #007bff;
        color: white;
        text-align: left;
    }
    table td {
        border: 1px solid #ddd;
    }
    ul {
        line-height: 1.8;
    }
    a {
        color: #007bff;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
