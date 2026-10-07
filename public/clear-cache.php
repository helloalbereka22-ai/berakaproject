<?php
$viewCache = __DIR__ . '/../storage/framework/views/';
foreach (glob($viewCache . '*') as $f) { if (is_file($f)) unlink($f); }
echo "✅ View cache cleared.";
