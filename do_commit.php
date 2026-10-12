<?php

chdir('c:\\xammpp82\\htdocs\\albarekaaaaaa');

echo "Current directory: " . getcwd() . "\n";
echo "Attempting to commit changes...\n\n";

// Stage files
exec('git add app/Livewire/Admin/Manufacturing/ManufacturingDashboard.php 2>&1', $output1);
exec('git add app/Livewire/Admin/Reports/ReportDashboard.php 2>&1', $output2);

echo "Files staged.\n";

// Commit
exec('git commit -m "Fix dashboard Daily Manufactured to show only today\'s production and fix report duplicates" 2>&1', $output3);

echo "\nCommit output:\n";
foreach ($output3 as $line) {
    echo $line . "\n";
}

echo "\nDone!\n";
