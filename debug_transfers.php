<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$today = now()->toDateString();

echo "=== TODAY'S IN MOVEMENTS ===\n";
$allIn = DB::table('stock_movements')
    ->whereDate('moved_at', $today)
    ->where('direction', 'in')
    ->get(['id', 'movement_type', 'reference_type', 'quantity', 'moved_at']);

echo "Total IN movements today: " . $allIn->count() . "\n\n";

echo "Reference types breakdown:\n";
$byRef = $allIn->groupBy('reference_type');
foreach ($byRef as $refType => $items) {
    echo "  " . ($refType ?? 'NULL') . ": " . $items->count() . " items\n";
}

echo "\n=== SAMPLE MOVEMENTS ===\n";
$allIn->take(5)->each(function($m) {
    echo "ID: {$m->id} | Type: {$m->movement_type} | Ref: " . ($m->reference_type ?? 'NULL') . " | Qty: {$m->quantity}\n";
});

echo "\n=== CHECKING FOR TRANSFER RECORDS ===\n";
$transfers = DB::table('stock_transfers')->whereDate('created_at', $today)->get(['id', 'reference', 'quantity']);
echo "StockTransfer records today: " . $transfers->count() . "\n";
$transfers->take(3)->each(function($t) {
    echo "  Ref: {$t->reference} | Qty: {$t->quantity}\n";
});
