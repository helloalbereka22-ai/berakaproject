<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    die('Log file not found');
}

// Get last 100 lines
$lines = file($logFile);
$lastLines = array_slice($lines, -100);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Laravel Logs</title>
    <style>
        body {
            font-family: monospace;
            margin: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        pre {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 3px solid #007bff;
        }
        .error {
            color: #f48771;
            font-weight: bold;
        }
        .info {
            color: #6a9955;
        }
        .warning {
            color: #dcdcaa;
        }
        h2 {
            color: #4ec9b0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h2>📋 Latest 100 Lines from Laravel Log</h2>
    <button onclick="location.reload()">🔄 Refresh</button>
    <button onclick="clearLog()">🗑️ Clear Log</button>
    
    <pre><?php
    foreach ($lastLines as $line) {
        $line = htmlspecialchars($line);
        
        if (strpos($line, 'ERROR') !== false) {
            echo "<span class='error'>$line</span>";
        } elseif (strpos($line, 'INFO') !== false || strpos($line, 'contact') !== false) {
            echo "<span class='info'>$line</span>";
        } elseif (strpos($line, 'WARNING') !== false) {
            echo "<span class='warning'>$line</span>";
        } else {
            echo $line;
        }
    }
    ?></pre>

    <script>
        function clearLog() {
            if (confirm('Are you sure you want to clear the log file?')) {
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=clear', {method: 'POST'})
                    .then(() => location.reload());
            }
        }
    </script>
</body>
</html>

<?php
// Handle clear action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    file_put_contents($logFile, '');
    echo 'Log cleared';
    exit;
}
?>
