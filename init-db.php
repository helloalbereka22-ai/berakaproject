<?php
// Quick database initialization script
$dbPath = __DIR__ . '/database/database.sqlite';

// Ensure the database file exists and is readable/writable
if (!file_exists($dbPath)) {
    touch($dbPath);
    chmod($dbPath, 0666);
    echo "Created database.sqlite\n";
} else {
    echo "database.sqlite already exists\n";
}

// Run Laravel migrations
exec('cd ' . __DIR__ . ' && php artisan migrate:fresh --force 2>&1', $output, $returnCode);

echo "Migration output:\n";
echo implode("\n", $output) . "\n";
echo "Return code: " . $returnCode . "\n";
?>
