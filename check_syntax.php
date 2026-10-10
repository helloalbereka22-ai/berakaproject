<?php
$file = 'app/Livewire/Admin/Manufacturing/ManufacturingUserDashboard.php';
$result = php_check_syntax($file);
if ($result === true) {
    echo "Syntax OK\n";
} else {
    echo "Syntax Error: $result\n";
}
