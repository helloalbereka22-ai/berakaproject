<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<pre style='background:#0f172a;color:#e2e8f0;padding:20px;font-size:13px;'>";
echo "=== RECENT LARAVEL LOG ERRORS ===\n\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last = array_slice($lines, -100);
    $errors = array_filter($last, fn($l) => str_contains($l, 'ERROR') || str_contains($l, 'CRITICAL'));
    if (empty($errors)) {
        echo "No recent errors found.\n";
        echo "\nLast 20 log lines:\n";
        foreach (array_slice($last, -20) as $line) echo $line;
    } else {
        foreach ($errors as $line) echo $line;
    }
} else {
    echo "No log file found at: " . $logFile;
}
echo "</pre>";
