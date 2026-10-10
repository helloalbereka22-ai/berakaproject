<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if setting already exists
$exists = DB::table('settings')
    ->where('key', 'system_logo_path')
    ->whereNull('business_id')
    ->whereNull('branch_id')
    ->first();

if ($exists) {
    DB::table('settings')
        ->where('key', 'system_logo_path')
        ->whereNull('business_id')
        ->whereNull('branch_id')
        ->update(['value' => 'system/oil-factory.png']);
    echo "✓ Updated system_logo_path to: system/oil-factory.png\n";
} else {
    DB::table('settings')->insert([
        'key' => 'system_logo_path',
        'value' => 'system/oil-factory.png',
        'business_id' => null,
        'branch_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ Created new system_logo_path setting: system/oil-factory.png\n";
}

// Verify it was set
$verify = DB::table('settings')
    ->where('key', 'system_logo_path')
    ->whereNull('business_id')
    ->whereNull('branch_id')
    ->value('value');

echo "✓ Verified logo path in database: " . ($verify ?? 'NOT FOUND') . "\n";
?>
