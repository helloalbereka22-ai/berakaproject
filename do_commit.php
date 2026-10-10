<?php
$cwd = __DIR__;
chdir($cwd);

echo "Current directory: " . getcwd() . "\n";

// Stage the file
$output = [];
$return = 0;
exec('git add app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php 2>&1', $output, $return);
echo "Git add output: " . implode("\n", $output) . "\n";
echo "Return code: $return\n";

// Commit
$output = [];
$return = 0;
$message = "fix: Handle legacy manufactured products in user dashboard filtering";
exec("git commit -m \"$message\" 2>&1", $output, $return);
echo "Git commit output: " . implode("\n", $output) . "\n";
echo "Return code: $return\n";

if ($return === 0) {
    echo "\n✅ Commit successful!\n";
} else {
    echo "\n⚠️ Commit may have failed or nothing to commit.\n";
}
