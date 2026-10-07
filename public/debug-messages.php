<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "<pre>";

// Check audit_logs for contact_form_submitted
$rows = DB::table('audit_logs')
    ->where('action', 'contact_form_submitted')
    ->orderByDesc('created_at')
    ->get();

echo "Total contact_form_submitted rows: " . $rows->count() . "\n\n";
foreach ($rows as $r) {
    $data = json_decode($r->after_data, true);
    echo "id={$r->id} | name=" . ($data['name'] ?? '?') . " | email=" . ($data['email'] ?? '?') . " | subject=" . ($data['subject'] ?? '?') . " | created={$r->created_at}\n";
    echo "   message=" . substr($data['message'] ?? '', 0, 80) . "\n\n";
}

// Also check all distinct actions
$actions = DB::table('audit_logs')->distinct()->pluck('action')->toArray();
echo "All actions in audit_logs: " . implode(', ', $actions) . "\n";
echo "</pre>";
